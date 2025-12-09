<?php

// CORS Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    http_response_code(204);
    exit;
}

// Validate ?id=
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing 'id' query parameter."]);
    exit;
}

$id = preg_replace('/[^0-9]/', '', $_GET['id']); // sanitize numeric ID
$targetUrl = "https://opplex.rw/live/umar/umar123/{$id}.m3u8";

// CURL Fetch Function
function curl_get($url) {
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    $info     = curl_getinfo($ch);

    curl_close($ch);

    return [$response, $error, $info];
}

list($response, $error, $info) = curl_get($targetUrl);

if ($error || $info['http_code'] !== 200) {
    http_response_code($info['http_code'] ?: 500);
    echo json_encode(["error" => "Failed to fetch data from upstream"]);
    exit;
}

// Extract final URL
$finalUrl = $info['url'];
$domain = preg_replace('#/+$#', '', parse_url($finalUrl, PHP_URL_SCHEME) . "://" . parse_url($finalUrl, PHP_URL_HOST));

// Split header + body
$headerSize = $info['header_size'];
$body = substr($response, $headerSize);

// Rewrite .ts & HLS URLs
$lines = explode("\n", $body);
$modified = [];

foreach ($lines as $line) {
    $trim = trim($line);

    if (
        (strpos($trim, "/hls/") !== false || str_ends_with($trim, ".ts")) &&
        !preg_match('#^https?://#', $trim)
    ) {
        $modified[] = $domain . $trim;
    } else {
        $modified[] = $line;
    }
}

$finalPlaylist = implode("\n", $modified);

// Output
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/vnd.apple.mpegurl");
header("Cache-Control: public, max-age=10");

echo $finalPlaylist;
