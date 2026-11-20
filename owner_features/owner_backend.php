<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "dbconn.php";

function ownerFetchAllRows(mysqli $conn, string $sql): array
{
    $result = $conn->query($sql);
    if ($result === false) {
        throw new mysqli_sql_exception($conn->error);
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function ownerLoadInvoices(mysqli $conn): array
{
    return ownerFetchAllRows(
        $conn,
        "
        SELECT
            i.`invoice_id`,
            i.`customer_name`,
            i.`contact_number`,
            i.`services`,
            i.`quantity`,
            i.`price`,
            i.`completion_date`,
            CASE
                WHEN EXISTS (
                    SELECT 1 FROM `admin_invoices` ai WHERE ai.`invoice_id` = i.`invoice_id`
                ) THEN 'Admin'
                WHEN EXISTS (
                    SELECT 1 FROM `completed_job_orders` cjo WHERE cjo.`invoice_id` = i.`invoice_id`
                ) THEN 'Ready to ship'
                WHEN EXISTS (
                    SELECT 1 FROM `qa_work` qw WHERE qw.`invoice_id` = i.`invoice_id`
                ) THEN 'QA Work'
                WHEN EXISTS (
                    SELECT 1 FROM `qa_incoming` qi WHERE qi.`invoice_id` = i.`invoice_id`
                ) THEN 'QA Incoming'
                WHEN EXISTS (
                    SELECT 1 FROM `production_work` pw WHERE pw.`invoice_id` = i.`invoice_id`
                ) THEN 'Production Work'
                WHEN EXISTS (
                    SELECT 1 FROM `production_incoming` pi WHERE pi.`invoice_id` = i.`invoice_id`
                ) THEN 'Production Incoming'
                WHEN EXISTS (
                    SELECT 1 FROM `layout_artist_work` law WHERE law.`invoice_id` = i.`invoice_id`
                ) THEN 'Layout Work'
                WHEN EXISTS (
                    SELECT 1 FROM `layout_artist_incoming` lai WHERE lai.`invoice_id` = i.`invoice_id`
                ) THEN 'Layout Incoming'
                ELSE 'No active work'
            END AS `current_stage`
        FROM `invoices` i

        UNION ALL

        SELECT
            a.`invoice_id`,
            a.`customer_name`,
            a.`contact_number`,
            a.`services`,
            a.`quantity`,
            a.`price`,
            a.`completion_date`,
            'Admin' AS `current_stage`
        FROM `admin_invoices` a
        WHERE NOT EXISTS (
            SELECT 1 FROM `invoices` i WHERE i.`invoice_id` = a.`invoice_id`
        )
        ORDER BY `completion_date` ASC, `invoice_id` DESC
        "
    );
}

function ownerLoadInvoiceTableRows(mysqli $conn): array
{
    return ownerFetchAllRows(
        $conn,
        "SELECT `invoice_id`, `customer_name`, `services`, `completion_date`, `price` "
        . "FROM `invoices` ORDER BY `completion_date` ASC, `invoice_id` DESC"
    );
}

function ownerCountCompletedProjects(mysqli $conn): int
{
    $rows = ownerFetchAllRows(
        $conn,
        "SELECT COUNT(DISTINCT `invoice_id`) AS `project_count` FROM `completed_job_orders`"
    );
    return (int) ($rows[0]["project_count"] ?? 0);
}

function ownerLoadMaterials(mysqli $conn): array
{
    return ownerFetchAllRows(
        $conn,
        "SELECT `materials_id`, `material`, `quantity` "
        . "FROM `layout_materials_holder` ORDER BY `material` ASC"
    );
}

function ownerLoadUsers(mysqli $conn): array
{
    return ownerFetchAllRows(
        $conn,
        "SELECT `user_id`, `fullname`, `username`, `department`, `role`, `created_at` "
        . "FROM `users` ORDER BY `created_at` DESC, `user_id` DESC"
    );
}

function ownerBuildInvoiceMetrics(array $invoices): array
{
    $metrics = [
        "invoice_count" => count($invoices),
        "total_invoiced" => 0.0,
        "customer_count" => 0,
        "stages" => [
            "Admin" => 0,
            "Layout" => 0,
            "Production" => 0,
            "Quality Assurance" => 0,
            "Ready to ship" => 0,
            "No active work" => 0,
        ],
    ];
    $customers = [];

    foreach ($invoices as $invoice) {
        $price = trim((string) ($invoice["price"] ?? ""));
        if (is_numeric($price)) {
            $metrics["total_invoiced"] += (float) $price;
        }

        $contactNumber = trim((string) ($invoice["contact_number"] ?? ""));
        $customerName = trim((string) ($invoice["customer_name"] ?? ""));
        $customerKey = $contactNumber !== "" ? $contactNumber : $customerName;
        if ($customerKey !== "") {
            $customers[$customerKey] = true;
        }

        $stage = (string) ($invoice["current_stage"] ?? "No active work");
        if ($stage === "Admin") {
            $metrics["stages"]["Admin"]++;
        } elseif (strpos($stage, "Layout ") === 0) {
            $metrics["stages"]["Layout"]++;
        } elseif (strpos($stage, "Production ") === 0) {
            $metrics["stages"]["Production"]++;
        } elseif (strpos($stage, "QA ") === 0) {
            $metrics["stages"]["Quality Assurance"]++;
        } elseif ($stage === "Ready to ship") {
            $metrics["stages"]["Ready to ship"]++;
        } else {
            $metrics["stages"]["No active work"]++;
        }
    }

    $metrics["customer_count"] = count($customers);
    return $metrics;
}

function ownerBuildMaterialMetrics(array $materials): array
{
    $metrics = [
        "material_count" => count($materials),
        "total_units" => 0,
        "in_stock" => 0,
        "out_of_stock" => 0,
    ];

    foreach ($materials as $material) {
        $quantity = (int) ($material["quantity"] ?? 0);
        $metrics["total_units"] += $quantity;
        if ($quantity > 0) {
            $metrics["in_stock"]++;
        } else {
            $metrics["out_of_stock"]++;
        }
    }

    return $metrics;
}

function ownerBuildUserMetrics(array $users): array
{
    $metrics = [
        "team_members" => count($users),
        "admins" => 0,
        "production" => 0,
        "design_qa" => 0,
    ];

    foreach ($users as $user) {
        $role = strtolower(trim((string) ($user["role"] ?? "")));
        if ($role === "admin" || $role === "owner") {
            $metrics["admins"]++;
        } elseif ($role === "production") {
            $metrics["production"]++;
        } elseif ($role === "layout" || $role === "qa") {
            $metrics["design_qa"]++;
        }
    }

    return $metrics;
}

function ownerFormatMoney($amount): string
{
    if (!is_numeric($amount)) {
        return "—";
    }
    return "₱" . number_format((float) $amount, 2);
}

function ownerFormatDate($date): string
{
    $date = trim((string) $date);
    if ($date === "" || $date === "0000-00-00") {
        return "—";
    }

    $timestamp = strtotime($date);
    return $timestamp === false ? "—" : date("M j, Y", $timestamp);
}

function ownerOrderStatusClass(string $stage): string
{
    if ($stage === "Ready to ship") {
        return "status-shipped";
    }
    if ($stage === "Admin" || strpos($stage, "Incoming") !== false || $stage === "No active work") {
        return "status-new";
    }
    return "status-packing";
}

function ownerInitials(string $name): string
{
    $name = trim($name);
    if ($name === "") {
        return "—";
    }

    $parts = preg_split('/\s+/', $name);
    $initials = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) {
        $initials .= strtoupper(substr($parts[count($parts) - 1], 0, 1));
    }
    return $initials;
}
