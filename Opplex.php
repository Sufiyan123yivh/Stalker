<?php

// -------------------------
// CORS
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    http_response_code(204);
    exit;
}

header("Access-Control-Allow-Origin: *");

// -------------------------
// Detect TS Request
// -------------------------
if (isset($_GET['ts'])) {
    serve_ts($_GET['ts'], $_GET['base']);
    exit;
}

// -------------------------
// Validate id
// -------------------------
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo "Missing id";
    exit;
}

$id = $_GET['id'];

// -------------------------
// STEP 1 — Request Opplex URL
// -------------------------
$firstUrl = "http://opplex.to/live/umar/umar123/{$id}.m3u8";

$playlist = curl_fetch($firstUrl, $finalUrl);

if (!$playlist) {
    http_response_code(500);
    echo "Error fetching playlist.";
    exit;
}

// Extract final playlist host
$parsed = parse_url($finalUrl);
$baseHost = $parsed['scheme'] . "://" . $parsed['host'] . ":" . ($parsed['port'] ?? 80);

// -------------------------
// STEP 2 — Rewrite TS URLs to proxy
// -------------------------
$lines = explode("\n", $playlist);
$new = [];

foreach ($lines as $line) {
    $trim = trim($line);

    if ($trim === "" || str_starts_with($trim, "#")) {
        $new[] = $line;
        continue;
    }

    // Convert "/hls/xxx.ts" → proxy.php?ts=/hls/xxx.ts&base=<host>
    if (!preg_match('/^https?:\/\//', $trim)) {
        $enc = urlencode($trim);
        $encHost = urlencode($baseHost);
        $line = "Opplex.php?ts={$enc}&base={$encHost}";
    }

    $new[] = $line;
}

$output = implode("\n", $new);

// -------------------------
// STEP 3 — Send final rewritten playlist
// -------------------------
header("Content-Type: application/vnd.apple.mpegurl");
echo $output;


// -------------------------
// Fetch function
// -------------------------
function curl_fetch($url, &$final = null)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => "Mozilla/5.0",
    ]);

    $res = curl_exec($ch);

    $final = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    return $res;
}

// -------------------------
// Serve TS segments properly
// -------------------------
function serve_ts($ts, $base)
{
    $path = urldecode($ts);
    $host = urldecode($base);

    // Build the real segment URL
    $segmentUrl = rtrim($host, "/") . $path;

    $data = curl_fetch($segmentUrl, $x);

    if (!$data) {
        http_response_code(404);
        exit;
    }

    header("Content-Type: video/mp2t");
    echo $data;
}

?>
