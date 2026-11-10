<?php
session_start();
require "admin_backend.php";
require "../logout.php";

if($_SESSION["role"] != "admin"){
    header("Location: /ISTILO_SYSTEM/Login/html/login.php");
    exit();
}
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
            <div class="view-switch" role="tablist" aria-label="Invoice pages">
                <input class="view-radio" type="radio" name="invoice-view" id="view-create" checked>
                <input class="view-radio" type="radio" name="invoice-view" id="view-logs">
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
                                        <input type="text" class="form-control" id="customer-name" name="customer_name" placeholder="Enter customer name" >
                                    </div>
                                    <div class="col-md-6">
                                        <label for="customer-number" class="form-label">Contact number</label>
                                        <input type="tel" class="form-control" id="customer-number" name="customer_number" placeholder="09XX XXX XXXX">
                                    </div>
                                    <div class="col-md-7">
                                        <label for="shipping-address" class="form-label">Address</label>
                                        <input type="text" class="form-control" id="shipping-address" name="shipping_address"
                                            placeholder="House number, street, barangay">
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
                                        <input type="text" class="form-control" id="product-name" name="product-service" placeholder="What are we making?">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="quantity" class="form-label">Quantity</label>
                                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" placeholder="Enter quantity" >
                                    </div>
                                    <div class="col-md-6">
                                        <label for="unit-price" class="form-label">Total Amount(₱)</label>
                                        <input type="text" class="form-control" id="total-price" name="total-price" placeholder="Agreed Price (Peso)"
                                            >
                                    </div>
                                    <div class="col-md-6">
                                        <label for="due-date" class="form-label">Target completion date</label>
                                        <input type="date" class="form-control" id="due-date" name="due_date">
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
                                    <span><strong>Choose an image to upload</strong><small>PNG, JPG, or JPEG</small></span>
                                    <input type="file" id="reference-image" name="reference_image" accept="image/png,image/jpeg" data-max-size="5242880">
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
                            <span class="record-count">4 records</span>
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
									<?php
										while($row = $select_invoice_sql->fetch_assoc()){
											echo "<tr>";
											echo "<td>". $row['invoice_id'] . "</td>";
											echo "<td>". $row['customer_name'] . "</td>";
											echo "<td>". $row['price'] . "</td>";
											echo "<td>". '<select><option>Layout Department</option> <option>Production Department</option></select>'  . "</td>";
											echo "<td>" . '<button>Send</button>' . "</td>"; 
											echo "</tr>";
										}
									
									?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
