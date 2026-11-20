<?php
session_start();
require "../logout.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "owner") {
    header("Location: /ISTILO_SYSTEM/Login/html/login.php");
    exit();
}

$owner_data_error = "";
$owner_materials = [];
try {
    require_once __DIR__ . DIRECTORY_SEPARATOR . "owner_backend.php";
    $owner_materials = ownerLoadMaterials($conn);
} catch (mysqli_sql_exception $exception) {
    error_log("Unable to load Owner materials: " . $exception->getMessage());
    $owner_data_error = "Materials data could not be loaded.";
}
$owner_material_metrics = ownerBuildMaterialMetrics($owner_materials);
$material_swatch_classes = ["swatch-cream", "swatch-blue", "swatch-pink", "swatch-green", "swatch-yellow", "swatch-lilac"];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f8f5ef">
    <title>Materials | little label</title>
    <link rel="stylesheet" href="../bootstrap_template/css/bootstrap.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="materials.css">
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <a class="brand" href="dashboard.php"><span class="brand-mark">l.</span><span>little label</span></a>
            <p class="nav-caption">MENU</p>
            <nav class="side-nav" aria-label="Main navigation">
                <a class="side-link" href="dashboard.php">Dashboard</a>
                <a class="side-link" href="orders.php">Orders</a>
                <a class="side-link active" href="materials.php" aria-current="page">Materials</a>
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
                    <h1>Materials</h1>
                    <p class="page-subtitle">A quick check-in on the things that make your products.</p>
                </div>
                <span class="subtle-badge">Live inventory</span>
            </header>

            <?php if ($owner_data_error !== ""): ?>
                <p class="alert alert-danger" role="alert"><?= htmlspecialchars($owner_data_error, ENT_QUOTES, "UTF-8") ?></p>
            <?php endif; ?>

            <section class="page-stats" aria-label="Materials summary">
                <article class="page-stat"><span>Material types</span><strong><?= (int) $owner_material_metrics["material_count"] ?></strong><small>In inventory</small></article>
                <article class="page-stat"><span>Total units</span><strong><?= (int) $owner_material_metrics["total_units"] ?></strong><small>Recorded quantity</small></article>
                <article class="page-stat"><span>In stock</span><strong><?= (int) $owner_material_metrics["in_stock"] ?></strong><small>Types with quantity above zero</small></article>
                <article class="page-stat"><span>Out of stock</span><strong><?= (int) $owner_material_metrics["out_of_stock"] ?></strong><small>Types at zero quantity</small></article>
            </section>

            <section class="panel page-panel" id="materials-table">
                <div class="panel-heading page-panel-heading">
                    <div><h2>Materials inventory</h2><p>Current quantities recorded by Layout</p></div>
                    <span class="subtle-badge"><?= (int) $owner_material_metrics["material_count"] ?> materials</span>
                </div>
                <div class="table-responsive">
                    <table class="table data-table align-middle mb-0">
                        <thead><tr><th>MATERIAL</th><th>QUANTITY</th><th>STATUS</th></tr></thead>
                        <tbody>
                            <?php if ($owner_materials !== []): ?>
                                <?php foreach ($owner_materials as $index => $material): ?>
                                    <?php
                                    $quantity = (int) ($material["quantity"] ?? 0);
                                    $materialStatus = $quantity > 0 ? "In stock" : "Out of stock";
                                    $materialStatusClass = $quantity > 0 ? "status-in-stock" : "status-low";
                                    $swatchClass = $material_swatch_classes[$index % count($material_swatch_classes)];
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="material-name">
                                                <span class="swatch <?= htmlspecialchars($swatchClass, ENT_QUOTES, "UTF-8") ?>"></span>
                                                <?= htmlspecialchars((string) ($material["material"] ?? ""), ENT_QUOTES, "UTF-8") ?>
                                            </span>
                                        </td>
                                        <td><?= $quantity ?></td>
                                        <td><span class="status-pill <?= htmlspecialchars($materialStatusClass, ENT_QUOTES, "UTF-8") ?>"><?= htmlspecialchars($materialStatus, ENT_QUOTES, "UTF-8") ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3">No material records are available.</td></tr>
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
