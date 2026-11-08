
<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION['role'] ?? '') !== 'student') {
    header("Location: admin_dashboard.php");
    exit;
}

$userId = (int) $_SESSION['user_id'];
$name = $_SESSION['full_name'] ?? 'Student';

$message = '';
$messageType = 'success';

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Register student for an event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_event'])) {
    $eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);

    if (!$eventId || $eventId < 1) {
        $message = 'Please select a valid event.';
        $messageType = 'error';
    } else {
        try {
            // Check if event exists
            $checkEvent = $pdo->prepare(
                "SELECT event_id FROM events WHERE event_id = ? LIMIT 1"
            );
            $checkEvent->execute([$eventId]);
            $eventExists = $checkEvent->fetch(PDO::FETCH_ASSOC);

            if (!$eventExists) {
                $message = 'The selected event does not exist.';
                $messageType = 'error';
            } else {
                // Check for duplicate registration
                $check = $pdo->prepare(
                    "SELECT registration_id
                     FROM event_registrations
                     WHERE user_id = ? AND event_id = ?
                     LIMIT 1"
                );

                $check->execute([$userId, $eventId]);
                $alreadyRegistered = $check->fetch(PDO::FETCH_ASSOC);

                if ($alreadyRegistered) {
                    $message = 'You are already registered for this event.';
                    $messageType = 'error';
                } else {
                    // Insert registration
                    $insert = $pdo->prepare(
                        "INSERT INTO event_registrations
                         (user_id, event_id)
                         VALUES (?, ?)"
                    );

                    $insert->execute([$userId, $eventId]);

                    header("Location: register.php?success=1");
                    exit;
                }
            }
        } catch (PDOException $error) {
            if ($error->getCode() === '23000') {
                $message = 'You are already registered for this event.';
            } else {
                $message = 'Registration failed. Please try again.';
            }

            $messageType = 'error';
        }
    }
}

if (isset($_GET['success'])) {
    $message = 'You have successfully registered for the event!';
    $messageType = 'success';
}

try {
    // Fetch available events
    $eventStmt = $pdo->query(
        "SELECT event_id, title, event_date, location, category
         FROM events
         ORDER BY event_date ASC"
    );

    $events = $eventStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch this student's registrations
    $stmt = $pdo->prepare(
        "SELECT
            ev.title,
            ev.event_date,
            ev.location,
            ev.category,
            er.registration_date
         FROM event_registrations er
         INNER JOIN events ev ON er.event_id = ev.event_id
         WHERE er.user_id = ?
         ORDER BY er.registration_date DESC"
    );

    $stmt->execute([$userId]);
    $myEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $registeredCount = count($myEvents);
    $availableCount = count($events);

} catch (PDOException $error) {
    die("Unable to load event information. Please check your database.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Event Registration | School Event System</title>

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
    font-size: 20px;
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
    color: white;
    text-decoration: none;
    padding: 13px 14px;
    border-radius: 8px;
    margin-bottom: 7px;
    font-size: 14px;
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
    font-size: 14px;
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
    line-height: 1.7;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 25px;
}

.stat-card {
    background: white;
    padding: 22px;
    border-radius: 14px;
    border-top: 4px solid #800000;
    box-shadow: 0 4px 16px rgba(0,0,0,.04);
}

.stat-card p {
    color: #777;
    font-size: 13px;
    margin-bottom: 9px;
}

.stat-card h3 {
    color: #5B0018;
    font-size: 27px;
}

.card {
    background: white;
    padding: 26px;
    border-radius: 14px;
    box-shadow: 0 4px 16px rgba(0,0,0,.04);
    margin-bottom: 25px;
}

.card h2 {
    color: #5B0018;
    font-size: 21px;
    margin-bottom: 22px;
}

label {
    display: block;
    color: #4B0015;
    font-weight: bold;
    font-size: 14px;
    margin-bottom: 10px;
}

select {
    width: 100%;
    max-width: 600px;
    padding: 14px;
    border: 1px solid #E2D6D9;
    border-radius: 8px;
    background: white;
    font-size: 14px;
    outline: none;
    margin-bottom: 18px;
}

select:focus {
    border-color: #800000;
    box-shadow: 0 0 0 3px rgba(128,0,0,.08);
}

.btn {
    display: inline-block;
    padding: 13px 19px;
    border: none;
    border-radius: 8px;
    background: #800000;
    color: white;
    text-decoration: none;
    font-size: 13px;
    font-weight: bold;
    cursor: pointer;
    transition: .2s;
}

.btn:hover {
    background: #5B0018;
}

.alert {
    padding: 14px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    background: #E8F5E9;
    color: #216B35;
}

.alert.error {
    background: #FBEAEC;
    color: #800000;
}

.event-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.event-item {
    border: 1px solid #F0E3E6;
    border-left: 4px solid #800000;
    border-radius: 10px;
    padding: 20px;
    background: #FFFEFE;
}

.event-item h3 {
    color: #5B0018;
    font-size: 17px;
    margin-bottom: 12px;
}

.event-item p {
    color: #666;
    font-size: 13px;
    margin-bottom: 8px;
    line-height: 1.5;
    overflow-wrap: anywhere;
}

.badge {
    display: inline-block;
    background: #F7E9ED;
    color: #800000;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 11px;
    margin-bottom: 12px;
}

.empty {
    padding: 25px;
    background: #FCF8F9;
    color: #777;
    text-align: center;
    border-radius: 10px;
    line-height: 1.7;
}

@media (max-width: 1000px) {
    .event-list {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 760px) {
    .sidebar {
        position: static;
        width: 100%;
        height: auto;
    }

    .main {
        margin-left: 0;
        padding: 18px;
    }

    .topbar {
        align-items: flex-start;
        flex-direction: column;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .card {
        padding: 20px;
    }

    select {
        max-width: 100%;
    }
}
</style>
</head>

<body>

<aside class="sidebar">
    <div class="brand">
        SCHOOL EVENT SYSTEM
        <span>Student Portal</span>
    </div>

    <div class="nav-title">Main Menu</div>
    <a href="dashboard.php" class="nav-link">▦ Dashboard</a>
    <a href="events.php" class="nav-link">▣ Browse School Events</a>
    <a href="register.php" class="nav-link active">＋ Event Registration</a>

    <div class="nav-title">Account</div>
    <a href="logout.php" class="nav-link">↪ Logout</a>
</aside>

<main class="main">

    <header class="topbar">
        <div>
            <h1>Event Registration</h1>
            <p>Register for school activities and track your registrations.</p>
        </div>

        <div class="profile">
            Welcome, <strong><?= e($name) ?></strong>
        </div>
    </header>

    <section class="welcome">
        <h2>Join Our School Events!</h2>
        <p>
            Choose an available event below and register.
            You can review your registered events on this page.
        </p>
    </section>

    <?php if ($message !== ''): ?>
        <div class="alert <?= $messageType === 'error' ? 'error' : '' ?>">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <section class="stats-grid">
        <div class="stat-card">
            <p>My Registered Events</p>
            <h3><?= $registeredCount ?></h3>
        </div>

        <div class="stat-card">
            <p>Available School Events</p>
            <h3><?= $availableCount ?></h3>
        </div>
    </section>

    <section class="card">
        <h2>Register for an Event</h2>

        <?php if ($availableCount > 0): ?>
            <form method="POST" action="register.php">
                <label for="event_id">Choose a school event</label>

                <select name="event_id" id="event_id" required>
                    <option value="">-- Select an Event --</option>

                    <?php foreach ($events as $event): ?>
                        <option value="<?= (int)$event['event_id'] ?>">
                            <?= e($event['title']) ?>
                            — <?= e($event['event_date']) ?>
                            (<?= e($event['location']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <br>

                <button type="submit" name="register_event" class="btn">
                    ＋ Register Now
                </button>
            </form>
        <?php else: ?>
            <div class="empty">
                Wala pang available na school events sa ngayon.
                Balikan ang page na ito sa susunod.
            </div>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2>My Registered Events</h2>

        <?php if ($registeredCount > 0): ?>
            <div class="event-list">
                <?php foreach ($myEvents as $event): ?>
                    <article class="event-item">
                        <span class="badge">
                            <?= e($event['category'] ?: 'School Event') ?>
                        </span>

                        <h3><?= e($event['title']) ?></h3>

                        <p>
                            <strong>Event Date:</strong>
                            <?= e($event['event_date']) ?>
                        </p>

                        <p>
                            <strong>Location:</strong>
                            <?= e($event['location']) ?>
                        </p>

                        <p>
                            <strong>Registered:</strong>
                            <?= e($event['registration_date']) ?>
                        </p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty">
                Wala ka pang registered events, pre.
                Pumili ng event sa itaas para makapag-register.
            </div>
        <?php endif; ?>
    </section>

</main>

</body>
</html>
