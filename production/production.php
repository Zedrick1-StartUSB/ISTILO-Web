<?php
session_start();
require "../logout.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "production") {
    header("Location: /ISTILO_SYSTEM/Login/html/login.php");
    exit();
}

require "production_backend.php";

$profileName = trim((string) ($_SESSION["fullname"] ?? ""));
if ($profileName === "") {
    $profileName = trim((string) ($_SESSION["username"] ?? "Production Account"));
}
$profileUsername = trim((string) ($_SESSION["username"] ?? ""));
$profileInitials = strtoupper(substr($profileName, 0, 1));
if (preg_match('/\s+(\S)/', $profileName, $profileMatches) === 1) {
    $profileInitials .= strtoupper($profileMatches[1]);
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
    <link rel="stylesheet" href="production.css?v=2">
</head>
<body>
    <div class="production-app">
        <aside class="production-sidebar">
            <a class="brand" href="production.php">
                <span class="brand-stamp">I</span>
                <span>ISTILO<small>@ NASUGBU ENTERPRISES</small></span>
            </a>
            <p class="side-label">WORKSPACE</p>
            <nav class="production-nav" aria-label="Production workspace">
                <a class="nav-item active" href="#incoming" aria-current="page">Job Orders</a>
                <a class="nav-item" href="attendance.php"><span>◷</span> Attendance</a>
            </nav>
            <form class="production-logout" method="POST">
                <button class="btn btn-danger" name="logout" type="submit">Logout</button>
            </form>
            <div class="maker-profile">
                <span class="maker-avatar"><?= htmlspecialchars($profileInitials, ENT_QUOTES, "UTF-8") ?></span>
                <span><strong><?= htmlspecialchars($profileName, ENT_QUOTES, "UTF-8") ?></strong><small><?= $profileUsername !== "" ? "@" . htmlspecialchars($profileUsername, ENT_QUOTES, "UTF-8") . " · " : "" ?>Production account</small></span>
            </div>
        </aside>

        <main class="production-main">
            <section class="screen-section" id="incoming" aria-labelledby="incoming-title">
            <header class="welcome-header">
                <div>
                    <p class="overline">PRODUCTION DESK · INCOMING</p>
                    <h1 id="incoming-title">Job Orders</h1>
                    <p class="welcome-subtitle">Invoices sent to Production by Admin or Layout.</p>
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
                    <h2 class="h5 mb-0" id="incoming-production-title">Incoming invoices</h2>
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
                                        <td><?= htmlspecialchars((string) ($row["queued_invoice_id"] ?? $row["invoice_id"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars((string) ($row["customer_name"] ?? "Invoice details unavailable"), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars((string) ($row["services"] ?? "N/A"), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars((string) ($row["quantity"] ?? "N/A"), ENT_QUOTES, "UTF-8") ?></td>
                                        <td>
                                            <?php if ($workFileUrl !== ""): ?>
                                                <a class="production-file-link" href="<?= htmlspecialchars($workFileUrl, ENT_QUOTES, "UTF-8") ?>" target="_blank" rel="noopener">Open file</a>
                                            <?php else: ?>
                                                <span>No file</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="post">
                                                <button class="btn btn-success" type="submit" name="accept" value="<?= htmlspecialchars((string) ($row["queued_invoice_id"] ?? $row["invoice_id"] ?? ""), ENT_QUOTES, "UTF-8") ?>">Accept</button>
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
                                            <button
                                                class="btn btn-success btn-sm"
                                                type="button"
                                                data-bs-toggle="modal"
                                                data-bs-target="#productionSendWorkModal"
                                                data-invoice="<?= htmlspecialchars((string) ($row["invoice_id"] ?? ""), ENT_QUOTES, "UTF-8") ?>"
                                            >Send</button>
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
    <div class="modal fade" id="productionSendWorkModal" tabindex="-1" aria-labelledby="productionSendWorkModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <div>
                            <p class="overline mb-1">PASS IT ALONG</p>
                            <h2 class="modal-title h5 mb-0" id="productionSendWorkModalTitle">Send Production work</h2>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input
                            type="hidden"
                            name="invoice_id"
                            id="productionSendInvoiceId"
                            value="<?= htmlspecialchars($production_send_modal_values["invoice_id"], ENT_QUOTES, "UTF-8") ?>"
                        >
                        <p class="mb-3">Invoice <strong id="productionSendInvoiceLabel"><?= htmlspecialchars($production_send_modal_values["invoice_id"], ENT_QUOTES, "UTF-8") ?></strong></p>
                        <label class="form-label" for="productionSendDepartment">Send to department</label>
                        <select class="form-select" name="department" id="productionSendDepartment" required>
                            <option value="" <?= $production_send_modal_values["department"] === "" ? "selected" : "" ?> disabled>Choose a department</option>
                            <option value="admin" <?= $production_send_modal_values["department"] === "admin" ? "selected" : "" ?>>Admin</option>
                            <option value="layout" <?= $production_send_modal_values["department"] === "layout" ? "selected" : "" ?>>Layout</option>
                            <option value="qa" <?= $production_send_modal_values["department"] === "qa" ? "selected" : "" ?>>Quality Assurance</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-success" type="submit" name="send_to_department" value="1">Send</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="../bootstrap_template/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var sendModal = document.getElementById("productionSendWorkModal");
            var departmentSelect = document.getElementById("productionSendDepartment");
            sendModal.addEventListener("show.bs.modal", function (event) {
                var trigger = event.relatedTarget;
                if (!trigger) {
                    return;
                }

                var invoiceId = trigger.dataset.invoice || "";
                document.getElementById("productionSendInvoiceId").value = invoiceId;
                document.getElementById("productionSendInvoiceLabel").textContent = invoiceId;
                departmentSelect.value = "";
            });
        });
    </script>
    <?php if ($production_action_error !== "" && $production_send_modal_values["invoice_id"] !== ""): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                var modal = document.getElementById("productionSendWorkModal");
                if (modal) {
                    bootstrap.Modal.getOrCreateInstance(modal).show();
                }
            });
        </script>
    <?php endif; ?>
</body>
</html>
