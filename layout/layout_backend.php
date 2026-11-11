<?php
require "../dbconn.php";

$layout_action_error = "";

if (isset($_POST["send_work"])) {
    $invoice_id = filter_var($_POST["invoice_id"] ?? null, FILTER_VALIDATE_INT);
    $department = (string) ($_POST["department"] ?? "");
    $allowedDepartments = ["admin", "production", "quality assurance"];
    $upload = $_FILES["work_file"] ?? null;
    $maxUploadSize = 10 * 1024 * 1024;
    $allowedMimeTypes = [
        "application/pdf" => "pdf",
        "image/jpeg" => "jpg",
        "image/png" => "png",
    ];
    $uploadedFilePath = null;

    if ($invoice_id === false || $invoice_id < 1 || !in_array($department, $allowedDepartments, true)) {
        $layout_action_error = "Choose a valid invoice and department.";
    } elseif (!is_array($upload) || !isset($upload["error"], $upload["size"], $upload["tmp_name"])) {
        $layout_action_error = "Choose a file to send.";
    } elseif ($upload["error"] !== UPLOAD_ERR_OK) {
        $layout_action_error = $upload["error"] === UPLOAD_ERR_NO_FILE
            ? "Choose a file to send."
            : "The file could not be uploaded. Please try again.";
    } elseif ($upload["size"] > $maxUploadSize) {
        $layout_action_error = "The file must be 10 MB or smaller.";
    } else {
        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file($upload["tmp_name"]);

        if (!isset($allowedMimeTypes[$mimeType])) {
            $layout_action_error = "Choose a PDF, PNG, or JPEG file.";
        } else {
            $uploadDirectory = __DIR__ . DIRECTORY_SEPARATOR . "handoff_uploads";
            $fileName = bin2hex(random_bytes(16)) . "." . $allowedMimeTypes[$mimeType];
            $filePath = "handoff_uploads/" . $fileName;
            $uploadedFilePath = $uploadDirectory . DIRECTORY_SEPARATOR . $fileName;

            try {
                if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true)) {
                    throw new RuntimeException("The server could not prepare file storage.");
                }
                if (!move_uploaded_file($upload["tmp_name"], $uploadedFilePath)) {
                    throw new RuntimeException("The uploaded file could not be saved.");
                }

                if (!$conn->query(
                    "CREATE TABLE IF NOT EXISTS `layout_artist_handoffs` (
                        `handoff_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                        `invoice_id` INT NOT NULL,
                        `department` VARCHAR(50) NOT NULL,
                        `file_path` VARCHAR(255) NOT NULL,
                        `sent_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`handoff_id`),
                        INDEX (`invoice_id`)
                    ) ENGINE=InnoDB"
                )) {
                    throw new mysqli_sql_exception($conn->error);
                }

                $saveHandoff = $conn->prepare(
                    "INSERT INTO `layout_artist_handoffs` (`invoice_id`, `department`, `file_path`)
                    SELECT ?, ?, ?
                    FROM `layout_artist_work`
                    WHERE `invoice_id` = ?"
                );
                if ($saveHandoff === false) {
                    throw new mysqli_sql_exception($conn->error);
                }
                $saveHandoff->bind_param("issi", $invoice_id, $department, $filePath, $invoice_id);
                if (!$saveHandoff->execute()) {
                    throw new mysqli_sql_exception($saveHandoff->error);
                }

                if ($saveHandoff->affected_rows !== 1) {
                    throw new RuntimeException("That invoice is no longer in working invoices.");
                }

                header("Location: layout_work.php?sent=1");
                exit();
            } catch (mysqli_sql_exception $exception) {
                if ($uploadedFilePath !== null && file_exists($uploadedFilePath)) {
                    unlink($uploadedFilePath);
                }
                error_log("Layout file handoff failed: " . $exception->getMessage());
                $layout_action_error = "The file could not be sent. Please try again.";
            } catch (RuntimeException $exception) {
                if ($uploadedFilePath !== null && file_exists($uploadedFilePath)) {
                    unlink($uploadedFilePath);
                }
                error_log("Layout file handoff failed: " . $exception->getMessage());
                $layout_action_error = $exception->getMessage();
            }
        }
    }
}

if (isset($_POST["accept"])) {
    $layout_id = filter_var($_POST["accept"], FILTER_VALIDATE_INT);

    if ($layout_id === false || $layout_id < 1) {
        $layout_action_error = "The selected incoming job is invalid.";
    } else {
        try {
            $conn->begin_transaction();

            $insert_work = $conn->prepare(
                "INSERT INTO layout_artist_work (`layout_id`, `invoice_id`)
                SELECT `layout_id`, `invoice_id`
                FROM layout_artist_incoming
                WHERE `layout_id` = ?"
            );
            $insert_work->bind_param("i", $layout_id);
            $insert_work->execute();

            if ($insert_work->affected_rows !== 1) {
                throw new RuntimeException("The selected incoming job was not found.");
            }

            $delete_incoming = $conn->prepare(
                "DELETE FROM layout_artist_incoming WHERE `layout_id` = ?"
            );
            $delete_incoming->bind_param("i", $layout_id);
            $delete_incoming->execute();

            if ($delete_incoming->affected_rows !== 1) {
                throw new RuntimeException("The selected incoming job could not be removed.");
            }

            $conn->commit();
            header("Location: layout_work.php?accepted=1");
            exit();
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            error_log("Layout job acceptance failed: " . $exception->getMessage());
            $layout_action_error = "The job could not be accepted. Please try again.";
        } catch (RuntimeException $exception) {
            $conn->rollback();
            error_log("Layout job acceptance failed: " . $exception->getMessage());
            $layout_action_error = $exception->getMessage();
        }
    }
}

$query_incoming_invoices = false;
$query_working_invoices = false;

try {
    $query_incoming_invoices = $conn->query(
        "SELECT incoming.`layout_id`, invoices.`invoice_id`, invoices.`customer_name`,
            invoices.`services`, invoices.`quantity`, invoices.`price`, invoices.`completion_date`
        FROM `admin_invoices` AS invoices
        INNER JOIN `layout_artist_incoming` AS incoming
            ON invoices.`invoice_id` = incoming.`invoice_id`"
    );

    $query_working_invoices = $conn->query(
        "SELECT work.`layout_id`, invoices.`invoice_id`, invoices.`customer_name`,
            invoices.`contact_number`, invoices.`customer_address`, invoices.`landmarks`,
            invoices.`services`, invoices.`quantity`, invoices.`price`, invoices.`completion_date`,
            invoices.`notes`, invoices.`file`
        FROM `admin_invoices` AS invoices
        INNER JOIN `layout_artist_work` AS work
            ON invoices.`invoice_id` = work.`invoice_id`"
    );
} catch (mysqli_sql_exception $exception) {
    error_log("Layout invoice listing failed: " . $exception->getMessage());
    $layout_action_error = "Invoice information could not be loaded. Please refresh the page.";
}
