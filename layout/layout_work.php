<?php
session_start();

require "../logout.php";

if($_SESSION["role"] != "layout"){
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

                <div class="work-section-grid">
                   <section class="panel dashboard-panel invoice-panel" aria-labelledby="incoming-invoices-title">
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
                                    <th scope="col">Invoice</th>
                                    <th scope="col">Customer</th>
                                    <th scope="col">Service</th>
                                    <th scope="col">Qty</th>
                                    <th scope="col">Due date</th>
                                    <th scope="col">ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>#2084</td>
                                    <td>Jamie Lee</td>
                                    <td>Everyday Tee</td>
                                    <td>24</td>
                                    <td>June 04</td>
                                    <td><button class="btn btn-success">Accept</button></td>
                                </tr>
                                <tr>
                                    <td>#2081</td>
                                    <td>Alex Cruz</td>
                                    <td>Weekend Tote</td>
                                    <td>12</td>
                                    <td>June 05</td>
                                    <td><button class="btn btn-success">Accept</button></td>
                                </tr>
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
                                    <th scope="col">Due date</th>
                                    <th scope="col">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>#2084</td>
                                    <td>Jamie Lee</td>
                                    <td>Everyday Tee</td>
                                    <td>24</td>
                                    <td>June 04</td>
                                    <td><button class="btn btn-info">View</button><button class="btn btn-success">Send</button></td>
                                </tr>
                                <tr>
                                    <td>#2081</td>
                                    <td>Alex Cruz</td>
                                    <td>Weekend Tote</td>
                                    <td>12</td>
                                    <td>June 05</td>
                                    <td><button class="btn btn-info">View</button><button class="btn btn-success">Send</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
