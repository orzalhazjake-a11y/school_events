
<!DOCTYPE html>
<html lang="en">
<head>
   
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Event System</title>

    <style>
        :root {
            --maroon: #800020;
            --dark-maroon: #500014;
            --pink: #f9e8ec;
            --background: #fff8f9;
            --text: #29232a;
            --muted: #6b6470;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: var(--background);
            color: var(--text);
            line-height: 1.6;
        }

        /* HEADER */
        .header {
            background: linear-gradient(
                135deg,
                var(--dark-maroon),
                var(--maroon)
            );
            color: white;
            padding: 25px 8%;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .brand-icon {
            font-size: 48px;
        }

        .brand h1 {
            font-size: 32px;
            line-height: 1.3;
        }

        .brand p {
            color: #f7dfe6;
            font-size: 15px;
        }

        /* NAVIGATION */
        .navbar {
            background: var(--dark-maroon);
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            padding: 12px 5%;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 7px;
            font-weight: bold;
            transition: 0.3s;
        }

        .navbar a:hover,
        .navbar a.active {
            background: rgba(255, 255, 255, 0.17);
        }

        /* MAIN CONTENT */
        .container {
            width: 90%;
            max-width: 1250px;
            margin: 45px auto;
        }

        /* HERO */
        .hero {
            background: var(--white);
            border: 1px solid #f2e4e8;
            border-radius: 22px;
            padding: 60px;
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            align-items: center;
            gap: 35px;
            box-shadow: 0 12px 35px rgba(80, 0, 20, 0.08);
        }

        .welcome-label {
            display: inline-block;
            background: var(--pink);
            color: var(--maroon);
            border-radius: 30px;
            padding: 6px 16px;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 18px;
        }

        .hero h2 {
            color: var(--dark-maroon);
            font-size: clamp(36px, 4vw, 56px);
            line-height: 1.15;
            margin-bottom: 20px;
        }

        .hero h2 span {
            color: #a31339;
        }

        .hero p {
            color: var(--muted);
            font-size: 18px;
            margin-bottom: 28px;
            max-width: 600px;
        }

        .buttons {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            text-decoration: none;
            padding: 13px 24px;
            border-radius: 10px;
            font-weight: bold;
            transition: 0.3s;
        }

        .btn-primary {
            background: var(--maroon);
            color: white;
            border: 2px solid var(--maroon);
        }

        .btn-primary:hover {
            background: var(--dark-maroon);
            transform: translateY(-2px);
        }

        .btn-outline {
            border: 2px solid var(--maroon);
            color: var(--maroon);
        }

        .btn-outline:hover {
            background: var(--pink);
            transform: translateY(-2px);
        }

        /* HERO ILLUSTRATION */
        .hero-art {
            background: linear-gradient(145deg, #fff2f5, #f6dce4);
            border-radius: 50% 40% 45% 35%;
            min-height: 280px;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 25px;
        }

        .hero-art .emoji {
            font-size: clamp(90px, 12vw, 160px);
        }

        .hero-art p {
            color: var(--maroon);
            font-weight: bold;
            margin: 10px 0 0;
            font-size: 17px;
        }

        /* FEATURE CARDS */
        .features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
            margin-top: 30px;
        }

        .feature-card {
            display: flex;
            gap: 18px;
            align-items: flex-start;
            background: white;
            padding: 28px 24px;
            border-radius: 17px;
            border: 1px solid #f2e4e8;
            box-shadow: 0 8px 25px rgba(80, 0, 20, 0.06);
            transition: 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(80, 0, 20, 0.12);
        }

        .feature-icon {
            flex-shrink: 0;
            width: 65px;
            height: 65px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: var(--pink);
            border-radius: 50%;
            font-size: 30px;
        }

        .feature-card h3 {
            color: var(--maroon);
            margin-bottom: 8px;
            font-size: 18px;
        }

        .feature-card p {
            color: var(--muted);
            font-size: 15px;
        }

        /* FOOTER */
        .footer {
            margin-top: 55px;
            background: var(--dark-maroon);
            color: #f7dfe6;
            text-align: center;
            padding: 22px 15px;
            font-size: 14px;
        }

        /* MOBILE RESPONSIVE */
        @media (max-width: 850px) {
            .hero {
                grid-template-columns: 1fr;
                padding: 35px 25px;
            }

            .hero-art {
                min-height: 220px;
            }

            .features {
                grid-template-columns: 1fr;
            }

            .header {
                padding: 22px 5%;
            }

            .brand h1 {
                font-size: 25px;
            }

            .navbar {
                gap: 3px;
            }

            .navbar a {
                padding: 9px 12px;
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .brand-icon {
                font-size: 36px;
            }

            .brand h1 {
                font-size: 21px;
            }

            .brand p {
                font-size: 12px;
            }

            .container {
                width: 92%;
                margin: 25px auto;
            }

            .hero {
                padding: 27px 20px;
            }

            .hero h2 {
                font-size: 34px;
            }

            .hero p {
                font-size: 16px;
            }

            .buttons {
                flex-direction: column;
            }

            .btn {
                text-align: center;
            }

            .feature-card {
                padding: 22px 17px;
            }
        }
    </style>
</head>

<body>

    <header class="header">
        <div class="brand">
            <div class="brand-icon">🎓</div>
            <div>
                <h1>School Event System</h1>
                <p>Manage and organize school events easily</p>
            </div>
        </div>
    </header>

    <nav class="navbar">
        <a href="index.php" class="active">Home</a>
        <a href="events.php">Events</a>
        <a href="register.php">Register</a>
        <a href="attendance.php">Attendance</a>
        <a href="login.php">Login</a>
    </nav>

    <main class="container">

        <section class="hero">
            <div class="hero-content">
                <span class="welcome-label">
                    WELCOME TO OUR SCHOOL
                </span>

                <h2>
                    Every School Moment
                    <span>Starts Here</span>
                </h2>

                <p>
                    Discover upcoming events, register for activities,
                    and manage your event registrations all in one place.
                </p>

                <div class="buttons">
                    <a href="events.php" class="btn btn-primary">
                        📅 Explore Events
                    </a>

                    <a href="login.php" class="btn btn-outline">
                        Get Started →
                    </a>
                </div>
            </div>

            <div class="hero-art">
                <div>
                    <div class="emoji">📅🎓</div>
                    <p>Learn • Participate • Celebrate</p>
                </div>
            </div>
        </section>

        <section class="features">

            <div class="feature-card">
                <div class="feature-icon">📅</div>
                <div>
                    <h3>Discover Events</h3>
                    <p>
                        Browse school events and find activities
                        that interest you.
                    </p>
                </div>
            </div>

            <div class="feature-card">
                <div class="feature-icon">📝</div>
                <div>
                    <h3>Easy Registration</h3>
                    <p>
                        Sign up for your favorite events in just
                        a few clicks.
                    </p>
                </div>
            </div>

            <div class="feature-card">
                <div class="feature-icon">✅</div>
                <div>
                    <h3>Track Attendance</h3>
                    <p>
                        Keep track of attendance and your
                        participation in school events.
                    </p>
                </div>
            </div>

        </section>

    </main>

    <footer class="footer">
        <p>
            &copy; <?php echo date("Y"); ?> School Event System.
            All rights reserved.
        </p>
    </footer>

</body>
</html>