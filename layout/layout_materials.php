<?php
session_start();

require "../logout.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "layout") {
    header("Location: /ISTILO_SYSTEM/Login/html/login.php");
    exit();
}

require "layout_materials_backend.php";

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
    <title>Materials | ISTILO @ Nasugbu Enterprises</title>
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
                <a class="studio-nav-link selected" href="layout_materials.php" aria-current="page"><span aria-hidden="true">◇</span> Materials</a>
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
            <section class="screen-section" aria-labelledby="materials-title">
                <header class="studio-header materials-header">
                    <div>
                        <p class="date-label">LAYOUT STUDIO · SUPPLIES</p>
                        <h1 id="materials-title">Materials</h1>
                        <p class="header-subtitle">A tidy view of the supplies ready for your next project.</p>
                    </div>
                    <button class="materials-add-button" type="button" data-bs-toggle="modal" data-bs-target="#addMaterialModal" <?= $materials_error !== "" ? "disabled" : "" ?>>
                        <span aria-hidden="true">＋</span> Add material
                    </button>
                </header>

                <?php if ($materials_action_error !== ""): ?>
                    <p class="alert alert-danger" role="alert"><?= htmlspecialchars($materials_action_error, ENT_QUOTES, "UTF-8") ?></p>
                <?php elseif ($materials_action_success !== ""): ?>
                    <p class="alert alert-success" role="status"><?= htmlspecialchars($materials_action_success, ENT_QUOTES, "UTF-8") ?></p>
                <?php endif; ?>

                <section class="panel dashboard-panel invoice-panel materials-panel" aria-labelledby="materials-inventory-title">
                    <div class="materials-panel-heading">
                        <div>
                            <p class="section-kicker">INVENTORY</p>
                            <h2 id="materials-inventory-title">Materials inventory</h2>
                            <p>Search, update, and record material use.</p>
                        </div>
                        <form class="materials-search" method="get" role="search">
                            <label class="visually-hidden" for="materialsSearch">Search materials</label>
                            <span aria-hidden="true">⌕</span>
                            <input id="materialsSearch" type="search" name="search" placeholder="Search materials..." value="<?= htmlspecialchars($materials_search, ENT_QUOTES, "UTF-8") ?>">
                            <?php if ($materials_search !== ""): ?>
                                <a class="materials-clear-search" href="layout_materials.php">Clear</a>
                            <?php endif; ?>
                            <button type="submit">Search</button>
                        </form>
                    </div>

                    <?php if ($materials_error !== ""): ?>
                        <div class="materials-empty-state" role="alert">
                            <span aria-hidden="true">!</span>
                            <strong>Inventory unavailable</strong>
                            <p><?= htmlspecialchars($materials_error, ENT_QUOTES, "UTF-8") ?></p>
                        </div>
                    <?php else: ?>
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
                                    <?php if (count($materials) > 0): ?>
                                        <?php foreach ($materials as $row): ?>
                                            <?php
                                            $materialKey = (string) ($row["material_key"] ?? "");
                                            $materialName = (string) ($row["material"] ?? "");
                                            $materialQuantity = (string) ($row["quantity"] ?? "0");
                                            ?>
                                            <tr>
                                                <td>
                                                    <span class="materials-name-mark" aria-hidden="true">✦</span>
                                                    <strong><?= htmlspecialchars($materialName, ENT_QUOTES, "UTF-8") ?></strong>
                                                </td>
                                                <td><span class="materials-quantity"><?= htmlspecialchars($materialQuantity, ENT_QUOTES, "UTF-8") ?></span></td>
                                                <td>
                                                    <div class="materials-actions">
                                                        <button
                                                            class="materials-action-button materials-update-button"
                                                            type="button"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#updateMaterialModal"
                                                            data-material-key="<?= htmlspecialchars($materialKey, ENT_QUOTES, "UTF-8") ?>"
                                                            data-material="<?= htmlspecialchars($materialName, ENT_QUOTES, "UTF-8") ?>"
                                                            data-quantity="<?= htmlspecialchars($materialQuantity, ENT_QUOTES, "UTF-8") ?>"
                                                        >Update</button>
                                                        <button
                                                            class="materials-action-button materials-use-button"
                                                            type="button"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#useMaterialModal"
                                                            data-material-key="<?= htmlspecialchars($materialKey, ENT_QUOTES, "UTF-8") ?>"
                                                            data-material="<?= htmlspecialchars($materialName, ENT_QUOTES, "UTF-8") ?>"
                                                            data-quantity="<?= htmlspecialchars($materialQuantity, ENT_QUOTES, "UTF-8") ?>"
                                                            <?= (int) $materialQuantity < 1 ? "disabled" : "" ?>
                                                        >Use</button>
                                                        <button
                                                            class="materials-action-button materials-delete-button"
                                                            type="button"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#deleteMaterialModal"
                                                            data-material-key="<?= htmlspecialchars($materialKey, ENT_QUOTES, "UTF-8") ?>"
                                                            data-material="<?= htmlspecialchars($materialName, ENT_QUOTES, "UTF-8") ?>"
                                                        >Delete</button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3">
                                                <div class="materials-empty-state">
                                                    <span aria-hidden="true">◇</span>
                                                    <strong><?= $materials_search !== "" ? "No matching materials" : "Your inventory is ready to begin" ?></strong>
                                                    <p><?= $materials_search !== "" ? "Try another search term or clear the search." : "Add a material to start tracking supplies." ?></p>
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

    <div class="modal fade" id="addMaterialModal" tabindex="-1" aria-labelledby="addMaterialModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content materials-modal">
                <form method="post">
                    <div class="modal-header">
                        <div>
                            <p class="section-kicker mb-1">INVENTORY ENTRY</p>
                            <h2 class="modal-title fs-5" id="addMaterialModalTitle">Add material</h2>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="material_action" value="add">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($materials_search, ENT_QUOTES, "UTF-8") ?>">
                        <label class="form-label" for="newMaterialName">Material name</label>
                        <input class="form-control" id="newMaterialName" name="material" type="text" maxlength="255" value="<?= htmlspecialchars($materials_modal_to_open === "addMaterialModal" ? $materials_form_values["material"] : "", ENT_QUOTES, "UTF-8") ?>" required>
                        <label class="form-label mt-3" for="newMaterialQuantity">Starting quantity</label>
                        <input class="form-control" id="newMaterialQuantity" name="quantity" type="number" min="0" step="1" value="<?= htmlspecialchars($materials_modal_to_open === "addMaterialModal" ? $materials_form_values["quantity"] : "", ENT_QUOTES, "UTF-8") ?>" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="materials-action-button" data-bs-dismiss="modal">Cancel</button>
                        <button class="materials-add-button" type="submit">Add to inventory</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="updateMaterialModal" tabindex="-1" aria-labelledby="updateMaterialModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content materials-modal">
                <form method="post">
                    <div class="modal-header">
                        <div>
                            <p class="section-kicker mb-1">EDIT INVENTORY</p>
                            <h2 class="modal-title fs-5" id="updateMaterialModalTitle">Update material</h2>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="material_action" value="update">
                        <input type="hidden" name="material_key" id="updateMaterialKey" value="<?= htmlspecialchars($materials_modal_to_open === "updateMaterialModal" ? $materials_form_values["material_key"] : "", ENT_QUOTES, "UTF-8") ?>">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($materials_search, ENT_QUOTES, "UTF-8") ?>">
                        <label class="form-label" for="updateMaterialName">Material name</label>
                        <input class="form-control" id="updateMaterialName" name="material" type="text" maxlength="255" value="<?= htmlspecialchars($materials_modal_to_open === "updateMaterialModal" ? $materials_form_values["material"] : "", ENT_QUOTES, "UTF-8") ?>" required>
                        <label class="form-label mt-3" for="updateMaterialQuantity">Quantity</label>
                        <input class="form-control" id="updateMaterialQuantity" name="quantity" type="number" min="0" step="1" value="<?= htmlspecialchars($materials_modal_to_open === "updateMaterialModal" ? $materials_form_values["quantity"] : "", ENT_QUOTES, "UTF-8") ?>" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="materials-action-button" data-bs-dismiss="modal">Cancel</button>
                        <button class="materials-add-button" type="submit">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="useMaterialModal" tabindex="-1" aria-labelledby="useMaterialModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content materials-modal">
                <form method="post">
                    <div class="modal-header">
                        <div>
                            <p class="section-kicker mb-1">RECORD MATERIAL USE</p>
                            <h2 class="modal-title fs-5" id="useMaterialModalTitle">Use material</h2>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="material_action" value="use">
                        <input type="hidden" name="material_key" id="useMaterialKey" value="<?= htmlspecialchars($materials_modal_to_open === "useMaterialModal" ? $materials_form_values["material_key"] : "", ENT_QUOTES, "UTF-8") ?>">
                        <input type="hidden" name="material" id="useMaterialNameInput" value="<?= htmlspecialchars($materials_modal_to_open === "useMaterialModal" ? $materials_form_values["material"] : "", ENT_QUOTES, "UTF-8") ?>">
                        <input type="hidden" name="available_quantity" id="useMaterialAvailableInput" value="<?= htmlspecialchars($materials_modal_to_open === "useMaterialModal" ? $materials_form_values["available_quantity"] : "", ENT_QUOTES, "UTF-8") ?>">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($materials_search, ENT_QUOTES, "UTF-8") ?>">
                        <p class="materials-use-copy">Record the amount used for <strong id="useMaterialName"><?= htmlspecialchars($materials_modal_to_open === "useMaterialModal" ? $materials_form_values["material"] : "", ENT_QUOTES, "UTF-8") ?></strong>.</p>
                        <p class="materials-available">Available: <strong id="useMaterialAvailable"><?= htmlspecialchars($materials_modal_to_open === "useMaterialModal" ? $materials_form_values["available_quantity"] : "0", ENT_QUOTES, "UTF-8") ?></strong></p>
                        <label class="form-label" for="useMaterialQuantity">Quantity to use</label>
                        <input class="form-control" id="useMaterialQuantity" name="quantity" type="number" min="1" step="1" value="<?= htmlspecialchars($materials_modal_to_open === "useMaterialModal" ? $materials_form_values["quantity"] : "", ENT_QUOTES, "UTF-8") ?>" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="materials-action-button" data-bs-dismiss="modal">Cancel</button>
                        <button class="materials-use-button materials-modal-use-button" type="submit">Record use</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteMaterialModal" tabindex="-1" aria-labelledby="deleteMaterialModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content materials-modal">
                <form method="post">
                    <div class="modal-header">
                        <div>
                            <p class="section-kicker mb-1">REMOVE FROM INVENTORY</p>
                            <h2 class="modal-title fs-5" id="deleteMaterialModalTitle">Delete material?</h2>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="material_action" value="delete">
                        <input type="hidden" name="material_key" id="deleteMaterialKey" value="<?= htmlspecialchars($materials_modal_to_open === "deleteMaterialModal" ? $materials_form_values["material_key"] : "", ENT_QUOTES, "UTF-8") ?>">
                        <input type="hidden" name="material" id="deleteMaterialNameInput" value="<?= htmlspecialchars($materials_modal_to_open === "deleteMaterialModal" ? $materials_form_values["material"] : "", ENT_QUOTES, "UTF-8") ?>">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($materials_search, ENT_QUOTES, "UTF-8") ?>">
                        <p class="materials-delete-copy">This will permanently remove <strong id="deleteMaterialName"><?= htmlspecialchars($materials_modal_to_open === "deleteMaterialModal" ? $materials_form_values["material"] : "", ENT_QUOTES, "UTF-8") ?></strong> from the inventory.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="materials-action-button" data-bs-dismiss="modal">Cancel</button>
                        <button class="materials-action-button materials-delete-button materials-modal-delete-button" type="submit">Delete material</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../bootstrap_template/js/bootstrap.bundle.min.js"></script>
    <script src="layout_materials.js"></script>
    <?php if ($materials_modal_to_open !== ""): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                var modal = document.getElementById(<?= json_encode($materials_modal_to_open) ?>);
                if (modal) {
                    bootstrap.Modal.getOrCreateInstance(modal).show();
                }
            });
        </script>
    <?php endif; ?>
</body>
</html>
