
<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$totalParticipants = 0;
$registeredEvents = 0;
$totalRegistrations = 0;
$registrations = [];
$attendanceRecords = [];
$search = trim($_GET['search'] ?? '');
$error = '';

try {
    $totalParticipants = (int) $pdo->query("
        SELECT COUNT(DISTINCT er.user_id)
        FROM event_registrations er
        INNER JOIN attendance a
            ON a.registration_id = er.registration_id
    ")->fetchColumn();

    $registeredEvents = (int) $pdo->query("
        SELECT COUNT(DISTINCT event_id)
        FROM event_registrations
    ")->fetchColumn();

    $totalRegistrations = (int) $pdo->query("
        SELECT COUNT(*)
        FROM event_registrations
    ")->fetchColumn();

    $sql = "
        SELECT
            u.full_name,
            u.email,
            ev.title AS event_title,
            ev.event_date,
            er.registration_date
        FROM event_registrations er
        INNER JOIN users u
            ON u.user_id = er.user_id
        INNER JOIN events ev
            ON ev.event_id = er.event_id
    ";

    if ($search !== '') {
        $sql .= "
            WHERE u.full_name LIKE :search
            OR u.email LIKE :search
            OR ev.title LIKE :search
        ";
    }

    $sql .= " ORDER BY er.registration_date DESC";

    $stmt = $pdo->prepare($sql);

    if ($search !== '') {
        $stmt->execute(['search' => "%$search%"]);
    } else {
        $stmt->execute();
    }

    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $attendanceSql = "
        SELECT
            u.full_name,
            u.user_id,
            ev.title AS event_title,
            ev.event_date,
            a.recorded_at,
            a.attendance_status
        FROM attendance a
        INNER JOIN event_registrations er
            ON er.registration_id = a.registration_id
        INNER JOIN users u
            ON u.user_id = er.user_id
        INNER JOIN events ev
            ON ev.event_id = er.event_id
    ";

    if ($search !== '') {
        $attendanceSql .= "
            WHERE u.full_name LIKE :search
            OR ev.title LIKE :search
            OR CAST(u.user_id AS CHAR) LIKE :search
        ";
    }

    $attendanceSql .= " ORDER BY a.recorded_at DESC";

    $attendanceStmt = $pdo->prepare($attendanceSql);

    if ($search !== '') {
        $attendanceStmt->execute(['search' => "%$search%"]);
    } else {
        $attendanceStmt->execute();
    }

    $attendanceRecords = $attendanceStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $ex) {
    error_log('Event records error: ' . $ex->getMessage());
    $error = 'May database query error. Pakisuri ang database connection, table names, columns, at relationships.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Event Management / Records | UEP School Events</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: #F8F5F5;
    color: #333;
}

.header {
    background: linear-gradient(135deg, #800000, #5B0018);
    color: white;
    padding: 25px 35px;
}

.header h1 {
    font-size: 27px;
    margin-bottom: 9px;
}

.header p {
    color: #F3DCDC;
    font-size: 14px;
}

.back-link {
    display: inline-block;
    color: white;
    text-decoration: none;
    margin-top: 18px;
    font-size: 13px;
    font-weight: bold;
}

.back-link:hover {
    text-decoration: underline;
}

.container {
    max-width: 1250px;
    margin: 30px auto;
    padding: 0 25px 40px;
}

.error-message {
    background: #FBEAEC;
    color: #800000;
    border: 1px solid #E8BBC4;
    padding: 15px;
    border-radius: 9px;
    margin-bottom: 25px;
    font-size: 14px;
}

.stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 35px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    border-left: 5px solid #800000;
    box-shadow: 0 4px 15px rgba(0,0,0,.06);
}

.stat-card h3 {
    color: #5B0018;
    font-size: 15px;
    margin-bottom: 15px;
}

.stat-card h2 {
    color: #800000;
    font-size: 34px;
    margin-bottom: 8px;
}

.stat-card p {
    color: #888;
    font-size: 12px;
}

.section {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,.05);
}

.section h2 {
    color: #5B0018;
    font-size: 20px;
    margin-bottom: 10px;
}

.section-description {
    color: #888;
    font-size: 13px;
    margin-bottom: 22px;
}

.search-form {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.search-form input {
    flex: 1;
    min-width: 0;
    padding: 12px 14px;
    border: 1px solid #DDD;
    border-radius: 8px;
    font-size: 13px;
    outline: none;
}

.search-form input:focus {
    border-color: #800000;
}

.search-btn {
    background: #800000;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px 22px;
    cursor: pointer;
    font-weight: bold;
}

.search-btn:hover {
    background: #5B0018;
}

.clear-btn {
    display: inline-flex;
    align-items: center;
    padding: 12px 17px;
    background: #EEE;
    color: #555;
    border-radius: 8px;
    text-decoration: none;
    font-size: 13px;
}

.record-count {
    color: #777;
    font-size: 13px;
    margin-bottom: 15px;
}

.table-wrapper {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 700px;
}

th {
    background: #800000;
    color: white;
    padding: 15px;
    text-align: left;
    font-size: 12px;
}

td {
    padding: 15px;
    border-bottom: 1px solid #EEE;
    font-size: 13px;
    color: #555;
}

tr:hover td {
    background: #FFF8F8;
}

.empty {
    text-align: center;
    padding: 30px;
    color: #999;
}

.status {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: bold;
    text-transform: capitalize;
}

.present {
    background: #E5F5E9;
    color: #237A3B;
}

.absent {
    background: #FBEAEC;
    color: #800000;
}

footer {
    text-align: center;
    padding: 20px;
    color: #999;
    font-size: 12px;
}

@media(max-width: 800px) {
    .stats {
        grid-template-columns: 1fr;
    }

    .header {
        padding: 22px;
    }

    .container {
        padding: 0 15px 30px;
    }

    .section {
        padding: 18px;
    }
}

@media(max-width: 480px) {
    .search-form {
        flex-wrap: wrap;
    }

    .search-form input {
        flex-basis: 100%;
    }
}
</style>
</head>

<body>

<header class="header">
    <h1>EVENT MANAGEMENT / RECORDS</h1>
    <p>Manage student registrations and review attendance reports in one place.</p>

    <a href="admin_dashboard.php" class="back-link">
        ← Back to Dashboard
    </a>
</header>

<main class="container">

    <?php if ($error !== ''): ?>
        <div class="error-message">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <section class="stats">

        <div class="stat-card">
            <h3>Total Participants</h3>
            <h2><?= $totalParticipants ?></h2>
            <p>Unique students with attendance records</p>
        </div>

        <div class="stat-card">
            <h3>Registered Events</h3>
            <h2><?= $registeredEvents ?></h2>
            <p>Unique events with student registrations</p>
        </div>

        <div class="stat-card">
            <h3>Total Registrations</h3>
            <h2><?= $totalRegistrations ?></h2>
            <p>Total student event registration records</p>
        </div>

    </section>

    <section class="section">

        <h2>Registered Events</h2>

        <p class="section-description">
            View the students registered for each school event.
        </p>

        <form method="GET" action="event_records.php" class="search-form">
            <input
                type="search"
                name="search"
                placeholder="Search student, email, or event..."
                value="<?= e($search) ?>"
            >

            <button type="submit" class="search-btn">
                Search
            </button>

            <?php if ($search !== ''): ?>
                <a href="event_records.php" class="clear-btn">Clear</a>
            <?php endif; ?>
        </form>

        <p class="record-count">
            Registration records found: <?= count($registrations) ?>
        </p>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Registered Event</th>
                        <th>Event Date</th>
                        <th>Registered At</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (count($registrations) > 0): ?>
                    <?php foreach ($registrations as $row): ?>
                        <tr>
                            <td><?= e($row['full_name']) ?></td>
                            <td><?= e($row['email']) ?></td>
                            <td><?= e($row['event_title']) ?></td>
                            <td><?= e($row['event_date']) ?></td>
                            <td><?= e($row['registration_date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="empty">
                            No event registrations found.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </section>

    <section class="section">

        <h2>Attendance Report</h2>

        <p class="section-description">
            Review recorded student attendance for each event.
        </p>

        <p class="record-count">
            Attendance records found: <?= count($attendanceRecords) ?>
        </p>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Student ID</th>
                        <th>Event Attended</th>
                        <th>Event Date</th>
                        <th>Recorded At</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (count($attendanceRecords) > 0): ?>
                    <?php foreach ($attendanceRecords as $row): ?>
                        <?php
                        $attendanceStatus = strtolower($row['attendance_status']);
                        ?>
                        <tr>
                            <td><?= e($row['full_name']) ?></td>
                            <td><?= (int)$row['user_id'] ?></td>
                            <td><?= e($row['event_title']) ?></td>
                            <td><?= e($row['event_date']) ?></td>
                            <td><?= e($row['recorded_at']) ?></td>
                            <td>
                                <span class="status <?= $attendanceStatus === 'present' ? 'present' : 'absent' ?>">
                                    <?= e($attendanceStatus) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty">
                            No attendance records found.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </section>

</main>

<footer>
    © 2026 UEP School Events. All rights reserved.
</footer>

</body>
</html>
