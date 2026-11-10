<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require "../dbconn.php";

$invoice_error = "";
$invoice_success = "";

if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin" && isset($_POST["create-invoice"])) {
    $customer_name = trim((string) ($_POST["customer_name"] ?? ""));
    $customer_number = trim((string) ($_POST["customer_number"] ?? ""));
    $customer_address = trim((string) ($_POST["shipping_address"] ?? ""));
    $land_marks = trim((string) ($_POST["landmarks"] ?? ""));
    $services_offered = trim((string) ($_POST["product-service"] ?? ""));
    $quantity = (int) ($_POST["quantity"] ?? 0);
    $agreed_price = trim((string) ($_POST["total-price"] ?? ""));
    $target_completion = trim((string) ($_POST["due_date"] ?? ""));
    $notes = trim((string) ($_POST["notes"] ?? ""));
    $file = null;
    $uploadedFilePath = null;
    $upload = $_FILES["reference_image"] ?? null;
    $uploadDirectory = __DIR__ . DIRECTORY_SEPARATOR . "uploads";
    $maxUploadSize = 5 * 1024 * 1024;
    $allowedMimeTypes = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
    ];

    if ($upload !== null) {
        if (!is_array($upload) || !isset($upload["error"], $upload["size"], $upload["tmp_name"])) {
            $invoice_error = "The reference image upload is invalid.";
        } elseif ($upload["error"] === UPLOAD_ERR_NO_FILE) {
            $upload = null;
        } elseif ($upload["error"] !== UPLOAD_ERR_OK) {
            $invoice_error = "The reference image could not be uploaded. Please try again.";
        } elseif ($upload["size"] > $maxUploadSize) {
            $invoice_error = "The reference image must be 5 MB or smaller.";
        } else {
            $fileInfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $fileInfo->file($upload["tmp_name"]);

            if (!isset($allowedMimeTypes[$mimeType])) {
                $invoice_error = "Please choose a PNG or JPEG image.";
            } else {
                $fileName = bin2hex(random_bytes(16)) . "." . $allowedMimeTypes[$mimeType];
                $file = "uploads/" . $fileName;
                $destination = $uploadDirectory . DIRECTORY_SEPARATOR . $fileName;

                if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true)) {
                    $invoice_error = "The server could not prepare image storage.";
                } elseif (!move_uploaded_file($upload["tmp_name"], $destination)) {
                    $invoice_error = "The reference image could not be saved. Please try again.";
                } else {
                    $uploadedFilePath = $destination;
                }
            }
        }
    }

    if ($invoice_error === "") {
        $invoice_sql = "INSERT INTO admin_invoices(
            `customer_name`, `contact_number`, `customer_address`, `landmarks`, `services`, `quantity`, `price`, `completion_date`, `notes`, `file`) VALUES(?,?,?,?,?,?,?,?,?,?)";

        try {
            $bind_invoice_sql = $conn->prepare($invoice_sql);
            if ($bind_invoice_sql === false) {
                throw new mysqli_sql_exception($conn->error);
            }

            $bind_invoice_sql->bind_param(
                "sssssissss",
                $customer_name,
                $customer_number,
                $customer_address,
                $land_marks,
                $services_offered,
                $quantity,
                $agreed_price,
                $target_completion,
                $notes,
                $file
            );
            if ($bind_invoice_sql->execute()) {
                $invoice_success = "Invoice created successfully.";
            } else {
                throw new mysqli_sql_exception($bind_invoice_sql->error);
            }
        } catch (mysqli_sql_exception $exception) {
            if ($uploadedFilePath !== null && file_exists($uploadedFilePath)) {
                unlink($uploadedFilePath);
            }
            error_log("Invoice insert failed: " . $exception->getMessage());
            $invoice_error = "The invoice could not be saved. Please check the entered information and try again.";
        }
    }
}

$select_invoice_sql = $conn->query("SELECT * FROM admin_invoices");



?>