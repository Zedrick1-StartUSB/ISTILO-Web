<?php
$owner_nav_items = [
    "dashboard" => ["label" => "Dashboard", "href" => "dashboard.php", "icon" => "⌂"],
    "orders" => ["label" => "Orders", "href" => "orders.php", "icon" => "▤"],
    "materials" => ["label" => "Materials", "href" => "materials.php", "icon" => "◇"],
    "users" => ["label" => "Users", "href" => "users.php", "icon" => "♙"],
    "history" => ["label" => "History Logs", "href" => "history_logs.php", "icon" => "◷"],
];
$owner_sidebar_name = trim((string) ($_SESSION["fullname"] ?? ""));
if ($owner_sidebar_name === "") {
    $owner_sidebar_name = trim((string) ($_SESSION["username"] ?? "Owner Account"));
}
$owner_sidebar_username = trim((string) ($_SESSION["username"] ?? ""));
$owner_sidebar_initials = strtoupper(substr($owner_sidebar_name, 0, 1));
if (preg_match('/\s+(\S)/', $owner_sidebar_name, $owner_sidebar_matches) === 1) {
    $owner_sidebar_initials .= strtoupper($owner_sidebar_matches[1]);
}
?>
<aside class="sidebar">
    <a class="brand" href="dashboard.php" aria-label="little label owner dashboard">
        <span class="brand-mark">l.</span>
        <span>little label</span>
    </a>

    <p class="nav-caption">MENU</p>
    <nav class="side-nav" aria-label="Owner navigation">
        <?php foreach ($owner_nav_items as $page => $item): ?>
            <a class="side-link<?= $owner_active_page === $page ? " active" : "" ?>"
                href="<?= htmlspecialchars($item["href"], ENT_QUOTES, "UTF-8") ?>"
                <?= $owner_active_page === $page ? 'aria-current="page"' : "" ?>>
                <span class="nav-icon" aria-hidden="true"><?= htmlspecialchars($item["icon"], ENT_QUOTES, "UTF-8") ?></span>
                <?= htmlspecialchars($item["label"], ENT_QUOTES, "UTF-8") ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <form class="owner-logout" method="POST">
        <button class="btn btn-danger btn-sm" name="logout" type="submit">Logout</button>
    </form>

    <div class="owner-profile">
        <span class="avatar"><?= htmlspecialchars($owner_sidebar_initials, ENT_QUOTES, "UTF-8") ?></span>
        <span class="owner-profile-copy">
            <strong><?= htmlspecialchars($owner_sidebar_name, ENT_QUOTES, "UTF-8") ?></strong>
            <small><?= $owner_sidebar_username !== "" ? "@" . htmlspecialchars($owner_sidebar_username, ENT_QUOTES, "UTF-8") . " · " : "" ?>Owner account</small>
        </span>
    </div>
</aside>
