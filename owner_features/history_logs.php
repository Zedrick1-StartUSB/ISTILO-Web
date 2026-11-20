<?php
session_start();
require "../logout.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "owner") {
    header("Location: /ISTILO_SYSTEM/Login/html/login.php");
    exit();
}

$owner_data_error = "";
$owner_invoices = [];
try {
    require_once __DIR__ . DIRECTORY_SEPARATOR . "owner_backend.php";
    $owner_invoices = ownerLoadInvoices($conn);
} catch (mysqli_sql_exception $exception) {
    error_log("Unable to load Owner workflow data: " . $exception->getMessage());
    $owner_data_error = "Current workflow data could not be loaded.";
}
$owner_workflow_rows = [
    "Admin" => [],
    "Layout" => [],
    "Production" => [],
    "Quality Assurance" => [],
];
foreach ($owner_invoices as $invoice) {
    $stage = (string) ($invoice["current_stage"] ?? "");
    if ($stage === "Admin") {
        $owner_workflow_rows["Admin"][] = $invoice;
    } elseif (strpos($stage, "Layout ") === 0) {
        $owner_workflow_rows["Layout"][] = $invoice;
    } elseif (strpos($stage, "Production ") === 0) {
        $owner_workflow_rows["Production"][] = $invoice;
    } elseif (strpos($stage, "QA ") === 0 || $stage === "Ready to ship") {
        $owner_workflow_rows["Quality Assurance"][] = $invoice;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f8f5ef">
    <title>History Logs | little label</title>
    <link rel="stylesheet" href="../bootstrap_template/css/bootstrap.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="history_logs.css">
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <a class="brand" href="dashboard.php"><span class="brand-mark">l.</span><span>little label</span></a>
            <p class="nav-caption">MENU</p>
            <nav class="side-nav" aria-label="Main navigation">
                <a class="side-link" href="dashboard.php">Dashboard</a>
                <a class="side-link" href="orders.php">Orders</a>
                <a class="side-link" href="materials.php">Materials</a>
                <a class="side-link" href="users.php">Users</a>
                <a class="side-link active" href="history_logs.php">History Logs</a>
                <form method="POST">
                    <button class="btn btn-danger" name="logout">Logout</button>
                </form>
            </nav>
        </aside>

        <main class="main-content">
            <header class="page-header">
                <div>
                    <p class="today-label">SHOP MANAGEMENT</p>
                    <h1>Current workflow</h1>
                    <p class="page-subtitle">See where each invoice is currently waiting or being worked on.</p>
                </div>
                <span class="subtle-badge header-badge">Live status</span>
            </header>

            <?php if ($owner_data_error !== ""): ?>
                <p class="alert alert-danger" role="alert"><?= htmlspecialchars($owner_data_error, ENT_QUOTES, "UTF-8") ?></p>
            <?php endif; ?>

            <section class="panel page-panel history-panel" aria-labelledby="department-title">
                <div class="panel-heading page-panel-heading">
                    <div><h2 id="department-title">Invoices by department</h2><p>These are current states, not a record of past transitions.</p></div>
                </div>

                <div class="department-tabs">
                    <input class="department-radio" type="radio" name="department" id="department-admin" checked>
                    <input class="department-radio" type="radio" name="department" id="department-layout">
                    <input class="department-radio" type="radio" name="department" id="department-production">
                    <input class="department-radio" type="radio" name="department" id="department-qa">

                    <div class="department-buttons" role="group" aria-label="Choose a department">
                        <label class="department-button" for="department-admin">Admin</label>
                        <label class="department-button" for="department-layout">Layout</label>
                        <label class="department-button" for="department-production">Production</label>
                        <label class="department-button" for="department-qa">Quality Assurance</label>
                    </div>

                    <div class="log-list log-list-admin">
                        <?php if ($owner_workflow_rows["Admin"] !== []): ?>
                            <?php foreach ($owner_workflow_rows["Admin"] as $invoice): ?>
                                <article class="log-entry">
                                    <span class="log-avatar avatar-pink">#</span>
                                    <div class="log-copy">
                                        <div><strong>Invoice #<?= htmlspecialchars((string) $invoice["invoice_id"], ENT_QUOTES, "UTF-8") ?></strong><span class="log-action">In Admin</span></div>
                                        <p><?= htmlspecialchars((string) ($invoice["customer_name"] ?? "Customer unavailable"), ENT_QUOTES, "UTF-8") ?> · <?= htmlspecialchars((string) ($invoice["services"] ?? "—"), ENT_QUOTES, "UTF-8") ?> · Qty <?= htmlspecialchars((string) ($invoice["quantity"] ?? "—"), ENT_QUOTES, "UTF-8") ?></p>
                                        <small>Due <?= htmlspecialchars(ownerFormatDate($invoice["completion_date"] ?? ""), ENT_QUOTES, "UTF-8") ?></small>
                                    </div>
                                    <span class="log-category">Current</span>
                                </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="py-3 text-muted">No invoices are currently in Admin.</p>
                        <?php endif; ?>
                    </div>

                    <div class="log-list log-list-layout">
                        <?php if ($owner_workflow_rows["Layout"] !== []): ?>
                            <?php foreach ($owner_workflow_rows["Layout"] as $invoice): ?>
                                <?php $stage = (string) $invoice["current_stage"]; ?>
                                <article class="log-entry">
                                    <span class="log-avatar avatar-yellow">#</span>
                                    <div class="log-copy">
                                        <div><strong>Invoice #<?= htmlspecialchars((string) $invoice["invoice_id"], ENT_QUOTES, "UTF-8") ?></strong><span class="log-action"><?= htmlspecialchars($stage, ENT_QUOTES, "UTF-8") ?></span></div>
                                        <p><?= htmlspecialchars((string) ($invoice["customer_name"] ?? "Customer unavailable"), ENT_QUOTES, "UTF-8") ?> · <?= htmlspecialchars((string) ($invoice["services"] ?? "—"), ENT_QUOTES, "UTF-8") ?> · Qty <?= htmlspecialchars((string) ($invoice["quantity"] ?? "—"), ENT_QUOTES, "UTF-8") ?></p>
                                        <small>Due <?= htmlspecialchars(ownerFormatDate($invoice["completion_date"] ?? ""), ENT_QUOTES, "UTF-8") ?></small>
                                    </div>
                                    <span class="log-category">Current</span>
                                </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="py-3 text-muted">No invoices are currently in Layout.</p>
                        <?php endif; ?>
                    </div>

                    <div class="log-list log-list-production">
                        <?php if ($owner_workflow_rows["Production"] !== []): ?>
                            <?php foreach ($owner_workflow_rows["Production"] as $invoice): ?>
                                <?php $stage = (string) $invoice["current_stage"]; ?>
                                <article class="log-entry">
                                    <span class="log-avatar avatar-green">#</span>
                                    <div class="log-copy">
                                        <div><strong>Invoice #<?= htmlspecialchars((string) $invoice["invoice_id"], ENT_QUOTES, "UTF-8") ?></strong><span class="log-action"><?= htmlspecialchars($stage, ENT_QUOTES, "UTF-8") ?></span></div>
                                        <p><?= htmlspecialchars((string) ($invoice["customer_name"] ?? "Customer unavailable"), ENT_QUOTES, "UTF-8") ?> · <?= htmlspecialchars((string) ($invoice["services"] ?? "—"), ENT_QUOTES, "UTF-8") ?> · Qty <?= htmlspecialchars((string) ($invoice["quantity"] ?? "—"), ENT_QUOTES, "UTF-8") ?></p>
                                        <small>Due <?= htmlspecialchars(ownerFormatDate($invoice["completion_date"] ?? ""), ENT_QUOTES, "UTF-8") ?></small>
                                    </div>
                                    <span class="log-category">Current</span>
                                </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="py-3 text-muted">No invoices are currently in Production.</p>
                        <?php endif; ?>
                    </div>

                    <div class="log-list log-list-qa">
                        <?php if ($owner_workflow_rows["Quality Assurance"] !== []): ?>
                            <?php foreach ($owner_workflow_rows["Quality Assurance"] as $invoice): ?>
                                <?php $stage = (string) $invoice["current_stage"]; ?>
                                <article class="log-entry">
                                    <span class="log-avatar avatar-lilac">#</span>
                                    <div class="log-copy">
                                        <div><strong>Invoice #<?= htmlspecialchars((string) $invoice["invoice_id"], ENT_QUOTES, "UTF-8") ?></strong><span class="log-action"><?= htmlspecialchars($stage, ENT_QUOTES, "UTF-8") ?></span></div>
                                        <p><?= htmlspecialchars((string) ($invoice["customer_name"] ?? "Customer unavailable"), ENT_QUOTES, "UTF-8") ?> · <?= htmlspecialchars((string) ($invoice["services"] ?? "—"), ENT_QUOTES, "UTF-8") ?> · Qty <?= htmlspecialchars((string) ($invoice["quantity"] ?? "—"), ENT_QUOTES, "UTF-8") ?></p>
                                        <small>Due <?= htmlspecialchars(ownerFormatDate($invoice["completion_date"] ?? ""), ENT_QUOTES, "UTF-8") ?></small>
                                    </div>
                                    <span class="log-category">Current</span>
                                </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="py-3 text-muted">No invoices are currently in Quality Assurance or ready to ship.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
            <p class="sample-note">Department timestamps are not stored, so this page shows current invoice locations rather than past activity.</p>
            <footer class="dashboard-footer">Made with care at little label <span aria-hidden="true">♡</span></footer>
        </main>
    </div>
</body>
</html>
