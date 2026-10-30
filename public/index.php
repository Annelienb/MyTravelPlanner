<?php
require_once __DIR__ . '/../config/helpers.php';

$defaultPageText = [
    'title' => 'Go somewhere wonderful.',
    'subtitle' => 'Keep the little details in one lovely place, and make more room for the moments that matter.',
];

$pdo = null;
if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
}

$pageText = $defaultPageText;
$trips = [];

if (!empty($pdo) && $pdo instanceof PDO) {
    try {
        // Load editable homepage content.
        $stmtText = $pdo->prepare('SELECT title, body FROM page_content WHERE page_key = ?');
        $stmtText->execute(['Home']);
        $pageContent = $stmtText->fetch();
        if ($pageContent) {
            $pageText = [
                'title' => $pageContent['title'],
                'subtitle' => $pageContent['body'],
            ];
        }

        // Load trips for the overview.
        $stmtTrips = $pdo->query('SELECT id, title, country, city, start_date, end_date, budget, description, cover_image FROM trips ORDER BY start_date ASC');
        $trips = $stmtTrips->fetchAll();
    } catch (Throwable $e) {
        $trips = [];
        $pageText = $defaultPageText;
    }
}

$pageTitle = $pageText['title'] ?? $defaultPageText['title'];
$pageSubtitle = $pageText['subtitle'] ?? $defaultPageText['subtitle'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?></title>
    <link rel="stylesheet" href="css/style.css?v=2">
    <link rel="icon" href="../uploads/Travelly.png" type="image/png">
    <script src="https://kit.fontawesome.com/a369785260.js" crossorigin="anonymous"></script>
</head>
<body class="home-page">

    <!-- Main navigation -->
    <nav class="navbar" aria-label="Main navigation">
        <a class="logo" href="index.php" aria-label="Travelly home">
            <img src="../uploads/Travelly.png" alt="">
            <span>Travelly</span>
        </a>
        <div class="nav-actions">
            <a class="nav-link" href="about.php">About</a>
            <a class="nav-link" href="../admin/login.php">Admin</a>
        </div>
    </nav>

    <!-- Main page layout -->
    <div class="main-container">
        
        <!-- Editable homepage content -->
        <header class="hero-banner">
            <h1><?= h($pageTitle) ?></h1>
            <p><?= h($pageSubtitle) ?></p>
        </header>

        <!-- Trip overview -->
        <main class="trip-overview">
            <div class="section-heading">
                <h2>Explore trips</h2>
                <span>Travel ideas by Travelly</span>
            </div>

            <div class="info-strip">
                <div><strong><?= count($trips) ?></strong><span><?= count($trips) === 1 ? 'shared trip' : 'shared trips' ?></span></div>
                <div><strong id="personal-trip-count">0</strong><span id="personal-trip-count-label">personal trips</span></div>
                <div><strong id="checklist-item-count">3</strong><span id="checklist-item-count-label">checklist items</span></div>
            </div>

            <section class="personal-trips-section" id="trip-planner" aria-labelledby="personal-trips-title">
                <div class="personal-trips-intro">
                    <p class="eyebrow">Your private planner</p>
                    <h2 id="personal-trips-title">Plan your own trips</h2>
                    <p>Add trips right here. Your trips and checklist are saved only in this browser.</p>
                    <p class="api-attribution">Destination search and forecast data by <a href="https://open-meteo.com/" target="_blank" rel="noopener noreferrer">Open-Meteo</a>.</p>
                    <p id="personal-trip-storage-status" class="storage-status" role="status" aria-live="polite"></p>
                </div>

                <div class="personal-trip-layout">
                    <form id="personal-trip-form" class="card personal-trip-form">
                        <h3 id="personal-trip-form-title">Add a trip</h3>
                        <input id="personal-trip-id" name="id" type="hidden">
                        <label for="personal-trip-name">Trip name</label>
                        <input id="personal-trip-name" name="trip_name" type="text" maxlength="100" placeholder="Weekend in Rome" required>
                        <label for="personal-trip-destination">Destination</label>
                        <input id="personal-trip-destination" name="destination" type="search" maxlength="100" placeholder="Search for a city" autocomplete="off" aria-autocomplete="list" aria-controls="destination-suggestions" aria-expanded="false" required>
                        <div id="destination-suggestions" class="destination-suggestions" role="listbox" hidden></div>
                        <p id="destination-status" class="field-hint" role="status" aria-live="polite"></p>
                        <input id="personal-trip-latitude" name="latitude" type="hidden">
                        <input id="personal-trip-longitude" name="longitude" type="hidden">
                        <input id="personal-trip-country" name="country" type="hidden">
                        <div class="personal-trip-dates">
                            <div>
                                <label for="personal-trip-start">Departure</label>
                                <input id="personal-trip-start" name="start_date" type="date">
                            </div>
                            <div>
                                <label for="personal-trip-end">Return</label>
                                <input id="personal-trip-end" name="end_date" type="date">
                            </div>
                        </div>
                        <p id="trip-weather" class="weather-summary" role="status" aria-live="polite"></p>
                        <label for="personal-trip-notes">Notes</label>
                        <textarea id="personal-trip-notes" name="notes" maxlength="2000" placeholder="What would you like to do?"></textarea>
                        <p id="personal-trip-error" class="error" role="alert" hidden></p>
                        <button id="personal-trip-submit" type="submit" class="btn">Save my trip</button>
                        <button id="personal-trip-cancel" type="button" class="btn secondary" hidden>Cancel edit</button>
                    </form>

                    <div id="personal-trip-list" class="personal-trip-list" aria-live="polite">
                        <div class="empty-state personal-trip-empty">
                            <p>Your personal trip list is empty. Add a trip to get started.</p>
                        </div>
                    </div>
                </div>
            </section>

            <div class="trips-grid">
                <?php if (!empty($trips)): ?>
                    <?php foreach ($trips as $trip): ?>
                        <div class="trip-card">
                            <?php if (!empty($trip['cover_image'])): ?>
                                <img src="../<?= h($trip['cover_image']) ?>" alt="<?= h($trip['title']) ?>" class="trip-img">
                            <?php endif; ?>
                            <h3><?= h($trip['title']) ?></h3>
                            <p><i class="fa-solid fa-location-dot" aria-hidden="true"></i> <?= h($trip['city'] . ', ' . $trip['country']) ?></p>
                            <a href="detail.php?id=<?= (int)$trip['id'] ?>" class="btn">View trip details</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>

        <!-- Interactive packing checklist -->
        <aside class="sidebar">
            <div class="card">
                <h3>Packing checklist</h3>
                <p id="packed-counter" aria-live="polite">0 of 3 packed</p>
                <div id="checklist-items" class="checklist-items"></div>
                <form id="checklist-add-form" class="checklist-add-form">
                    <label for="checklist-new-item">Add your own item</label>
                    <input id="checklist-new-item" name="item" type="text" maxlength="80" placeholder="Wallet" required>
                    <button type="submit" class="btn secondary">Add item</button>
                </form>
                <p id="checklist-status" class="field-hint" role="status" aria-live="polite"></p>
            </div>

        </aside>

    </div>

    <section class="travel-features" aria-label="Travel planning tools">
        <div class="travel-tool-stack">
            <article class="feature-box travel-tool" id="flights">
                <div class="travel-tool-heading">
                    <span class="feature-icon"><i class="fa-solid fa-plane" aria-hidden="true"></i></span>
                    <div><p class="tool-kicker">Compare options</p><h2>Flights</h2></div>
                </div>
                <p>Find and compare flight options on Skyscanner.</p>
                <a class="btn" href="https://www.skyscanner.net/" target="_blank" rel="noopener noreferrer">Find flights on Skyscanner</a>
            </article>

            <article class="feature-box travel-tool" id="stays">
                <div class="travel-tool-heading">
                    <span class="feature-icon"><i class="fa-solid fa-hotel" aria-hidden="true"></i></span>
                    <div><p class="tool-kicker">Find a place</p><h2>Stays</h2></div>
                </div>
                <p>Find places to stay on Booking.com.</p>
                <a class="btn" href="https://www.booking.com/" target="_blank" rel="noopener noreferrer">Find stays on Booking.com</a>
            </article>
        </div>

        <article class="feature-box travel-tool" id="itinerary">
            <div class="travel-tool-heading">
                <span class="feature-icon"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i></span>
                <div><p class="tool-kicker">Keep your plans</p><h2>Itinerary</h2></div>
            </div>
            <p>Add the stops and small details you want to remember.</p>
            <form id="itinerary-form" class="travel-search-form">
                <input name="id" type="hidden">
                <label for="itinerary-title">Plan item</label>
                <input id="itinerary-title" name="title" type="text" maxlength="100" placeholder="Museum visit" required>
                <div class="search-form-row">
                    <div><label for="itinerary-date">Date</label><input id="itinerary-date" name="date" type="date" required></div>
                    <div><label for="itinerary-time">Time</label><input id="itinerary-time" name="time" type="time"></div>
                </div>
                <label for="itinerary-place">Place (optional)</label>
                <input id="itinerary-place" name="place" type="text" maxlength="120" placeholder="Address or place name">
                <label for="itinerary-notes">Notes (optional)</label>
                <textarea id="itinerary-notes" name="notes" maxlength="500" placeholder="Anything to remember"></textarea>
                <button id="itinerary-submit" type="submit" class="btn">Add to itinerary</button>
                <button id="itinerary-cancel" type="button" class="btn secondary" hidden>Cancel edit</button>
            </form>
            <p id="itinerary-status" class="search-status" role="status" aria-live="polite"></p>
            <div id="itinerary-list" class="itinerary-list" aria-live="polite"></div>
            <p class="field-hint">Your itinerary is saved only in this browser.</p>
        </article>
    </section>

    <footer class="site-footer">
        <a class="logo" href="index.php" aria-label="Travelly home">
            <img src="../uploads/Travelly.png" alt="">
            <span>Travelly</span>
        </a>
        <p>Don't stress and just relax.</p>
        <small>&copy; <?= date('Y') ?> Travelly. Your personal trip planner.</small>
    </footer>

    <script src="js/main.js?v=2"></script>
</body>
</html>