
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$name = $_SESSION['full_name'] ?? 'Student';
$role = $_SESSION['role'] ?? 'student';

$isAdmin = ($role === 'admin');
$safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$safeRole = htmlspecialchars(ucfirst($role), ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard | School Event System</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --maroon: #800000;
    --dark-maroon: #5b0018;
    --light-bg: #f8f5f5;
    --white: #ffffff;
    --text: #30252a;
    --muted: #81757a;
    --border: #eee3e6;
}

body {
    font-family: Arial, sans-serif;
    background: var(--light-bg);
    color: var(--text);
}

.layout {
    display: flex;
    min-height: 100vh;
}

/* SIDEBAR */
.sidebar {
    width: 255px;
    background: linear-gradient(180deg, #5b0018, #800000);
    color: white;
    padding: 28px 18px;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
}

.brand {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 8px 28px;
    border-bottom: 1px solid #ffffff30;
}

.brand-icon {
    font-size: 32px;
}

.brand h2 {
    font-size: 17px;
    line-height: 1.4;
}

.brand small {
    color: #f4dfe5;
    font-size: 11px;
    font-weight: normal;
}

.nav-title {
    margin: 28px 10px 12px;
    color: #e8c6cf;
    font-size: 11px;
    letter-spacing: 1.4px;
    font-weight: bold;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #f8e9ed;
    padding: 13px 14px;
    margin-bottom: 7px;
    border-radius: 9px;
    text-decoration: none;
    font-size: 14px;
    transition: 0.2s;
}

.nav-link:hover,
.nav-link.active {
    background: #ffffff20;
    color: white;
}

.nav-icon {
    width: 22px;
    text-align: center;
    font-size: 17px;
}

.sidebar-bottom {
    margin-top: auto;
    padding-top: 25px;
}

/* MAIN */
.main {
    flex: 1;
    min-width: 0;
}

.topbar {
    height: 78px;
    background: white;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 35px;
    gap: 15px;
}

.topbar h2 {
    font-size: 20px;
    color: var(--dark-maroon);
}

.profile {
    display: flex;
    align-items: center;
    gap: 12px;
}

.avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f0dce2;
    color: var(--maroon);
    font-weight: bold;
    font-size: 18px;
}

.profile-name {
    font-weight: bold;
    font-size: 14px;
}

.profile-role {
    font-size: 12px;
    color: var(--muted);
    margin-top: 4px;
}

.content {
    padding: 32px 35px;
    max-width: 1450px;
    margin: auto;
}

.welcome {
    background: linear-gradient(115deg, #5b0018, #800000 65%, #a52a42);
    color: white;
    padding: 35px;
    border-radius: 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 30px;
    box-shadow: 0 10px 25px #80000018;
}

.welcome h1 {
    font-size: 28px;
    margin-bottom: 12px;
}

.welcome p {
    color: #f4dfe5;
    line-height: 1.7;
}

.welcome-icon {
    font-size: 68px;
    opacity: 0.9;
}

.section-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 18px;
}

.section-heading h2 {
    font-size: 21px;
    color: var(--dark-maroon);
}

.section-heading p {
    font-size: 13px;
    color: var(--muted);
    margin-top: 5px;
}

.cards {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
}

.card {
    background: #ffffff;
    border: 1px solid var(--border);
    padding: 28px;
    border-radius: 14px;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    min-height: 290px;
    transition: transform 0.2s, box-shadow 0.2s;
}

.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 28px #5b001810;
}

.card-icon {
    width: 61px;
    height: 61px;
    flex-shrink: 0;
    background: #f8e9ed;
    color: var(--dark-maroon);
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    margin-bottom: 22px;
}

.card h3 {
    font-size: 20px;
    margin-bottom: 14px;
    color: var(--dark-maroon);
}

.card p {
    color: var(--muted);
    font-size: 16px;
    line-height: 1.6;
    min-height: 52px;
    margin-bottom: 28px;
}

.card-link {
    display: inline-block;
    margin-top: auto;
    background: var(--maroon);
    color: white;
    padding: 14px 19px;
    text-decoration: none;
    border-radius: 9px;
    font-size: 15px;
    font-weight: bold;
    transition: 0.2s;
}

.card-link:hover {
    background: var(--dark-maroon);
}

.card-link.secondary {
    background: #f7e9ed;
    color: var(--maroon);
}

.card-link.secondary:hover {
    background: #edd2da;
}

.footer {
    padding: 28px 0 8px;
    text-align: center;
    color: var(--muted);
    font-size: 12px;
}

/* MOBILE */
@media (max-width: 1000px) {
    .cards {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .sidebar {
        width: 220px;
    }

    .content {
        padding: 25px 22px;
    }
}

@media (max-width: 700px) {
    .layout {
        flex-direction: column;
    }

    .sidebar {
        width: 100%;
        padding: 16px;
    }

    .brand {
        padding-bottom: 15px;
    }

    .nav-title {
        margin-top: 16px;
    }

    .sidebar nav {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }

    .nav-link {
        margin: 0;
        padding: 10px;
        font-size: 12px;
    }

    .sidebar-bottom {
        margin-top: 12px;
        padding-top: 0;
    }

    .topbar {
        height: auto;
        min-height: 70px;
        padding: 15px 18px;
    }

    .topbar h2 {
        font-size: 17px;
    }

    .profile-name {
        max-width: 130px;
        overflow-wrap: anywhere;
    }

    .content {
        padding: 20px 15px;
    }

    .welcome {
        padding: 25px 20px;
    }

    .welcome h1 {
        font-size: 23px;
    }

    .welcome-icon {
        font-size: 40px;
    }

    .cards {
        grid-template-columns: 1fr;
        gap: 14px;
    }
}
</style>
</head>

<body>
<div class="layout">

    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">🎓</div>
            <div>
                <h2>School Event</h2>
                <small>Management System</small>
            </div>
        </div>

        <div class="nav-title">MAIN MENU</div>

        <nav>
            <a href="dashboard.php" class="nav-link active">
                <span class="nav-icon">▦</span> Dashboard
            </a>

            <a href="events.php" class="nav-link">
                <span class="nav-icon">📅</span> School Events
            </a>

            <?php if (!$isAdmin): ?>
                <a href="register.php" class="nav-link">
                    <span class="nav-icon">✍️</span> My Registrations
                </a>

                <a href="attendance.php" class="nav-link">
                    <span class="nav-icon">✓</span> Attendance
                </a>
            <?php endif; ?>

            <?php if ($isAdmin): ?>
                <div class="nav-title">ADMINISTRATION</div>

                <a href="admin.php" class="nav-link">
                    <span class="nav-icon">⚙️</span> Manage Events
                </a>

                <a href="manage_users.php" class="nav-link">
                    <span class="nav-icon">👥</span> Manage Users
                </a>

                <a href="manage_attendance.php" class="nav-link">
                    <span class="nav-icon">✓</span> Manage Attendance
                </a>

                <a href="attendance_report.php" class="nav-link">
                    <span class="nav-icon">📊</span> Attendance Reports
                </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-bottom">
            <a href="logout.php" class="nav-link">
                <span class="nav-icon">↪</span> Logout
            </a>
        </div>
    </aside>

    <main class="main">

        <header class="topbar">
            <h2>Dashboard</h2>

            <div class="profile">
                <div class="avatar">
                    <?php
                    echo htmlspecialchars(
                        strtoupper(substr($name, 0, 1)),
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>
                </div>
                <div>
                    <div class="profile-name"><?php echo $safeName; ?></div>
                    <div class="profile-role"><?php echo $safeRole; ?></div>
                </div>
            </div>
        </header>

        <div class="content">

            <section class="welcome">
                <div>
                    <h1>Hello, <?php echo $safeName; ?>! 👋</h1>
                    <p>
                        Welcome to your School Event System dashboard.<br>
                        <?php if ($isAdmin): ?>
                            Manage school events, users, and attendance in one place.
                        <?php else: ?>
                            Discover school events and keep track of your registrations.
                        <?php endif; ?>
                    </p>
                </div>
                <div class="welcome-icon">🎓</div>
            </section>

            <div class="section-heading">
                <div>
                    <h2>Quick Access</h2>
                    <p>Choose what you want to do today.</p>
                </div>
            </div>

            <section class="cards">

                <article class="card">
                    <div class="card-icon">📅</div>
                    <h3>School Events</h3>
                    <p>Explore school activities, event schedules, and locations.</p>
                    <a class="card-link" href="events.php">Browse Events →</a>
                </article>

                <?php if (!$isAdmin): ?>
                    <article class="card">
                        <div class="card-icon">✍️</div>
                        <h3>My Registrations</h3>
                        <p>Register for events and view your registered events.</p>
                        <a class="card-link" href="register.php">View Registrations →</a>
                    </article>

                    <article class="card">
                        <div class="card-icon" aria-hidden="true">✓</div>
                        <h3>My Attendance</h3>
                        <p>Open the attendance page for your event records.</p>
                        <a class="card-link" href="attendance.php">View Attendance →</a>
                    </article>
                <?php endif; ?>

                <?php if ($isAdmin): ?>
                    <article class="card">
                        <div class="card-icon">⚙️</div>
                        <h3>Manage Events</h3>
                        <p>Create, edit, and manage school event information.</p>
                        <a class="card-link" href="admin.php">Manage Events →</a>
                    </article>

                    <article class="card">
                        <div class="card-icon">👥</div>
                        <h3>Manage Users</h3>
                        <p>View accounts and roles registered in the system.</p>
                        <a class="card-link" href="manage_users.php">View Users →</a>
                    </article>

                    <article class="card">
                        <div class="card-icon">✓</div>
                        <h3>Manage Attendance</h3>
                        <p>Record Present or Absent for registered students.</p>
                        <a class="card-link" href="manage_attendance.php">Manage Attendance →</a>
                    </article>

                    <article class="card">
                        <div class="card-icon">📊</div>
                        <h3>Attendance Reports</h3>
                        <p>Review event attendance summaries and print reports.</p>
                        <a class="card-link" href="attendance_report.php">View Reports →</a>
                    </article>
                <?php endif; ?>

            </section>

            <footer class="footer">
                © <?php echo date('Y'); ?> School Event System.
                All rights reserved.
            </footer>

        </div>
    </main>
</div>
</body>
</html>