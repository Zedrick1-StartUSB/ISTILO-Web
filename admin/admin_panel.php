<?php
session_start();
require "../logout.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: /ISTILO_SYSTEM/Login/html/login.php");
    exit();
}

require "admin_backend.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f8f5ef">
    <title>Admin Panel | Istilo@Nasugbu Enterprises</title>
    <link rel="stylesheet" href="../bootstrap_template/css/bootstrap.min.css">
    <link rel="stylesheet" href="admin_panel.css">
    <script src="admin_panel.js" defer></script>
</head>
<body>
    <div class="admin-app">
        <header class="topbar">
            <a class="brand" href="#create-invoice">Istilo@Nasugbu Enterprises</a>
            <span class="workspace-label"><span></span> ADMIN WORKSPACE</span>
            <form method="POST">
                <button class="btn btn-danger" name="logout">Logout</button>
            </form>
        </header>

        <main class="page-container">
            <header class="page-heading">
                <div>
                    <p class="eyebrow">ORDER MANAGEMENT</p>
                    <h1>Invoices</h1>
                    <p class="heading-copy">Create a new job order invoice or check one you made earlier.</p>
                </div>
            </header>
            <?php if ($invoice_action_error !== ""): ?>
                <p class="invoice-message invoice-message-error" role="alert"><?= htmlspecialchars($invoice_action_error, ENT_QUOTES, "UTF-8") ?></p>
            <?php elseif ($invoice_action_success !== ""): ?>
                <p class="invoice-message invoice-message-success" role="status"><?= htmlspecialchars($invoice_action_success, ENT_QUOTES, "UTF-8") ?></p>
            <?php endif; ?>
            <div class="view-switch" role="tablist" aria-label="Invoice pages">
                <input class="view-radio" type="radio" name="invoice-view" id="view-create" <?= $show_invoice_logs ? "" : "checked" ?>>
                <input class="view-radio" type="radio" name="invoice-view" id="view-logs" <?= $show_invoice_logs ? "checked" : "" ?>>
                <div class="view-buttons">
                    <label class="view-button" for="view-create" role="tab">＋ &nbsp;Create invoice</label>
                    <label class="view-button" for="view-logs" role="tab">▤ &nbsp;Invoice logs</label>
                </div>

                <div class="view-panels">
                    <section class="view-panel create-panel" id="create-invoice" role="tabpanel" aria-labelledby="view-create">
                        <div class="form-intro">
                            <span class="intro-icon">✎</span>
                            <div>
                                <p class="panel-kicker">NEW JOB ORDER</p>
                                <h2>Create invoice</h2>
                                <p>Enter the customer and job order details below.</p>
                            </div>
                        </div>

                        <form class="invoice-form" action="" method="POST" enctype="multipart/form-data">
                            <?php if (!empty($invoice_error)): ?>
                                <p class="invoice-message invoice-message-error" role="alert"><?= htmlspecialchars($invoice_error, ENT_QUOTES, "UTF-8") ?></p>
                            <?php elseif (!empty($invoice_success)): ?>
                                <p class="invoice-message invoice-message-success" role="status"><?= htmlspecialchars($invoice_success, ENT_QUOTES, "UTF-8") ?></p>
                            <?php endif; ?>
                            <section class="form-card" aria-labelledby="customer-heading">
                                <div class="card-heading">
                                    <span class="step-number">01</span>
                                    <div>
                                        <h3 id="customer-heading">Customer information</h3>
                                        <p>Who is this job order for?</p>
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="customer-name" class="form-label">Customer name</label>
                                        <input type="text" class="form-control" id="customer-name" name="customer_name" placeholder="Enter customer name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="customer-number" class="form-label">Contact number</label>
                                        <input type="tel" class="form-control" id="customer-number" name="customer_number" placeholder="09XX XXX XXXX" required>
                                    </div>
                                    <div class="col-md-7">
                                        <label for="shipping-address" class="form-label">Address</label>
                                        <input type="text" class="form-control" id="shipping-address" name="shipping_address"
                                            placeholder="House number, street, barangay" required>
                                    </div>
                                    <div class="col-md-5">
                                        <label for="landmarks" class="form-label">Nearby landmark <span class="optional">(optional)</span></label>
                                        <input type="text" class="form-control" id="landmarks" name="landmarks" placeholder="e.g. Near the town plaza">
                                    </div>
                                </div>
                            </section>

                            <section class="form-card" aria-labelledby="job-heading">
                                <div class="card-heading">
                                    <span class="step-number">02</span>
                                    <div>
                                        <h3 id="job-heading">Job order details</h3>
                                        <p>Add the requested item and quantity.</p>
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label for="product-name" class="form-label">Service/s Offered | Accepted </label>
                                        <input type="text" class="form-control" id="product-name" name="product-service" placeholder="What are we making?" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="quantity" class="form-label">Quantity</label>
                                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" step="1" placeholder="Enter quantity" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="unit-price" class="form-label">Total Amount(₱)</label>
                                        <input type="number" class="form-control" id="total-price" name="total-price" min="0" step="0.01" placeholder="Agreed Price (Peso)" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="due-date" class="form-label">Target completion date</label>
                                        <input type="date" class="form-control" id="due-date" name="due_date" required>
                                    </div>
                                    <div class="col-12">
                                        <label for="job-notes" class="form-label">Job order notes <span class="optional">(optional)</span></label>
                                        <textarea class="form-control" id="job-notes" name="notes" rows="3"
                                            placeholder="Add sizes, colors, special instructions, or other details..."></textarea>
                                    </div>
                                </div>
                            </section>

                            <section class="form-card" aria-labelledby="image-heading">
                                <div class="card-heading">
                                    <span class="step-number">03</span>
                                    <div>
                                        <h3 id="image-heading">Reference image</h3>
                                        <p>Attach an image if the job needs one.</p>
                                    </div>
                                </div>
                                <label for="reference-image" class="upload-area">
                                    <span class="upload-icon">↑</span>
                                    <span><strong>Choose an image to upload</strong><small>PNG, JPG, or JPEG · Maximum 10 MB</small></span>
                                    <input type="file" id="reference-image" name="reference_image" accept="image/png,image/jpeg" data-max-size="10485760">
                                </label>
                                <div class="reference-preview" id="reference-image-preview" aria-live="polite"></div>
                            </section>

                            <div class="form-actions">
								<button class="btn clear-button" type="reset">Clear form</button>
								<button class="btn submit-button" type="submit" name="create-invoice">Create invoice <span aria-hidden="true">→</span></button>
                            </div>
                        </form>
                    </section>

                    <section class="view-panel logs-panel" id="invoice-logs" role="tabpanel" aria-labelledby="view-logs">
                        <div class="logs-intro">
                            <div>
                                <p class="panel-kicker">INVOICE RECORDS</p>
                                <h2>Invoice logs</h2>
                                <p>Recent job order invoices created by the admin.</p>
                            </div>
                            <div class="logs-intro-actions">
                                <span class="record-count"><?= $select_invoice_sql !== false ? (int) $select_invoice_sql->num_rows : 0 ?> records</span>
                                <button class="completed-project-button" type="button" data-open-admin-modal="completed-project-dialog">
                                    <span class="completed-project-button-icon" aria-hidden="true">✓</span>
                                    <span>Completed projects</span>
                                </button>
                                <button class="completed-project-button to-ship-button" type="button" data-open-admin-modal="to-ship-dialog">
                                    <span class="completed-project-button-icon" aria-hidden="true">↑</span>
                                    <span>To ship</span>
                                </button>
                            </div>
                        </div>

                        <div class="log-filters">
                            <label for="log-search" class="visually-hidden">Search invoices</label>
                            <input class="form-control search-input" id="log-search" type="search"
                                placeholder="Search customer or invoice number">
                            <label for="status-filter" class="visually-hidden">Filter by status</label>
                            <select class="form-select status-filter" id="status-filter">
                                <option>All statuses</option>
                                <option>Pending</option>
                                <option>In progress</option>
                                <option>Completed</option>
                            </select>
                        </div>
                        <div class="table-responsive">
                            <table class="table invoice-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">INVOICE NO.</th>
                                        <th scope="col">CUSTOMER</th>
                                        <th scope="col">TOTAL</th>
                                        <th scope="col">PASS TO</th>
                                        <th scope="col">ACTION</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($invoice_load_error !== ""): ?>
                                        <tr><td colspan="5"><?= htmlspecialchars($invoice_load_error, ENT_QUOTES, "UTF-8") ?></td></tr>
                                    <?php elseif ($select_invoice_sql->num_rows > 0): ?>
                                        <?php $sendFormIndex = 0; ?>
                                        <?php while ($row = $select_invoice_sql->fetch_assoc()): ?>
                                            <?php
                                            $invoiceId = (string) ($row["invoice_id"] ?? "");
                                            $sendFormId = "send-invoice-" . (++$sendFormIndex);
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($invoiceId, ENT_QUOTES, "UTF-8") ?></td>
                                                <td><?= htmlspecialchars((string) $row["customer_name"], ENT_QUOTES, "UTF-8") ?></td>
                                                <td><?= htmlspecialchars((string) $row["price"], ENT_QUOTES, "UTF-8") ?></td>
                                                <td>
                                                    <form id="<?= $sendFormId ?>" action="" method="POST">
                                                        <input type="hidden" name="send-to" value="<?= htmlspecialchars($invoiceId, ENT_QUOTES, "UTF-8") ?>">
                                                        <select name="department" class="form-select form-select-sm" required>
                                                            <option value="" selected disabled>Select department</option>
                                                            <option value="layout">Layout</option>
                                                            <option value="production">Production</option>
                                                            <option value="qa">QA</option>
                                                        </select>
                                                    </form>
                                                </td>
                                                <td><button class="btn btn-success" type="submit" form="<?= $sendFormId ?>">Send</button></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5">No invoices have been created yet.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>

            <dialog class="completed-project-dialog" id="completed-project-dialog" aria-labelledby="completed-project-title">
                <div class="completed-project-modal-heading">
                    <div class="completed-project-title-group">
                        <span class="completed-project-modal-icon" aria-hidden="true">✓</span>
                        <div>
                            <p class="panel-kicker">COMPLETED WORK</p>
                            <h2 id="completed-project-title">Completed projects</h2>
                            <p>Projects completed by QA and sent for shipping.</p>
                        </div>
                    </div>
                    <button class="completed-project-close" type="button" data-close-admin-modal aria-label="Close completed projects">×</button>
                </div>
                <div class="completed-project-modal-content">
                    <?php if ($completed_project_load_error !== ""): ?>
                        <p class="invoice-message invoice-message-error" role="alert"><?= htmlspecialchars($completed_project_load_error, ENT_QUOTES, "UTF-8") ?></p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table invoice-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">INVOICE NO.</th>
                                        <th scope="col">CUSTOMER</th>
                                        <th scope="col">SERVICES</th>
                                        <th scope="col">COMPLETION DATE</th>
                                        <th scope="col">TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($completed_projects_sql->num_rows > 0): ?>
                                        <?php while ($row = $completed_projects_sql->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars((string) ($row["invoice_id"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                                <td><?= htmlspecialchars((string) ($row["customer_name"] ?? "—"), ENT_QUOTES, "UTF-8") ?></td>
                                                <td><?= htmlspecialchars((string) ($row["services"] ?? "—"), ENT_QUOTES, "UTF-8") ?></td>
                                                <td><?= htmlspecialchars((string) ($row["completion_date"] ?? "—"), ENT_QUOTES, "UTF-8") ?></td>
                                                <td><?= htmlspecialchars((string) ($row["price"] ?? "—"), ENT_QUOTES, "UTF-8") ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="completed-project-empty">
                                                <div>
                                                    <span class="completed-project-empty-icon" aria-hidden="true">✓</span>
                                                    <strong>No completed projects yet</strong>
                                                    <small>Projects will appear here after QA completes them.</small>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="completed-project-modal-footer">
                    <span>Completion status is managed by Quality Assurance.</span>
                    <button class="completed-project-dismiss" type="button" data-close-admin-modal>Close</button>
                </div>
            </dialog>

            <dialog class="completed-project-dialog" id="to-ship-dialog" aria-labelledby="to-ship-title">
                <div class="completed-project-modal-heading">
                    <div class="completed-project-title-group">
                        <span class="completed-project-modal-icon" aria-hidden="true">↑</span>
                        <div>
                            <p class="panel-kicker">INVOICE RECORDS</p>
                            <h2 id="to-ship-title">To ship</h2>
                            <p>Invoice details currently stored in the invoices table.</p>
                        </div>
                    </div>
                    <button class="completed-project-close" type="button" data-close-admin-modal aria-label="Close invoices to ship">×</button>
                </div>
                <div class="completed-project-modal-content">
                    <?php if ($to_ship_load_error !== ""): ?>
                        <p class="invoice-message invoice-message-error" role="alert"><?= htmlspecialchars($to_ship_load_error, ENT_QUOTES, "UTF-8") ?></p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table invoice-table to-ship-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">INVOICE ID</th>
                                        <th scope="col">CUSTOMER NAME</th>
                                        <th scope="col">COMPLETION DATE</th>
                                        <th scope="col">SHIPPING ADDRESS</th>
                                        <th scope="col">ADMIN</th>
                                        <th scope="col">LAYOUT</th>
                                        <th scope="col">PRODUCTION</th>
                                        <th scope="col">QUALITY</th>
                                        <th scope="col">VIEW</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($to_ship_sql->num_rows > 0): ?>
                                        <?php while ($row = $to_ship_sql->fetch_assoc()): ?>
                                            <?php
                                            $passStatuses = [
                                                "admin" => strtoupper(trim((string) ($row["pass_admin"] ?? ""))) === "PASS",
                                                "layout" => strtoupper(trim((string) ($row["pass_layout"] ?? ""))) === "PASS",
                                                "production" => strtoupper(trim((string) ($row["pass_production"] ?? ""))) === "PASS",
                                                "quality" => strtoupper(trim((string) ($row["pass_qa"] ?? ""))) === "PASS",
                                            ];
                                            $invoiceDetails = [
                                                "invoice_id" => (string) ($row["invoice_id"] ?? ""),
                                                "customer_name" => (string) ($row["customer_name"] ?? ""),
                                                "contact_number" => (string) ($row["contact_number"] ?? ""),
                                                "customer_address" => (string) ($row["customer_address"] ?? ""),
                                                "landmarks" => (string) ($row["landmarks"] ?? ""),
                                                "services" => (string) ($row["services"] ?? ""),
                                                "quantity" => (string) ($row["quantity"] ?? ""),
                                                "price" => (string) ($row["price"] ?? ""),
                                                "completion_date" => (string) ($row["completion_date"] ?? ""),
                                                "notes" => (string) ($row["notes"] ?? ""),
                                            ];
                                            $invoiceDetailsJson = json_encode(
                                                $invoiceDetails,
                                                JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_INVALID_UTF8_SUBSTITUTE
                                            );
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars((string) ($row["invoice_id"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                                <td><?= htmlspecialchars((string) ($row["customer_name"] ?? "—"), ENT_QUOTES, "UTF-8") ?></td>
                                                <td><?= htmlspecialchars((string) ($row["completion_date"] ?? "—"), ENT_QUOTES, "UTF-8") ?></td>
                                                <td><?= htmlspecialchars((string) ($row["customer_address"] ?? "—"), ENT_QUOTES, "UTF-8") ?></td>
                                                <?php foreach ($passStatuses as $passed): ?>
                                                    <td>
                                                        <span class="pass-value <?= $passed ? "pass-true" : "pass-false" ?>">
                                                            <?= $passed ? "true" : "false" ?>
                                                        </span>
                                                    </td>
                                                <?php endforeach; ?>
                                                <td>
                                                    <button
                                                        class="invoice-view-button"
                                                        type="button"
                                                        data-view-invoice
                                                        data-invoice-details="<?= htmlspecialchars((string) $invoiceDetailsJson, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") ?>"
                                                    >View</button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="completed-project-empty">
                                                <div>
                                                    <span class="completed-project-empty-icon" aria-hidden="true">↑</span>
                                                    <strong>No invoices are available</strong>
                                                    <small>Invoice records will appear here when they are saved.</small>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="completed-project-modal-footer">
                    <span>Invoice data is read from the invoices table.</span>
                    <button class="completed-project-dismiss" type="button" data-close-admin-modal>Close</button>
                </div>
            </dialog>

            <dialog class="completed-project-dialog invoice-details-dialog" id="invoice-details-dialog" aria-labelledby="invoice-details-title">
                <div class="completed-project-modal-heading">
                    <div class="completed-project-title-group">
                        <span class="completed-project-modal-icon" aria-hidden="true">▤</span>
                        <div>
                            <p class="panel-kicker">INVOICE RECORD</p>
                            <h2 id="invoice-details-title">Invoice details</h2>
                            <p>Full invoice information without department pass statuses.</p>
                        </div>
                    </div>
                    <button class="completed-project-close" type="button" data-close-admin-modal aria-label="Close invoice details">×</button>
                </div>
                <div class="completed-project-modal-content invoice-details-content">
                    <dl class="invoice-details-grid">
                        <div class="invoice-detail-card">
                            <dt>Invoice ID</dt>
                            <dd data-invoice-detail="invoice_id">—</dd>
                        </div>
                        <div class="invoice-detail-card">
                            <dt>Customer name</dt>
                            <dd data-invoice-detail="customer_name">—</dd>
                        </div>
                        <div class="invoice-detail-card">
                            <dt>Contact number</dt>
                            <dd data-invoice-detail="contact_number">—</dd>
                        </div>
                        <div class="invoice-detail-card invoice-detail-wide">
                            <dt>Shipping address</dt>
                            <dd data-invoice-detail="customer_address">—</dd>
                        </div>
                        <div class="invoice-detail-card">
                            <dt>Landmarks</dt>
                            <dd data-invoice-detail="landmarks">—</dd>
                        </div>
                        <div class="invoice-detail-card">
                            <dt>Services</dt>
                            <dd data-invoice-detail="services">—</dd>
                        </div>
                        <div class="invoice-detail-card">
                            <dt>Quantity</dt>
                            <dd data-invoice-detail="quantity">—</dd>
                        </div>
                        <div class="invoice-detail-card">
                            <dt>Price</dt>
                            <dd data-invoice-detail="price">—</dd>
                        </div>
                        <div class="invoice-detail-card">
                            <dt>Completion date</dt>
                            <dd data-invoice-detail="completion_date">—</dd>
                        </div>
                        <div class="invoice-detail-card invoice-detail-wide">
                            <dt>Job notes</dt>
                            <dd data-invoice-detail="notes">—</dd>
                        </div>
                        <div class="invoice-detail-card invoice-detail-wide invoice-complete-card">
                            <div>
                                <strong>Ready to complete this project?</strong>
                                <small>This moves the invoice from active invoices to Completed Projects.</small>
                            </div>
                            <form method="POST" action="admin_panel.php?view=logs#invoice-logs" data-complete-project-form>
                                <input type="hidden" name="complete-project" id="complete-project-invoice-id" value="">
                                <button class="project-completed-button" type="submit">Project Completed</button>
                            </form>
                        </div>
                    </dl>
                </div>
                <div class="completed-project-modal-footer">
                    <span>Details are loaded from the invoices table.</span>
                    <button class="completed-project-dismiss" type="button" data-close-admin-modal>Close</button>
                </div>
            </dialog>
        </main>
    </div>
</body>
</html>
