
<?php
require_once __DIR__ . '/config/database.php';

function h($value): string {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

$search = trim($_GET['search'] ?? '');

$totalParticipants = 0;
$totalRegistrations = 0;
$totalEvents = 0;

$registrations = [];
$attendanceRecords = [];
$errorMessage = '';

try {
    /*
     * TOTAL PARTICIPANTS
     * Unique students with attendance records.
     */
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT er.user_id)
        FROM attendance a
        INNER JOIN event_registrations er
            ON a.registration_id = er.registration_id
    ");

    $totalParticipants = (int) $stmt->fetchColumn();

    /*
     * TOTAL REGISTRATIONS
     */
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM event_registrations
    ");

    $totalRegistrations = (int) $stmt->fetchColumn();

    /*
     * REGISTERED EVENTS
     * Count unique events with registrations.
     */
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT event_id)
        FROM event_registrations
    ");

    $totalEvents = (int) $stmt->fetchColumn();

    /*
     * EVENT REGISTRATION RECORDS
     */
    $registrationSql = "
        SELECT
            u.full_name,
            u.email,
            e.title AS event_title,
            e.event_date,
            er.registered_at
        FROM event_registrations er
        INNER JOIN users u
            ON er.user_id = u.user_id
        INNER JOIN events e
            ON er.event_id = e.event_id
    ";

    /*
     * ATTENDANCE RECORDS
     */
    $attendanceSql = "
        SELECT
            u.full_name AS student_name,
            u.user_id AS student_id,
            e.title AS event_name,
            e.event_date,
            a.recorded_at AS time_in,
            a.status
        FROM attendance a
        INNER JOIN event_registrations er
            ON a.registration_id = er.registration_id
        INNER JOIN users u
            ON er.user_id = u.user_id
        INNER JOIN events e
            ON er.event_id = e.event_id
    ";

    $registrationParams = [];
    $attendanceParams = [];

    if ($search !== '') {
        $registrationSql .= "
            WHERE u.full_name LIKE :name
               OR u.email LIKE :email
               OR e.title LIKE :event
        ";

        $registrationParams = [
            ':name'  => "%$search%",
            ':email' => "%$search%",
            ':event' => "%$search%"
        ];

        $attendanceSql .= "
            WHERE u.full_name LIKE :student_name
               OR CAST(u.user_id AS CHAR) LIKE :student_id
               OR e.title LIKE :event_name
        ";

        $attendanceParams = [
            ':student_name' => "%$search%",
            ':student_id'   => "%$search%",
            ':event_name'   => "%$search%"
        ];
    }

    $registrationSql .= "
        ORDER BY er.registered_at DESC
    ";

    $attendanceSql .= "
        ORDER BY a.recorded_at DESC
    ";

    $stmt = $pdo->prepare($registrationSql);
    $stmt->execute($registrationParams);
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare($attendanceSql);
    $stmt->execute($attendanceParams);
    $attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Attendance page error: ' . $e->getMessage());

    $errorMessage =
        'May database query error. Pakisuri ang database connection, '
        . 'table names, columns, at relationships.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Event Records & Attendance Report</title>

    <style>
        :root {
            --maroon: #800020;
            --maroon-dark: #590016;
            --maroon-light: #a83250;
            --pink: #f8e8ee;
            --pink-light: #fff7f9;
            --white: #ffffff;
            --text: #291c23;
            --muted: #77717a;
            --border: #ead5dd;
            --green: #167647;
            --green-bg: #e5f6ec;
            --red: #a61b32;
            --red-bg: #fde8ec;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: var(--text);
            background: #faf5f7;
        }

        .topbar {
            height: 12px;
            background: linear-gradient(
                90deg,
                var(--maroon-dark),
                var(--maroon),
                var(--maroon-light)
            );
            border-bottom: 4px solid #edcbd6;
        }

        .container {
            width: 100%;
            max-width: 1500px;
            margin: 0 auto;
            padding: 38px 30px;
        }

        /* HEADER */

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }

        .eyebrow {
            color: var(--maroon);
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 2px;
        }

        h1 {
            color: var(--maroon-dark);
            font-size: 38px;
            margin: 14px 0 10px;
        }

        .subtitle {
            color: var(--muted);
            line-height: 1.7;
            margin-top: 8px;
        }

        .back {
            display: inline-block;
            background: var(--maroon);
            color: white;
            padding: 14px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.2s;
        }

        .back:hover {
            background: var(--maroon-dark);
            transform: translateY(-2px);
        }

        /* STATISTICS */

        .cards {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .card {
            min-width: 0;
            padding: 26px;
            border-radius: 18px;
            box-shadow: 0 7px 22px rgba(70, 20, 40, 0.10);
            transition: transform 0.25s, box-shadow 0.25s;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(70, 20, 40, 0.15);
        }

        .card:first-child {
            background: linear-gradient(
                135deg,
                #590016,
                #800020,
                #a83250
            );
            color: white;
        }

        .card:nth-child(2) {
            background: white;
            color: var(--maroon);
            border: 2px solid var(--maroon);
        }

        .card:nth-child(3) {
            background: #f3e3e8;
            color: var(--maroon-dark);
            border: 1px solid #e5c7d1;
        }

        .card-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 26px;
            margin-bottom: 20px;
            background: rgba(255, 255, 255, 0.17);
        }

        .card:nth-child(2) .card-icon,
        .card:nth-child(3) .card-icon {
            background: rgba(128, 0, 32, 0.09);
        }

        .card-label {
            font-size: 15px;
            font-weight: bold;
        }

        .card-number {
            font-size: 40px;
            font-weight: bold;
            margin: 18px 0 10px;
            overflow-wrap: anywhere;
        }

        .card-description {
            font-size: 13px;
            line-height: 1.6;
            opacity: 0.85;
        }

        /* SECTIONS */

        .section {
            background: white;
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 26px;
            margin-bottom: 28px;
            box-shadow: 0 6px 20px rgba(70, 20, 40, 0.05);
        }

        .section-header {
            margin-bottom: 8px;
        }

        .section h2 {
            color: var(--maroon);
            margin: 0 0 10px;
            font-size: 23px;
        }

        /* SEARCH */

        .search-form {
            display: flex;
            gap: 10px;
            margin: 22px 0;
        }

        .search-form input {
            flex: 1;
            min-width: 0;
            padding: 14px 16px;
            border: 1px solid #dfc5ce;
            border-radius: 10px;
            font-size: 15px;
            outline: none;
            transition: 0.2s;
        }

        .search-form input:focus {
            border-color: var(--maroon);
            box-shadow: 0 0 0 3px rgba(128, 0, 32, 0.09);
        }

        .btn {
            display: inline-block;
            border: none;
            background: var(--maroon);
            color: white;
            padding: 13px 21px;
            border-radius: 10px;
            font-weight: bold;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn:hover {
            background: var(--maroon-dark);
        }

        .btn-light {
            background: var(--pink);
            color: var(--maroon);
            border: 1px solid var(--border);
        }

        .btn-light:hover {
            background: #f0d6df;
            color: var(--maroon-dark);
        }

        /* TABLES */

        .table-wrap {
            width: 100%;
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: 10px;
        }

        table {
            width: 100%;
            min-width: 720px;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 15px 14px;
            text-align: left;
            border-bottom: 1px solid #f0e6ea;
            vertical-align: middle;
        }

        th {
            background: var(--maroon);
            color: white;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            white-space: nowrap;
        }

        td {
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        tbody tr:nth-child(even) {
            background: var(--pink-light);
        }

        tbody tr:hover {
            background: var(--pink);
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .event-tag {
            display: inline-block;
            background: #f8e3eb;
            color: var(--maroon);
            padding: 7px 11px;
            border-radius: 7px;
            font-size: 12px;
            font-weight: bold;
        }

        .status {
            display: inline-block;
            padding: 7px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            background: #f0edf0;
            color: #554953;
            white-space: nowrap;
        }

        .status-present,
        .status-attended {
            background: var(--green-bg);
            color: var(--green);
        }

        .status-absent {
            background: var(--red-bg);
            color: var(--red);
        }

        .empty {
            text-align: center;
            color: var(--muted);
            padding: 28px;
        }

        .message {
            background: #fff0f0;
            color: #8a142b;
            border-left: 5px solid #b4233d;
            padding: 16px;
            border-radius: 9px;
            overflow-wrap: anywhere;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .result-count {
            font-size: 13px;
            color: var(--muted);
            margin: 12px 0;
        }

        .footer {
            background: var(--maroon-dark);
            color: white;
            text-align: center;
            padding: 22px 15px;
            font-size: 13px;
            margin-top: 35px;
        }

        @media (max-width: 900px) {
            .cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .card:last-child {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 600px) {
            .container {
                padding: 25px 14px;
            }

            h1 {
                font-size: 30px;
            }

            .cards {
                grid-template-columns: 1fr;
            }

            .card:last-child {
                grid-column: auto;
            }

            .card {
                padding: 22px;
            }

            .card-number {
                font-size: 35px;
            }

            .section {
                padding: 17px;
            }

            .search-form {
                flex-direction: column;
            }

            .search-form .btn {
                width: 100%;
            }

            .back {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>

<div class="topbar"></div>

<main class="container">

    <header class="header">
        <div>
            <div class="eyebrow">
                EVENT MANAGEMENT / RECORDS
            </div>

            <h1>Event Records</h1>

            <p class="subtitle">
                Manage student registrations and review attendance
                reports in one place.
            </p>
        </div>

        <a class="back" href="dashboard.php">
            &larr; Back to Dashboard
        </a>
    </header>

    <?php if ($errorMessage !== ''): ?>
        <div class="message">
            <?= h($errorMessage) ?>
        </div>
    <?php endif; ?>

    <!-- STATISTIC CARDS -->

    <section class="cards">

        <div class="card">
            <div class="card-icon" aria-hidden="true">👥</div>

            <div class="card-label">
                Total Participants
            </div>

            <div class="card-number">
                <?= number_format($totalParticipants) ?>
            </div>

            <div class="card-description">
                Unique students with attendance records
            </div>
        </div>

        <div class="card">
            <div class="card-icon" aria-hidden="true">📅</div>

            <div class="card-label">
                Registered Events
            </div>

            <div class="card-number">
                <?= number_format($totalEvents) ?>
            </div>

            <div class="card-description">
                Unique events with student registrations
            </div>
        </div>

        <div class="card">
            <div class="card-icon" aria-hidden="true">📝</div>

            <div class="card-label">
                Total Registrations
            </div>

            <div class="card-number">
                <?= number_format($totalRegistrations) ?>
            </div>

            <div class="card-description">
                Total student event registration records
            </div>
        </div>

    </section>

    <!-- REGISTRATION RECORDS -->

    <section class="section">

        <div class="section-header">
            <h2>Registered Events</h2>

            <p class="subtitle">
                View the students registered for each school event.
            </p>
        </div>

        <form method="GET" action="attendance.php"
              class="search-form">

            <input
                type="search"
                name="search"
                placeholder="Search student name, email, or event..."
                value="<?= h($search) ?>"
                aria-label="Search registration and attendance records"
            >

            <button class="btn" type="submit">
                Search
            </button>

            <?php if ($search !== ''): ?>
                <a class="btn btn-light" href="attendance.php">
                    Clear
                </a>
            <?php endif; ?>

        </form>

        <p class="result-count">
            Registration records found:
            <?= number_format(count($registrations)) ?>
        </p>

        <div class="table-wrap">
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
                <?php if ($registrations): ?>

                    <?php foreach ($registrations as $row): ?>
                        <tr>
                            <td><?= h($row['full_name']) ?></td>

                            <td><?= h($row['email']) ?></td>

                            <td>
                                <span class="event-tag">
                                    <?= h($row['event_title']) ?>
                                </span>
                            </td>

                            <td><?= h($row['event_date']) ?></td>

                            <td><?= h($row['registered_at']) ?></td>
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

    <!-- ATTENDANCE REPORT -->

    <section class="section">

        <div class="section-header">
            <h2>Attendance Report</h2>

            <p class="subtitle">
                Review recorded student attendance for each event.
            </p>
        </div>

        <p class="result-count">
            Attendance records found:
            <?= number_format(count($attendanceRecords)) ?>
        </p>

        <div class="table-wrap">
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
                <?php if ($attendanceRecords): ?>

                    <?php foreach ($attendanceRecords as $row): ?>
                        <?php
                            $status = trim(
                                (string)($row['status'] ?? '')
                            );

                            $statusClass = strtolower($status);

                            if (in_array(
                                $statusClass,
                                ['present', 'attended'],
                                true
                            )) {
                                $statusClass = 'status-present';
                            } elseif ($statusClass === 'absent') {
                                $statusClass = 'status-absent';
                            } else {
                                $statusClass = '';
                            }
                        ?>

                        <tr>
                            <td><?= h($row['student_name']) ?></td>

                            <td><?= h($row['student_id']) ?></td>

                            <td>
                                <span class="event-tag">
                                    <?= h($row['event_name']) ?>
                                </span>
                            </td>

                            <td><?= h($row['event_date']) ?></td>

                            <td><?= h($row['time_in']) ?></td>

                            <td>
                                <span class="status <?= h($statusClass) ?>">
                                    <?= h($status !== '' ? $status : 'Unknown') ?>
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

<footer class="footer">
    &copy; <?= date('Y') ?> School Event Management System
    | Event Records & Attendance Report
</footer>

</body>
</html>
