<?php
session_start();
require "../logout.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "owner") {
    header("Location: /ISTILO_SYSTEM/Login/html/login.php");
    exit();
}

$owner_data_error = "";
$owner_users = [];
try {
    require_once __DIR__ . DIRECTORY_SEPARATOR . "owner_backend.php";
    $owner_users = ownerLoadUsers($conn);
} catch (mysqli_sql_exception $exception) {
    error_log("Unable to load Owner users: " . $exception->getMessage());
    $owner_data_error = "User data could not be loaded.";
}
$owner_user_metrics = ownerBuildUserMetrics($owner_users);
$user_avatar_classes = ["avatar-pink", "avatar-blue", "avatar-yellow", "avatar-green", "avatar-lilac", "avatar-peach"];
$user_role_labels = [
    "owner" => "Owner",
    "admin" => "Administrator",
    "layout" => "Layout",
    "production" => "Production",
    "qa" => "Quality assurance",
];

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
            <a class="brand" href="dashboard.php"><span class="brand-mark">l.</span><span>little label</span></a>
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
                <span class="subtle-badge">Registered accounts</span>
            </header>

            <?php if ($owner_data_error !== ""): ?>
                <p class="alert alert-danger" role="alert"><?= htmlspecialchars($owner_data_error, ENT_QUOTES, "UTF-8") ?></p>
            <?php endif; ?>

            <section class="page-stats" aria-label="Team summary">
                <article class="page-stat"><span>Team members</span><strong><?= (int) $owner_user_metrics["team_members"] ?></strong><small>Registered accounts</small></article>
                <article class="page-stat"><span>Owners &amp; admins</span><strong><?= (int) $owner_user_metrics["admins"] ?></strong><small>Management access</small></article>
                <article class="page-stat"><span>Production</span><strong><?= (int) $owner_user_metrics["production"] ?></strong><small>Production accounts</small></article>
                <article class="page-stat"><span>Layout &amp; QA</span><strong><?= (int) $owner_user_metrics["design_qa"] ?></strong><small>Design and inspection</small></article>
            </section>

            <section class="panel page-panel" id="users-table">
                <div class="panel-heading page-panel-heading">
                    <div><h2>Team members</h2><p>Accounts registered in the system</p></div>
                    <span class="subtle-badge"><?= (int) $owner_user_metrics["team_members"] ?> members</span>
                </div>
                <div class="table-responsive">
                    <table class="table data-table align-middle mb-0">
                        <thead><tr>
                            <th>NAME</th>
                            <th>USERNAME</th>
                            <th>DEPARTMENT</th>
                            <th>ROLE</th>
                            <th>JOINED</th>
                        </tr>
                    </thead>
                        <tbody>
                            <?php if ($owner_users !== []): ?>
                                <?php foreach ($owner_users as $index => $user): ?>
                                    <?php
                                    $fullname = (string) ($user["fullname"] ?? "");
                                    $role = strtolower(trim((string) ($user["role"] ?? "")));
                                    $avatarClass = $user_avatar_classes[$index % count($user_avatar_classes)];
                                    $department = trim((string) ($user["department"] ?? ""));
                                    ?>
                                    <tr>
                                        <td><span class="user-name"><span class="user-avatar <?= htmlspecialchars($avatarClass, ENT_QUOTES, "UTF-8") ?>"><?= htmlspecialchars(ownerInitials($fullname), ENT_QUOTES, "UTF-8") ?></span><?= htmlspecialchars($fullname !== "" ? $fullname : "—", ENT_QUOTES, "UTF-8") ?></span></td>
                                        <td><?= htmlspecialchars((string) ($user["username"] ?? "—"), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars($department !== "" ? $department : "—", ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars($user_role_labels[$role] ?? ($role !== "" ? ucfirst($role) : "—"), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars(ownerFormatDate($user["created_at"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5">No registered users are available.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <footer class="dashboard-footer">Made with care at little label <span aria-hidden="true">♡</span></footer>
        </main>
    </div>
</body>
</html>
