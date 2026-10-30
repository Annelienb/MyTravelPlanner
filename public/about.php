<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Travelly</title>
    <link rel="stylesheet" href="css/style.css?v=5">
    <link rel="icon" href="../uploads/Travelly.png" type="image/png">
    <script src="https://kit.fontawesome.com/a369785260.js" crossorigin="anonymous"></script>
</head>
<body class="about-background">
    <nav class="navbar" aria-label="Main navigation">
        <a class="logo" href="index.php" aria-label="Travelly home">
            <img src="../uploads/Travelly.png" alt="">
            <span>Travelly</span>
        </a>
        <div class="nav-actions">
            <a class="nav-link" href="index.php">Home</a>
            <a class="nav-link" href="about.php" aria-current="page">About</a>
            <a class="nav-link" href="../admin/login.php">Admin</a>
        </div>
    </nav>

    <main>
        <section class="about-section about-page" aria-labelledby="about-title">
            <div class="about-intro">
                <p class="eyebrow">A little about us</p>
                <h1 id="about-title">Travel planner</h1>
                <p>Travelly is a simple place to gather your ideas, shape a trip, and don't forget things before you go. No rush to plan it all at once it's there to make the next step easier.</p>
                <a class="btn about-cta" href="index.php#trip-planner">Start planning <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>

            <div class="about-notes">
                <article class="about-note">
                    <i class="fa-solid fa-map" aria-hidden="true"></i>
                    <h2>Make it personal</h2>
                    <p>Keep your own trip ideas and notes together in your browser.</p>
                </article>
                <article class="about-note">
                    <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                    <h2>Feel ready to go</h2>
                    <p>Build a checklist that fits the way you like to travel.</p>
                </article>
                <article class="about-note">
                    <i class="fa-solid fa-compass" aria-hidden="true"></i>
                    <h2>Find your next place</h2>
                    <p>Browse shared trip inspiration and start imagining what the next step looks like.</p>
                </article>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <a class="logo" href="index.php" aria-label="Travelly home">
            <img src="../uploads/Travelly.png" alt="">
            <span>Travelly</span>
        </a>
        <p>Don't stress and just relax.</p>
        <small>&copy; <?= date('Y') ?> Travelly. Your personal trip planner.</small>
    </footer>
</body>
</html>