<?php

// CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Validate ID
if (!isset($_GET["id"]) || empty($_GET["id"])) {
    http_response_code(400);
    echo "Missing id";
    exit;
}

$id = $_GET["id"];

// STEP 1 — Load original URL
$start = "http://opplex.to/live/umar/umar123/{$id}.m3u8";

$ch = curl_init($start);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_USERAGENT => "Mozilla/5.0",
]);

$body = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

// STEP 2 — Get final host after redirect
$final = $info["url"];    // EFFECTIVE URL after redirect

if (!$final) {
    // fallback
    $final = $start;
}

// Extract base domain
$u = parse_url($final);
$port = isset($u["port"]) ? ":" . $u["port"] : "";
$base = $u["scheme"] . "://" . $u["host"] . $port;

// STEP 3 — Rewrite only TS segments to absolute URLs
$lines = explode("\n", $body);
$out = [];

foreach ($lines as $line) {
    $trim = trim($line);

    if ($trim === "" || str_starts_with($trim, "#")) {
        $out[] = $line;
        continue;
    }

    // if it's a relative path like "/hls/xxxx.ts"
    if (str_starts_with($trim, "/hls/")) {
        $out[] = $base . $trim;   // convert to absolute
        continue;
    }

    // If it's already absolute URL, keep it
    $out[] = $line;
}

// STEP 4 — Output final M3U8
header("Content-Type: application/vnd.apple.mpegurl");
echo implode("\n", $out);
