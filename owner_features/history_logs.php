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
    <title>History Logs | little label</title>
    <link rel="stylesheet" href="../bootstrap_template/css/bootstrap.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="history_logs.css">
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
                <a class="side-link" href="users.php">Users</a>
                <a class="side-link active" href="history_logs.php">History Logs</a>
                <form method="POST">
                    <button class="btn btn-danger" name="logout">Logout</button>
                </form>
            </nav>
        </aside>

        <main class="main-content">
            <header class="page-header">
                <div>
                    <p class="today-label">SHOP MANAGEMENT</p>
                    <h1>History logs</h1>
                    <p class="page-subtitle">A peek at the recent updates made by each department.</p>
                </div>
                <span class="subtle-badge header-badge">Sample activity</span>
            </header>

            <section class="panel page-panel history-panel" aria-labelledby="department-title">
                <div class="panel-heading page-panel-heading">
                    <div><h2 id="department-title">Department activity</h2><p>Showing recent activity from the selected team</p></div>
                </div>

                <div class="department-tabs">
                    <input class="department-radio" type="radio" name="department" id="department-admin" checked>
                    <input class="department-radio" type="radio" name="department" id="department-layout">
                    <input class="department-radio" type="radio" name="department" id="department-production">
                    <input class="department-radio" type="radio" name="department" id="department-qa">

                    <div class="department-buttons" role="group" aria-label="Choose a department">
                        <label class="department-button" for="department-admin">Admin</label>
                        <label class="department-button" for="department-layout">Layout</label>
                        <label class="department-button" for="department-production">Production</label>
                        <label class="department-button" for="department-qa">Quality Assurance</label>
                    </div>

                    <div class="log-list log-list-admin">
                        <article class="log-entry"><span class="log-avatar avatar-pink">JL</span><div class="log-copy"><div><strong>Jamie Lee</strong><span class="log-action">updated an order</span></div><p>Changed order <strong>#LL-1048</strong> to “Packing”.</p><small>Today · 10:42 AM</small></div><span class="log-category">Orders</span></article>
                        <article class="log-entry"><span class="log-avatar avatar-blue">AM</span><div class="log-copy"><div><strong>Alex Morgan</strong><span class="log-action">added a team member</span></div><p>Invited Taylor Chen to the Quality Assurance department.</p><small>Today · 9:15 AM</small></div><span class="log-category">Users</span></article>
                        <article class="log-entry"><span class="log-avatar avatar-yellow">JL</span><div class="log-copy"><div><strong>Jamie Lee</strong><span class="log-action">updated shop details</span></div><p>Changed the shop's contact email address.</p><small>Yesterday · 4:32 PM</small></div><span class="log-category">Settings</span></article>
                    </div>

                    <div class="log-list log-list-layout">
                        <article class="log-entry"><span class="log-avatar avatar-yellow">SK</span><div class="log-copy"><div><strong>Sky Kim</strong><span class="log-action">updated a product layout</span></div><p>Adjusted the placement of the logo on the Everyday Tee.</p><small>Today · 11:08 AM</small></div><span class="log-category">Products</span></article>
                        <article class="log-entry"><span class="log-avatar avatar-lilac">DP</span><div class="log-copy"><div><strong>Drew Patel</strong><span class="log-action">uploaded a design</span></div><p>Added a new color option to the Weekend Tote product page.</p><small>Today · 9:36 AM</small></div><span class="log-category">Design</span></article>
                        <article class="log-entry"><span class="log-avatar avatar-peach">SK</span><div class="log-copy"><div><strong>Sky Kim</strong><span class="log-action">saved a draft</span></div><p>Saved the summer collection banner for review.</p><small>Yesterday · 3:21 PM</small></div><span class="log-category">Content</span></article>
                    </div>

                    <div class="log-list log-list-production">
                        <article class="log-entry"><span class="log-avatar avatar-green">MR</span><div class="log-copy"><div><strong>Morgan Reed</strong><span class="log-action">started a production batch</span></div><p>Started 12 Everyday Tees for batch <strong>#B-208</strong>.</p><small>Today · 10:24 AM</small></div><span class="log-category">Production</span></article>
                        <article class="log-entry"><span class="log-avatar avatar-blue">JR</span><div class="log-copy"><div><strong>Jordan Rivera</strong><span class="log-action">updated material usage</span></div><p>Recorded 6 yards of cotton jersey used for today's batch.</p><small>Today · 9:50 AM</small></div><span class="log-category">Materials</span></article>
                        <article class="log-entry"><span class="log-avatar avatar-green">MR</span><div class="log-copy"><div><strong>Morgan Reed</strong><span class="log-action">completed a production batch</span></div><p>Marked 8 Weekend Totes ready for quality checking.</p><small>Yesterday · 4:05 PM</small></div><span class="log-category">Production</span></article>
                    </div>

                    <div class="log-list log-list-qa">
                        <article class="log-entry"><span class="log-avatar avatar-lilac">TC</span><div class="log-copy"><div><strong>Taylor Chen</strong><span class="log-action">passed a quality check</span></div><p>Approved 8 Weekend Totes from batch <strong>#B-207</strong>.</p><small>Today · 11:32 AM</small></div><span class="log-category">Inspection</span></article>
                        <article class="log-entry"><span class="log-avatar avatar-peach">AL</span><div class="log-copy"><div><strong>Alex Lopez</strong><span class="log-action">flagged an item for review</span></div><p>Set aside one sample with an uneven hem for a second look.</p><small>Today · 10:02 AM</small></div><span class="log-category">Inspection</span></article>
                        <article class="log-entry"><span class="log-avatar avatar-lilac">TC</span><div class="log-copy"><div><strong>Taylor Chen</strong><span class="log-action">completed a product check</span></div><p>Checked stitching and labels on the new Everyday Tee batch.</p><small>Yesterday · 2:47 PM</small></div><span class="log-category">Inspection</span></article>
                    </div>
                </div>
            </section>
            <p class="sample-note">These entries are example UI content and are not connected to a live activity database.</p>
            <footer class="dashboard-footer">Made with care at little label <span aria-hidden="true">♡</span></footer>
        </main>
    </div>
</body>
</html>
