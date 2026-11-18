<?php

function ensure_layout_materials_archive_table(mysqli $conn): void
{
    $result = $conn->query(
        "CREATE TABLE IF NOT EXISTS `layout_materials_archive` ("
        . "`archive_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,"
        . "`material` TEXT NOT NULL,"
        . "`quantity` TEXT NOT NULL,"
        . "`deleted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,"
        . "PRIMARY KEY (`archive_id`)"
        . ") ENGINE=InnoDB"
    );
    if ($result === false) {
        throw new mysqli_sql_exception($conn->error);
    }
}
