<?php
session_start();
require "../logout.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "production") {
    header("Location: /ISTILO_SYSTEM/Login/html/login.php");
    exit();
}

require_once __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "dbconn.php";

$profileName = trim((string) ($_SESSION["fullname"] ?? ""));
if ($profileName === "") {
    $profileName = trim((string) ($_SESSION["username"] ?? "Production Account"));
}
$profileUsername = trim((string) ($_SESSION["username"] ?? ""));
$profileInitials = strtoupper(substr($profileName, 0, 1));
if (preg_match('/\s+(\S)/', $profileName, $profileMatches) === 1) {
    $profileInitials .= strtoupper($profileMatches[1]);
}

function formatAttendanceTime($value): string
{
    if ($value === null) {
        return "—";
    }

    $time = trim((string) $value);
    if ($time === "" || $time === "00:00:00" || $time === "0000-00-00 00:00:00") {
        return "—";
    }

    $timestamp = strtotime($time);
    return $timestamp === false ? $time : date("g:i A", $timestamp);
}

function hasAttendanceTime($value): bool
{
    if ($value === null) {
        return false;
    }

    $time = trim((string) $value);
    return $time !== ""
        && $time !== "00:00:00"
        && $time !== "0000-00-00 00:00:00";
}

$attendance_rows = [];
$attendance_error = "";
$attendance_action_error = "";
$attendance_action_success = "";
$attendance_modal_to_open = "";
$attendance_form_values = [
    "emp_name" => "",
    "task" => "",
    "attendance_key" => "",
];
$attendance_key_column = "emp_name";
$checked_in_count = 0;

try {
    $columnsResult = $conn->query("SHOW COLUMNS FROM `production_attendance`");
    if ($columnsResult === false) {
        throw new mysqli_sql_exception($conn->error);
    }

    $columns = [];
    $primaryKeyColumns = [];
    while ($column = $columnsResult->fetch_assoc()) {
        $columnName = (string) $column["Field"];
        $columns[$columnName] = true;
        if ($column["Key"] === "PRI") {
            $primaryKeyColumns[] = $columnName;
        }
    }

    foreach (["emp_name", "task", "time_int", "time_out"] as $requiredColumn) {
        if (!isset($columns[$requiredColumn])) {
            throw new mysqli_sql_exception("The production_attendance table is missing the " . $requiredColumn . " column.");
        }
    }
    if (count($primaryKeyColumns) === 1) {
        $attendance_key_column = $primaryKeyColumns[0];
    }
    if (!preg_match('/\A[A-Za-z0-9_]+\z/', $attendance_key_column)) {
        throw new mysqli_sql_exception("The attendance row identifier is invalid.");
    }
} catch (mysqli_sql_exception $exception) {
    error_log("Unable to inspect Production attendance table: " . $exception->getMessage());
    $attendance_error = "Attendance records could not be loaded. Check the production_attendance table.";
}

if ($attendance_error === "" && $_SERVER["REQUEST_METHOD"] === "POST") {
    $action = is_string($_POST["attendance_action"] ?? null) ? $_POST["attendance_action"] : "";
    $employeeName = is_string($_POST["emp_name"] ?? null) ? trim($_POST["emp_name"]) : "";
    $task = is_string($_POST["task"] ?? null) ? trim($_POST["task"]) : "";
    $attendanceKey = is_string($_POST["attendance_key"] ?? null)
        ? trim($_POST["attendance_key"])
        : "";

    $attendance_form_values = [
        "emp_name" => $employeeName,
        "task" => $task,
        "attendance_key" => $attendanceKey,
    ];
    if ($action === "add_employee") {
        $attendance_modal_to_open = "addEmployeeModal";
        if ($employeeName === "") {
            $attendance_action_error = "Enter an employee name.";
        } elseif (strlen($employeeName) > 255) {
            $attendance_action_error = "Employee names must be 255 characters or fewer.";
        }
    } elseif ($action === "save_task") {
        $attendance_modal_to_open = "assignedTaskModal";
        if ($attendanceKey === "") {
            $attendance_action_error = "The employee could not be identified.";
        }
    } elseif (in_array($action, ["check_in", "check_out"], true)) {
        if ($attendanceKey === "") {
            $attendance_action_error = "The employee could not be identified.";
        }
    } else {
        $attendance_action_error = "Choose a valid attendance action.";
    }

    if ($attendance_action_error === "") {
        $transactionStarted = false;
        try {
            if (!$conn->begin_transaction()) {
                throw new mysqli_sql_exception($conn->error);
            }
            $transactionStarted = true;

            if ($action === "add_employee") {
                $findEmployee = $conn->prepare(
                    "SELECT 1 FROM `production_attendance` WHERE `emp_name` = ? FOR UPDATE"
                );
                if ($findEmployee === false) {
                    throw new mysqli_sql_exception($conn->error);
                }
                $findEmployee->bind_param("s", $employeeName);
                if (!$findEmployee->execute()) {
                    throw new mysqli_sql_exception($findEmployee->error);
                }
                $findEmployee->store_result();
                if ($findEmployee->num_rows > 0) {
                    $findEmployee->close();
                    throw new RuntimeException("An employee with that name already exists.");
                }
                $findEmployee->close();

                $insertEmployee = $conn->prepare(
                    "INSERT INTO `production_attendance` (`emp_name`, `task`) VALUES (?, '')"
                );
                if ($insertEmployee === false) {
                    throw new mysqli_sql_exception($conn->error);
                }
                $insertEmployee->bind_param("s", $employeeName);
                if (!$insertEmployee->execute()) {
                    throw new mysqli_sql_exception($insertEmployee->error);
                }
                if ($insertEmployee->affected_rows !== 1) {
                    $insertEmployee->close();
                    throw new mysqli_sql_exception("The employee could not be added.");
                }
                $insertEmployee->close();
                $attendance_action_success = "Employee added to Production attendance.";
            } else {
                $lockEmployee = $conn->prepare(
                    "SELECT `emp_name`, `time_int`, `time_out` FROM `production_attendance` "
                    . "WHERE `" . $attendance_key_column . "` = ? FOR UPDATE"
                );
                if ($lockEmployee === false) {
                    throw new mysqli_sql_exception($conn->error);
                }
                $lockEmployee->bind_param("s", $attendanceKey);
                if (!$lockEmployee->execute()) {
                    throw new mysqli_sql_exception($lockEmployee->error);
                }
                $lockEmployee->store_result();
                if ($lockEmployee->num_rows !== 1) {
                    $lockEmployee->close();
                    throw new RuntimeException("The selected employee is no longer available or is not uniquely identified.");
                }
                $lockEmployee->bind_result($lockedEmployeeName, $lockedTimeIn, $lockedTimeOut);
                if (!$lockEmployee->fetch()) {
                    $lockEmployee->close();
                    throw new mysqli_sql_exception("The employee attendance record could not be read.");
                }
                $lockEmployee->close();

                if ($action === "save_task") {
                    $updateTask = $conn->prepare(
                        "UPDATE `production_attendance` SET `task` = ? "
                        . "WHERE `" . $attendance_key_column . "` = ?"
                    );
                    if ($updateTask === false) {
                        throw new mysqli_sql_exception($conn->error);
                    }
                    $updateTask->bind_param("ss", $task, $attendanceKey);
                    if (!$updateTask->execute()) {
                        throw new mysqli_sql_exception($updateTask->error);
                    }
                    if ($updateTask->affected_rows > 1) {
                        $updateTask->close();
                        throw new RuntimeException("The employee identifier does not uniquely identify one record.");
                    }
                    $updateTask->close();
                    $attendance_action_success = "Assigned task saved for " . $lockedEmployeeName . ".";
                    $attendance_modal_to_open = "";
                } elseif ($action === "check_in") {
                    $hasTimeIn = hasAttendanceTime($lockedTimeIn);
                    $hasTimeOut = hasAttendanceTime($lockedTimeOut);
                    if ($hasTimeIn && !$hasTimeOut) {
                        throw new RuntimeException($lockedEmployeeName . " is already checked in.");
                    }

                    $checkIn = $conn->prepare(
                        "UPDATE `production_attendance` SET `time_int` = CURRENT_TIME(), `time_out` = NULL "
                        . "WHERE `" . $attendance_key_column . "` = ?"
                    );
                    if ($checkIn === false) {
                        throw new mysqli_sql_exception($conn->error);
                    }
                    $checkIn->bind_param("s", $attendanceKey);
                    if (!$checkIn->execute()) {
                        throw new mysqli_sql_exception($checkIn->error);
                    }
                    if ($checkIn->affected_rows > 1) {
                        $checkIn->close();
                        throw new RuntimeException("The employee identifier does not uniquely identify one record.");
                    }
                    $checkIn->close();
                    $attendance_action_success = $lockedEmployeeName . " checked in.";
                } else {
                    $hasTimeIn = hasAttendanceTime($lockedTimeIn);
                    $hasTimeOut = hasAttendanceTime($lockedTimeOut);
                    if (!$hasTimeIn || $hasTimeOut) {
                        throw new RuntimeException($lockedEmployeeName . " is not currently checked in.");
                    }

                    $checkOut = $conn->prepare(
                        "UPDATE `production_attendance` SET `time_out` = CURRENT_TIME() "
                        . "WHERE `" . $attendance_key_column . "` = ?"
                    );
                    if ($checkOut === false) {
                        throw new mysqli_sql_exception($conn->error);
                    }
                    $checkOut->bind_param("s", $attendanceKey);
                    if (!$checkOut->execute()) {
                        throw new mysqli_sql_exception($checkOut->error);
                    }
                    if ($checkOut->affected_rows > 1) {
                        $checkOut->close();
                        throw new RuntimeException("The employee identifier does not uniquely identify one record.");
                    }
                    $checkOut->close();
                    $attendance_action_success = $lockedEmployeeName . " checked out.";
                }
            }

            if (!$conn->commit()) {
                throw new mysqli_sql_exception($conn->error);
            }
            $transactionStarted = false;
            if ($action !== "save_task") {
                $attendance_modal_to_open = "";
            }
            $attendance_form_values = [
                "emp_name" => "",
                "task" => "",
                "attendance_key" => "",
            ];
        } catch (mysqli_sql_exception $exception) {
            if ($transactionStarted) {
                try {
                    $conn->rollback();
                } catch (mysqli_sql_exception $rollbackException) {
                    error_log("Unable to roll back Production attendance action: " . $rollbackException->getMessage());
                }
            }
            error_log("Unable to save Production attendance action: " . $exception->getMessage());
            $attendance_action_error = "The attendance change could not be saved. Check the production_attendance table.";
        } catch (RuntimeException $exception) {
            if ($transactionStarted) {
                try {
                    $conn->rollback();
                } catch (mysqli_sql_exception $rollbackException) {
                    error_log("Unable to roll back Production attendance action: " . $rollbackException->getMessage());
                }
            }
            $attendance_action_error = $exception->getMessage();
        }
    }
}

if ($attendance_error === "") {
    try {
        $attendanceQuery = $conn->query(
            "SELECT `" . $attendance_key_column . "` AS `attendance_key`, "
            . "`emp_name`, `task`, `time_int`, `time_out` "
            . "FROM `production_attendance` ORDER BY `emp_name` ASC"
        );
        if ($attendanceQuery === false) {
            throw new mysqli_sql_exception($conn->error);
        }

        while ($row = $attendanceQuery->fetch_assoc()) {
            $attendance_rows[] = $row;
            $hasTimeIn = hasAttendanceTime($row["time_int"]);
            $hasTimeOut = hasAttendanceTime($row["time_out"]);
            if ($hasTimeIn && !$hasTimeOut) {
                $checked_in_count++;
            }
        }
    } catch (mysqli_sql_exception $exception) {
        error_log("Unable to load Production attendance records: " . $exception->getMessage());
        $attendance_error = "Attendance records could not be loaded.";
    }
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
    <link rel="stylesheet" href="production.css?v=2">
</head>
<body>
    <div class="production-app">
        <aside class="production-sidebar">
            <a class="brand" href="production.php">
                <span class="brand-stamp">I</span>
                <span>ISTILO<small>@ NASUGBU ENTERPRISES</small></span>
            </a>
            <p class="side-label">WORKSPACE</p>
            <nav class="production-nav" aria-label="Production workspace">
                <a class="nav-item" href="production.php">Job Orders</a>
                <a class="nav-item active" href="attendance.php" aria-current="page"><span>◷</span> Attendance</a>
            </nav>
            <form class="production-logout" method="POST">
                <button class="btn btn-danger" name="logout" type="submit">Logout</button>
            </form>
            <div class="maker-profile">
                <span class="maker-avatar"><?= htmlspecialchars($profileInitials, ENT_QUOTES, "UTF-8") ?></span>
                <span><strong><?= htmlspecialchars($profileName, ENT_QUOTES, "UTF-8") ?></strong><small><?= $profileUsername !== "" ? "@" . htmlspecialchars($profileUsername, ENT_QUOTES, "UTF-8") . " · " : "" ?>Production account</small></span>
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

            <section class="card shadow-sm border-0" aria-labelledby="todayAttendanceTitle">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                    <div>
                        <p class="overline mb-1">DAILY RECORD</p>
                        <h2 class="h5 mb-0" id="todayAttendanceTitle">Today's attendance</h2>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-pill text-bg-success"><?= $checked_in_count ?> / <?= count($attendance_rows) ?> checked in</span>
                        <button class="btn btn-success" type="button" data-bs-toggle="modal" data-bs-target="#addEmployeeModal" <?= $attendance_error !== "" ? "disabled" : "" ?>>Add employee</button>
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
                                <th scope="col">Assigned Task</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($attendance_action_error !== ""): ?>
                                <tr><td colspan="6"><div class="alert alert-danger mb-0" role="alert"><?= htmlspecialchars($attendance_action_error, ENT_QUOTES, "UTF-8") ?></div></td></tr>
                            <?php elseif ($attendance_action_success !== ""): ?>
                                <tr><td colspan="6"><div class="alert alert-success mb-0" role="status"><?= htmlspecialchars($attendance_action_success, ENT_QUOTES, "UTF-8") ?></div></td></tr>
                            <?php endif; ?>
                            <?php if ($attendance_error !== ""): ?>
                                <tr><td colspan="6" class="text-danger"><?= htmlspecialchars($attendance_error, ENT_QUOTES, "UTF-8") ?></td></tr>
                            <?php elseif (count($attendance_rows) > 0): ?>
                                <?php foreach ($attendance_rows as $row): ?>
                                    <?php
                                    $attendanceKey = (string) ($row["attendance_key"] ?? "");
                                    $employeeName = (string) ($row["emp_name"] ?? "");
                                    $assignedTask = (string) ($row["task"] ?? "");
                                    $hasTimeIn = hasAttendanceTime($row["time_int"] ?? null);
                                    $hasTimeOut = hasAttendanceTime($row["time_out"] ?? null);
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($employeeName, ENT_QUOTES, "UTF-8") ?></td>
                                        <td>
                                            <?php if ($hasTimeIn && !$hasTimeOut): ?>
                                                <span class="badge rounded-pill text-bg-success">Working</span>
                                            <?php elseif ($hasTimeIn && $hasTimeOut): ?>
                                                <span class="badge rounded-pill text-bg-primary">Checked out</span>
                                            <?php else: ?>
                                                <span class="badge rounded-pill text-bg-secondary">Not checked in</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars(formatAttendanceTime($row["time_int"] ?? null), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars(formatAttendanceTime($row["time_out"] ?? null), ENT_QUOTES, "UTF-8") ?></td>
                                        <td>
                                            <button
                                                class="btn btn-sm btn-outline-secondary"
                                                type="button"
                                                data-bs-toggle="modal"
                                                data-bs-target="#assignedTaskModal"
                                                data-attendance-key="<?= htmlspecialchars($attendanceKey, ENT_QUOTES, "UTF-8") ?>"
                                                data-employee="<?= htmlspecialchars($employeeName, ENT_QUOTES, "UTF-8") ?>"
                                                data-task="<?= htmlspecialchars($assignedTask, ENT_QUOTES, "UTF-8") ?>"
                                            >View</button>
                                        </td>
                                        <td>
                                            <form method="post">
                                                <input type="hidden" name="attendance_key" value="<?= htmlspecialchars($attendanceKey, ENT_QUOTES, "UTF-8") ?>">
                                                <?php if ($hasTimeIn && !$hasTimeOut): ?>
                                                    <button class="btn btn-sm btn-outline-primary" type="submit" name="attendance_action" value="check_out">Check out</button>
                                                <?php elseif ($hasTimeIn && $hasTimeOut): ?>
                                                    <button class="btn btn-sm btn-outline-secondary" type="button" disabled>Complete</button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-success" type="submit" name="attendance_action" value="check_in">Check in</button>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">No employees in Production attendance yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-labelledby="addEmployeeModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <div>
                            <p class="overline mb-1">TEAM ROSTER</p>
                            <h2 class="modal-title h5 mb-0" id="addEmployeeModalTitle">Add an employee</h2>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="attendance_action" value="add_employee">
                        <label class="form-label" for="employeeName">Employee name</label>
                        <input class="form-control" id="employeeName" name="emp_name" type="text" maxlength="255" value="<?= htmlspecialchars($attendance_modal_to_open === "addEmployeeModal" ? $attendance_form_values["emp_name"] : "", ENT_QUOTES, "UTF-8") ?>" placeholder="Enter employee name" required>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-success" type="submit">Add employee</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="assignedTaskModal" tabindex="-1" aria-labelledby="assignedTaskModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <div>
                            <p class="overline mb-1">PRODUCTION ASSIGNMENT</p>
                            <h2 class="modal-title h5 mb-0" id="assignedTaskModalTitle">Assigned task</h2>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="attendance_action" value="save_task">
                        <input type="hidden" name="attendance_key" id="assignedTaskKey" value="<?= htmlspecialchars($attendance_modal_to_open === "assignedTaskModal" ? $attendance_form_values["attendance_key"] : "", ENT_QUOTES, "UTF-8") ?>">
                        <input type="hidden" name="emp_name" id="assignedTaskEmployeeInput" value="<?= htmlspecialchars($attendance_modal_to_open === "assignedTaskModal" ? $attendance_form_values["emp_name"] : "", ENT_QUOTES, "UTF-8") ?>">
                        <p class="text-muted small mb-1">Team member</p>
                        <p class="fw-semibold" id="assignedTaskEmployee"><?= htmlspecialchars($attendance_modal_to_open === "assignedTaskModal" ? $attendance_form_values["emp_name"] : "", ENT_QUOTES, "UTF-8") ?></p>
                        <label class="form-label" for="assignedTaskDescription">Assigned task</label>
                        <textarea class="form-control" id="assignedTaskDescription" name="task" rows="4" maxlength="2000" placeholder="Describe this employee's task"><?= htmlspecialchars($attendance_modal_to_open === "assignedTaskModal" ? $attendance_form_values["task"] : "", ENT_QUOTES, "UTF-8") ?></textarea>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Close</button>
                        <button class="btn btn-success" type="submit">Save task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../bootstrap_template/js/bootstrap.bundle.min.js"></script>
    <script>
        var assignedTaskModal = document.getElementById("assignedTaskModal");
        assignedTaskModal.addEventListener("show.bs.modal", function (event) {
            var trigger = event.relatedTarget;
            if (!trigger) {
                return;
            }

            var employeeName = trigger.dataset.employee || "";
            document.getElementById("assignedTaskKey").value = trigger.dataset.attendanceKey || "";
            document.getElementById("assignedTaskEmployee").textContent = employeeName;
            document.getElementById("assignedTaskEmployeeInput").value = employeeName;
            document.getElementById("assignedTaskDescription").value = trigger.dataset.task || "";
        });
        <?php if ($attendance_modal_to_open !== ""): ?>
        document.addEventListener("DOMContentLoaded", function () {
            var modal = document.getElementById(<?= json_encode($attendance_modal_to_open) ?>);
            if (modal) {
                bootstrap.Modal.getOrCreateInstance(modal).show();
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
