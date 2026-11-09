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
    <title>Users | little label</title>
    <link rel="stylesheet" href="../bootstrap_template/css/bootstrap.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="users.css">
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <a class="brand" href="dashboard.html"><span class="brand-mark">l.</span><span>little label</span></a>
            <p class="nav-caption">MENU</p>
            <nav class="side-nav" aria-label="Main navigation">
                <a class="side-link" href="dashboard.php">Dashboard</a>
                <a class="side-link" href="orders.php">Orders</a>
                <a class="side-link" href="materials.php">Materials</a>
                <a class="side-link active" href="users.php" aria-current="page">Users</a>
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
                    <h1>Users</h1>
                    <p class="page-subtitle">Meet the people who help make your little shop work.</p>
                </div>
                <a class="add-product-button" href="#users-table"><span aria-hidden="true">＋</span> Invite a user</a>
            </header>

            <section class="page-stats" aria-label="Team summary">
                <article class="page-stat"><span>Team members</span><strong>8</strong><small>Across all departments</small></article>
                <article class="page-stat"><span>Admins</span><strong>2</strong><small>Shop management</small></article>
                <article class="page-stat"><span>Production</span><strong>3</strong><small>Making the good stuff</small></article>
                <article class="page-stat"><span>Design &amp; QA</span><strong>3</strong><small>Details and quality</small></article>
            </section>

            <section class="panel page-panel" id="users-table">
                <div class="panel-heading page-panel-heading">
                    <div><h2>Team members</h2><p>People with access to the shop dashboard</p></div>
                    <span class="subtle-badge">8 members</span>
                </div>
                <div class="table-responsive">
                    <table class="table data-table align-middle mb-0">
                        <thead><tr>
                            <th>NAME</th>
                            <th>EMAIL</th>
                            <th>DEPARTMENT</th>
                            <th>ROLE</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                        <tbody>
                            <tr><td><span class="user-name"><span class="user-avatar avatar-pink">JL</span> Jamie Lee</span></td><td>jamie@littlelabel.example</td><td>Admin</td><td>Shop owner</td><td><span class="status-pill status-in-stock">Active</span></td></tr>
                            <tr><td><span class="user-name"><span class="user-avatar avatar-blue">AM</span> Alex Morgan</span></td><td>alex@littlelabel.example</td><td>Admin</td><td>Administrator</td><td><span class="status-pill status-in-stock">Active</span></td></tr>
                            <tr><td><span class="user-name"><span class="user-avatar avatar-yellow">SK</span> Sky Kim</span></td><td>sky@littlelabel.example</td><td>Layout</td><td>Designer</td><td><span class="status-pill status-in-stock">Active</span></td></tr>
                            <tr><td><span class="user-name"><span class="user-avatar avatar-green">MR</span> Morgan Reed</span></td><td>morgan@littlelabel.example</td><td>Production</td><td>Production lead</td><td><span class="status-pill status-in-stock">Active</span></td></tr>
                            <tr><td><span class="user-name"><span class="user-avatar avatar-lilac">TC</span> Taylor Chen</span></td><td>taylor@littlelabel.example</td><td>Quality assurance</td><td>QA checker</td><td><span class="status-pill status-in-stock">Active</span></td></tr>
                            <tr><td><span class="user-name"><span class="user-avatar avatar-peach">JR</span> Jordan Rivera</span></td><td>jordan@littlelabel.example</td><td>Production</td><td>Maker</td><td><span class="status-pill status-in-stock">Active</span></td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
            <footer class="dashboard-footer">Made with care at little label <span aria-hidden="true">♡</span></footer>
        </main>
    </div>
</body>
</html>
