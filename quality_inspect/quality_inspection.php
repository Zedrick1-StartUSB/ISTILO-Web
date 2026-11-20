<?php
session_start();
require "../logout.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "qa") {
    header("Location: /ISTILO_SYSTEM/Login/html/login.php");
    exit();
}

require __DIR__ . DIRECTORY_SEPARATOR . "quality_inspection_backend.php";

$profileName = trim((string) ($_SESSION["fullname"] ?? ""));
if ($profileName === "") {
    $profileName = trim((string) ($_SESSION["username"] ?? "QA Account"));
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
    <title>Quality Assurance | ISTILO @ Nasugbu Enterprises</title>
    <link rel="stylesheet" href="../bootstrap_template/css/bootstrap.min.css">
    <link rel="stylesheet" href="../production/production.css?v=2">
</head>
<body>
    <div class="production-app">
        <aside class="production-sidebar">
            <a class="brand" href="quality_inspection.php">
                <span class="brand-stamp">I</span>
                <span>ISTILO<small>@ NASUGBU ENTERPRISES</small></span>
            </a>
            <p class="side-label">WORKSPACE</p>
            <nav class="production-nav" aria-label="Quality assurance workspace">
                <a class="nav-item active" href="#incoming" aria-current="page">Job Orders</a>
            </nav>
            <form class="production-logout" method="POST">
                <button class="btn btn-danger" name="logout" type="submit">Logout</button>
            </form>
            <div class="maker-profile">
                <span class="maker-avatar"><?= htmlspecialchars($profileInitials, ENT_QUOTES, "UTF-8") ?></span>
                <span><strong><?= htmlspecialchars($profileName, ENT_QUOTES, "UTF-8") ?></strong><small><?= $profileUsername !== "" ? "@" . htmlspecialchars($profileUsername, ENT_QUOTES, "UTF-8") . " · " : "" ?>QA account</small></span>
            </div>
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
                                    <th scope="col">Actions</th>
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
                                            <td>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <form method="post">
                                                        <button class="btn btn-success btn-sm" type="submit" name="complete" value="<?= htmlspecialchars((string) ($row["invoice_id"] ?? ""), ENT_QUOTES, "UTF-8") ?>">Complete</button>
                                                    </form>
                                                    <button
                                                        class="btn btn-outline-primary btn-sm"
                                                        type="button"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#qaSendWorkModal"
                                                        data-invoice="<?= htmlspecialchars((string) ($row["invoice_id"] ?? ""), ENT_QUOTES, "UTF-8") ?>"
                                                    >Send</button>
                                                </div>
                                            </td>
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

    <div class="modal fade" id="qaSendWorkModal" tabindex="-1" aria-labelledby="qaSendWorkModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <div>
                            <p class="overline mb-1">PASS IT ALONG</p>
                            <h2 class="modal-title h5 mb-0" id="qaSendWorkModalTitle">Send QA work</h2>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="invoice_id" id="qaSendInvoiceId" value="<?= htmlspecialchars($qa_send_modal_values["invoice_id"], ENT_QUOTES, "UTF-8") ?>">
                        <p class="mb-3">Invoice <strong id="qaSendInvoiceLabel"><?= htmlspecialchars($qa_send_modal_values["invoice_id"], ENT_QUOTES, "UTF-8") ?></strong></p>
                        <label class="form-label" for="qaSendDepartment">Send to department</label>
                        <select class="form-select" name="department" id="qaSendDepartment" required>
                            <option value="" <?= $qa_send_modal_values["department"] === "" ? "selected" : "" ?> disabled>Choose a department</option>
                            <option value="admin" <?= $qa_send_modal_values["department"] === "admin" ? "selected" : "" ?>>Admin</option>
                            <option value="layout" <?= $qa_send_modal_values["department"] === "layout" ? "selected" : "" ?>>Layout</option>
                            <option value="production" <?= $qa_send_modal_values["department"] === "production" ? "selected" : "" ?>>Production</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-success" type="submit" name="send_qa_work" value="1">Send</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../bootstrap_template/js/bootstrap.bundle.min.js"></script>
    <script src="quality_inspection.js?v=2"></script>
    <?php if ($qa_action_error !== "" && $qa_send_modal_values["invoice_id"] !== ""): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                var modal = document.getElementById("qaSendWorkModal");
                if (modal) {
                    bootstrap.Modal.getOrCreateInstance(modal).show();
                }
            });
        </script>
    <?php endif; ?>
</body>
</html>
