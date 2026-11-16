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
            <p class="side-label">YOUR WORK</p>
            <nav class="production-nav" aria-label="Production workspace">
                <a class="nav-item" href="production.php">Job Orders</a>
                <a class="nav-item active" href="attendance.php" aria-current="page"><span>◷</span> Attendance</a>
                <form method="POST">
                    <button class="btn btn-danger" name="logout" type="submit">Logout</button>
                </form>
            </nav>

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

            <section class="card shadow-sm border-0" aria-labelledby="todayAttendanceTitle">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                    <div>
                        <p class="overline mb-1">DAILY RECORD</p>
                        <h2 class="h5 mb-0" id="todayAttendanceTitle">Today's attendance</h2>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-pill text-bg-success">2 / 3 checked in</span>
                        <button class="btn btn-success" type="button" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">Add employee</button>
                    </div>
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

    <div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-labelledby="addEmployeeModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <p class="overline mb-1">TEAM ROSTER</p>
                        <h2 class="modal-title h5 mb-0" id="addEmployeeModalTitle">Add an employee</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="employeeName">Employee name</label>
                    <input class="form-control" id="employeeName" type="text" placeholder="Enter employee name">
                    <p class="form-text mb-0">Employee records are not connected yet; this form is a UI preview.</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-success" type="button" data-bs-dismiss="modal">Add employee</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../bootstrap_template/js/bootstrap.bundle.min.js"></script>
</body>
</html>
