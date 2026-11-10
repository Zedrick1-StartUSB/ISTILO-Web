<?php
require "../dbconn.php";

if(isset($_POST['create-invoice'])){
    $customer_name = $_POST["customer_name"];
    $customer_number = $_POST["customer_number"];
    $customer_address = $_POST["shipping_address"];
    $land_marks = $_POST["landmarks"];
    $services_offered = $_POST["product-service"];
    $quantity = $_POST["quantity"];
    $agreed_price = $_POST["total-price"];
    $target_completion = $_POST["due_date"];
    $notes = $_POST["notes"];
    $file = $_POST["reference_image"];

    $invoice_sql = "INSERT INTO admin_invoices(
        `customer_name`, `contact_number`, `customer_address`, `landmarks`, `services`, `quantity`, `price`, `completion_date`, `notes`, `file`) VALUES(?,?,?,?,?,?,?,?,?,?)";

    $bind_invoice_sql = $conn->prepare($invoice_sql);
    $bind_invoice_sql->bind_param("sssssissss", $customer_name, $customer_number, $customer_address, $land_marks, $services_offered, $quantity, $agreed_price, $target_completion, $notes, $file);
    $bind_invoice_sql->execute();
    
}


?>