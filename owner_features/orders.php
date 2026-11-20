<?php
session_start();
require "../logout.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "owner") {
    header("Location: /ISTILO_SYSTEM/Login/html/login.php");
    exit();
}

$owner_data_error = "";
$owner_orders = [];
try {
    require_once __DIR__ . DIRECTORY_SEPARATOR . "owner_backend.php";
    $owner_orders = ownerLoadInvoices($conn);
} catch (mysqli_sql_exception $exception) {
    error_log("Unable to load Owner orders: " . $exception->getMessage());
    $owner_data_error = "Order data could not be loaded.";
}
$owner_invoice_metrics = ownerBuildInvoiceMetrics($owner_orders);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f8f5ef">
    <title>Orders | little label</title>
    <link rel="stylesheet" href="../bootstrap_template/css/bootstrap.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="orders.css">
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <a class="brand" href="dashboard.php"><span class="brand-mark">l.</span><span>little label</span></a>
            <p class="nav-caption">MENU</p>
            <nav class="side-nav" aria-label="Main navigation">
                <a class="side-link" href="dashboard.php">Dashboard</a>
                <a class="side-link active" href="orders.php" aria-current="page">Orders</a>
                <a class="side-link" href="materials.php">Materials</a>
                <a class="side-link" href="users.php">Users</a>
                <a class="side-link" href="history_logs.php">History Logs</a>
                <form method="POST">
                    <button class="btn btn-danger" name="logout">Logout</button>
                </form>
            </nav>
        </aside>

        <main class="main-content">
            <header class="page-header">
                <div>
                    <p class="today-label">SHOP MANAGEMENT</p>
                    <h1>Orders</h1>
                    <p class="page-subtitle">Keep track of what customers are waiting for.</p>
                </div>
                <span class="subtle-badge">Live order records</span>
            </header>

            <?php if ($owner_data_error !== ""): ?>
                <p class="alert alert-danger" role="alert"><?= htmlspecialchars($owner_data_error, ENT_QUOTES, "UTF-8") ?></p>
            <?php endif; ?>

            <section class="page-stats" aria-label="Order summary">
                <article class="page-stat"><span>All invoices</span><strong><?= (int) $owner_invoice_metrics["invoice_count"] ?></strong><small>Across the workflow</small></article>
                <article class="page-stat"><span>In Admin</span><strong><?= (int) $owner_invoice_metrics["stages"]["Admin"] ?></strong><small>Waiting for dispatch</small></article>
                <article class="page-stat"><span>In Production</span><strong><?= (int) $owner_invoice_metrics["stages"]["Production"] ?></strong><small>Incoming or in progress</small></article>
                <article class="page-stat"><span>Ready to ship</span><strong><?= (int) $owner_invoice_metrics["stages"]["Ready to ship"] ?></strong><small>Completed QA</small></article>
            </section>

            <section class="panel page-panel" id="orders-table">
                <div class="panel-heading page-panel-heading">
                    <div><h2>All invoices</h2><p>Customer orders and their current workflow stage</p></div>
                    <span class="subtle-badge"><?= (int) $owner_invoice_metrics["invoice_count"] ?> records</span>
                </div>
                <div class="table-responsive">
                    <table class="table data-table align-middle mb-0">
                        <thead><tr><th>INVOICE</th><th>CUSTOMER</th><th>DUE DATE</th><th>SERVICE</th><th>QTY</th><th>TOTAL</th><th>STATUS</th></tr></thead>
                        <tbody>
                            <?php if ($owner_orders !== []): ?>
                                <?php foreach ($owner_orders as $order): ?>
                                    <?php
                                    $stage = (string) ($order["current_stage"] ?? "No active work");
                                    $statusClass = ownerOrderStatusClass($stage);
                                    ?>
                                    <tr>
                                        <td><strong>#<?= htmlspecialchars((string) ($order["invoice_id"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong></td>
                                        <td><?= htmlspecialchars((string) ($order["customer_name"] ?? "—"), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars(ownerFormatDate($order["completion_date"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars((string) ($order["services"] ?? "—"), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars((string) ($order["quantity"] ?? "—"), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><strong><?= htmlspecialchars(ownerFormatMoney($order["price"] ?? null), ENT_QUOTES, "UTF-8") ?></strong></td>
                                        <td><span class="status-pill <?= htmlspecialchars($statusClass, ENT_QUOTES, "UTF-8") ?>"><?= htmlspecialchars($stage, ENT_QUOTES, "UTF-8") ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7">No invoice records are available.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <footer class="dashboard-footer">Made with care at little label <span aria-hidden="true">♡</span></footer>
        </main>
    </div>
</body>
</html>
