<?php
session_start();
require "../logout.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "owner") {
    header("Location: /ISTILO_SYSTEM/Login/html/login.php");
    exit();
}

$owner_data_error = "";
$owner_history_logs = [];
try {
    require_once __DIR__ . DIRECTORY_SEPARATOR . "owner_backend.php";
    $owner_history_logs = ownerLoadHistoryLogs($conn);
} catch (mysqli_sql_exception $exception) {
    error_log("Unable to load Owner history logs: " . $exception->getMessage());
    $owner_data_error = "History logs could not be loaded. Check that the history_logs table has been created.";
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
        <?php $owner_active_page = "history"; require __DIR__ . DIRECTORY_SEPARATOR . "owner_sidebar.php"; ?>

        <main class="main-content">
            <header class="page-header">
                <div>
                    <p class="today-label">SHOP MANAGEMENT</p>
                    <h1>History logs</h1>
                    <p class="page-subtitle">Review invoice changes and the department status fields that were updated.</p>
                </div>
                <span class="subtle-badge header-badge">Invoice activity</span>
            </header>

            <?php if ($owner_data_error !== ""): ?>
                <p class="alert alert-danger" role="alert"><?= htmlspecialchars($owner_data_error, ENT_QUOTES, "UTF-8") ?></p>
            <?php endif; ?>

            <section class="panel page-panel history-panel" aria-labelledby="history-title">
                <div class="panel-heading page-panel-heading">
                    <div><h2 id="history-title">Invoice history</h2><p>Each row records an insert or update made to an invoice.</p></div>
                </div>

                <div class="history-table-wrap">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th scope="col">Invoice</th>
                                <th scope="col">Customer</th>
                                <th scope="col">Qty</th>
                                <th scope="col">Updated department</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($owner_history_logs === []): ?>
                                <tr><td class="history-empty" colspan="4">No history has been recorded yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($owner_history_logs as $log): ?>
                                    <tr>
                                        <td>#<?= htmlspecialchars((string) ($log["invoice_id"] ?? "—"), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars((string) ($log["customer_name"] ?? "—"), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars((string) ($log["qty"] ?? "—"), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars((string) ($log["updated_dept"] ?? "—"), ENT_QUOTES, "UTF-8") ?></td>
                                    </tr>
                                <?php endforeach; ?>
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
