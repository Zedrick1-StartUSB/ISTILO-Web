<?php
session_start();
require "../logout.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "owner") {
    header("Location: /ISTILO_SYSTEM/Login/html/login.php");
    exit();
}

$owner_data_error = "";
$owner_invoice_table_rows = [];
$owner_materials = [];
$owner_users = [];
$owner_completed_project_count = 0;
try {
    require_once __DIR__ . DIRECTORY_SEPARATOR . "owner_backend.php";
    $owner_invoice_table_rows = ownerLoadInvoiceTableRows($conn);
    $owner_completed_project_count = ownerCountCompletedProjects($conn);
    $owner_materials = ownerLoadMaterials($conn);
    $owner_users = ownerLoadUsers($conn);
} catch (mysqli_sql_exception $exception) {
    error_log("Unable to load Owner dashboard data: " . $exception->getMessage());
    $owner_data_error = "Dashboard data could not be loaded.";
}
$owner_material_metrics = ownerBuildMaterialMetrics($owner_materials);
$owner_user_metrics = ownerBuildUserMetrics($owner_users);
$owner_name = trim((string) ($_SESSION["fullname"] ?? $_SESSION["username"] ?? "Owner"));
$owner_first_name = preg_split('/\s+/', $owner_name)[0] ?? "Owner";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f8f5ef">
    <title>Dashboard | little label</title>
    <link rel="stylesheet" href="../bootstrap_template/css/bootstrap.min.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="dashboard-layout">
        <?php $owner_active_page = "dashboard"; require __DIR__ . DIRECTORY_SEPARATOR . "owner_sidebar.php"; ?>

        <main class="main-content" id="overview">
            <header class="topbar">
                <div>
                    <p class="today-label"><?= htmlspecialchars(strtoupper(date("l, F j, Y")), ENT_QUOTES, "UTF-8") ?></p>
                    <h1>Hey, <?= htmlspecialchars($owner_first_name, ENT_QUOTES, "UTF-8") ?> <span aria-hidden="true">☀</span></h1>
                    <p class="page-subtitle">Current business totals from your invoices and work queues.</p>
                </div>
            </header>

            <?php if ($owner_data_error !== ""): ?>
                <p class="alert alert-danger" role="alert"><?= htmlspecialchars($owner_data_error, ENT_QUOTES, "UTF-8") ?></p>
            <?php endif; ?>

            <section class="stats-grid" aria-label="Shop overview">
                <article class="stat-card stat-card-peach">
                    <div class="stat-heading"><span>Total materials</span><span class="stat-icon">▧</span></div>
                    <p class="stat-value"><?= (int) $owner_material_metrics["material_count"] ?></p>
                    <p class="stat-foot"><span>Material types in inventory</span></p>
                    <div class="stat-decoration decoration-circle"></div>
                </article>
                <article class="stat-card stat-card-lilac">
                    <div class="stat-heading"><span>Job orders</span><span class="stat-icon">▤</span></div>
                    <p class="stat-value"><?= count($owner_invoice_table_rows) ?></p>
                    <p class="stat-foot"><span>Records in the invoices table</span></p>
                    <div class="stat-decoration decoration-star">✳</div>
                </article>
                <article class="stat-card stat-card-sage">
                    <div class="stat-heading"><span>Completed Projects</span><span class="stat-icon">♧</span></div>
                    <p class="stat-value"><?= $owner_completed_project_count ?></p>
                    <p class="stat-foot"><span class="stat-neutral">Passed quality inspection</span></p>
                    <div class="stat-decoration decoration-ring"></div>
                </article>
                <article class="stat-card stat-card-yellow">
                    <div class="stat-heading"><span>Total employees</span><span class="stat-icon">♡</span></div>
                    <p class="stat-value"><?= (int) $owner_user_metrics["team_members"] ?></p>
                    <p class="stat-foot"><span>Registered user accounts</span></p>
                    <div class="stat-decoration decoration-heart">♡</div>
                </article>
            </section>

            <section class="content-grid">
                <article class="panel orders-panel" id="current-invoices">
                    <div class="panel-heading orders-heading">
                        <div>
                            <h2>Current invoices</h2>
                            <p>Invoice details recorded in the system.</p>
                        </div>
                        <a class="view-all-link" href="orders.php">View all orders <span aria-hidden="true">→</span></a>
                    </div>
                    <div class="table-responsive">
                        <table class="table orders-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>INVOICE ID</th>
                                    <th>NAME</th>
                                    <th>SERVICES</th>
                                    <th>COMPLETION DATE</th>
                                    <th>PRICE</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($owner_invoice_table_rows !== []): ?>
                                    <?php foreach ($owner_invoice_table_rows as $invoice): ?>
                                        <tr>
                                            <td><strong>#<?= htmlspecialchars((string) ($invoice["invoice_id"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong></td>
                                            <td><?= htmlspecialchars((string) ($invoice["customer_name"] ?? "—"), ENT_QUOTES, "UTF-8") ?></td>
                                            <td><?= htmlspecialchars((string) ($invoice["services"] ?? "—"), ENT_QUOTES, "UTF-8") ?></td>
                                            <td><?= htmlspecialchars(ownerFormatDate($invoice["completion_date"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                            <td><strong><?= htmlspecialchars(ownerFormatMoney($invoice["price"] ?? null), ENT_QUOTES, "UTF-8") ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5">No invoice records are available.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </article>
            </section>
        </main>
    </div>
</body>
</html>
