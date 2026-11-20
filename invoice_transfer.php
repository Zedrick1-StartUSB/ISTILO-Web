<?php
function copyInvoiceRecordIfMissing(
    mysqli $conn,
    string $sourceTable,
    string $destinationTable,
    string $identifierColumn,
    string $invoiceId,
    array $fields,
    array $additionalValues = []
): bool {
    $supportedTables = ["admin_invoices", "invoices"];
    if (
        !in_array($sourceTable, $supportedTables, true)
        || !in_array($destinationTable, $supportedTables, true)
        || $sourceTable === $destinationTable
    ) {
        throw new mysqli_sql_exception("Unsupported invoice transfer.");
    }

    $fields = array_values(array_unique($fields));
    if ($fields === [] || !in_array($identifierColumn, $fields, true)) {
        throw new mysqli_sql_exception("The invoice transfer fields are invalid.");
    }

    $quotedFields = [];
    foreach (array_merge($fields, array_keys($additionalValues)) as $field) {
        if (!is_string($field) || !preg_match('/\A[A-Za-z0-9_]+\z/', $field)) {
            throw new mysqli_sql_exception("An invoice column name is invalid.");
        }
        $quotedFields[$field] = "`" . $field . "`";
    }
    if (!preg_match('/\A[A-Za-z0-9_]+\z/', $identifierColumn)) {
        throw new mysqli_sql_exception("The invoice identifier column name is invalid.");
    }

    $matchingConditions = [];
    foreach ($fields as $field) {
        $matchingConditions[] = "destination." . $quotedFields[$field]
            . " <=> source." . $quotedFields[$field];
    }

    $findMatchingInvoice = $conn->prepare(
        "SELECT 1 FROM `" . $destinationTable . "` destination "
        . "INNER JOIN `" . $sourceTable . "` source "
        . "ON destination.`" . $identifierColumn . "` = source.`" . $identifierColumn . "` "
        . "WHERE source.`" . $identifierColumn . "` = ? AND "
        . implode(" AND ", $matchingConditions)
        . " FOR UPDATE"
    );
    if ($findMatchingInvoice === false) {
        throw new mysqli_sql_exception($conn->error);
    }
    $findMatchingInvoice->bind_param("s", $invoiceId);
    if (!$findMatchingInvoice->execute()) {
        throw new mysqli_sql_exception($findMatchingInvoice->error);
    }
    $findMatchingInvoice->store_result();
    $invoiceAlreadyCopied = $findMatchingInvoice->num_rows === 1;
    $findMatchingInvoice->close();
    if ($invoiceAlreadyCopied) {
        return false;
    }

    $findExistingInvoice = $conn->prepare(
        "SELECT 1 FROM `" . $destinationTable . "` "
        . "WHERE `" . $identifierColumn . "` = ? FOR UPDATE"
    );
    if ($findExistingInvoice === false) {
        throw new mysqli_sql_exception($conn->error);
    }
    $findExistingInvoice->bind_param("s", $invoiceId);
    if (!$findExistingInvoice->execute()) {
        throw new mysqli_sql_exception($findExistingInvoice->error);
    }
    $findExistingInvoice->store_result();
    $invoiceIdInUse = $findExistingInvoice->num_rows === 1;
    $findExistingInvoice->close();
    if ($invoiceIdInUse) {
        throw new mysqli_sql_exception(
            "This invoice ID is already assigned to a different invoice."
        );
    }

    $targetFields = array_merge(
        array_map(
            static function (string $field): string {
                return "`" . $field . "`";
            },
            $fields
        ),
        array_map(
            static function (string $field): string {
                return "`" . $field . "`";
            },
            array_keys($additionalValues)
        )
    );
    $sourceFields = array_map(
        static function (string $field): string {
            return "`" . $field . "`";
        },
        $fields
    );
    $selectValues = array_merge(
        $sourceFields,
        array_fill(0, count($additionalValues), "?")
    );
    $insertInvoice = $conn->prepare(
        "INSERT INTO `" . $destinationTable . "` (" . implode(", ", $targetFields) . ") "
        . "SELECT " . implode(", ", $selectValues) . " FROM `" . $sourceTable . "` "
        . "WHERE `" . $identifierColumn . "` = ?"
    );
    if ($insertInvoice === false) {
        throw new mysqli_sql_exception($conn->error);
    }

    $parameters = array_values($additionalValues);
    $parameters[] = $invoiceId;
    $bindArguments = [str_repeat("s", count($parameters))];
    foreach ($parameters as &$parameter) {
        $bindArguments[] = &$parameter;
    }
    call_user_func_array([$insertInvoice, "bind_param"], $bindArguments);
    unset($parameter);

    if (!$insertInvoice->execute()) {
        throw new mysqli_sql_exception($insertInvoice->error);
    }
    if ($insertInvoice->affected_rows !== 1) {
        $insertInvoice->close();
        throw new mysqli_sql_exception("The invoice could not be copied.");
    }
    $insertInvoice->close();

    return true;
}
