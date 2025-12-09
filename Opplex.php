<?php

// Enable CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    http_response_code(204);
    exit;
}

header("Access-Control-Allow-Origin: *");

// Validate id
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing 'id' query parameter."]);
    exit;
}

$id = $_GET['id'];

// Proxy URL
$targetUrl = "http://opplex.to/live/umar/umar123/{$id}.m3u8";

// cURL request
$ch = curl_init($targetUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HEADER => true,
    CURLOPT_TIMEOUT => 15,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);

if ($httpCode < 200 || $httpCode >= 300) {
    http_response_code($httpCode);
    echo json_encode(["error" => "Failed to fetch data from upstream server."]);
    exit;
}

// Extract body from header
$body = substr($response, $headerSize);

// Domain of final URL
$domain = parse_url($finalUrl, PHP_URL_SCHEME) . "://" . parse_url($finalUrl, PHP_URL_HOST);

// Modify M3U8 content
$lines = explode("\n", $body);
$modified = [];

foreach ($lines as $line) {
    $trim = trim($line);

    if ($trim === "" || str_starts_with($trim, "#")) {
        $modified[] = $line;
        continue;
    }

    // If line contains segment URLs or ends with .ts
    if (strpos($trim, "/hls/") !== false || str_ends_with($trim, ".ts")) {
        if (!preg_match('/^https?:\/\//', $trim)) {
            $line = $domain . $trim;
        }
    }

    $modified[] = $line;
}

$modifiedData = implode("\n", $modified);

// Output
header("Content-Type: application/vnd.apple.mpegurl");
header("Cache-Control: public, max-age=10");

echo $modifiedData;
