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
    <title>Dashboard | little label</title>
    <link rel="stylesheet" href="../bootstrap_template/css/bootstrap.min.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <a class="brand" href="dashboard.html" aria-label="little label dashboard home">
                <span class="brand-mark">l.</span>
                <span>little label</span>
            </a>

            <p class="nav-caption">MENU</p>
            <nav class="side-nav" aria-label="Main navigation">
                <a class="side-link active" href="dashboard.php" aria-current="page">Dashboard</a>
                <a class="side-link" href="orders.php">Orders</a>
                <a class="side-link" href="materials.php">Materials</a>
                <a class="side-link" href="users.php">Users</a>
                <a class="side-link" href="history_logs.php">History Logs</a>
                <form method="POST">
                    <button class="btn btn-danger" name="logout">Logout</button>
                </form>
            </nav>
        </aside>

        <main class="main-content" id="overview">
            <header class="topbar">
                <div>
                    <p class="today-label">MONDAY, MAY 18, 2026</p>
                    <h1>Hey, Jamie <span aria-hidden="true">☀</span></h1>
                    <p class="page-subtitle">Here's what's happening in your little shop today.</p>
                </div>
            </header>

            <section class="stats-grid" aria-label="Shop overview">
                <article class="stat-card stat-card-peach">
                    <div class="stat-heading"><span>Today's sales</span><span class="stat-icon">↗</span></div>
                    <p class="stat-value">$1,284<span class="stat-cents">.50</span></p>
                    <p class="stat-foot"><span class="trend-up">↑ 12%</span> <span>compared to last week</span></p>
                    <div class="stat-decoration decoration-circle"></div>
                </article>
                <article class="stat-card stat-card-lilac">
                    <div class="stat-heading"><span>Orders</span><span class="stat-icon">▤</span></div>
                    <p class="stat-value">28</p>
                    <p class="stat-foot"><span class="trend-up">↑ 8%</span> <span>compared to last week</span></p>
                    <div class="stat-decoration decoration-star">✳</div>
                </article>
                <article class="stat-card stat-card-sage">
                    <div class="stat-heading"><span>Items to ship</span><span class="stat-icon">♧</span></div>
                    <p class="stat-value">7</p>
                    <p class="stat-foot"><span class="stat-neutral">3 need packing</span></p>
                    <div class="stat-decoration decoration-ring"></div>
                </article>
                <article class="stat-card stat-card-yellow">
                    <div class="stat-heading"><span>New customers</span><span class="stat-icon">♡</span></div>
                    <p class="stat-value">14</p>
                    <p class="stat-foot"><span class="trend-up">↑ 4%</span> <span>compared to last week</span></p>
                    <div class="stat-decoration decoration-heart">♡</div>
                </article>
            </section>

            <section class="content-grid">
                <article class="panel sales-panel" id="reports">
                    <div class="panel-heading">
                        <div>
                            <h2>Sales overview</h2>
                            <p>A little look at your week</p>
                        </div>
                        <a class="period-button" href="#reports">This week <span aria-hidden="true">⌄</span></a>
                    </div>
                    <div class="sales-total">$6,430 <span>+12.8% this week</span></div>
                    <div class="chart" role="img" aria-label="Bar chart showing sales from Monday to Sunday, with the highest sales on Saturday">
                        <div class="chart-y-labels"><span>$2k</span><span>$1.5k</span><span>$1k</span><span>$500</span><span>$0</span></div>
                        <div class="chart-bars">
                            <div class="chart-column"><span class="bar bar-1"></span><span class="day-label">Mon</span></div>
                            <div class="chart-column"><span class="bar bar-2"></span><span class="day-label">Tue</span></div>
                            <div class="chart-column"><span class="bar bar-3"></span><span class="day-label">Wed</span></div>
                            <div class="chart-column"><span class="bar bar-4"></span><span class="day-label">Thu</span></div>
                            <div class="chart-column"><span class="bar bar-5"></span><span class="day-label">Fri</span></div>
                            <div class="chart-column"><span class="bar bar-6"></span><span class="day-label">Sat</span></div>
                            <div class="chart-column"><span class="bar bar-7"></span><span class="day-label">Sun</span></div>
                        </div>
                    </div>
                </article>
            </section>
        </main>
    </div>
</body>
</html>
