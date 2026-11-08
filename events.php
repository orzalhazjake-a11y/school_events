
<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$name = $_SESSION['full_name'] ?? 'User';
$role = $_SESSION['role'] ?? 'student';

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

try {
    $sql = "SELECT * FROM events WHERE 1=1";
    $params = [];

    if ($search !== '') {
        $sql .= " AND (
            title LIKE ? OR
            description LIKE ? OR
            location LIKE ?
        )";

        $keyword = "%{$search}%";
        $params[] = $keyword;
        $params[] = $keyword;
        $params[] = $keyword;
    }

    if ($category !== '') {
        $sql .= " AND category = ?";
        $params[] = $category;
    }

    $sql .= " ORDER BY event_date ASC, start_time ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categoryStmt = $pdo->query(
        "SELECT DISTINCT category
         FROM events
         WHERE category IS NOT NULL AND category != ''
         ORDER BY category ASC"
    );

    $categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

    $eventCount = count($events);

} catch (PDOException $error) {
    die("Unable to load school events. Please check your database.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Events | School Event System</title>

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

        .sidebar {
            width: 250px;
            height: 100vh;
            background: linear-gradient(180deg, #800000, #5B0018);
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            padding: 28px 18px;
            overflow-y: auto;
        }

        .brand {
            font-size: 21px;
            font-weight: bold;
            text-align: center;
            padding-bottom: 30px;
            border-bottom: 1px solid rgba(255,255,255,.2);
        }

        .brand span {
            display: block;
            font-size: 12px;
            font-weight: normal;
            margin-top: 7px;
            color: #F2DCDC;
        }

        .nav-title {
            font-size: 11px;
            color: #E5BFC8;
            margin: 28px 12px 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .nav-link {
            display: block;
            color: #fff;
            text-decoration: none;
            padding: 13px 14px;
            border-radius: 8px;
            margin-bottom: 7px;
            transition: .2s;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(255,255,255,.18);
        }

        .main {
            margin-left: 250px;
            padding: 30px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 28px;
        }

        .topbar h1 {
            color: #5B0018;
            font-size: 28px;
        }

        .topbar p {
            color: #777;
            margin-top: 7px;
            font-size: 14px;
        }

        .profile {
            background: white;
            padding: 12px 16px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,.05);
            white-space: nowrap;
        }

        .profile strong {
            color: #800000;
        }

        .welcome {
            background: linear-gradient(120deg, #800000, #A52A46);
            color: white;
            padding: 28px;
            border-radius: 16px;
            margin-bottom: 25px;
            box-shadow: 0 8px 20px rgba(128,0,0,.12);
        }

        .welcome h2 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .welcome p {
            color: #F8E9EC;
            line-height: 1.6;
        }

        .filter-card {
            background: white;
            padding: 22px;
            border-radius: 14px;
            box-shadow: 0 4px 16px rgba(0,0,0,.04);
            margin-bottom: 25px;
        }

        .filter-card h3 {
            color: #5B0018;
            margin-bottom: 17px;
        }

        .filter-form {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .filter-form input,
        .filter-form select {
            flex: 1;
            min-width: 180px;
            padding: 12px 14px;
            border: 1px solid #E2D6D9;
            border-radius: 8px;
            outline: none;
            background: white;
            font-size: 14px;
        }

        .filter-form input:focus,
        .filter-form select:focus {
            border-color: #800000;
        }

        .btn {
            display: inline-block;
            border: none;
            border-radius: 8px;
            padding: 12px 18px;
            background: #800000;
            color: white;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: .2s;
        }

        .btn:hover {
            background: #5B0018;
        }

        .btn-light {
            background: #F3E6E9;
            color: #800000;
        }

        .btn-light:hover {
            background: #E8D1D8;
        }

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            gap: 12px;
        }

        .section-heading h2 {
            color: #5B0018;
            font-size: 21px;
        }

        .event-count {
            background: #F0DDE2;
            color: #800000;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .event-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .event-card {
            background: white;
            border-radius: 14px;
            padding: 22px;
            border-top: 4px solid #800000;
            box-shadow: 0 4px 16px rgba(0,0,0,.04);
            display: flex;
            flex-direction: column;
            min-width: 0;
            transition: transform .2s, box-shadow .2s;
        }

        .event-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 24px rgba(91,0,24,.10);
        }

        .event-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: #F7E9ED;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 17px;
        }

        .category {
            display: inline-block;
            align-self: flex-start;
            color: #800000;
            background: #F7E9ED;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            margin-bottom: 12px;
        }

        .event-card h3 {
            color: #4B0015;
            font-size: 19px;
            line-height: 1.4;
            margin-bottom: 12px;
            overflow-wrap: anywhere;
        }

        .description {
            color: #777;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 18px;
            overflow-wrap: anywhere;
            flex-grow: 1;
        }

        .event-details {
            border-top: 1px solid #F0E8EA;
            padding-top: 15px;
            margin-bottom: 18px;
        }

        .detail {
            color: #666;
            font-size: 13px;
            margin-bottom: 10px;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .detail strong {
            color: #5B0018;
        }

        .event-card .btn {
            text-align: center;
            width: 100%;
        }

        .empty-state {
            background: white;
            border-radius: 14px;
            padding: 45px 20px;
            text-align: center;
            color: #777;
            grid-column: 1 / -1;
        }

        .empty-state h3 {
            color: #5B0018;
            margin-bottom: 10px;
        }

        @media (max-width: 1100px) {
            .event-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
            }

            .nav-title {
                margin-top: 18px;
            }

            .main {
                margin-left: 0;
                padding: 18px;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .event-grid {
                grid-template-columns: 1fr;
            }

            .filter-form {
                flex-direction: column;
            }

            .filter-form input,
            .filter-form select {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<aside class="sidebar">
    <div class="brand">
        SCHOOL EVENT SYSTEM
        <span>Manage • Participate • Celebrate</span>
    </div>

    <div class="nav-title">Main Menu</div>

    <a href="dashboard.php" class="nav-link">▦ Dashboard</a>
    <a href="events.php" class="nav-link active">▣ School Events</a>

    <?php if ($role === 'student'): ?>
        <a href="register.php" class="nav-link">✚ My Registrations</a>
        <a href="attendance.php" class="nav-link">✓ Attendance</a>
    <?php endif; ?>

    <?php if ($role === 'admin'): ?>
        <div class="nav-title">Administration</div>
        <a href="admin.php" class="nav-link">＋ Add / Manage Events</a>
        <a href="manage_attendance.php" class="nav-link">✓ Manage Attendance</a>
        <a href="attendance_report.php" class="nav-link">▤ Attendance Reports</a>
        <a href="manage_users.php" class="nav-link">♙ Manage Users</a>
    <?php endif; ?>

    <div class="nav-title">Account</div>
    <a href="logout.php" class="nav-link">↪ Logout</a>
</aside>

<main class="main">

    <header class="topbar">
        <div>
            <h1>School Events</h1>
            <p>Discover upcoming activities and events in your school.</p>
        </div>

        <div class="profile">
            Welcome, <strong><?= e($name) ?></strong>
        </div>
    </header>

    <section class="welcome">
        <h2>Discover. Participate. Make Memories.</h2>
        <p>
            Explore school activities, find events that interest you,
            and join the exciting experiences happening in your school.
        </p>
    </section>

    <section class="filter-card">
        <h3>Find an Event</h3>

        <form method="GET" action="events.php" class="filter-form">
            <input
                type="search"
                name="search"
                placeholder="Search title, description, location..."
                value="<?= e($search) ?>"
            >

            <select name="category">
                <option value="">All Categories</option>

                <?php foreach ($categories as $cat): ?>
                    <option
                        value="<?= e($cat) ?>"
                        <?= $category === $cat ? 'selected' : '' ?>
                    >
                        <?= e($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn">Search Events</button>
            <a href="events.php" class="btn btn-light">Reset</a>
        </form>
    </section>

    <div class="section-heading">
        <h2>Available Events</h2>
        <span class="event-count">
            <?= $eventCount ?> event(s) found
        </span>
    </div>

    <section class="event-grid">

        <?php if ($eventCount > 0): ?>

            <?php foreach ($events as $event): ?>

                <article class="event-card">
                    <div class="event-icon">🎓</div>

                    <span class="category">
                        <?= e($event['category'] ?: 'General') ?>
                    </span>

                    <h3><?= e($event['title']) ?></h3>

                    <p class="description">
                        <?= e($event['description'] ?: 'No description available.') ?>
                    </p>

                    <div class="event-details">
                        <div class="detail">
                            <strong>📅 Date:</strong>
                            <?= e($event['event_date']) ?>
                        </div>

                        <?php if (!empty($event['start_time'])): ?>
                            <div class="detail">
                                <strong>🕒 Time:</strong>
                                <?= e(date('h:i A', strtotime($event['start_time']))) ?>

                                <?php if (!empty($event['end_time'])): ?>
                                    - <?= e(date('h:i A', strtotime($event['end_time']))) ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="detail">
                            <strong>📍 Location:</strong>
                            <?= e($event['location'] ?: 'To be announced') ?>
                        </div>

                        <div class="detail">
                            <strong>👤 Organizer:</strong>
                            School Administration
                        </div>

                        <?php if (!empty($event['status'])): ?>
                            <div class="detail">
                                <strong>Status:</strong>
                                <?= e(ucfirst($event['status'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($role === 'student'): ?>
                        <a
                            href="register.php?event_id=<?= (int)$event['event_id'] ?>"
                            class="btn"
                        >
                            Register for Event
                        </a>
                    <?php elseif ($role === 'admin'): ?>
                        <a href="admin.php" class="btn">
                            Manage Events
                        </a>
                    <?php endif; ?>
                </article>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty-state">
                <h3>No Events Found</h3>
                <p>Walang event na tumutugma sa iyong search.</p>
                <br>
                <a href="events.php" class="btn">View All Events</a>
            </div>

        <?php endif; ?>

    </section>

</main>

</body>
</html>
