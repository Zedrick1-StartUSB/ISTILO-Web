<?php
session_start();

require "../logout.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "layout") {
    header("Location: /ISTILO_SYSTEM/Login/html/login.php");
    exit();
}

require "layout_backend.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f8f5ef">
    <title>Work | Thread &amp; Form</title>
    <link rel="stylesheet" href="../bootstrap_template/css/bootstrap.min.css">
    <link rel="stylesheet" href="layout_artist.css">
</head>
<body>
    <div class="studio-shell work-page-shell">
        <aside class="studio-sidebar">
            <a class="studio-brand" href="layout_artist.php">
                <span class="brand-mark">t.</span>
                <span>thread &amp; form<small>LAYOUT STUDIO</small></span>
            </a>

            <p class="menu-label">WORKSPACE</p>
            <nav class="studio-nav" aria-label="Layout workspace">
                <a class="studio-nav-link" href="layout_artist.php"><span aria-hidden="true">⌂</span> Dashboard</a>
                <a class="studio-nav-link selected" href="layout_work.php" aria-current="page"><span aria-hidden="true">✓</span> Work</a>
                <form method="POST">
                    <button class="btn btn-danger btn-sm w-100" name="logout" type="submit">Logout</button>
                </form>
            </nav>

            <div class="artist-profile">
                <span class="artist-avatar">LA</span>
                <span><strong>Layout Artist</strong><small>Layout studio</small></span>
            </div>
        </aside>

        <main class="studio-main work-page-main">
            <section class="screen-section" id="work" aria-labelledby="work-title">
                <header class="studio-header">
                    <div>
                        <p class="date-label">LAYOUT STUDIO · JOB ORDERS</p>
                        <h1 id="work-title">Work</h1>
                        <p class="header-subtitle">Review new work and manage your jobs in progress.</p>
                    </div>
                </header>

                <?php if ($layout_action_error !== ""): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($layout_action_error) ?>
                    </div>
                <?php elseif (isset($_GET["accepted"]) && $_GET["accepted"] === "1"): ?>
                    <div class="alert alert-success" role="status">
                        Work accepted and moved to your working invoices.
                    </div>
                <?php elseif (isset($_GET["sent"]) && $_GET["sent"] === "1"): ?>
                    <div class="alert alert-success" role="status">
                        The file was sent successfully.
                    </div>
                <?php endif; ?>

                <section class="panel dashboard-panel invoice-panel">
                    <div class="section-heading">
                        <div>
                            <p class="section-kicker">FROM ADMIN</p>
                            <h2 id="incoming-invoices-title">Incoming invoices</h2>
                            <p>Invoices ready for the layout team.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table invoice-list mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Invoice ID</th>
                                    <th scope="col">Customer</th>
                                    <th scope="col">Service</th>
                                    <th scope="col">Qty</th>
                                    <th scope="col">Due date</th>
                                    <th scope="col">ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($query_incoming_invoices !== false && $query_incoming_invoices->num_rows > 0): ?>
                                    <?php while ($row = $query_incoming_invoices->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row["invoice_id"] ?? '') ?></td>
                                            <td><?= htmlspecialchars($row["customer_name"] ?? '') ?></td>
                                            <td><?= htmlspecialchars($row["services"] ?? '') ?></td>
                                            <td><?= htmlspecialchars($row["quantity"] ?? '') ?></td>
                                            <td><?= htmlspecialchars($row["completion_date"] ?? '') ?></td>
                                            <td>
                                                <form method="post">
                                                    <button class="btn btn-success" type="submit" name="accept" value="<?= htmlspecialchars($row["layout_id"]) ?>">Accept Work</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php elseif ($query_incoming_invoices !== false): ?>
                                    <tr>
                                        <td colspan="6">No incoming invoices found.</td>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6">Incoming invoices could not be loaded.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <br><br>

                <section class="panel dashboard-panel invoice-panel" aria-labelledby="working-invoices-title">
                    <div class="section-heading">
                        <div>
                            <p class="section-kicker">LAYOUT TEAM</p>
                            <h2 id="working-invoices-title">Working Invoices</h2>
                            <p>Invoices currently being handled by the layout team.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table invoice-list work-invoice-list mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Invoice</th>
                                    <th scope="col">Customer</th>
                                    <th scope="col">Service</th>
                                    <th scope="col">Qty</th>
                                    <th scope="col">Due date</th>
                                    <th scope="col">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($query_working_invoices !== false && $query_working_invoices->num_rows > 0): ?>
                                    <?php while ($row = $query_working_invoices->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row["invoice_id"] ?? '') ?></td>
                                            <td><?= htmlspecialchars($row["customer_name"] ?? '') ?></td>
                                            <td><?= htmlspecialchars($row["services"] ?? '') ?></td>
                                            <td><?= htmlspecialchars($row["quantity"] ?? '') ?></td>
                                            <td><?= htmlspecialchars($row["completion_date"] ?? '') ?></td>
                                            <td>
                                                <?php
                                                $referenceFileName = basename((string) ($row["file"] ?? ""));
                                                $referenceFilePath = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR
                                                    . "admin" . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . $referenceFileName;
                                                $referenceFileExtension = strtolower(pathinfo($referenceFileName, PATHINFO_EXTENSION));
                                                $referenceFileUrl = $referenceFileName !== ""
                                                    && in_array($referenceFileExtension, ["jpg", "jpeg", "png"], true)
                                                    && is_file($referenceFilePath)
                                                        ? "../admin/uploads/" . rawurlencode($referenceFileName)
                                                        : "";
                                                ?>
                                                <div class="work-invoice-actions">
                                                    <button
                                                        class="btn btn-view"
                                                        type="button"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#viewWorkModal"
                                                        data-invoice="<?= htmlspecialchars($row["invoice_id"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                                                        data-customer="<?= htmlspecialchars($row["customer_name"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                                                        data-contact="<?= htmlspecialchars($row["contact_number"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                                                        data-address="<?= htmlspecialchars($row["customer_address"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                                                        data-landmarks="<?= htmlspecialchars($row["landmarks"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                                                        data-service="<?= htmlspecialchars($row["services"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                                                        data-quantity="<?= htmlspecialchars($row["quantity"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                                                        data-price="<?= htmlspecialchars($row["price"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                                                        data-due="<?= htmlspecialchars($row["completion_date"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                                                        data-notes="<?= htmlspecialchars($row["notes"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                                                        data-file="<?= htmlspecialchars($referenceFileUrl, ENT_QUOTES, "UTF-8") ?>"
                                                    >View</button>
                                                    <button
                                                        class="btn btn-send"
                                                        type="button"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#sendWorkModal"
                                                        data-invoice="<?= htmlspecialchars($row["invoice_id"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                                                    >Send</button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php elseif ($query_working_invoices !== false): ?>
                                    <tr>
                                        <td colspan="6">No working invoices found.</td>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6">Working invoices could not be loaded.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </section>
        </main>
    </div>

    <div class="modal fade" id="viewWorkModal" tabindex="-1" aria-labelledby="viewWorkModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content work-modal">
                <div class="modal-header">
                    <div>
                        <p class="section-kicker mb-1">WORKING INVOICE</p>
                        <h2 class="modal-title fs-5" id="viewWorkModalTitle">Invoice details</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="work-detail-grid">
                        <div><span>Invoice ID: </span><strong data-work-detail="invoice"></strong></div>
                        <div><span>Customer Name: </span><strong data-work-detail="customer"></strong></div>
                        <div><span>Contact: </span><strong data-work-detail="contact"></strong></div>
                        <div><span>Service: </span><strong data-work-detail="service"></strong></div>
                        <div><span>Quantity: </span><strong data-work-detail="quantity"></strong></div>
                        <div><span>Total: </span><strong data-work-detail="price"></strong></div>
                        <div><span>Due date: </span><strong data-work-detail="due"></strong></div>
                        <div class="work-detail-wide"><span>Address: </span><strong data-work-detail="address"></strong></div>
                        <div class="work-detail-wide"><span>Landmarks: </span><strong data-work-detail="landmarks"></strong></div>
                        <div class="work-detail-wide"><span>Notes: </span><strong data-work-detail="notes"></strong></div>
                    </div>
                    <section class="work-reference-section" id="workReferenceSection" hidden>
                        <h4>Passing Receipt</h4>
                        <a id="workReferenceLink" class="work-reference-preview" target="_blank" rel="noopener">
                            <img id="workReferenceImage" alt="Invoice reference image">
                        </a>
                    </section>
                    <p class="work-reference-empty" id="workReferenceEmpty" hidden>No reference image was attached to this invoice.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-view" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="sendWorkModal" tabindex="-1" aria-labelledby="sendWorkModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content work-modal">
                <form method="post" enctype="multipart/form-data">
                    <div class="modal-header">
                        <div>
                            <p class="section-kicker mb-1">PASS IT ALONG</p>
                            <h2 class="modal-title fs-5" id="sendWorkModalTitle">Send working file</h2>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="invoice_id" id="sendWorkInvoiceId">
                        <p class="send-work-invoice">Invoice <strong id="sendWorkInvoiceLabel"></strong></p>
                        <label class="form-label" for="sendWorkDepartment">Send to department</label>
                        <select class="form-select" name="department" id="sendWorkDepartment" required>
                            <option value="" selected disabled>Choose a department</option>
                            <option value="admin">Admin</option>
                            <option value="production">Production</option>
                            <option value="quality assurance">Quality Assurance</option>
                        </select>
                        <label class="form-label mt-3" for="sendWorkFile">File to send</label>
                        <input class="form-control" type="file" name="work_file" id="sendWorkFile" accept=".pdf,.png,.jpg,.jpeg,application/pdf,image/png,image/jpeg" required>
                        <p class="work-upload-help">PDF, PNG, or JPEG · Maximum 10 MB</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-view" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-send" type="submit" name="send_work" value="1">Send file</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../bootstrap_template/js/bootstrap.bundle.min.js"></script>
    <script src="layout_work.js"></script>
</body>
</html>
