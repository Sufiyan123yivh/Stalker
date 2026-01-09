<?php
header("Content-Type: application/json");

/// TinyProxy port
// ================================================================= //


//=============================================================================//
// FOR EDUCATION PURPOSE ONLY. Don't Sell this Script, This is 100% Free.
//=============================================================================//

function generateDDToken() {
    return base64_encode(json_encode([
        'schema_version' => '1',
        'os_name' => 'N/A',
        'os_version' => 'N/A',
        'platform_name' => 'Chrome',
        'platform_version' => '104',
        'device_name' => '',
        'app_name' => 'Web',
        'app_version' => '2.52.31',
        'player_capabilities' => [
            'audio_channel' => ['STEREO'],
            'video_codec' => ['H264'],
            'container' => ['MP4', 'TS'],
            'package' => ['DASH', 'HLS'],
            'resolution' => ['240p', 'SD', 'HD', 'FHD'],
            'dynamic_range' => ['SDR']
        ],
        'security_capabilities' => [
            'encryption' => ['WIDEVINE_AES_CTR'],
            'widevine_security_level' => ['L3'],
            'hdcp_version' => ['HDCP_V1', 'HDCP_V2', 'HDCP_V2_1', 'HDCP_V2_2']
        ]
    ]));
}

function generateGuestToken() {
    $bin = bin2hex(random_bytes(16));
    return substr($bin, 0, 8) . '-' .
           substr($bin, 8, 4) . '-' .
           substr($bin, 12, 4) . '-' .
           substr($bin, 16, 4) . '-' .
           substr($bin, 20);
}

function fetchPlatformToken() {
    global $PHONE_PROXY_IP, $PHONE_PROXY_PORT;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://www.zee5.com/live-tv/aaj-tak/0-9-aajtak',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'User-Agent: Mozilla/5.0'
        ]
    ]);

    // ADD PROXY
    addProxy($ch, $PHONE_PROXY_IP, $PHONE_PROXY_PORT);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$response) {
        return ["error" => "Proxy connection failed"];
    }

    if ($httpcode !== 200) {
        return ['error' => "Your server IP is blocked (solution: proxy not active)."];
    }

    preg_match('/"gwapiPlatformToken"\s*:\s*"([^"]+)"/', $response, $matches);
    return $matches[1] ?? ['error' => "Platform token not found."];
}

function fetchM3U8url() {
    global $PHONE_PROXY_IP, $PHONE_PROXY_PORT;

    $guestToken = generateGuestToken();
    $platformToken = fetchPlatformToken();

    if (is_array($platformToken)) return $platformToken; // error

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL =>
            'https://spapi.zee5.com/singlePlayback/getDetails/secure?' .
            'channel_id=0-9-9z583538&device_id=' . $guestToken .
            '&platform_name=desktop_web&translation=en&user_language=en,hi,te&country=IN' .
            '&state=&app_version=4.24.0&user_type=guest&check_parental_control=false',

        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_HTTPHEADER => [
            'accept: application/json',
            'content-type: application/json',
            'origin: https://www.zee5.com',
            'referer: https://www.zee5.com/',
            'user-agent: Mozilla/5.0'
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'x-access-token' => $platformToken,
            'X-Z5-Guest-Token' => $guestToken,
            'x-dd-token' => generateDDToken()
        ])
    ]);

    // ADD PROXY
    addProxy($ch, $PHONE_PROXY_IP, $PHONE_PROXY_PORT);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if (!$data) return ['error' => "Invalid API response (proxy issue)."];

    if (!isset($data['keyOsDetails']['video_token'])) {
        return ['error' => "M3U8 URL not found (token invalid)."];
    }

    return $data['keyOsDetails']['video_token'];
}

function generateCookieZee5($userAgent) {
    global $PHONE_PROXY_IP, $PHONE_PROXY_PORT;

    $m3u8Url = fetchM3U8url();

    if (is_array($m3u8Url)) return $m3u8Url; // error

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $m3u8Url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => $userAgent,
        CURLOPT_FOLLOWLOCATION => true
    ]);

    // ADD PROXY
    addProxy($ch, $PHONE_PROXY_IP, $PHONE_PROXY_PORT);

    $result = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($httpcode !== 200) {
        return ['error' => "hdntl token can't be extracted (proxy blocked or expired)."];
    }

    if (preg_match('/hdntl=([^\s"]+)/', $result, $matches)) {
        return [
            'status' => 'success',
            'cookie' => $matches[0]
        ];
    }

    return ['error' => "Cookie not found."];
}

// ============================================================================
// FINAL OUTPUT
// ============================================================================

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? "Mozilla/5.0";
echo json_encode(generateCookieZee5($userAgent), JSON_PRETTY_PRINT);