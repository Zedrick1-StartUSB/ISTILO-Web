<?php
session_start();
require "../logout.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "production") {
    header("Location: /ISTILO_SYSTEM/Login/html/login.php");
    exit();
}

require "production_backend.php";

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

            <p class="side-label">YOUR WORK</p>
            <nav class="production-nav" aria-label="Production workspace">
                <a class="nav-item active" href="#incoming" aria-current="page">Job Orders</a>
                <a class="nav-item" href="attendance.php"><span>◷</span> Attendance</a>
                <form method="POST">
                    <button class="btn btn-danger" name="logout">Logout</button>
                </form>
            </nav>

        </aside>

        <main class="production-main">
            <section class="screen-section" id="incoming" aria-labelledby="incoming-title">
            <header class="welcome-header">
                <div>
                    <p class="overline">PRODUCTION DESK · FROM LAYOUT</p>
                    <h1 id="incoming-title">Job Orders</h1>
                    <p class="welcome-subtitle">Invoices and files sent to Production by the Layout team.</p>
                </div>
                <div class="work-status"><span></span> Ready to make</div>
            </header>
            <?php if ($production_action_error !== ""): ?>
                <p class="alert alert-danger" role="alert"><?= htmlspecialchars($production_action_error, ENT_QUOTES, "UTF-8") ?></p>
            <?php elseif ($production_action_success !== ""): ?>
                <p class="alert alert-success" role="status"><?= htmlspecialchars($production_action_success, ENT_QUOTES, "UTF-8") ?></p>
            <?php endif; ?>

            <section class="card-surface production-invoice-panel mb-4" aria-labelledby="incoming-production-title">
                <div class="p-3 border-bottom">
                    <h2 class="h5 mb-0" id="incoming-production-title">Incoming from Layout</h2>
                </div>
                <div class="table-responsive">
                    <table class="table production-invoice-list mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Invoice ID</th>
                                <th scope="col">Customer</th>
                                <th scope="col">Service</th>
                                <th scope="col">Qty</th>
                                <th scope="col">File</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($production_incoming_error !== ""): ?>
                                <tr><td colspan="6" class="production-empty-state"><?= htmlspecialchars($production_incoming_error, ENT_QUOTES, "UTF-8") ?></td></tr>
                            <?php elseif (count($production_incoming_rows) > 0): ?>
                                <?php foreach ($production_incoming_rows as $row): ?>
                                    <?php
                                    $workFileName = basename((string) ($row["work_file_name"] ?? ""));
                                    $workFileExtension = strtolower(pathinfo($workFileName, PATHINFO_EXTENSION));
                                    $workFilePath = __DIR__ . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . $workFileName;
                                    $workFileUrl = $workFileName !== ""
                                        && in_array($workFileExtension, ["pdf", "jpg", "jpeg", "png"], true)
                                        && is_file($workFilePath)
                                            ? "uploads/" . rawurlencode($workFileName)
                                            : "";
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) ($row["invoice_id"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars((string) ($row["customer_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars((string) ($row["services"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars((string) ($row["quantity"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                        <td>
                                            <?php if ($workFileUrl !== ""): ?>
                                                <a class="production-file-link" href="<?= htmlspecialchars($workFileUrl, ENT_QUOTES, "UTF-8") ?>" target="_blank" rel="noopener">Open file</a>
                                            <?php else: ?>
                                                <span>No file</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="post">
                                                <button class="btn btn-success" type="submit" name="accept" value="<?= htmlspecialchars((string) ($row["invoice_id"] ?? ""), ENT_QUOTES, "UTF-8") ?>">Accept</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="production-empty-state">No incoming invoices have been sent to Production.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card-surface production-invoice-panel" aria-labelledby="production-work-title">
                <div class="p-3 border-bottom">
                    <h2 class="h5 mb-0" id="production-work-title">Production Work</h2>
                </div>
                <div class="table-responsive">
                    <table class="table production-invoice-list production-work-list mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Invoice ID</th>
                                <th scope="col">Customer</th>
                                <th scope="col">Service</th>
                                <th scope="col">Qty</th>
                                <th scope="col">File</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($production_working_error !== ""): ?>
                                <tr><td colspan="6" class="production-empty-state"><?= htmlspecialchars($production_working_error, ENT_QUOTES, "UTF-8") ?></td></tr>
                            <?php elseif (count($production_working_rows) > 0): ?>
                                <?php foreach ($production_working_rows as $row): ?>
                                    <?php
                                    $workFileName = basename((string) ($row["work_file_name"] ?? ""));
                                    $workFileExtension = strtolower(pathinfo($workFileName, PATHINFO_EXTENSION));
                                    $workFilePath = __DIR__ . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . $workFileName;
                                    $workFileUrl = $workFileName !== ""
                                        && in_array($workFileExtension, ["pdf", "jpg", "jpeg", "png"], true)
                                        && is_file($workFilePath)
                                            ? "uploads/" . rawurlencode($workFileName)
                                            : "";
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) ($row["invoice_id"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars((string) ($row["customer_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars((string) ($row["services"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars((string) ($row["quantity"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                        <td>
                                            <?php if ($workFileUrl !== ""): ?>
                                                <a class="production-file-link" href="<?= htmlspecialchars($workFileUrl, ENT_QUOTES, "UTF-8") ?>" target="_blank" rel="noopener">Open file</a>
                                            <?php else: ?>
                                                <span>No file</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="post" class="production-send-form">
                                                <input type="hidden" name="invoice_id" value="<?= htmlspecialchars((string) ($row["invoice_id"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                                                <label class="visually-hidden" for="send-department-<?= htmlspecialchars((string) ($row["invoice_id"] ?? ""), ENT_QUOTES, "UTF-8") ?>">Send invoice to department</label>
                                                <select class="form-select form-select-sm" id="send-department-<?= htmlspecialchars((string) ($row["invoice_id"] ?? ""), ENT_QUOTES, "UTF-8") ?>" name="department" required>
                                                    <option value="" selected disabled>Choose department</option>
                                                    <option value="admin">Admin</option>
                                                    <option value="layout">Layout</option>
                                                    <option value="qa">Quality Assurance</option>
                                                </select>
                                                <button class="btn btn-success btn-sm" type="submit" name="send_to_department" value="1">Send</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="production-empty-state">No production work has been accepted yet.</td></tr>
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
