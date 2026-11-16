<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "qa") {
    http_response_code(403);
    exit("Forbidden");
}

require_once __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "dbconn.php";

function getQaInvoiceIdColumn(mysqli $conn): string
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

$qa_incoming_rows = [];
$qa_working_rows = [];
$qa_incoming_error = "";
$qa_working_error = "";
$qa_action_error = "";
$qa_action_success = "";
$qa_send_modal_values = [
    "invoice_id" => "",
    "department" => "",
];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["send_qa_work"])) {
    $invoiceId = is_string($_POST["invoice_id"] ?? null) ? trim($_POST["invoice_id"]) : "";
    $department = is_string($_POST["department"] ?? null)
        ? strtolower(trim($_POST["department"]))
        : "";
    $departments = [
        "admin" => "Admin",
        "layout" => "Layout",
        "production" => "Production",
    ];
    $upload = $_FILES["work_file"] ?? null;
    $workFileName = "";
    $workFilePath = "";

    $qa_send_modal_values["invoice_id"] = $invoiceId;
    $qa_send_modal_values["department"] = $department;

    if ($invoiceId === "") {
        $qa_action_error = "The QA Work invoice could not be identified.";
    } elseif (!isset($departments[$department])) {
        $qa_action_error = "Choose a valid department. The invoice remains in QA Work.";
    } elseif (
        $department === "production"
        && ($upload === null || $upload["error"] !== UPLOAD_ERR_OK || $upload["size"] > 10 * 1024 * 1024)
    ) {
        $qa_action_error = "Choose a PDF, PNG, or JPEG no larger than 10 MB for Production.";
    }

    if ($qa_action_error === "" && $department === "production") {
        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file($upload["tmp_name"]);
        $allowedMimeTypes = [
            "application/pdf" => "pdf",
            "image/jpeg" => "jpg",
            "image/png" => "png",
        ];

        if (!isset($allowedMimeTypes[$mimeType])) {
            $qa_action_error = "Choose a PDF, PNG, or JPEG no larger than 10 MB for Production.";
        } else {
            $uploadDirectory = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR
                . "production" . DIRECTORY_SEPARATOR . "uploads";
            if (
                !is_dir($uploadDirectory)
                && !mkdir($uploadDirectory, 0755, true)
                && !is_dir($uploadDirectory)
            ) {
                $qa_action_error = "The work file could not be stored. Please try again.";
            } else {
                $workFileName = bin2hex(random_bytes(16)) . "." . $allowedMimeTypes[$mimeType];
                $workFilePath = $uploadDirectory . DIRECTORY_SEPARATOR . $workFileName;
                if (!move_uploaded_file($upload["tmp_name"], $workFilePath)) {
                    $qa_action_error = "The work file could not be stored. Please try again.";
                    $workFileName = "";
                    $workFilePath = "";
                }
            }
        }
    }

    if ($qa_action_error === "") {
        $transactionStarted = false;
        try {
            if (!$conn->begin_transaction()) {
                throw new mysqli_sql_exception($conn->error);
            }
            $transactionStarted = true;

            $lockQaWork = $conn->prepare(
                "SELECT 1 FROM `qa_work` WHERE `invoice_id` = ? FOR UPDATE"
            );
            if ($lockQaWork === false) {
                throw new mysqli_sql_exception($conn->error);
            }
            $lockQaWork->bind_param("s", $invoiceId);
            if (!$lockQaWork->execute()) {
                throw new mysqli_sql_exception($lockQaWork->error);
            }
            $lockQaWork->store_result();
            if ($lockQaWork->num_rows !== 1) {
                $lockQaWork->close();
                throw new mysqli_sql_exception("The QA Work invoice is no longer available.");
            }
            $lockQaWork->close();

            $statusIdColumn = getQaInvoiceIdColumn($conn);

            if ($department === "production") {
                $sendToProduction = $conn->prepare(
                    "INSERT INTO `production_incoming` (`invoice_id`, `file`) VALUES (?, ?)"
                );
                if ($sendToProduction === false) {
                    throw new mysqli_sql_exception($conn->error);
                }
                $sendToProduction->bind_param("ss", $invoiceId, $workFileName);
                if (!$sendToProduction->execute()) {
                    throw new mysqli_sql_exception($sendToProduction->error);
                }
                if ($sendToProduction->affected_rows !== 1) {
                    $sendToProduction->close();
                    throw new mysqli_sql_exception("The invoice was not added to Production incoming.");
                }
                $sendToProduction->close();
            } elseif ($department === "layout") {
                $sendToLayout = $conn->prepare(
                    "INSERT INTO `layout_artist_incoming` (`invoice_id`) VALUES (?)"
                );
                if ($sendToLayout === false) {
                    throw new mysqli_sql_exception($conn->error);
                }
                $sendToLayout->bind_param("s", $invoiceId);
                if (!$sendToLayout->execute()) {
                    throw new mysqli_sql_exception($sendToLayout->error);
                }
                if ($sendToLayout->affected_rows !== 1) {
                    $sendToLayout->close();
                    throw new mysqli_sql_exception("The invoice was not added to Layout incoming.");
                }
                $sendToLayout->close();
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
                    if (!preg_match('/\A[A-Za-z0-9_]+\z/', $field)) {
                        throw new mysqli_sql_exception("An invoice column name is invalid.");
                    }
                    $transferFields[] = "`" . $field . "`";
                }
                if ($transferFields === []) {
                    throw new mysqli_sql_exception("No invoice fields are available to return to Admin.");
                }
                $fieldList = implode(", ", $transferFields);
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
                    throw new mysqli_sql_exception("The invoice was not returned to Admin.");
                }
                $sendToAdmin->close();
            }

            $markQaPassed = $conn->prepare(
                "UPDATE `invoices` SET `pass_qa` = 'PASS' WHERE `" . $statusIdColumn . "` = ?"
            );
            if ($markQaPassed === false) {
                throw new mysqli_sql_exception($conn->error);
            }
            $markQaPassed->bind_param("s", $invoiceId);
            if (!$markQaPassed->execute()) {
                throw new mysqli_sql_exception($markQaPassed->error);
            }
            $markQaPassed->close();

            $verifyQaPassed = $conn->prepare(
                "SELECT 1 FROM `invoices` "
                . "WHERE `" . $statusIdColumn . "` = ? AND `pass_qa` = 'PASS' FOR UPDATE"
            );
            if ($verifyQaPassed === false) {
                throw new mysqli_sql_exception($conn->error);
            }
            $verifyQaPassed->bind_param("s", $invoiceId);
            if (!$verifyQaPassed->execute()) {
                throw new mysqli_sql_exception($verifyQaPassed->error);
            }
            $verifyQaPassed->store_result();
            if ($verifyQaPassed->num_rows !== 1) {
                $verifyQaPassed->close();
                throw new mysqli_sql_exception("The invoice QA status could not be updated.");
            }
            $verifyQaPassed->close();

            $removeQaWork = $conn->prepare(
                "DELETE FROM `qa_work` WHERE `invoice_id` = ?"
            );
            if ($removeQaWork === false) {
                throw new mysqli_sql_exception($conn->error);
            }
            $removeQaWork->bind_param("s", $invoiceId);
            if (!$removeQaWork->execute()) {
                throw new mysqli_sql_exception($removeQaWork->error);
            }
            if ($removeQaWork->affected_rows !== 1) {
                $removeQaWork->close();
                throw new mysqli_sql_exception("The invoice could not be removed from QA Work.");
            }
            $removeQaWork->close();

            if (!$conn->commit()) {
                throw new mysqli_sql_exception($conn->error);
            }
            $transactionStarted = false;
            $qa_action_success = "Invoice sent to " . $departments[$department] . ".";
            $qa_send_modal_values = ["invoice_id" => "", "department" => ""];
            if ($department !== "production" && $workFilePath !== "" && is_file($workFilePath)) {
                unlink($workFilePath);
            }
        } catch (mysqli_sql_exception $exception) {
            if ($transactionStarted) {
                try {
                    $conn->rollback();
                } catch (mysqli_sql_exception $rollbackException) {
                    error_log("Unable to roll back QA department handoff: " . $rollbackException->getMessage());
                }
            }
            if ($workFilePath !== "" && is_file($workFilePath)) {
                unlink($workFilePath);
            }
            error_log("Unable to send QA invoice: " . $exception->getMessage());
            $qa_action_error = "Invoice was not sent: " . $exception->getMessage();
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["accept"])) {
    $invoiceId = is_string($_POST["accept"]) ? trim($_POST["accept"]) : "";

    if ($invoiceId === "") {
        $qa_action_error = "The incoming invoice could not be identified.";
    } else {
        $transactionStarted = false;
        try {
            if (!$conn->begin_transaction()) {
                throw new mysqli_sql_exception($conn->error);
            }
            $transactionStarted = true;

            $lockIncoming = $conn->prepare(
                "SELECT 1 FROM `qa_incoming` WHERE `invoice_id` = ? FOR UPDATE"
            );
            if ($lockIncoming === false) {
                throw new mysqli_sql_exception($conn->error);
            }
            $lockIncoming->bind_param("s", $invoiceId);
            if (!$lockIncoming->execute()) {
                throw new mysqli_sql_exception($lockIncoming->error);
            }
            $lockIncoming->store_result();
            if ($lockIncoming->num_rows !== 1) {
                $lockIncoming->close();
                throw new mysqli_sql_exception("The incoming invoice is no longer available.");
            }
            $lockIncoming->close();

            $copyToQaWork = $conn->prepare(
                "INSERT INTO `qa_work` (`invoice_id`) "
                . "SELECT `invoice_id` FROM `qa_incoming` WHERE `invoice_id` = ?"
            );
            if ($copyToQaWork === false) {
                throw new mysqli_sql_exception($conn->error);
            }
            $copyToQaWork->bind_param("s", $invoiceId);
            if (!$copyToQaWork->execute()) {
                throw new mysqli_sql_exception($copyToQaWork->error);
            }
            if ($copyToQaWork->affected_rows !== 1) {
                $copyToQaWork->close();
                throw new mysqli_sql_exception("The invoice could not be moved to QA Work.");
            }
            $copyToQaWork->close();

            $removeIncoming = $conn->prepare(
                "DELETE FROM `qa_incoming` WHERE `invoice_id` = ?"
            );
            if ($removeIncoming === false) {
                throw new mysqli_sql_exception($conn->error);
            }
            $removeIncoming->bind_param("s", $invoiceId);
            if (!$removeIncoming->execute()) {
                throw new mysqli_sql_exception($removeIncoming->error);
            }
            if ($removeIncoming->affected_rows !== 1) {
                $removeIncoming->close();
                throw new mysqli_sql_exception("The incoming invoice could not be removed.");
            }
            $removeIncoming->close();

            if (!$conn->commit()) {
                throw new mysqli_sql_exception($conn->error);
            }
            $transactionStarted = false;
            $qa_action_success = "Invoice accepted and moved to QA Work.";
        } catch (mysqli_sql_exception $exception) {
            if ($transactionStarted) {
                try {
                    $conn->rollback();
                } catch (mysqli_sql_exception $rollbackException) {
                    error_log("Unable to roll back QA work transfer: " . $rollbackException->getMessage());
                }
            }
            error_log("Unable to accept QA invoice: " . $exception->getMessage());
            $qa_action_error = "Invoice was not accepted: " . $exception->getMessage();
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["complete"])) {
    $invoiceId = is_string($_POST["complete"]) ? trim($_POST["complete"]) : "";

    if ($invoiceId === "") {
        $qa_action_error = "The QA invoice could not be identified.";
    } else {
        $transactionStarted = false;
        try {
            if (!$conn->begin_transaction()) {
                throw new mysqli_sql_exception($conn->error);
            }
            $transactionStarted = true;

            $lockQaWork = $conn->prepare(
                "SELECT 1 FROM `qa_work` WHERE `invoice_id` = ? FOR UPDATE"
            );
            if ($lockQaWork === false) {
                throw new mysqli_sql_exception($conn->error);
            }
            $lockQaWork->bind_param("s", $invoiceId);
            if (!$lockQaWork->execute()) {
                throw new mysqli_sql_exception($lockQaWork->error);
            }
            $lockQaWork->store_result();
            if ($lockQaWork->num_rows !== 1) {
                $lockQaWork->close();
                throw new mysqli_sql_exception("The QA invoice is no longer available.");
            }
            $lockQaWork->close();

            $copyToShipping = $conn->prepare(
                "INSERT INTO `completed_for_shipping` (`invoice_id`) "
                . "SELECT `invoice_id` FROM `qa_work` WHERE `invoice_id` = ?"
            );
            if ($copyToShipping === false) {
                throw new mysqli_sql_exception($conn->error);
            }
            $copyToShipping->bind_param("s", $invoiceId);
            if (!$copyToShipping->execute()) {
                throw new mysqli_sql_exception($copyToShipping->error);
            }
            if ($copyToShipping->affected_rows !== 1) {
                $copyToShipping->close();
                throw new mysqli_sql_exception("The invoice could not be sent for shipping.");
            }
            $copyToShipping->close();

            $statusIdColumn = getQaInvoiceIdColumn($conn);
            $markQaPassed = $conn->prepare(
                "UPDATE `invoices` SET `pass_qa` = 'PASS' WHERE `" . $statusIdColumn . "` = ?"
            );
            if ($markQaPassed === false) {
                throw new mysqli_sql_exception($conn->error);
            }
            $markQaPassed->bind_param("s", $invoiceId);
            if (!$markQaPassed->execute()) {
                throw new mysqli_sql_exception($markQaPassed->error);
            }
            $markQaPassed->close();

            $verifyQaPassed = $conn->prepare(
                "SELECT 1 FROM `invoices` "
                . "WHERE `" . $statusIdColumn . "` = ? AND `pass_qa` = 'PASS' FOR UPDATE"
            );
            if ($verifyQaPassed === false) {
                throw new mysqli_sql_exception($conn->error);
            }
            $verifyQaPassed->bind_param("s", $invoiceId);
            if (!$verifyQaPassed->execute()) {
                throw new mysqli_sql_exception($verifyQaPassed->error);
            }
            $verifyQaPassed->store_result();
            if ($verifyQaPassed->num_rows !== 1) {
                $verifyQaPassed->close();
                throw new mysqli_sql_exception("The invoice QA status could not be updated.");
            }
            $verifyQaPassed->close();

            $removeQaWork = $conn->prepare(
                "DELETE FROM `qa_work` WHERE `invoice_id` = ?"
            );
            if ($removeQaWork === false) {
                throw new mysqli_sql_exception($conn->error);
            }
            $removeQaWork->bind_param("s", $invoiceId);
            if (!$removeQaWork->execute()) {
                throw new mysqli_sql_exception($removeQaWork->error);
            }
            if ($removeQaWork->affected_rows !== 1) {
                $removeQaWork->close();
                throw new mysqli_sql_exception("The completed invoice could not be removed from QA Work.");
            }
            $removeQaWork->close();

            if (!$conn->commit()) {
                throw new mysqli_sql_exception($conn->error);
            }
            $transactionStarted = false;
            $qa_action_success = "Invoice completed and sent for shipping.";
        } catch (mysqli_sql_exception $exception) {
            if ($transactionStarted) {
                try {
                    $conn->rollback();
                } catch (mysqli_sql_exception $rollbackException) {
                    error_log("Unable to roll back QA completion: " . $rollbackException->getMessage());
                }
            }
            error_log("Unable to complete QA invoice: " . $exception->getMessage());
            $qa_action_error = "Invoice was not completed: " . $exception->getMessage();
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
    error_log("Unable to inspect invoices table for QA: " . $exception->getMessage());
    $qa_incoming_error = "Incoming invoices could not be loaded.";
    $qa_working_error = "QA work could not be loaded.";
}

if ($invoiceIdColumn !== "") {
    try {
        $incomingInvoices = "
            SELECT qi.`invoice_id`, i.`customer_name`, i.`services`, i.`quantity`
            FROM `qa_incoming` qi
            LEFT JOIN `invoices` i ON i.`" . $invoiceIdColumn . "` = qi.`invoice_id`
            ORDER BY qi.`invoice_id` DESC
        ";
        $queryIncomingInvoices = $conn->query($incomingInvoices);
        if ($queryIncomingInvoices === false) {
            throw new mysqli_sql_exception($conn->error);
        }
        while ($row = $queryIncomingInvoices->fetch_assoc()) {
            $qa_incoming_rows[] = $row;
        }
    } catch (mysqli_sql_exception $exception) {
        error_log("Unable to load incoming QA invoices: " . $exception->getMessage());
        $qa_incoming_error = "Incoming invoices could not be loaded.";
    }

    try {
        $workingInvoices = "
            SELECT qw.`invoice_id`, i.`customer_name`, i.`services`, i.`quantity`
            FROM `qa_work` qw
            LEFT JOIN `invoices` i ON i.`" . $invoiceIdColumn . "` = qw.`invoice_id`
            ORDER BY qw.`invoice_id` DESC
        ";
        $queryWorkingInvoices = $conn->query($workingInvoices);
        if ($queryWorkingInvoices === false) {
            throw new mysqli_sql_exception($conn->error);
        }
        while ($row = $queryWorkingInvoices->fetch_assoc()) {
            $qa_working_rows[] = $row;
        }
    } catch (mysqli_sql_exception $exception) {
        error_log("Unable to load QA Work invoices: " . $exception->getMessage());
        $qa_working_error = "QA work could not be loaded.";
    }
}
