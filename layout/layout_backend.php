<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "layout") {
    http_response_code(403);
    exit("Forbidden");
}

require_once "../dbconn.php";

$layout_incoming_rows = [];
$layout_working_rows = [];
$layout_incoming_error = "";
$layout_working_error = "";
$layout_action_error = "";
$layout_action_success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["send_work"])) {
    $invoiceId = trim((string) ($_POST["invoice_id"] ?? ""));
    $department = (string) ($_POST["department"] ?? "");
    $upload = $_FILES["work_file"] ?? null;

    if ($department !== "production") {
        $layout_action_error = "Only Production transfers are configured. The invoice remains in Layout.";
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
                        $sendToProduction->close();

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
                        $layout_action_success = "Invoice and work file sent to Production.";
                    } catch (mysqli_sql_exception $exception) {
                        if ($transactionStarted) {
                            try {
                                $conn->rollback();
                            } catch (mysqli_sql_exception $rollbackException) {
                                error_log("Unable to roll back Production handoff: " . $rollbackException->getMessage());
                            }
                        }
                        if (is_file($workFilePath)) {
                            unlink($workFilePath);
                        }
                        error_log("Unable to send Layout invoice to Production: " . $exception->getMessage());
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
