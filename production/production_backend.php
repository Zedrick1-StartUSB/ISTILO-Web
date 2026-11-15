<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "production") {
    http_response_code(403);
    exit("Forbidden");
}

require_once "../dbconn.php";

$production_incoming_rows = [];
$production_working_rows = [];
$production_incoming_error = "";
$production_working_error = "";
$production_action_error = "";
$production_action_success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["send_to_department"])) {
    $invoiceId = is_string($_POST["invoice_id"] ?? null) ? trim($_POST["invoice_id"]) : "";
    $department = is_string($_POST["department"] ?? null) ? $_POST["department"] : "";
    $departmentNames = [
        "admin" => "Admin",
        "layout" => "Layout",
        "qa" => "Quality Assurance",
    ];

    if ($invoiceId === "") {
        $production_action_error = "The Production Work invoice could not be identified.";
    } elseif (!isset($departmentNames[$department])) {
        $production_action_error = "Choose a valid department.";
    } else {
        $transactionStarted = false;
        try {
            if (!$conn->begin_transaction()) {
                throw new mysqli_sql_exception($conn->error);
            }
            $transactionStarted = true;

            $lockWork = $conn->prepare(
                "SELECT `work_file` FROM `production_work` WHERE `invoice_id` = ? FOR UPDATE"
            );
            if ($lockWork === false) {
                throw new mysqli_sql_exception($conn->error);
            }
            $lockWork->bind_param("s", $invoiceId);
            if (!$lockWork->execute()) {
                throw new mysqli_sql_exception($lockWork->error);
            }
            $lockWork->store_result();
            if ($lockWork->num_rows !== 1) {
                $lockWork->close();
                throw new mysqli_sql_exception("The invoice is no longer available in Production Work.");
            }
            $lockWork->close();

            if ($department === "admin") {
                $invoiceColumns = $conn->query("SHOW COLUMNS FROM `invoices`");
                $adminColumns = $conn->query("SHOW COLUMNS FROM `admin_invoices`");
                if ($invoiceColumns === false || $adminColumns === false) {
                    throw new mysqli_sql_exception($conn->error);
                }

                $invoiceDefinitions = [];
                $adminDefinitions = [];
                $invoiceFields = [];
                $invoiceIdentifierColumn = "";
                $primaryKeyColumn = "";
                while ($column = $invoiceColumns->fetch_assoc()) {
                    $field = (string) $column["Field"];
                    $invoiceFields[] = $field;
                    $invoiceDefinitions[$field] = [
                        $column["Type"],
                        $column["Null"],
                        $column["Default"],
                        $column["Extra"],
                    ];
                    if ($field === "invoice_id") {
                        $invoiceIdentifierColumn = $field;
                    } elseif ($field === "id") {
                        $primaryKeyColumn = $field;
                    } elseif ($primaryKeyColumn === "" && $column["Key"] === "PRI") {
                        $primaryKeyColumn = $field;
                    }
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

                if ($invoiceIdentifierColumn === "") {
                    $invoiceIdentifierColumn = $primaryKeyColumn;
                }
                $comparableInvoiceDefinitions = $invoiceDefinitions;
                $comparableAdminDefinitions = $adminDefinitions;
                ksort($comparableInvoiceDefinitions);
                ksort($comparableAdminDefinitions);
                if ($comparableInvoiceDefinitions !== $comparableAdminDefinitions) {
                    throw new mysqli_sql_exception("The `invoices` and `admin_invoices` table structures do not match.");
                }
                if (
                    $invoiceIdentifierColumn === ""
                    || !preg_match('/\A[A-Za-z0-9_]+\z/', $invoiceIdentifierColumn)
                ) {
                    throw new mysqli_sql_exception("No valid invoice identifier column was found.");
                }

                $quotedFields = [];
                foreach ($invoiceFields as $field) {
                    if (!preg_match('/\A[A-Za-z0-9_]+\z/', $field)) {
                        throw new mysqli_sql_exception("An invoice column name is invalid.");
                    }
                    $quotedFields[] = "`" . $field . "`";
                }
                $fieldList = implode(", ", $quotedFields);
                $copyToAdmin = $conn->prepare(
                    "INSERT INTO `admin_invoices` (" . $fieldList . ") "
                    . "SELECT " . $fieldList . " FROM `invoices` "
                    . "WHERE `" . $invoiceIdentifierColumn . "` = ?"
                );
                if ($copyToAdmin === false) {
                    throw new mysqli_sql_exception($conn->error);
                }
                $copyToAdmin->bind_param("s", $invoiceId);
                if (!$copyToAdmin->execute()) {
                    throw new mysqli_sql_exception($copyToAdmin->error);
                }
                if ($copyToAdmin->affected_rows !== 1) {
                    $copyToAdmin->close();
                    throw new mysqli_sql_exception("The invoice could not be returned to Admin.");
                }
                $copyToAdmin->close();
            } else {
                $destinationTable = $department === "layout"
                    ? "layout_artist_incoming"
                    : "qa_incoming";
                $sendToDepartment = $conn->prepare(
                    "INSERT INTO `" . $destinationTable . "` (`invoice_id`) VALUES (?)"
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
                    throw new mysqli_sql_exception("The invoice could not be sent to " . $departmentNames[$department] . ".");
                }
                $sendToDepartment->close();
            }

            $removeProductionWork = $conn->prepare(
                "DELETE FROM `production_work` WHERE `invoice_id` = ?"
            );
            if ($removeProductionWork === false) {
                throw new mysqli_sql_exception($conn->error);
            }
            $removeProductionWork->bind_param("s", $invoiceId);
            if (!$removeProductionWork->execute()) {
                throw new mysqli_sql_exception($removeProductionWork->error);
            }
            if ($removeProductionWork->affected_rows !== 1) {
                $removeProductionWork->close();
                throw new mysqli_sql_exception("The invoice could not be removed from Production Work.");
            }
            $removeProductionWork->close();

            if (!$conn->commit()) {
                throw new mysqli_sql_exception($conn->error);
            }
            $transactionStarted = false;
            $production_action_success = "Invoice sent to " . $departmentNames[$department] . ".";
        } catch (mysqli_sql_exception $exception) {
            if ($transactionStarted) {
                try {
                    $conn->rollback();
                } catch (mysqli_sql_exception $rollbackException) {
                    error_log("Unable to roll back Production department handoff: " . $rollbackException->getMessage());
                }
            }
            error_log("Unable to send Production invoice to another department: " . $exception->getMessage());
            $production_action_error = "Invoice was not sent: " . $exception->getMessage();
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["accept"])) {
    $invoiceId = is_string($_POST["accept"]) ? trim($_POST["accept"]) : "";

    if ($invoiceId === "") {
        $production_action_error = "The incoming invoice could not be identified.";
    } else {
        $transactionStarted = false;
        try {
            if (!$conn->begin_transaction()) {
                throw new mysqli_sql_exception($conn->error);
            }
            $transactionStarted = true;

            $lockIncoming = $conn->prepare(
                "SELECT 1 FROM `production_incoming` WHERE `invoice_id` = ? FOR UPDATE"
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

            $copyToProductionWork = $conn->prepare(
                "INSERT INTO `production_work` (`invoice_id`, `work_file`) "
                . "SELECT `invoice_id`, `file` FROM `production_incoming` WHERE `invoice_id` = ?"
            );
            if ($copyToProductionWork === false) {
                throw new mysqli_sql_exception($conn->error);
            }
            $copyToProductionWork->bind_param("s", $invoiceId);
            if (!$copyToProductionWork->execute()) {
                throw new mysqli_sql_exception($copyToProductionWork->error);
            }
            if ($copyToProductionWork->affected_rows !== 1) {
                $copyToProductionWork->close();
                throw new mysqli_sql_exception("The invoice could not be moved to Production Work.");
            }
            $copyToProductionWork->close();

            $removeIncoming = $conn->prepare(
                "DELETE FROM `production_incoming` WHERE `invoice_id` = ?"
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
            $production_action_success = "Invoice accepted and moved to Production Work.";
        } catch (mysqli_sql_exception $exception) {
            if ($transactionStarted) {
                try {
                    $conn->rollback();
                } catch (mysqli_sql_exception $rollbackException) {
                    error_log("Unable to roll back Production work transfer: " . $rollbackException->getMessage());
                }
            }
            error_log("Unable to accept Production invoice: " . $exception->getMessage());
            $production_action_error = "Invoice was not accepted: " . $exception->getMessage();
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
    error_log("Unable to inspect invoices table for Production: " . $exception->getMessage());
    $production_incoming_error = "Incoming invoices could not be loaded.";
    $production_working_error = "Production work could not be loaded.";
}

if ($invoiceIdColumn !== "") {
    $invoiceIdSelection = $invoiceIdColumn === "invoice_id"
        ? ""
        : ", i.`" . $invoiceIdColumn . "` AS `invoice_id`";

    try {
        $incomingInvoices = "
            SELECT i.*" . $invoiceIdSelection . ", pi.`file` AS `work_file_name`
            FROM `invoices` i
            INNER JOIN `production_incoming` pi ON i.`" . $invoiceIdColumn . "` = pi.`invoice_id`
            ORDER BY i.`" . $invoiceIdColumn . "` DESC
        ";
        $queryIncomingInvoices = $conn->query($incomingInvoices);
        if ($queryIncomingInvoices === false) {
            throw new mysqli_sql_exception($conn->error);
        }
        while ($row = $queryIncomingInvoices->fetch_assoc()) {
            $production_incoming_rows[] = $row;
        }
    } catch (mysqli_sql_exception $exception) {
        error_log("Unable to load incoming Production invoices: " . $exception->getMessage());
        $production_incoming_error = "Incoming invoices could not be loaded.";
    }

    try {
        $workingInvoices = "
            SELECT i.*" . $invoiceIdSelection . ", pw.`work_file` AS `work_file_name`
            FROM `invoices` i
            INNER JOIN `production_work` pw ON i.`" . $invoiceIdColumn . "` = pw.`invoice_id`
            ORDER BY i.`" . $invoiceIdColumn . "` DESC
        ";
        $queryWorkingInvoices = $conn->query($workingInvoices);
        if ($queryWorkingInvoices === false) {
            throw new mysqli_sql_exception($conn->error);
        }
        while ($row = $queryWorkingInvoices->fetch_assoc()) {
            $production_working_rows[] = $row;
        }
    } catch (mysqli_sql_exception $exception) {
        error_log("Unable to load Production Work invoices: " . $exception->getMessage());
        $production_working_error = "Production work could not be loaded.";
    }
}
