<?php
session_start();
require "../logout.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "qa") {
    header("Location: /ISTILO_SYSTEM/Login/html/login.php");
    exit();
}

require __DIR__ . DIRECTORY_SEPARATOR . "quality_inspection_backend.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6f5ef">
    <title>Quality Assurance Desk</title>
    <link rel="stylesheet" href="../bootstrap_template/css/bootstrap.min.css">
    <link rel="stylesheet" href="../production/production.css">
</head>
<body>
    <div class="production-app">
        <aside class="production-sidebar">
            <p class="side-label">YOUR WORK</p>
            <nav class="production-nav" aria-label="Quality assurance workspace">
                <a class="nav-item active" href="#incoming" aria-current="page">Job Orders</a>
                <form method="POST">
                    <button class="btn btn-danger" name="logout" type="submit">Logout</button>
                </form>
            </nav>
        </aside>

        <main class="production-main">
            <section class="screen-section" id="incoming" aria-labelledby="qa-title">
                <header class="welcome-header">
                    <div>
                        <p class="overline">QUALITY ASSURANCE · FROM PRODUCTION</p>
                        <h1 id="qa-title">Quality Check Desk</h1>
                        <p class="welcome-subtitle">Review job orders sent by Production and manage accepted QA work.</p>
                    </div>
                    <div class="work-status"><span></span> Ready to inspect</div>
                </header>

                <?php if ($qa_action_error !== ""): ?>
                    <p class="alert alert-danger" role="alert"><?= htmlspecialchars($qa_action_error, ENT_QUOTES, "UTF-8") ?></p>
                <?php elseif ($qa_action_success !== ""): ?>
                    <p class="alert alert-success" role="status"><?= htmlspecialchars($qa_action_success, ENT_QUOTES, "UTF-8") ?></p>
                <?php endif; ?>

                <section class="card-surface production-invoice-panel mb-4" aria-labelledby="qa-incoming-title">
                    <div class="p-3 border-bottom">
                        <h2 class="h5 mb-0" id="qa-incoming-title">Incoming from Production</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table production-invoice-list qa-invoice-list qa-incoming-list mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Invoice ID</th>
                                    <th scope="col">Customer</th>
                                    <th scope="col">Service</th>
                                    <th scope="col">Qty</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($qa_incoming_error !== ""): ?>
                                    <tr><td colspan="5" class="production-empty-state"><?= htmlspecialchars($qa_incoming_error, ENT_QUOTES, "UTF-8") ?></td></tr>
                                <?php elseif (count($qa_incoming_rows) > 0): ?>
                                    <?php foreach ($qa_incoming_rows as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string) ($row["invoice_id"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                            <td><?= htmlspecialchars((string) ($row["customer_name"] ?? "Invoice details unavailable"), ENT_QUOTES, "UTF-8") ?></td>
                                            <td><?= htmlspecialchars((string) ($row["services"] ?? "N/A"), ENT_QUOTES, "UTF-8") ?></td>
                                            <td><?= htmlspecialchars((string) ($row["quantity"] ?? "N/A"), ENT_QUOTES, "UTF-8") ?></td>
                                            <td>
                                                <form method="post">
                                                    <button class="btn btn-success btn-sm" type="submit" name="accept" value="<?= htmlspecialchars((string) ($row["invoice_id"] ?? ""), ENT_QUOTES, "UTF-8") ?>">Accept</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="production-empty-state">No incoming invoices have been sent to Quality Assurance.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="card-surface production-invoice-panel" aria-labelledby="qa-work-title">
                    <div class="p-3 border-bottom">
                        <h2 class="h5 mb-0" id="qa-work-title">QA Work</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table production-invoice-list qa-invoice-list qa-work-list mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Invoice ID</th>
                                    <th scope="col">Customer</th>
                                    <th scope="col">Service</th>
                                    <th scope="col">Qty</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($qa_working_error !== ""): ?>
                                    <tr><td colspan="5" class="production-empty-state"><?= htmlspecialchars($qa_working_error, ENT_QUOTES, "UTF-8") ?></td></tr>
                                <?php elseif (count($qa_working_rows) > 0): ?>
                                    <?php foreach ($qa_working_rows as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string) ($row["invoice_id"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                            <td><?= htmlspecialchars((string) ($row["customer_name"] ?? "Invoice details unavailable"), ENT_QUOTES, "UTF-8") ?></td>
                                            <td><?= htmlspecialchars((string) ($row["services"] ?? "N/A"), ENT_QUOTES, "UTF-8") ?></td>
                                            <td><?= htmlspecialchars((string) ($row["quantity"] ?? "N/A"), ENT_QUOTES, "UTF-8") ?></td>
                                            <td><button class="btn btn-success btn-sm" type="button">Complete</button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="production-empty-state">No QA work has been accepted yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </section>
        </main>
    </div>
</body>
</html>
