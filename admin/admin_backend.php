<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    http_response_code(403);
    exit("Forbidden");
}

require_once "../dbconn.php";

function getInvoiceTableColumns(mysqli $conn, string $tableName): array
{
    if (!in_array($tableName, ["admin_invoices", "invoices"], true)) {
        throw new mysqli_sql_exception("Unsupported invoice table.");
    }

    $result = $conn->query("SHOW COLUMNS FROM `" . $tableName . "`");
    if ($result === false) {
        throw new mysqli_sql_exception($conn->error);
    }

    $columns = [];
    while ($column = $result->fetch_assoc()) {
        $columns[] = $column;
    }
    return $columns;
}

function getInvoiceIdentifierColumn(array $columns): string
{
    $primaryKeyColumn = "";
    foreach ($columns as $column) {
        if ($column["Field"] === "invoice_id") {
            return "invoice_id";
        }
        if ($column["Field"] === "id") {
            $primaryKeyColumn = "id";
        } elseif ($primaryKeyColumn === "" && $column["Key"] === "PRI") {
            $primaryKeyColumn = (string) $column["Field"];
        }
    }
    return $primaryKeyColumn;
}

$invoice_error = "";
$invoice_success = "";
$invoice_load_error = "";
$invoice_action_error = "";
$invoice_action_success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["send-to"])) {
    $invoiceId = trim((string) $_POST["send-to"]);
    $department = strtolower(trim((string) ($_POST["department"] ?? "")));
    $departments = [
        "layout" => ["name" => "Layout", "table" => "layout_artist_incoming"],
        "production" => ["name" => "Production", "table" => "production_incoming"],
        "qa" => ["name" => "QA", "table" => "qa_incoming"],
    ];

    if (!isset($departments[$department])) {
        $invoice_action_error = "Choose a valid department. The invoice remains in Admin.";
    } elseif ($invoiceId === "") {
        $invoice_action_error = "The invoice could not be identified.";
    } else {
        $transactionStarted = false;
        try {
            $sourceColumns = getInvoiceTableColumns($conn, "admin_invoices");
            $destinationColumns = getInvoiceTableColumns($conn, "invoices");
            $sourceDefinition = [];
            $destinationDefinition = [];

            foreach ($sourceColumns as $column) {
                $field = (string) $column["Field"];
                $sourceDefinition[$field] = [$column["Type"], $column["Null"], $column["Default"], $column["Extra"]];
            }
            foreach ($destinationColumns as $column) {
                $field = (string) $column["Field"];
                $destinationDefinition[$field] = [$column["Type"], $column["Null"], $column["Default"], $column["Extra"]];
            }

            $passColumns = ["pass_admin", "pass_layout", "pass_production", "pass_qa"];
            $transferFields = [];
            foreach ($sourceDefinition as $field => $definition) {
                if (in_array($field, $passColumns, true)) {
                    continue;
                }
                if (
                    !isset($destinationDefinition[$field])
                    || $destinationDefinition[$field] !== $definition
                ) {
                    throw new mysqli_sql_exception("The `invoices` and `admin_invoices` table structures do not match.");
                }
                $transferFields[] = $field;
            }
            foreach ($destinationDefinition as $field => $definition) {
                if (!in_array($field, $passColumns, true) && !isset($sourceDefinition[$field])) {
                    throw new mysqli_sql_exception("The `invoices` and `admin_invoices` table structures do not match.");
                }
            }
            foreach ($passColumns as $passColumn) {
                if (!isset($destinationDefinition[$passColumn])) {
                    throw new mysqli_sql_exception("The `invoices` table is missing the `" . $passColumn . "` column.");
                }
            }

            $identifierColumn = getInvoiceIdentifierColumn($sourceColumns);
            if ($identifierColumn === "" || !preg_match('/\A[A-Za-z0-9_]+\z/', $identifierColumn)) {
                throw new mysqli_sql_exception("No valid invoice identifier column was found.");
            }

            $quotedFields = [];
            $quotedSourceFields = [];
            foreach ($transferFields as $field) {
                if (!preg_match('/\A[A-Za-z0-9_]+\z/', $field)) {
                    throw new mysqli_sql_exception("An invoice column name is invalid.");
                }
                $quotedFields[] = "`" . $field . "`";
                $quotedSourceFields[] = "`" . $field . "`";
            }
            foreach ($passColumns as $passColumn) {
                $quotedFields[] = "`" . $passColumn . "`";
            }
            $fieldList = implode(", ", $quotedFields);
            $sourceFieldList = implode(", ", $quotedSourceFields);
            $passValues = "'PASS', '', '', ''";

            if (!$conn->begin_transaction()) {
                throw new mysqli_sql_exception($conn->error);
            }
            $transactionStarted = true;

            $lockInvoice = $conn->prepare(
                "SELECT 1 FROM `admin_invoices` WHERE `" . $identifierColumn . "` = ? FOR UPDATE"
            );
            if ($lockInvoice === false) {
                throw new mysqli_sql_exception($conn->error);
            }
            $lockInvoice->bind_param("s", $invoiceId);
            if (!$lockInvoice->execute()) {
                throw new mysqli_sql_exception($lockInvoice->error);
            }
            $lockInvoice->store_result();
            if ($lockInvoice->num_rows !== 1) {
                $lockInvoice->close();
                throw new mysqli_sql_exception("The invoice is no longer available in Admin.");
            }
            $lockInvoice->close();

            $copyInvoice = $conn->prepare(
                "INSERT INTO `invoices` (" . $fieldList . ") "
                . "SELECT " . $sourceFieldList . ", " . $passValues . " FROM `admin_invoices` "
                . "WHERE `" . $identifierColumn . "` = ?"
            );
            if ($copyInvoice === false) {
                throw new mysqli_sql_exception($conn->error);
            }
            $copyInvoice->bind_param("s", $invoiceId);
            if (!$copyInvoice->execute()) {
                throw new mysqli_sql_exception($copyInvoice->error);
            }
            if ($copyInvoice->affected_rows !== 1) {
                $copyInvoice->close();
                throw new mysqli_sql_exception("The invoice could not be copied to `invoices`.");
            }
            $copyInvoice->close();

            switch ($department) {
                case "layout":
                case "qa":
                    $destinationTable = $departments[$department]["table"];
                    $addDepartmentIncoming = $conn->prepare(
                        "INSERT INTO `" . $destinationTable . "` (`invoice_id`) VALUES (?)"
                    );
                    if ($addDepartmentIncoming === false) {
                        throw new mysqli_sql_exception($conn->error);
                    }
                    $addDepartmentIncoming->bind_param("s", $invoiceId);
                    if (!$addDepartmentIncoming->execute()) {
                        throw new mysqli_sql_exception($addDepartmentIncoming->error);
                    }
                    if ($addDepartmentIncoming->affected_rows !== 1) {
                        $addDepartmentIncoming->close();
                        throw new mysqli_sql_exception(
                            "The invoice was not added to " . $departments[$department]["name"] . " incoming."
                        );
                    }
                    $addDepartmentIncoming->close();
                    break;
                case "production":
                    $addProductionIncoming = $conn->prepare(
                        "INSERT INTO `" . $departments[$department]["table"] . "` (`invoice_id`, `file`) VALUES (?, '')"
                    );
                    if ($addProductionIncoming === false) {
                        throw new mysqli_sql_exception($conn->error);
                    }
                    $addProductionIncoming->bind_param("s", $invoiceId);
                    if (!$addProductionIncoming->execute()) {
                        throw new mysqli_sql_exception($addProductionIncoming->error);
                    }
                    if ($addProductionIncoming->affected_rows !== 1) {
                        $addProductionIncoming->close();
                        throw new mysqli_sql_exception("The invoice was not added to Production incoming.");
                    }
                    $addProductionIncoming->close();
                    break;
                default:
                    throw new mysqli_sql_exception("The selected department has no incoming table.");
            }

            $removeAdminInvoice = $conn->prepare(
                "DELETE FROM `admin_invoices` WHERE `" . $identifierColumn . "` = ?"
            );
            if ($removeAdminInvoice === false) {
                throw new mysqli_sql_exception($conn->error);
            }
            $removeAdminInvoice->bind_param("s", $invoiceId);
            if (!$removeAdminInvoice->execute()) {
                throw new mysqli_sql_exception($removeAdminInvoice->error);
            }
            if ($removeAdminInvoice->affected_rows !== 1) {
                $removeAdminInvoice->close();
                throw new mysqli_sql_exception("The invoice could not be removed from Admin.");
            }
            $removeAdminInvoice->close();

            if (!$conn->commit()) {
                throw new mysqli_sql_exception($conn->error);
            }
            $transactionStarted = false;
            $invoice_action_success = "Invoice sent to " . $departments[$department]["name"] . " and removed from Admin logs.";
        } catch (mysqli_sql_exception $exception) {
            if ($transactionStarted) {
                try {
                    $conn->rollback();
                } catch (mysqli_sql_exception $rollbackException) {
                    error_log("Unable to roll back invoice transfer: " . $rollbackException->getMessage());
                }
            }
            error_log("Unable to transfer admin invoice: " . $exception->getMessage());
            $invoice_action_error = "Invoice was not sent: " . $exception->getMessage();
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["create-invoice"])) {
    $customerName = trim((string) ($_POST["customer_name"] ?? ""));
    $contactNumber = trim((string) ($_POST["customer_number"] ?? ""));
    $customerAddress = trim((string) ($_POST["shipping_address"] ?? ""));
    $landmarks = trim((string) ($_POST["landmarks"] ?? ""));
    $services = trim((string) ($_POST["product-service"] ?? ""));
    $quantityInput = trim((string) ($_POST["quantity"] ?? ""));
    $priceInput = trim((string) ($_POST["total-price"] ?? ""));
    $completionDate = trim((string) ($_POST["due_date"] ?? ""));
    $notes = trim((string) ($_POST["notes"] ?? ""));

    $quantity = filter_var($quantityInput, FILTER_VALIDATE_INT, [
        "options" => ["min_range" => 1],
    ]);
    $completionDateValue = DateTime::createFromFormat("!Y-m-d", $completionDate);
    $validDate = $completionDateValue !== false && $completionDateValue->format("Y-m-d") === $completionDate;

    if (
        $customerName === ""
        || $contactNumber === ""
        || $customerAddress === ""
        || $services === ""
        || $quantity === false
        || !preg_match('/\A\d+(?:\.\d{1,2})?\z/', $priceInput)
        || !$validDate
    ) {
        $invoice_error = "Complete all required fields with a valid quantity, amount, and target date.";
    } else {
        $referenceFileName = "";
        $referenceFilePath = "";
        $upload = $_FILES["reference_image"] ?? null;

        if ($upload !== null && $upload["error"] !== UPLOAD_ERR_NO_FILE) {
            if ($upload["error"] !== UPLOAD_ERR_OK || $upload["size"] > 10 * 1024 * 1024) {
                $invoice_error = "The reference image must be a PNG or JPEG no larger than 10 MB.";
            } else {
                $fileInfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $fileInfo->file($upload["tmp_name"]);
                $allowedMimeTypes = [
                    "image/jpeg" => "jpg",
                    "image/png" => "png",
                ];

                if (!isset($allowedMimeTypes[$mimeType])) {
                    $invoice_error = "The reference image must be a PNG or JPEG no larger than 10 MB.";
                } else {
                    $uploadDirectory = __DIR__ . DIRECTORY_SEPARATOR . "uploads";
                    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                        $invoice_error = "The reference image could not be stored. Please try again.";
                    } else {
                        $referenceFileName = bin2hex(random_bytes(16)) . "." . $allowedMimeTypes[$mimeType];
                        $referenceFilePath = $uploadDirectory . DIRECTORY_SEPARATOR . $referenceFileName;

                        if (!move_uploaded_file($upload["tmp_name"], $referenceFilePath)) {
                            $invoice_error = "The reference image could not be stored. Please try again.";
                            $referenceFileName = "";
                            $referenceFilePath = "";
                        }
                    }
                }
            }
        }

        if ($invoice_error === "") {
            $transactionStarted = false;
            try {
                $conn->begin_transaction();
                $transactionStarted = true;
                $insertInvoice = $conn->prepare(
                    "INSERT INTO `admin_invoices` "
                    . "(`customer_name`, `contact_number`, `customer_address`, `landmarks`, `services`, "
                    . "`quantity`, `price`, `completion_date`, `notes`, `file`) "
                    . "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                if ($insertInvoice === false) {
                    throw new mysqli_sql_exception($conn->error);
                }
                $price = (float) $priceInput;
                $insertInvoice->bind_param(
                    "sssssidsss",
                    $customerName,
                    $contactNumber,
                    $customerAddress,
                    $landmarks,
                    $services,
                    $quantity,
                    $price,
                    $completionDate,
                    $notes,
                    $referenceFileName
                );
                if (!$insertInvoice->execute()) {
                    throw new mysqli_sql_exception($insertInvoice->error);
                }
                $insertInvoice->close();
                if (!$conn->commit()) {
                    throw new mysqli_sql_exception($conn->error);
                }
                $transactionStarted = false;
                $invoice_success = "Invoice created successfully.";
            } catch (mysqli_sql_exception $exception) {
                if ($transactionStarted) {
                    try {
                        $conn->rollback();
                    } catch (mysqli_sql_exception $rollbackException) {
                        error_log("Unable to roll back admin invoice transaction: " . $rollbackException->getMessage());
                    }
                }
                if ($referenceFilePath !== "" && is_file($referenceFilePath)) {
                    unlink($referenceFilePath);
                }
                error_log("Unable to create admin invoice: " . $exception->getMessage());
                $invoice_error = "The invoice could not be saved. Please check the database and try again.";
            }
        }
    }
}

$select_invoice_sql = false;
try {
    $invoiceColumns = $conn->query("SHOW COLUMNS FROM `admin_invoices`");
    if ($invoiceColumns === false) {
        $invoice_load_error = "Invoice records could not be loaded: " . $conn->error;
    } else {
        $availableColumns = [];
        $primaryKeyColumn = "";
        while ($column = $invoiceColumns->fetch_assoc()) {
            $columnName = (string) $column["Field"];
            $availableColumns[$columnName] = true;
            if ($primaryKeyColumn === "" && $column["Key"] === "PRI") {
                $primaryKeyColumn = $columnName;
            }
        }

        if (isset($availableColumns["invoice_id"])) {
            $invoiceIdColumn = "invoice_id";
        } elseif (isset($availableColumns["id"])) {
            $invoiceIdColumn = "id";
        } else {
            $invoiceIdColumn = $primaryKeyColumn;
        }

        if ($invoiceIdColumn === "") {
            $invoice_load_error = "Invoice records could not be loaded: the table has no primary key to identify invoices.";
        } elseif (!preg_match('/\A[A-Za-z0-9_]+\z/', $invoiceIdColumn)) {
            $invoice_load_error = "Invoice records could not be loaded: the invoice identifier column name is invalid.";
        } else {
            $invoiceIdSelection = $invoiceIdColumn === "invoice_id"
                ? ""
                : ", `" . $invoiceIdColumn . "` AS `invoice_id`";
            $select_invoice_sql = $conn->query(
                "SELECT *" . $invoiceIdSelection
                . " FROM `admin_invoices` ORDER BY `" . $invoiceIdColumn . "` DESC"
            );
        }
    }
} catch (mysqli_sql_exception $exception) {
    error_log("Unable to load admin invoices: " . $exception->getMessage());
    $invoice_load_error = "Invoice records could not be loaded: " . $exception->getMessage();
}
if ($select_invoice_sql === false && $invoice_load_error === "") {
    error_log("Unable to load admin invoices: " . $conn->error);
    $invoice_load_error = "Invoice records could not be loaded: " . $conn->error;
}

?>