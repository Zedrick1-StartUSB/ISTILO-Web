<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "dbconn.php";

$materials = [];
$materials_error = "";
$materials_action_error = "";
$materials_action_success = "";
$searchInput = $_GET["search"] ?? $_POST["search"] ?? "";
$materials_search = is_string($searchInput) ? trim($searchInput) : "";
$materials_modal_to_open = "";
$materials_form_values = [
    "material" => "",
    "quantity" => "",
    "material_key" => "",
    "available_quantity" => "",
];
$material_key_column = "material";

try {
    $columnResult = $conn->query("SHOW COLUMNS FROM `layout_materials_holder`");
    if ($columnResult === false) {
        throw new mysqli_sql_exception($conn->error);
    }

    $columns = [];
    $primaryKeyColumns = [];
    while ($column = $columnResult->fetch_assoc()) {
        $columnName = (string) $column["Field"];
        $columns[$columnName] = true;
        if ($column["Key"] === "PRI") {
            $primaryKeyColumns[] = $columnName;
        }
    }

    if (!isset($columns["material"]) || !isset($columns["quantity"])) {
        throw new mysqli_sql_exception("The layout_materials_holder table must contain material and quantity columns.");
    }
    if (count($primaryKeyColumns) === 1) {
        $material_key_column = $primaryKeyColumns[0];
    }
    if (!preg_match('/\A[A-Za-z0-9_]+\z/', $material_key_column)) {
        throw new mysqli_sql_exception("The material identifier column is invalid.");
    }
} catch (mysqli_sql_exception $exception) {
    error_log("Unable to inspect Layout materials table: " . $exception->getMessage());
    $materials_error = "Materials could not be loaded. Check the layout_materials_holder table.";
}

if ($materials_error === "" && $_SERVER["REQUEST_METHOD"] === "POST") {
    $action = is_string($_POST["material_action"] ?? null) ? $_POST["material_action"] : "";
    $materialName = is_string($_POST["material"] ?? null) ? trim($_POST["material"]) : "";
    $quantityInput = is_string($_POST["quantity"] ?? null) ? trim($_POST["quantity"]) : "";
    $materialKey = is_string($_POST["material_key"] ?? null) ? trim($_POST["material_key"]) : "";
    $availableQuantityInput = is_string($_POST["available_quantity"] ?? null)
        ? trim($_POST["available_quantity"])
        : "";
    $materials_form_values["material"] = $materialName;
    $materials_form_values["quantity"] = $quantityInput;
    $materials_form_values["material_key"] = $materialKey;
    $materials_form_values["available_quantity"] = $availableQuantityInput;
    $materials_modal_to_open = [
        "add" => "addMaterialModal",
        "update" => "updateMaterialModal",
        "use" => "useMaterialModal",
        "delete" => "deleteMaterialModal",
    ][$action] ?? "";

    $quantity = filter_var($quantityInput, FILTER_VALIDATE_INT, [
        "options" => ["min_range" => $action === "use" ? 1 : 0],
    ]);

    if ($action === "add" || $action === "update") {
        if ($materialName === "" || $quantity === false) {
            $materials_action_error = "Enter a material name and a whole-number quantity of zero or more.";
        }
    } elseif ($action === "use" && $quantity === false) {
        $materials_action_error = "Enter a whole-number quantity of at least one to use.";
    } elseif (!in_array($action, ["add", "update", "use", "delete"], true)) {
        $materials_action_error = "Choose a valid materials action.";
    } elseif ($action !== "add" && $materialKey === "") {
        $materials_action_error = "The selected material could not be identified.";
    }

    if ($materials_action_error === "") {
        $transactionStarted = false;
        try {
            if (!$conn->begin_transaction()) {
                throw new mysqli_sql_exception($conn->error);
            }
            $transactionStarted = true;

            if ($action === "add") {
                $findDuplicate = $conn->prepare(
                    "SELECT 1 FROM `layout_materials_holder` WHERE `material` = ? FOR UPDATE"
                );
                if ($findDuplicate === false) {
                    throw new mysqli_sql_exception($conn->error);
                }
                $findDuplicate->bind_param("s", $materialName);
                if (!$findDuplicate->execute()) {
                    throw new mysqli_sql_exception($findDuplicate->error);
                }
                $findDuplicate->store_result();
                if ($findDuplicate->num_rows > 0) {
                    $findDuplicate->close();
                    throw new RuntimeException("A material with that name already exists.");
                }
                $findDuplicate->close();

                $insertMaterial = $conn->prepare(
                    "INSERT INTO `layout_materials_holder` (`material`, `quantity`) VALUES (?, ?)"
                );
                if ($insertMaterial === false) {
                    throw new mysqli_sql_exception($conn->error);
                }
                $insertMaterial->bind_param("si", $materialName, $quantity);
                if (!$insertMaterial->execute()) {
                    throw new mysqli_sql_exception($insertMaterial->error);
                }
                $insertMaterial->close();
                $materials_action_success = "Material added to inventory.";
            } elseif ($action === "update") {
                $lockMaterial = $conn->prepare(
                    "SELECT 1 FROM `layout_materials_holder` "
                    . "WHERE `" . $material_key_column . "` = ? FOR UPDATE"
                );
                if ($lockMaterial === false) {
                    throw new mysqli_sql_exception($conn->error);
                }
                $lockMaterial->bind_param("s", $materialKey);
                if (!$lockMaterial->execute()) {
                    throw new mysqli_sql_exception($lockMaterial->error);
                }
                $lockMaterial->store_result();
                if ($lockMaterial->num_rows !== 1) {
                    $lockMaterial->close();
                    throw new RuntimeException("The selected material is no longer available or is not uniquely identified.");
                }
                $lockMaterial->close();

                $findDuplicate = $conn->prepare(
                    "SELECT 1 FROM `layout_materials_holder` "
                    . "WHERE `material` = ? AND `" . $material_key_column . "` <> ? LIMIT 1"
                );
                if ($findDuplicate === false) {
                    throw new mysqli_sql_exception($conn->error);
                }
                $findDuplicate->bind_param("ss", $materialName, $materialKey);
                if (!$findDuplicate->execute()) {
                    throw new mysqli_sql_exception($findDuplicate->error);
                }
                $findDuplicate->store_result();
                if ($findDuplicate->num_rows > 0) {
                    $findDuplicate->close();
                    throw new RuntimeException("Another material already uses that name.");
                }
                $findDuplicate->close();

                $updateMaterial = $conn->prepare(
                    "UPDATE `layout_materials_holder` SET `material` = ?, `quantity` = ? "
                    . "WHERE `" . $material_key_column . "` = ?"
                );
                if ($updateMaterial === false) {
                    throw new mysqli_sql_exception($conn->error);
                }
                $updateMaterial->bind_param("sis", $materialName, $quantity, $materialKey);
                if (!$updateMaterial->execute()) {
                    throw new mysqli_sql_exception($updateMaterial->error);
                }
                if ($updateMaterial->affected_rows > 1) {
                    $updateMaterial->close();
                    throw new RuntimeException("The material identifier does not uniquely identify one row.");
                }
                $updateMaterial->close();
                $materials_action_success = "Material details updated.";
            } elseif ($action === "use") {
                $lockMaterial = $conn->prepare(
                    "SELECT `quantity` FROM `layout_materials_holder` "
                    . "WHERE `" . $material_key_column . "` = ? FOR UPDATE"
                );
                if ($lockMaterial === false) {
                    throw new mysqli_sql_exception($conn->error);
                }
                $lockMaterial->bind_param("s", $materialKey);
                if (!$lockMaterial->execute()) {
                    throw new mysqli_sql_exception($lockMaterial->error);
                }
                $lockMaterial->store_result();
                if ($lockMaterial->num_rows !== 1) {
                    $lockMaterial->close();
                    throw new RuntimeException("The selected material is no longer available or is not uniquely identified.");
                }
                $lockMaterial->bind_result($availableQuantity);
                if (!$lockMaterial->fetch()) {
                    $lockMaterial->close();
                    throw new mysqli_sql_exception("The material quantity could not be read.");
                }
                $lockMaterial->close();

                if ((int) $availableQuantity < $quantity) {
                    throw new RuntimeException("There is not enough stock to use that quantity.");
                }

                $useMaterial = $conn->prepare(
                    "UPDATE `layout_materials_holder` SET `quantity` = `quantity` - ? "
                    . "WHERE `" . $material_key_column . "` = ? AND `quantity` >= ?"
                );
                if ($useMaterial === false) {
                    throw new mysqli_sql_exception($conn->error);
                }
                $useMaterial->bind_param("isi", $quantity, $materialKey, $quantity);
                if (!$useMaterial->execute()) {
                    throw new mysqli_sql_exception($useMaterial->error);
                }
                if ($useMaterial->affected_rows !== 1) {
                    $useMaterial->close();
                    throw new RuntimeException("The material quantity could not be reduced.");
                }
                $useMaterial->close();
                $materials_action_success = "Material use recorded and inventory quantity reduced.";
            } else {
                $lockMaterial = $conn->prepare(
                    "SELECT 1 FROM `layout_materials_holder` "
                    . "WHERE `" . $material_key_column . "` = ? FOR UPDATE"
                );
                if ($lockMaterial === false) {
                    throw new mysqli_sql_exception($conn->error);
                }
                $lockMaterial->bind_param("s", $materialKey);
                if (!$lockMaterial->execute()) {
                    throw new mysqli_sql_exception($lockMaterial->error);
                }
                $lockMaterial->store_result();
                if ($lockMaterial->num_rows !== 1) {
                    $lockMaterial->close();
                    throw new RuntimeException("The selected material is no longer available or is not uniquely identified.");
                }
                $lockMaterial->close();

                $deleteMaterial = $conn->prepare(
                    "DELETE FROM `layout_materials_holder` WHERE `" . $material_key_column . "` = ?"
                );
                if ($deleteMaterial === false) {
                    throw new mysqli_sql_exception($conn->error);
                }
                $deleteMaterial->bind_param("s", $materialKey);
                if (!$deleteMaterial->execute()) {
                    throw new mysqli_sql_exception($deleteMaterial->error);
                }
                if ($deleteMaterial->affected_rows !== 1) {
                    $deleteMaterial->close();
                    throw new RuntimeException("The material could not be deleted.");
                }
                $deleteMaterial->close();
                $materials_action_success = "Material deleted from inventory.";
            }

            if (!$conn->commit()) {
                throw new mysqli_sql_exception($conn->error);
            }
            $transactionStarted = false;
            $materials_modal_to_open = "";
            $materials_form_values = [
                "material" => "",
                "quantity" => "",
                "material_key" => "",
                "available_quantity" => "",
            ];
        } catch (mysqli_sql_exception $exception) {
            if ($transactionStarted) {
                try {
                    $conn->rollback();
                } catch (mysqli_sql_exception $rollbackException) {
                    error_log("Unable to roll back Layout material action: " . $rollbackException->getMessage());
                }
            }
            error_log("Unable to save Layout material action: " . $exception->getMessage());
            $materials_action_error = "The materials change could not be saved. Check the table and try again.";
        } catch (RuntimeException $exception) {
            if ($transactionStarted) {
                try {
                    $conn->rollback();
                } catch (mysqli_sql_exception $rollbackException) {
                    error_log("Unable to roll back Layout material action: " . $rollbackException->getMessage());
                }
            }
            $materials_action_error = $exception->getMessage();
        }
    }
}

if ($materials_error === "") {
    try {
        if ($materials_search !== "") {
            $searchPattern = "%" . $materials_search . "%";
            $queryMaterials = $conn->prepare(
                "SELECT `" . $material_key_column . "` AS `material_key`, `material`, `quantity` "
                . "FROM `layout_materials_holder` WHERE `material` LIKE ? ORDER BY `material` ASC"
            );
            if ($queryMaterials === false) {
                throw new mysqli_sql_exception($conn->error);
            }
            $queryMaterials->bind_param("s", $searchPattern);
            if (!$queryMaterials->execute()) {
                throw new mysqli_sql_exception($queryMaterials->error);
            }
            $result = $queryMaterials->get_result();
            if ($result === false) {
                throw new mysqli_sql_exception($queryMaterials->error);
            }
        } else {
            $result = $conn->query(
                "SELECT `" . $material_key_column . "` AS `material_key`, `material`, `quantity` "
                . "FROM `layout_materials_holder` ORDER BY `material` ASC"
            );
            if ($result === false) {
                throw new mysqli_sql_exception($conn->error);
            }
        }

        while ($row = $result->fetch_assoc()) {
            $materials[] = $row;
        }
        if ($materials_search !== "") {
            $queryMaterials->close();
        }
    } catch (mysqli_sql_exception $exception) {
        error_log("Unable to load Layout materials: " . $exception->getMessage());
        $materials_error = "Materials could not be loaded.";
    }
}
