
<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$fullName = $_SESSION['full_name'] ?? 'Administrator';

$sql = "
    SELECT
        ev.event_id,
        ev.title,
        ev.event_date,
        COUNT(DISTINCT er.registration_id) AS registered_count,
        COUNT(DISTINCT CASE
            WHEN a.registration_id IS NOT NULL
            THEN er.registration_id
        END) AS total_participants,
        COUNT(DISTINCT CASE
            WHEN a.registration_id IS NULL
            THEN er.registration_id
        END) AS not_recorded_count
    FROM events ev
    LEFT JOIN event_registrations er
        ON er.event_id = ev.event_id
    LEFT JOIN attendance a
        ON a.registration_id = er.registration_id
    GROUP BY
        ev.event_id,
        ev.title,
        ev.event_date
    ORDER BY
        ev.event_date DESC,
        ev.event_id DESC
";

try {
    $stmt = $pdo->query($sql);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $error) {
    die('Unable to load attendance report. Please check your database.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Attendance Summary by Event</title>

<style>
:root {
    --maroon: #800000;
    --dark: #650000;
    --text: #3d2020;
    --line: #f0e3e3;
    --bg: #f7f4f4;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    color: var(--text);
    background: var(--bg);
}

.sidebar {
    position: fixed;
    inset: 0 auto 0 0;
    width: 245px;
    padding: 28px 18px;
    background: var(--maroon);
    color: #fff;
}

.brand {
    margin: 0 10px 32px;
    font-size: 22px;
    font-weight: 800;
    line-height: 1.35;
}

.admin-name {
    margin: 0 10px 25px;
    padding: 12px;
    border-radius: 10px;
    background: #ffffff1f;
    font-size: 14px;
}

.nav a {
    display: block;
    margin: 5px 0;
    padding: 12px 13px;
    color: #fff;
    text-decoration: none;
    border-radius: 8px;
    font-size: 15px;
}

.nav a:hover,
.nav a.active {
    background: #ffffff2b;
}

.main {
    margin-left: 245px;
    padding: 29px;
    min-height: 100vh;
}

.panel {
    background: #fff;
    border-radius: 16px;
    padding: 29px;
    box-shadow: 0 5px 22px #3c00000f;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 28px;
}

h1 {
    margin: 0;
    color: var(--maroon);
    font-size: 30px;
}

.print-button {
    border: 0;
    border-radius: 11px;
    padding: 17px 24px;
    background: var(--maroon);
    color: #fff;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
}

.print-button:hover {
    background: var(--dark);
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 700px;
}

thead {
    background: var(--maroon);
    color: #fff;
}

th {
    padding: 20px 16px;
    text-align: left;
    font-size: 16px;
    white-space: nowrap;
}

td {
    padding: 20px 16px;
    border-bottom: 1px solid var(--line);
    font-size: 16px;
}

td:first-child {
    font-weight: 700;
}

.number {
    color: var(--maroon);
    font-weight: 700;
}

.empty {
    text-align: center;
    padding: 32px;
    color: #756565;
}

@media(max-width: 850px) {
    .sidebar {
        position: static;
        width: 100%;
    }

    .main {
        margin-left: 0;
        padding: 15px;
    }

    .panel {
        padding: 18px;
    }

    .page-header {
        align-items: flex-start;
        flex-direction: column;
    }

    h1 {
        font-size: 25px;
    }
}

@media print {
    body {
        background: #fff;
    }

    .sidebar,
    .print-button {
        display: none !important;
    }

    .main {
        margin: 0;
        padding: 0;
    }

    .panel {
        box-shadow: none;
        padding: 0;
    }

    .page-header {
        margin-bottom: 20px;
    }

    table {
        min-width: 0;
    }

    th,
    td {
        padding: 10px;
        font-size: 12px;
    }
}
</style>
</head>

<body>

<aside class="sidebar">

    <div class="brand">
        School Event<br>
        Management
    </div>

    <div class="admin-name">
        Signed in as<br>
        <strong><?= e($fullName) ?></strong>
    </div>

    <nav class="nav">
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="manage_users.php">Manage Users</a>
        <a href="admin.php">Manage Events</a>
        <a href="manage_attendance.php">Attendance</a>
        <a class="active" href="attendance_report.php">Attendance Report</a>
        <a href="logout.php">Logout</a>
    </nav>

</aside>

<main class="main">

    <section class="panel">

        <div class="page-header">
            <h1>Attendance Summary by Event</h1>

            <button
                class="print-button"
                onclick="window.print()"
                type="button"
            >
                ▣ Print Report
            </button>
        </div>

        <div class="table-wrap">

            <table>
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Event Date</th>
                        <th>Registered</th>
                        <th>Total Participants</th>
                        <th>Not Recorded</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (!empty($events)): ?>

                    <?php foreach ($events as $event): ?>

                        <tr>
                            <td><?= e($event['title']) ?></td>

                            <td><?= e($event['event_date']) ?></td>

                            <td class="number">
                                <?= (int)$event['registered_count'] ?>
                            </td>

                            <td class="number">
                                <?= (int)$event['total_participants'] ?>
                            </td>

                            <td class="number">
                                <?= (int)$event['not_recorded_count'] ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5" class="empty">
                            No events found.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>

        </div>

    </section>

</main>

</body>
</html>
