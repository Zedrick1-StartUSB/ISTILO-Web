<?php
session_start();
require "../logout.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "production") {
    header("Location: /ISTILO_SYSTEM/Login/html/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6f5ef">
    <title>Attendance | Production Desk</title>
    <link rel="stylesheet" href="../bootstrap_template/css/bootstrap.min.css">
    <link rel="stylesheet" href="production.css">
</head>
<body>
    <div class="production-app">
        <aside class="production-sidebar">
            <a class="brand" href="production.php"><span class="brand-stamp">p.</span><span>made &amp; stitched<small>PRODUCTION DESK</small></span></a>
            <p class="side-label">YOUR WORK</p>
            <nav class="production-nav" aria-label="Production workspace">
                <a class="nav-item" href="production.php"><span>▤</span> Incoming invoices</a>
                <a class="nav-item active" href="attendance.php" aria-current="page"><span>◷</span> Attendance</a>
                <form method="POST">
                    <button class="btn btn-danger" name="logout" type="submit">Logout</button>
                </form>
            </nav>
            <div class="sidebar-note">
                <span>✿</span>
                <p><strong>One stitch at a time.</strong><br>You're making good things happen.</p>
            </div>
            <div class="maker-profile">
                <span class="maker-avatar"><?= htmlspecialchars(strtoupper(substr((string) ($_SESSION["fullname"] ?? "P"), 0, 1))) ?></span>
                <span><strong><?= htmlspecialchars($_SESSION["fullname"] ?? "Production team") ?></strong><small>Production maker</small></span>
            </div>
        </aside>

        <main class="production-main">
            <header class="welcome-header">
                <div>
                    <p class="overline">PRODUCTION DESK · TEAM</p>
                    <h1>Attendance</h1>
                    <p class="welcome-subtitle">A clear view of today's team attendance.</p>
                </div>
                <span class="badge rounded-pill text-bg-secondary px-3 py-2"><?= htmlspecialchars(date("l, F j, Y")) ?></span>
            </header>

            <div class="alert alert-info" role="note">
                Attendance controls are a UI preview only. Adding employees and recording attendance are not connected yet.
            </div>

            <section class="card shadow-sm border-0 mb-4" aria-labelledby="addEmployeeTitle">
                <div class="card-header bg-white py-3">
                    <p class="overline mb-1">TEAM ROSTER</p>
                    <h2 class="h5 mb-0" id="addEmployeeTitle">Add an employee</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-8">
                            <label class="form-label" for="employeeName">Employee name</label>
                            <input class="form-control" id="employeeName" type="text" placeholder="Enter employee name">
                        </div>
                        <div class="col-12 col-md-4">
                            <button class="btn btn-success w-100" type="button">Add employee</button>
                        </div>
                    </div>
                </div>
            </section>

            <section class="card shadow-sm border-0" aria-labelledby="todayAttendanceTitle">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                    <div>
                        <p class="overline mb-1">DAILY RECORD</p>
                        <h2 class="h5 mb-0" id="todayAttendanceTitle">Today's attendance</h2>
                    </div>
                    <span class="badge rounded-pill text-bg-success">2 / 3 checked in</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Employee</th>
                                <th scope="col">Status</th>
                                <th scope="col">Check-in</th>
                                <th scope="col">Check-out</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Avery Brooks</td>
                                <td><span class="badge rounded-pill text-bg-success">Working</span></td>
                                <td>8:02 AM</td>
                                <td>—</td>
                                <td><button class="btn btn-sm btn-outline-primary" type="button">Check out</button></td>
                            </tr>
                            <tr>
                                <td>Morgan Reed</td>
                                <td><span class="badge rounded-pill text-bg-primary">Checked out</span></td>
                                <td>7:54 AM</td>
                                <td>4:31 PM</td>
                                <td><button class="btn btn-sm btn-outline-secondary" type="button" disabled>Complete</button></td>
                            </tr>
                            <tr>
                                <td>Taylor Chen</td>
                                <td><span class="badge rounded-pill text-bg-secondary">Not checked in</span></td>
                                <td>—</td>
                                <td>—</td>
                                <td><button class="btn btn-sm btn-success" type="button">Check in</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
