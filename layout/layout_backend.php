<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "layout") {
    http_response_code(403);
    exit("Forbidden");
}

require_once "../dbconn.php";

function getLayoutInvoiceIdColumn(mysqli $conn): string
{
    $columns = $conn->query("SHOW COLUMNS FROM `invoices`");
    if ($columns === false) {
        throw new mysqli_sql_exception($conn->error);
    }

    $primaryKeyColumn = "";
    while ($column = $columns->fetch_assoc()) {
        $columnName = (string) $column["Field"];
        if ($columnName === "invoice_id") {
            return $columnName;
        }
        if ($columnName === "id") {
            $primaryKeyColumn = $columnName;
        } elseif ($primaryKeyColumn === "" && $column["Key"] === "PRI") {
            $primaryKeyColumn = $columnName;
        }
    }

    if ($primaryKeyColumn === "" || !preg_match('/\A[A-Za-z0-9_]+\z/', $primaryKeyColumn)) {
        throw new mysqli_sql_exception("No valid invoice identifier column was found.");
    }
    return $primaryKeyColumn;
}

$layout_incoming_rows = [];
$layout_working_rows = [];
$layout_incoming_error = "";
$layout_working_error = "";
$layout_action_error = "";
$layout_action_success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["send_work"])) {
    $invoiceId = trim((string) ($_POST["invoice_id"] ?? ""));
    $department = strtolower(trim((string) ($_POST["department"] ?? "")));
    $departmentNames = [
        "admin" => "Admin",
        "production" => "Production",
        "quality assurance" => "Quality Assurance",
    ];
    $upload = $_FILES["work_file"] ?? null;

    if (!isset($departmentNames[$department])) {
        $layout_action_error = "Choose a valid department. The invoice remains in Layout.";
    } elseif ($invoiceId === "") {
        $layout_action_error = "The invoice could not be identified.";
    } elseif ($upload === null || $upload["error"] !== UPLOAD_ERR_OK || $upload["size"] > 10 * 1024 * 1024) {
        $layout_action_error = "Choose a PDF, PNG, or JPEG no larger than 10 MB.";
    } else {
        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file($upload["tmp_name"]);
        $allowedMimeTypes = [
            "application/pdf" => "pdf",
            "image/jpeg" => "jpg",
            "image/png" => "png",
        ];

        if (!isset($allowedMimeTypes[$mimeType])) {
            $layout_action_error = "Choose a PDF, PNG, or JPEG no larger than 10 MB.";
        } else {
            $uploadDirectory = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR
                . "production" . DIRECTORY_SEPARATOR . "uploads";
            if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                $layout_action_error = "The work file could not be stored. Please try again.";
            } else {
                $workFileName = bin2hex(random_bytes(16)) . "." . $allowedMimeTypes[$mimeType];
                $workFilePath = $uploadDirectory . DIRECTORY_SEPARATOR . $workFileName;

                if (!move_uploaded_file($upload["tmp_name"], $workFilePath)) {
                    $layout_action_error = "The work file could not be stored. Please try again.";
                } else {
                    $transactionStarted = false;
                    try {
                        if (!$conn->begin_transaction()) {
                            throw new mysqli_sql_exception($conn->error);
                        }
                        $transactionStarted = true;

                        $lockWorkingInvoice = $conn->prepare(
                            "SELECT 1 FROM `layout_artist_work` WHERE `invoice_id` = ? FOR UPDATE"
                        );
                        if ($lockWorkingInvoice === false) {
                            throw new mysqli_sql_exception($conn->error);
                        }
                        $lockWorkingInvoice->bind_param("s", $invoiceId);
                        if (!$lockWorkingInvoice->execute()) {
                            throw new mysqli_sql_exception($lockWorkingInvoice->error);
                        }
                        $lockWorkingInvoice->store_result();
                        if ($lockWorkingInvoice->num_rows !== 1) {
                            $lockWorkingInvoice->close();
                            throw new mysqli_sql_exception("The invoice is no longer available in Layout.");
                        }
                        $lockWorkingInvoice->close();

                        $statusIdColumn = getLayoutInvoiceIdColumn($conn);

                        if ($department === "production") {
                            $sendToDepartment = $conn->prepare(
                                "INSERT INTO `production_incoming` (`invoice_id`, `file`) VALUES (?, ?)"
                            );
                            if ($sendToDepartment === false) {
                                throw new mysqli_sql_exception($conn->error);
                            }
                            $sendToDepartment->bind_param("ss", $invoiceId, $workFileName);
                            if (!$sendToDepartment->execute()) {
                                throw new mysqli_sql_exception($sendToDepartment->error);
                            }
                            if ($sendToDepartment->affected_rows !== 1) {
                                $sendToDepartment->close();
                                throw new mysqli_sql_exception("The invoice was not added to Production incoming.");
                            }
                            $sendToDepartment->close();
                        } elseif ($department === "quality assurance") {
                            $sendToDepartment = $conn->prepare(
                                "INSERT INTO `qa_incoming` (`invoice_id`) VALUES (?)"
                            );
                            if ($sendToDepartment === false) {
                                throw new mysqli_sql_exception($conn->error);
                            }
                            $sendToDepartment->bind_param("s", $invoiceId);
                            if (!$sendToDepartment->execute()) {
                                throw new mysqli_sql_exception($sendToDepartment->error);
                            }
                            if ($sendToDepartment->affected_rows !== 1) {
                                $sendToDepartment->close();
                                throw new mysqli_sql_exception("The invoice was not added to QA incoming.");
                            }
                            $sendToDepartment->close();
                        } else {
                            $invoiceColumns = $conn->query("SHOW COLUMNS FROM `invoices`");
                            $adminColumns = $conn->query("SHOW COLUMNS FROM `admin_invoices`");
                            if ($invoiceColumns === false || $adminColumns === false) {
                                throw new mysqli_sql_exception($conn->error);
                            }

                            $invoiceDefinitions = [];
                            $adminDefinitions = [];
                            while ($column = $invoiceColumns->fetch_assoc()) {
                                $field = (string) $column["Field"];
                                $invoiceDefinitions[$field] = [
                                    $column["Type"],
                                    $column["Null"],
                                    $column["Default"],
                                    $column["Extra"],
                                ];
                            }
                            while ($column = $adminColumns->fetch_assoc()) {
                                $field = (string) $column["Field"];
                                $adminDefinitions[$field] = [
                                    $column["Type"],
                                    $column["Null"],
                                    $column["Default"],
                                    $column["Extra"],
                                ];
                            }

                            $passColumns = ["pass_admin", "pass_layout", "pass_production", "pass_qa"];
                            $transferFields = [];
                            foreach ($invoiceDefinitions as $field => $definition) {
                                if (in_array($field, $passColumns, true)) {
                                    continue;
                                }
                                if (
                                    !isset($adminDefinitions[$field])
                                    || $adminDefinitions[$field] !== $definition
                                ) {
                                    throw new mysqli_sql_exception("The `invoices` and `admin_invoices` table structures do not match.");
                                }
                            }
                            foreach ($adminDefinitions as $field => $definition) {
                                if (in_array($field, $passColumns, true)) {
                                    continue;
                                }
                                if (
                                    !isset($invoiceDefinitions[$field])
                                    || $invoiceDefinitions[$field] !== $definition
                                ) {
                                    throw new mysqli_sql_exception("The `invoices` and `admin_invoices` table structures do not match.");
                                }
                                $transferFields[] = $field;
                            }

                            $quotedFields = [];
                            foreach ($transferFields as $field) {
                                if (!preg_match('/\A[A-Za-z0-9_]+\z/', $field)) {
                                    throw new mysqli_sql_exception("An invoice column name is invalid.");
                                }
                                $quotedFields[] = "`" . $field . "`";
                            }
                            $fieldList = implode(", ", $quotedFields);
                            $sendToAdmin = $conn->prepare(
                                "INSERT INTO `admin_invoices` (" . $fieldList . ") "
                                . "SELECT " . $fieldList . " FROM `invoices` "
                                . "WHERE `" . $statusIdColumn . "` = ?"
                            );
                            if ($sendToAdmin === false) {
                                throw new mysqli_sql_exception($conn->error);
                            }
                            $sendToAdmin->bind_param("s", $invoiceId);
                            if (!$sendToAdmin->execute()) {
                                throw new mysqli_sql_exception($sendToAdmin->error);
                            }
                            if ($sendToAdmin->affected_rows !== 1) {
                                $sendToAdmin->close();
                                throw new mysqli_sql_exception("The invoice was not added to Admin invoices.");
                            }
                            $sendToAdmin->close();
                        }

                        $markLayoutPassed = $conn->prepare(
                            "UPDATE `invoices` SET `pass_layout` = 'PASS' WHERE `" . $statusIdColumn . "` = ?"
                        );
                        if ($markLayoutPassed === false) {
                            throw new mysqli_sql_exception($conn->error);
                        }
                        $markLayoutPassed->bind_param("s", $invoiceId);
                        if (!$markLayoutPassed->execute()) {
                            throw new mysqli_sql_exception($markLayoutPassed->error);
                        }
                        $markLayoutPassed->close();

                        $verifyLayoutPassed = $conn->prepare(
                            "SELECT 1 FROM `invoices` "
                            . "WHERE `" . $statusIdColumn . "` = ? AND `pass_layout` = 'PASS' FOR UPDATE"
                        );
                        if ($verifyLayoutPassed === false) {
                            throw new mysqli_sql_exception($conn->error);
                        }
                        $verifyLayoutPassed->bind_param("s", $invoiceId);
                        if (!$verifyLayoutPassed->execute()) {
                            throw new mysqli_sql_exception($verifyLayoutPassed->error);
                        }
                        $verifyLayoutPassed->store_result();
                        if ($verifyLayoutPassed->num_rows !== 1) {
                            $verifyLayoutPassed->close();
                            throw new mysqli_sql_exception("The invoice Layout status could not be updated.");
                        }
                        $verifyLayoutPassed->close();

                        $removeLayoutWork = $conn->prepare(
                            "DELETE FROM `layout_artist_work` WHERE `invoice_id` = ?"
                        );
                        if ($removeLayoutWork === false) {
                            throw new mysqli_sql_exception($conn->error);
                        }
                        $removeLayoutWork->bind_param("s", $invoiceId);
                        if (!$removeLayoutWork->execute()) {
                            throw new mysqli_sql_exception($removeLayoutWork->error);
                        }
                        if ($removeLayoutWork->affected_rows !== 1) {
                            $removeLayoutWork->close();
                            throw new mysqli_sql_exception("The invoice could not be removed from Layout.");
                        }
                        $removeLayoutWork->close();

                        if (!$conn->commit()) {
                            throw new mysqli_sql_exception($conn->error);
                        }
                        $transactionStarted = false;
                        $layout_action_success = "Invoice sent to " . $departmentNames[$department] . ".";
                        if ($department !== "production" && is_file($workFilePath)) {
                            unlink($workFilePath);
                        }
                    } catch (mysqli_sql_exception $exception) {
                        if ($transactionStarted) {
                            try {
                                $conn->rollback();
                            } catch (mysqli_sql_exception $rollbackException) {
                                error_log("Unable to roll back Layout handoff: " . $rollbackException->getMessage());
                            }
                        }
                        if (is_file($workFilePath)) {
                            unlink($workFilePath);
                        }
                        error_log(
                            "Unable to send Layout invoice to " . $departmentNames[$department] . ": "
                            . $exception->getMessage()
                        );
                        $layout_action_error = "Invoice was not sent: " . $exception->getMessage();
                    }
                }
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["accept"])) {
    $layoutId = filter_var($_POST["accept"], FILTER_VALIDATE_INT, [
        "options" => ["min_range" => 1],
    ]);

    if ($layoutId === false) {
        $layout_action_error = "The incoming work item could not be identified.";
    } else {
        $transactionStarted = false;
        try {
            if (!$conn->begin_transaction()) {
                throw new mysqli_sql_exception($conn->error);
            }
            $transactionStarted = true;

            $lockIncoming = $conn->prepare(
                "SELECT 1 FROM `layout_artist_incoming` WHERE `layout_id` = ? FOR UPDATE"
            );
            if ($lockIncoming === false) {
                throw new mysqli_sql_exception($conn->error);
            }
            $lockIncoming->bind_param("i", $layoutId);
            if (!$lockIncoming->execute()) {
                throw new mysqli_sql_exception($lockIncoming->error);
            }
            $lockIncoming->store_result();
            if ($lockIncoming->num_rows !== 1) {
                $lockIncoming->close();
                throw new mysqli_sql_exception("The incoming work item is no longer available.");
            }
            $lockIncoming->close();

            $copyToWorking = $conn->prepare(
                "INSERT INTO `layout_artist_work` (`invoice_id`) "
                . "SELECT `invoice_id` FROM `layout_artist_incoming` WHERE `layout_id` = ?"
            );
            if ($copyToWorking === false) {
                throw new mysqli_sql_exception($conn->error);
            }
            $copyToWorking->bind_param("i", $layoutId);
            if (!$copyToWorking->execute()) {
                throw new mysqli_sql_exception($copyToWorking->error);
            }
            if ($copyToWorking->affected_rows !== 1) {
                $copyToWorking->close();
                throw new mysqli_sql_exception("The invoice could not be moved to Working Invoices.");
            }
            $copyToWorking->close();

            $removeIncoming = $conn->prepare(
                "DELETE FROM `layout_artist_incoming` WHERE `layout_id` = ?"
            );
            if ($removeIncoming === false) {
                throw new mysqli_sql_exception($conn->error);
            }
            $removeIncoming->bind_param("i", $layoutId);
            if (!$removeIncoming->execute()) {
                throw new mysqli_sql_exception($removeIncoming->error);
            }
            if ($removeIncoming->affected_rows !== 1) {
                $removeIncoming->close();
                throw new mysqli_sql_exception("The incoming work item could not be removed.");
            }
            $removeIncoming->close();

            if (!$conn->commit()) {
                throw new mysqli_sql_exception($conn->error);
            }
            $transactionStarted = false;
            $layout_action_success = "Work accepted and moved to Working Invoices.";
        } catch (mysqli_sql_exception $exception) {
            if ($transactionStarted) {
                try {
                    $conn->rollback();
                } catch (mysqli_sql_exception $rollbackException) {
                    error_log("Unable to roll back Layout work transfer: " . $rollbackException->getMessage());
                }
            }
            error_log("Unable to accept Layout work: " . $exception->getMessage());
            $layout_action_error = "Work was not accepted: " . $exception->getMessage();
        }
    }
}

$invoiceIdColumn = "";
try {
    $invoiceColumns = $conn->query("SHOW COLUMNS FROM `invoices`");
    if ($invoiceColumns === false) {
        throw new mysqli_sql_exception($conn->error);
    }

    $primaryKeyColumn = "";
    while ($column = $invoiceColumns->fetch_assoc()) {
        $columnName = (string) $column["Field"];
        if ($columnName === "invoice_id") {
            $invoiceIdColumn = $columnName;
            break;
        }
        if ($columnName === "id") {
            $primaryKeyColumn = $columnName;
        } elseif ($primaryKeyColumn === "" && $column["Key"] === "PRI") {
            $primaryKeyColumn = $columnName;
        }
    }

    if ($invoiceIdColumn === "") {
        $invoiceIdColumn = $primaryKeyColumn;
    }
    if ($invoiceIdColumn === "" || !preg_match('/\A[A-Za-z0-9_]+\z/', $invoiceIdColumn)) {
        throw new mysqli_sql_exception("No valid invoice identifier column was found.");
    }
} catch (mysqli_sql_exception $exception) {
    error_log("Unable to inspect invoices table for Layout: " . $exception->getMessage());
    $layout_incoming_error = "Incoming invoices could not be loaded.";
    $layout_working_error = "Working invoices could not be loaded.";
}

if ($invoiceIdColumn !== "") {
    $invoiceIdSelection = $invoiceIdColumn === "invoice_id"
        ? ""
        : ", i.`" . $invoiceIdColumn . "` AS `invoice_id`";

    try {
        $incomingInvoices = "
            SELECT i.*" . $invoiceIdSelection . ", lai.`layout_id` AS `layout_id`
            FROM `invoices` i
            INNER JOIN `layout_artist_incoming` lai ON i.`" . $invoiceIdColumn . "` = lai.`invoice_id`
            ORDER BY lai.`layout_id` DESC
        ";
        $queryIncomingInvoices = $conn->query($incomingInvoices);
        if ($queryIncomingInvoices === false) {
            throw new mysqli_sql_exception($conn->error);
        }
        while ($row = $queryIncomingInvoices->fetch_assoc()) {
            $layout_incoming_rows[] = $row;
        }
    } catch (mysqli_sql_exception $exception) {
        error_log("Unable to load incoming Layout invoices: " . $exception->getMessage());
        $layout_incoming_error = "Incoming invoices could not be loaded.";
    }

    try {
        $workingInvoices = "
            SELECT i.*" . $invoiceIdSelection . "
            FROM `invoices` i
            INNER JOIN `layout_artist_work` law ON i.`" . $invoiceIdColumn . "` = law.`invoice_id`
            ORDER BY i.`" . $invoiceIdColumn . "` DESC
        ";
        $queryWorkingInvoices = $conn->query($workingInvoices);
        if ($queryWorkingInvoices === false) {
            throw new mysqli_sql_exception($conn->error);
        }
        while ($row = $queryWorkingInvoices->fetch_assoc()) {
            $layout_working_rows[] = $row;
        }
    } catch (mysqli_sql_exception $exception) {
        error_log("Unable to load Working Layout invoices: " . $exception->getMessage());
        $layout_working_error = "Working invoices could not be loaded.";
    }
}
