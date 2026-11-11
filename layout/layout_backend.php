<?php
require "../dbconn.php";

$layout_action_error = "";

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
            invoices.`services`, invoices.`quantity`, invoices.`price`, invoices.`completion_date`
        FROM `admin_invoices` AS invoices
        INNER JOIN `layout_artist_work` AS work
            ON invoices.`invoice_id` = work.`invoice_id`"
    );
} catch (mysqli_sql_exception $exception) {
    error_log("Layout invoice listing failed: " . $exception->getMessage());
    $layout_action_error = "Invoice information could not be loaded. Please refresh the page.";
}
