<?php
session_start();

require "../logout.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "layout") {
    header("Location: /ISTILO_SYSTEM/Login/html/login.php");
    exit();
}

require_once __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "dbconn.php";
require_once __DIR__ . DIRECTORY_SEPARATOR . "layout_materials_archive_storage.php";

$archived_materials = [];
$archive_error = "";
$archive_action_error = "";
$archive_action_success = "";
$archive_search_input = $_GET["search"] ?? $_POST["search"] ?? "";
$archive_search = is_string($archive_search_input) ? trim($archive_search_input) : "";

try {
    ensure_layout_materials_archive_table($conn);

    if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["archive_action"] ?? "") === "restore") {
        $archive_id_input = $_POST["archive_id"] ?? "";
        $archive_id = is_string($archive_id_input)
            ? filter_var($archive_id_input, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])
            : false;

        if ($archive_id === false) {
            $archive_action_error = "The selected archived material could not be identified.";
        } else {
            $transaction_started = false;
            try {
                if (!$conn->begin_transaction()) {
                    throw new mysqli_sql_exception($conn->error);
                }
                $transaction_started = true;

                $lock_archived = $conn->prepare(
                    "SELECT `material` FROM `layout_materials_archive` "
                    . "WHERE `archive_id` = ? FOR UPDATE"
                );
                if ($lock_archived === false) {
                    throw new mysqli_sql_exception($conn->error);
                }
                $lock_archived->bind_param("i", $archive_id);
                if (!$lock_archived->execute()) {
                    throw new mysqli_sql_exception($lock_archived->error);
                }
                $lock_archived->store_result();
                if ($lock_archived->num_rows !== 1) {
                    $lock_archived->close();
                    throw new RuntimeException("This archived material is no longer available.");
                }
                $lock_archived->bind_result($restored_material);
                if (!$lock_archived->fetch()) {
                    $lock_archived->close();
                    throw new mysqli_sql_exception("The archived material could not be read.");
                }
                $lock_archived->close();

                $find_duplicate = $conn->prepare(
                    "SELECT 1 FROM `layout_materials_holder` WHERE `material` = ? LIMIT 1 FOR UPDATE"
                );
                if ($find_duplicate === false) {
                    throw new mysqli_sql_exception($conn->error);
                }
                $find_duplicate->bind_param("s", $restored_material);
                if (!$find_duplicate->execute()) {
                    throw new mysqli_sql_exception($find_duplicate->error);
                }
                $find_duplicate->store_result();
                if ($find_duplicate->num_rows > 0) {
                    $find_duplicate->close();
                    throw new RuntimeException("A material with that name already exists. Remove or rename it before restoring.");
                }
                $find_duplicate->close();

                $restore_material = $conn->prepare(
                    "INSERT INTO `layout_materials_holder` (`material`, `quantity`) "
                    . "SELECT `material`, `quantity` FROM `layout_materials_archive` "
                    . "WHERE `archive_id` = ?"
                );
                if ($restore_material === false) {
                    throw new mysqli_sql_exception($conn->error);
                }
                $restore_material->bind_param("i", $archive_id);
                if (!$restore_material->execute()) {
                    throw new mysqli_sql_exception($restore_material->error);
                }
                if ($restore_material->affected_rows !== 1) {
                    $restore_material->close();
                    throw new RuntimeException("The archived material could not be restored.");
                }
                $restore_material->close();

                $remove_archived = $conn->prepare(
                    "DELETE FROM `layout_materials_archive` WHERE `archive_id` = ?"
                );
                if ($remove_archived === false) {
                    throw new mysqli_sql_exception($conn->error);
                }
                $remove_archived->bind_param("i", $archive_id);
                if (!$remove_archived->execute()) {
                    throw new mysqli_sql_exception($remove_archived->error);
                }
                if ($remove_archived->affected_rows !== 1) {
                    $remove_archived->close();
                    throw new RuntimeException("The archived entry could not be removed after restoring.");
                }
                $remove_archived->close();

                if (!$conn->commit()) {
                    throw new mysqli_sql_exception($conn->error);
                }
                $transaction_started = false;
                $archive_action_success = "Material restored to inventory.";
            } catch (mysqli_sql_exception $exception) {
                if ($transaction_started) {
                    try {
                        $conn->rollback();
                    } catch (mysqli_sql_exception $rollback_exception) {
                        error_log("Unable to roll back Layout material restore: " . $rollback_exception->getMessage());
                    }
                }
                error_log("Unable to restore Layout material: " . $exception->getMessage());
                $archive_action_error = "The material could not be restored. Check the inventory table and try again.";
            } catch (RuntimeException $exception) {
                if ($transaction_started) {
                    try {
                        $conn->rollback();
                    } catch (mysqli_sql_exception $rollback_exception) {
                        error_log("Unable to roll back Layout material restore: " . $rollback_exception->getMessage());
                    }
                }
                $archive_action_error = $exception->getMessage();
            }
        }
    }

    if ($archive_search !== "") {
        $search_pattern = "%" . $archive_search . "%";
        $query = $conn->prepare(
            "SELECT `archive_id`, `material`, `quantity` FROM `layout_materials_archive` "
            . "WHERE `material` LIKE ? ORDER BY `deleted_at` DESC, `archive_id` DESC"
        );
        if ($query === false) {
            throw new mysqli_sql_exception($conn->error);
        }
        $query->bind_param("s", $search_pattern);
        if (!$query->execute()) {
            throw new mysqli_sql_exception($query->error);
        }
        $result = $query->get_result();
        if ($result === false) {
            throw new mysqli_sql_exception($query->error);
        }
        $query->close();
    } else {
        $result = $conn->query(
            "SELECT `archive_id`, `material`, `quantity` FROM `layout_materials_archive` "
            . "ORDER BY `deleted_at` DESC, `archive_id` DESC"
        );
        if ($result === false) {
            throw new mysqli_sql_exception($conn->error);
        }
    }

    while ($row = $result->fetch_assoc()) {
        $archived_materials[] = $row;
    }
} catch (mysqli_sql_exception $exception) {
    error_log("Unable to load Layout materials archive: " . $exception->getMessage());
    $archive_error = "Archived materials could not be loaded. Check the archive table and try again.";
}

$profileName = trim((string) ($_SESSION["fullname"] ?? ""));
if ($profileName === "") {
    $profileName = trim((string) ($_SESSION["username"] ?? "Layout Account"));
}
$profileUsername = trim((string) ($_SESSION["username"] ?? ""));
$profileInitials = strtoupper(substr($profileName, 0, 1));
if (preg_match('/\s+(\S)/', $profileName, $profileMatches) === 1) {
    $profileInitials .= strtoupper($profileMatches[1]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f8f5ef">
    <title>Archive | ISTILO @ Nasugbu Enterprises</title>
    <link rel="stylesheet" href="../bootstrap_template/css/bootstrap.min.css">
    <link rel="stylesheet" href="layout_artist.css?v=2">
    <link rel="stylesheet" href="layout_materials.css">
</head>
<body>
    <div class="studio-shell">
        <aside class="studio-sidebar">
            <a class="studio-brand" href="layout_work.php">
                <span class="brand-mark">I</span>
                <span>ISTILO<small>@ NASUGBU ENTERPRISES</small></span>
            </a>

            <p class="menu-label">WORKSPACE</p>
            <nav class="studio-nav" aria-label="Layout workspace">
                <a class="studio-nav-link" href="layout_work.php"><span aria-hidden="true">⌂</span> Dashboard</a>
                <a class="studio-nav-link" href="layout_materials.php"><span aria-hidden="true">◇</span> Materials</a>
                <a class="studio-nav-link selected" href="layout_materials_archive.php" aria-current="page"><span aria-hidden="true">▤</span> Archive</a>
            </nav>

            <form class="studio-logout" method="POST">
                <button class="btn btn-danger btn-sm w-100" name="logout" type="submit">Logout</button>
            </form>

            <div class="artist-profile">
                <span class="artist-avatar"><?= htmlspecialchars($profileInitials, ENT_QUOTES, "UTF-8") ?></span>
                <span><strong><?= htmlspecialchars($profileName, ENT_QUOTES, "UTF-8") ?></strong><small><?= $profileUsername !== "" ? "@" . htmlspecialchars($profileUsername, ENT_QUOTES, "UTF-8") . " · " : "" ?>Layout account</small></span>
            </div>
        </aside>

        <main class="studio-main work-page-main materials-main">
            <section class="screen-section" aria-labelledby="archive-title">
                <header class="studio-header materials-header">
                    <div>
                        <p class="date-label">LAYOUT STUDIO · SUPPLIES</p>
                        <h1 id="archive-title">Archive</h1>
                        <p class="header-subtitle">Review materials removed from your inventory.</p>
                    </div>
                    <a class="materials-add-button" href="layout_materials.php">Back to materials</a>
                </header>

                <section class="panel dashboard-panel invoice-panel materials-panel" aria-labelledby="archive-list-title">
                    <div class="materials-panel-heading">
                        <div>
                            <p class="section-kicker">ARCHIVED ITEMS</p>
                            <h2 id="archive-list-title">Materials archive</h2>
                            <p>Restore an archived material to return it to your inventory.</p>
                        </div>
                        <form class="materials-search" method="get" role="search">
                            <label class="visually-hidden" for="archiveSearch">Search archived materials</label>
                            <span aria-hidden="true">⌕</span>
                            <input id="archiveSearch" type="search" name="search" placeholder="Search archive..." value="<?= htmlspecialchars($archive_search, ENT_QUOTES, "UTF-8") ?>">
                            <?php if ($archive_search !== ""): ?>
                                <a class="materials-clear-search" href="layout_materials_archive.php">Clear</a>
                            <?php endif; ?>
                            <button type="submit">Search</button>
                        </form>
                    </div>

                    <?php if ($archive_error !== ""): ?>
                        <div class="materials-empty-state" role="alert">
                            <span aria-hidden="true">!</span>
                            <strong>Archive unavailable</strong>
                            <p><?= htmlspecialchars($archive_error, ENT_QUOTES, "UTF-8") ?></p>
                        </div>
                    <?php else: ?>
                        <?php if ($archive_action_error !== ""): ?>
                            <p class="alert alert-danger" role="alert"><?= htmlspecialchars($archive_action_error, ENT_QUOTES, "UTF-8") ?></p>
                        <?php elseif ($archive_action_success !== ""): ?>
                            <p class="alert alert-success" role="status"><?= htmlspecialchars($archive_action_success, ENT_QUOTES, "UTF-8") ?></p>
                        <?php endif; ?>
                        <div class="table-responsive">
                            <table class="table invoice-list materials-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Material</th>
                                        <th scope="col">Quantity</th>
                                        <th scope="col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($archived_materials) > 0): ?>
                                        <?php foreach ($archived_materials as $row): ?>
                                            <tr>
                                                <td>
                                                    <span class="materials-name-mark" aria-hidden="true">✦</span>
                                                    <strong><?= htmlspecialchars((string) ($row["material"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong>
                                                </td>
                                                <td><span class="materials-quantity"><?= htmlspecialchars((string) ($row["quantity"] ?? "0"), ENT_QUOTES, "UTF-8") ?></span></td>
                                                <td>
                                                    <form class="materials-actions" method="post">
                                                        <input type="hidden" name="archive_action" value="restore">
                                                        <input type="hidden" name="archive_id" value="<?= htmlspecialchars((string) ($row["archive_id"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                                                        <input type="hidden" name="search" value="<?= htmlspecialchars($archive_search, ENT_QUOTES, "UTF-8") ?>">
                                                        <button class="materials-action-button materials-update-button" type="submit">Restore</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3">
                                                <div class="materials-empty-state">
                                                    <span aria-hidden="true">▤</span>
                                                    <strong><?= $archive_search !== "" ? "No matching archived materials" : "Your archive is empty" ?></strong>
                                                    <p><?= $archive_search !== "" ? "Try another search term or clear the search." : "Materials you remove from inventory will appear here." ?></p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
            </section>
        </main>
    </div>
</body>
</html>
