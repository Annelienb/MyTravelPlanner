<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respond(405, ['error' => 'Use the search form to submit a request.']);
}

$apiKey = getenv('SERPAPI_KEY');
if ($apiKey === false || trim($apiKey) === '') {
    respond(503, ['error' => 'Live search is not configured yet. Add a SERPAPI_KEY server environment variable to enable it.']);
}

session_start();
$now = time();
$recentRequests = array_filter($_SESSION['travel_search_requests'] ?? [], static fn ($time): bool => is_int($time) && $time > $now - 60);
if (count($recentRequests) >= 10) {
    respond(429, ['error' => 'Too many searches in a short time. Please wait a minute and try again.']);
}
$recentRequests[] = $now;
$_SESSION['travel_search_requests'] = array_values($recentRequests);
session_write_close();

$kind = $_POST['kind'] ?? '';
$parameters = [
    'api_key' => $apiKey,
    'hl' => 'en',
    'gl' => 'us',
    'currency' => strtoupper((string)($_POST['currency'] ?? 'USD')),
    'adults' => (string)($_POST['adults'] ?? '1'),
];

if (!in_array($parameters['currency'], ['USD', 'EUR', 'GBP'], true)) {
    respond(400, ['error' => 'Choose a supported currency.']);
}
if (!ctype_digit($parameters['adults']) || (int)$parameters['adults'] < 1 || (int)$parameters['adults'] > 4) {
    respond(400, ['error' => 'Choose between 1 and 4 adults.']);
}

$isValidDate = static function (string $value): bool {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
};

if ($kind === 'flights') {
    $origin = strtoupper(trim((string)($_POST['origin'] ?? '')));
    $destination = strtoupper(trim((string)($_POST['destination'] ?? '')));
    $departure = trim((string)($_POST['departure'] ?? ''));
    $return = trim((string)($_POST['return'] ?? ''));

    if (!preg_match('/^[A-Z]{3}$/', $origin) || !preg_match('/^[A-Z]{3}$/', $destination)) {
        respond(400, ['error' => 'Enter 3-letter airport codes, such as AMS or JFK.']);
    }
    if (!$isValidDate($departure) || ($return !== '' && !$isValidDate($return))) {
        respond(400, ['error' => 'Enter valid travel dates.']);
    }
    if ($departure < gmdate('Y-m-d') || ($return !== '' && $return <= $departure)) {
        respond(400, ['error' => 'Departure must be today or later, and return must be after departure.']);
    }

    $parameters += [
        'engine' => 'google_flights',
        'departure_id' => $origin,
        'arrival_id' => $destination,
        'outbound_date' => $departure,
        'type' => $return === '' ? '2' : '1',
        'sort_by' => '2',
    ];
    if ($return !== '') {
        $parameters['return_date'] = $return;
    }
} elseif ($kind === 'hotels') {
    $destination = trim((string)($_POST['destination'] ?? ''));
    $checkIn = trim((string)($_POST['check_in'] ?? ''));
    $checkOut = trim((string)($_POST['check_out'] ?? ''));

    if ($destination === '' || mb_strlen($destination) > 100) {
        respond(400, ['error' => 'Enter a destination up to 100 characters long.']);
    }
    if (!$isValidDate($checkIn) || !$isValidDate($checkOut)) {
        respond(400, ['error' => 'Enter valid check-in and check-out dates.']);
    }
    if ($checkIn < gmdate('Y-m-d') || $checkOut <= $checkIn) {
        respond(400, ['error' => 'Check-in must be today or later, and check-out must be after check-in.']);
    }

    $parameters += [
        'engine' => 'google_hotels',
        'q' => $destination,
        'check_in_date' => $checkIn,
        'check_out_date' => $checkOut,
        'sort_by' => '3',
    ];
} else {
    respond(400, ['error' => 'Choose a valid travel search.']);
}

$curl = curl_init('https://serpapi.com/search.json?' . http_build_query($parameters));
if ($curl === false) {
    respond(500, ['error' => 'Could not start the travel search.']);
}
curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$responseBody = curl_exec($curl);
$curlError = curl_error($curl);
$httpStatus = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($responseBody === false || $httpStatus < 200 || $httpStatus >= 300) {
    error_log('Travel search provider request failed: ' . ($curlError !== '' ? $curlError : 'HTTP ' . $httpStatus));
    respond(502, ['error' => 'The travel search provider is unavailable right now. Please try again later.']);
}

$results = json_decode($responseBody, true);
if (!is_array($results) || isset($results['error'])) {
    error_log('Travel search provider returned an invalid response or API error.');
    respond(502, ['error' => 'The travel search provider could not complete the search. Check API access and try again.']);
}

if ($kind === 'flights') {
    respond(200, [
        'kind' => $kind,
        'best_flights' => array_slice(is_array($results['best_flights'] ?? null) ? $results['best_flights'] : [], 0, 5),
        'other_flights' => array_slice(is_array($results['other_flights'] ?? null) ? $results['other_flights'] : [], 0, 5),
        'search_metadata' => $results['search_metadata'] ?? [],
    ]);
}

respond(200, [
    'kind' => $kind,
    'properties' => array_slice(is_array($results['properties'] ?? null) ? $results['properties'] : [], 0, 8),
    'search_metadata' => $results['search_metadata'] ?? [],
]);
