<?php
session_start();
require "../logout.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "production") {
    header("Location: /ISTILO_SYSTEM/Login/html/login.php");
    exit();
}

require "../dbconn.php";

$production_error = "";
$production_invoices = false;

try {
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

    $production_invoices = $conn->query(
        "SELECT handoffs.`handoff_id`, handoffs.`invoice_id`, handoffs.`file_path`,
            invoices.`customer_name`, invoices.`services`, invoices.`quantity`,
            invoices.`price`, invoices.`completion_date`
        FROM `layout_artist_handoffs` AS handoffs
        INNER JOIN `admin_invoices` AS invoices
            ON invoices.`invoice_id` = handoffs.`invoice_id`
        WHERE handoffs.`department` = 'production'
        ORDER BY handoffs.`sent_at` DESC"
    );

} catch (mysqli_sql_exception $exception) {
    error_log("Production incoming invoice listing failed: " . $exception->getMessage());
    $production_error = "Incoming invoices could not be loaded. Please refresh the page.";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6f5ef">
    <title>Production Desk</title>
    <link rel="stylesheet" href="../bootstrap_template/css/bootstrap.min.css">
    <link rel="stylesheet" href="production.css">
</head>
<body>
    <div class="production-app">
        <aside class="production-sidebar">
            <a class="brand" href="#incoming"><span class="brand-stamp">p.</span><span>made &amp; stitched<small>PRODUCTION DESK</small></span></a>

            <p class="side-label">YOUR WORK</p>
            <nav class="production-nav" aria-label="Production workspace">
                <a class="nav-item active" href="#incoming" aria-current="page"><span>▤</span> Incoming invoices</a>
                <a class="nav-item" href="attendance.php"><span>◷</span> Attendance</a>
                <form method="POST">
                    <button class="btn btn-danger" name="logout">Logout</button>
                </form>
            </nav>

            <div class="sidebar-note">
                <span>✿</span>
                <p><strong>One stitch at a time.</strong><br>You're making good things happen.</p>
            </div>

            <div class="maker-profile">
                <span class="maker-avatar">MR</span>
                <span><strong>Morgan Reed</strong><small>Production maker</small></span>
            </div>
        </aside>

        <main class="production-main">
            <section class="screen-section" id="incoming" aria-labelledby="incoming-title">
            <header class="welcome-header">
                <div>
                    <p class="overline">PRODUCTION DESK · FROM LAYOUT</p>
                    <h1 id="incoming-title">Incoming invoices</h1>
                    <p class="welcome-subtitle">Invoices and files sent to Production by the Layout team.</p>
                </div>
                <div class="work-status"><span></span> Ready to make</div>
            </header>

            <?php if ($production_error !== ""): ?>
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($production_error) ?></div>
            <?php endif; ?>

            <section class="card-surface production-invoice-panel" aria-label="Incoming invoices from Layout">
                <div class="table-responsive">
                    <table class="table production-invoice-list mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Invoice ID</th>
                                <th scope="col">Customer</th>
                                <th scope="col">Service</th>
                                <th scope="col">Qty</th>
                                <th scope="col">Total</th>
                                <th scope="col">Due date</th>
                                <th scope="col">File</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($production_invoices !== false && $production_invoices->num_rows > 0): ?>
                                <?php while ($row = $production_invoices->fetch_assoc()): ?>
                                    <?php
                                    $fileName = basename((string) $row["file_path"]);
                                    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                                    $fileUrl = in_array($fileExtension, ["pdf", "jpg", "jpeg", "png"], true)
                                        ? "../layout/handoff_uploads/" . rawurlencode($fileName)
                                        : "";
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row["invoice_id"] ?? "") ?></td>
                                        <td><?= htmlspecialchars($row["customer_name"] ?? "") ?></td>
                                        <td><?= htmlspecialchars($row["services"] ?? "") ?></td>
                                        <td><?= htmlspecialchars($row["quantity"] ?? "") ?></td>
                                        <td><?= htmlspecialchars($row["price"] ?? "") ?></td>
                                        <td><?= htmlspecialchars($row["completion_date"] ?? "") ?></td>
                                        <td>
                                            <?php if ($fileUrl !== ""): ?>
                                                <a class="production-file-link" href="<?= htmlspecialchars($fileUrl, ENT_QUOTES, "UTF-8") ?>" target="_blank" rel="noopener">View file</a>
                                            <?php else: ?>
                                                <span class="text-muted">Unavailable</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php elseif ($production_invoices !== false): ?>
                                <tr><td colspan="7" class="production-empty-state">No incoming invoices have been sent to Production.</td></tr>
                            <?php else: ?>
                                <tr><td colspan="7" class="production-empty-state">Incoming invoices could not be loaded.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            </section>

            <footer class="page-footer">Made by hand, with care <span aria-hidden="true">♡</span></footer>
        </main>
    </div>
</body>
</html>
