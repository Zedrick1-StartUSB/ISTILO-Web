<?php
session_start();
require "../logout.php";

if($_SESSION["role"] != "owner"){
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
    <title>Orders | little label</title>
    <link rel="stylesheet" href="../bootstrap_template/css/bootstrap.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="orders.css">
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <a class="brand" href="dashboard.html"><span class="brand-mark">l.</span><span>little label</span></a>
            <p class="nav-caption">MENU</p>
            <nav class="side-nav" aria-label="Main navigation">
                <a class="side-link" href="dashboard.php">Dashboard</a>
                <a class="side-link active" href="orders.php" aria-current="page">Orders</a>
                <a class="side-link" href="materials.php">Materials</a>
                <a class="side-link" href="users.php">Users</a>
                <a class="side-link" href="history_logs.php">History Logs</a>
                <form method="POST">
                    <button class="btn btn-danger" name="logout">Logout</button>
                </form>
            </nav>
        </aside>

        <main class="main-content">
            <header class="page-header">
                <div>
                    <p class="today-label">SHOP MANAGEMENT</p>
                    <h1>Orders</h1>
                    <p class="page-subtitle">Keep track of what customers are waiting for.</p>
                </div>
                <a class="add-product-button" href="#orders-table"><span aria-hidden="true">＋</span> New order</a>
            </header>

            <section class="page-stats" aria-label="Order summary">
                <article class="page-stat"><span>All orders</span><strong>28</strong><small>Orders this week</small></article>
                <article class="page-stat"><span>Needs packing</span><strong>7</strong><small>Ready to prepare</small></article>
                <article class="page-stat"><span>On the way</span><strong>12</strong><small>Shipped to customers</small></article>
                <article class="page-stat"><span>Completed</span><strong>9</strong><small>Delivered with care</small></article>
            </section>

            <section class="panel page-panel" id="orders-table">
                <div class="panel-heading page-panel-heading">
                    <div><h2>All orders</h2><p>A little list of your recent shop orders</p></div>
                    <span class="subtle-badge">Updated today</span>
                </div>
                <div class="table-responsive">
                    <table class="table data-table align-middle mb-0">
                        <thead><tr><th>ORDER</th><th>CUSTOMER</th><th>DATE</th><th>ITEMS</th><th>TOTAL</th><th>STATUS</th></tr></thead>
                        <tbody>
                            <tr><td><strong>#LL-1048</strong></td><td>Ava Mitchell</td><td>May 18, 2026</td><td>2 items</td><td><strong>$86.00</strong></td><td><span class="status-pill status-packing">Packing</span></td></tr>
                            <tr><td><strong>#LL-1047</strong></td><td>Riley Kim</td><td>May 18, 2026</td><td>1 item</td><td><strong>$42.50</strong></td><td><span class="status-pill status-shipped">Shipped</span></td></tr>
                            <tr><td><strong>#LL-1046</strong></td><td>Sam Jones</td><td>May 17, 2026</td><td>3 items</td><td><strong>$119.00</strong></td><td><span class="status-pill status-new">New</span></td></tr>
                            <tr><td><strong>#LL-1045</strong></td><td>Taylor Reed</td><td>May 17, 2026</td><td>2 items</td><td><strong>$64.00</strong></td><td><span class="status-pill status-delivered">Delivered</span></td></tr>
                            <tr><td><strong>#LL-1044</strong></td><td>Casey Park</td><td>May 16, 2026</td><td>1 item</td><td><strong>$35.00</strong></td><td><span class="status-pill status-shipped">Shipped</span></td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
            <footer class="dashboard-footer">Made with care at little label <span aria-hidden="true">♡</span></footer>
        </main>
    </div>
</body>
</html>
