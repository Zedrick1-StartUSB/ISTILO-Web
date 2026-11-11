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
    <title>Layout Dashboard | Thread &amp; Form</title>
    <link rel="stylesheet" href="../bootstrap_template/css/bootstrap.min.css">
    <link rel="stylesheet" href="layout_artist.css">
</head>
<body>
    <div class="studio-shell">
        <aside class="studio-sidebar">
            <a class="studio-brand" href="layout_artist.php">
                <span class="brand-mark">t.</span>
                <span>thread &amp; form<small>LAYOUT STUDIO</small></span>
            </a>

            <p class="menu-label">WORKSPACE</p>
            <nav class="studio-nav" aria-label="Layout workspace">
                <a class="studio-nav-link selected" href="layout_artist.php" aria-current="page"><span aria-hidden="true">⌂</span> Dashboard</a>
                <a class="studio-nav-link" href="layout_work.php"><span aria-hidden="true">✓</span> Work</a>
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
            <section class="screen-section" id="dashboard" aria-labelledby="dashboard-title">
                <header class="studio-header">
                    <div>
                        <p class="date-label">LAYOUT STUDIO</p>
                        <h1 id="dashboard-title">Dashboard</h1>
                        <p class="header-subtitle">A quick overview of incoming jobs, active work, and invoices.</p>
                    </div>
                    <span class="artist-status"><span></span> Studio is open</span>
                </header>

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
                                    <th scope="col">Total</th>
                                    <th scope="col">Due date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($query_incoming_invoices->num_rows > 0): ?>
                                    <?php while ($row = $query_incoming_invoices->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row["invoice_id"] ?? '') ?></td>
                                            <td><?= htmlspecialchars($row["customer_name"] ?? '') ?></td>
                                            <td><?= htmlspecialchars($row["services"] ?? '') ?></td>
                                            <td><?= htmlspecialchars($row["quantity"] ?? '') ?></td>
                                            <td><?= htmlspecialchars($row["price"] ?? '') ?></td>
                                            <td><?= htmlspecialchars($row["completion_date"] ?? '') ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6">No incoming invoices found.</td>
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
                        <table class="table invoice-list mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Invoice</th>
                                    <th scope="col">Customer</th>
                                    <th scope="col">Service</th>
                                    <th scope="col">Qty</th>
                                    <th scope="col">Total</th>
                                    <th scope="col">Due date</th>
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
                                            <td><?= htmlspecialchars($row["price"] ?? '') ?></td>
                                            <td><?= htmlspecialchars($row["completion_date"] ?? '') ?></td>
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
</body>
</html>
