<?php
error_reporting(0);

// Hardcoded Stalker Portal Details - Yahan apni details bharen
$url = "http://tv.stream4k.cc/"; // Sirf domain
$mac = "00:1A:79:00:31:14"; 
$sn = "12A1BDB0FEA5D"; 
$device_id_1 = "1F85A5927EC37F7416495E2BC8E7032988F91D59ADA5B939FA56E7E5D957328D";
$device_id_2 = "1F85A5927EC37F7416495E2BC8E7032988F91D59ADA5B939FA56E7E5D957328D";
$sig = "";

$api = "263";
$host = parse_url($url)["host"];

$directories = [
  "data" => "data",
  "filter" => "data/filter",
  "playlist" => "data/playlist"
];

foreach ($directories as $key => $dir_path) {
  if (!is_dir($dir_path)) {
      mkdir($dir_path, 0777, true);
  }
}

$tokenFile = $directories["data"] . "/token.txt";
date_default_timezone_set("Asia/Kolkata");

// Handshake function
function handshake() { 
  global $host;
  $Xurl = "http://$host/stalker_portal/server/load.php?type=stb&action=handshake&token=&JsHttpRequest=1-xml";
  $HED = [
    'User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3',
    'Connection: Keep-Alive',
    'Accept-Encoding: gzip',
    'X-User-Agent: Model: MAG250; Link: WiFi',
    "Referer: http://$host/stalker_portal/c/",
    "Host: $host",
    "Connection: Keep-Alive"
  ];
  $Info_Data = Info($Xurl,$HED);
  $Info_Status = $Info_Data["Info_arr"]["info"];
  $Info_Data =  $Info_Data["Info_arr"]["data"];
  $Info_Data_Json = json_decode($Info_Data,true);
  $Info_Encode = array(
    "Info_arr" => array(
        "token" => $Info_Data_Json["js"]["token"],
        "random" => $Info_Data_Json["js"]["random"],
        "Status Code" => $Info_Status
    )
  );
  return $Info_Encode;
}

// Generate Token function
function generate_token() {
  global $tokenFile, $host, $mac;
  $Info_Decode = handshake();
  $Bearer_token = $Info_Decode["Info_arr"]["token"];
  $Bearer_token = re_generate_token($Bearer_token);
  $Bearer_token = $Bearer_token["Info_arr"]["token"];
  get_profile($Bearer_token);
  file_put_contents($tokenFile, $Bearer_token);  
  return $Bearer_token;
}

// Re Generate Token function
function re_generate_token($Bearer_token) {
  global $host;
  $Xurl = "http://$host/stalker_portal/server/load.php?type=stb&action=handshake&token=$Bearer_token&JsHttpRequest=1-xml";
  $HED = [
      'User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3',
      'Connection: Keep-Alive',
      'Accept-Encoding: gzip',
      'X-User-Agent: Model: MAG250; Link: WiFi',
      "Referer: http://$host/stalker_portal/c/",
      "Host: $host",
      "Connection: Keep-Alive",
  ];
  $Info_Data = Info($Xurl,$HED);
  $Info_Data =  $Info_Data["Info_arr"]["data"];
  $Info_Data_Json = json_decode($Info_Data,true);
  $Info_Encode = array(
    "Info_arr" => array(
    "token" => $Info_Data_Json["js"]["token"],
    "random" => $Info_Data_Json["js"]["random"],
    )
  );
  return $Info_Encode;
}

// Get Profile function
function get_profile($Bearer_token) {
  global $host,$mac,$sn,$device_id_1,$device_id_2,$sig,$api;
  $timestamp = time();
  $Info_Decode = handshake();
  $Info_Decode_Random = $Info_Decode["Info_arr"]["random"];
  $Xurl = "http://$host/stalker_portal/server/load.php?type=stb&action=get_profile&hd=1&ver=ImageDescription%3A+0.2.18-r14-pub-250%3B+ImageDate%3A+Fri+Jan+15+15%3A20%3A44+EET+2016%3B+PORTAL+version%3A+5.1.0%3B+API+Version%3A+JS+API+version%3A+328%3B+STB+API+version%3A+134%3B+Player+Engine+version%3A+0x566&num_banks=2&sn=$sn&stb_type=MAG250&image_version=218&video_out=hdmi&device_id=$device_id_1&device_id2=$device_id_2&signature=$sig&auth_second_step=1&hw_version=1.7-BD-00&not_valid_token=0&client_type=STB&hw_version_2=08e10744513ba2b4847402b6718c0eae&timestamp=$timestamp&api_signature=$api&metrics=%7B%22mac%22%3A%22$mac%22%2C%22sn%22%3A%22$sn%22%2C%22model%22%3A%22MAG250%22%2C%22type%22%3A%22STB%22%2C%22uid%22%3A%22%22%2C%22random%22%3A%22$Info_Decode_Random%22%7D&JsHttpRequest=1-xml";
  $HED = [
    'User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3',
    'Connection: Keep-Alive',
    'Accept-Encoding: gzip',
    'X-User-Agent: Model: MAG250; Link: WiFi',
    "Referer: http://$host/stalker_portal/c/",
    "Authorization: Bearer " . $Bearer_token,
    "Host: $host",
    "Connection: Keep-Alive",
  ];
  Info($Xurl,$HED);
}

// INFO function
function Info($Xurl,$HED) {
  global $mac;
  $cURL_Info = curl_init();
  curl_setopt_array($cURL_Info, [
    CURLOPT_URL => $Xurl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => 'gzip',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_COOKIE => "mac=$mac; stb_lang=en; timezone=GMT",
    CURLOPT_HTTPHEADER => $HED,
  ]);
  $Info_Data = curl_exec($cURL_Info);
  curl_close($cURL_Info);
  $Info_Status = curl_getinfo($cURL_Info);
  $Info_Encode = array(
    "Info_arr" => array(
        "data" => $Info_Data,
        "info" => $Info_Status,
    )
  );
  return  $Info_Encode;
}

// Get Groups function
function group_title($all = false) {
  global $host;
  global $directories;

  $dir_path = $directories["filter"];
  if (!is_dir($dir_path)) {
      mkdir($dir_path, 0777, true);
  }
  $filter_file = "$dir_path/$host.json";

  if (file_exists($filter_file)) {
      $json_data = json_decode(file_get_contents($filter_file), true);
      if (!empty($json_data)) {
          unset($json_data["*"]);
          if ($all) {
              return array_column($json_data, 'title', 'id');
          }
          return array_column(array_filter($json_data, function ($item) {
              return $item['filter'] === true;
          }), 'title', 'id');
      }
  }

  $group_title_url = "http://$host/stalker_portal/server/load.php?type=itv&action=get_genres&JsHttpRequest=1-xml";
  $headers = [
      "User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3",
      "Authorization: Bearer " . generate_token(),
      "X-User-Agent: Model: MAG250; Link: WiFi",
      "Referer: http://$host/stalker_portal/c/",
      "Accept: */*",
      "Host: $host",
      "Connection: Keep-Alive",
      "Accept-Encoding: gzip",
  ];

  $response = Info($group_title_url, $headers);
  if (empty($response["Info_arr"]["data"])) {
      return [];
  }

  $json_api_data = json_decode($response["Info_arr"]["data"], true);
  if (!isset($json_api_data["js"]) || !is_array($json_api_data["js"])) {
      return [];
  }

  $filtered_data = [];
  foreach ($json_api_data["js"] as $genre) {
      if ($genre['id'] === "*") {
          continue;
      }
      $filtered_data[$genre['id']] = [
          'id' => $genre['id'],
          'title' => $genre['title'],
          'filter' => true,
      ];
  }

  file_put_contents($filter_file, json_encode($filtered_data));
  return array_column($filtered_data, 'title', 'id');
}

// Generate ID function
function generateId($cmd) {
    $cmdParts = explode("/", $cmd);
    if ($cmdParts[2] === "localhost") {
        $cmd = str_ireplace('ffrt http://localhost/ch/', '', $cmd);
    } else if ($cmdParts[2] === "") {
        $cmd = str_ireplace('ffrt http:///ch/', '', $cmd);
    }
    return $cmd;
}

// Get Image URL function
function getImageUrl($channel, $host) {    
    $imageExtensions = [".png", ".jpg"];
    $emptyReplacements = ['', ""];
    $logo = str_replace($imageExtensions, $emptyReplacements, $channel['logo']);
    if (is_numeric($logo)) {
        return 'http://' . $host . '/stalker_portal/misc/logos/320/' . $channel['logo'];
    } else {
        return "https://i.ibb.co/DPd27cCK/photo-2024-12-29-23-10-30.jpg";
    }
}

// Fetch Stream URL function (for play)
function fetchStreamUrl($config, $channelId) {
    $headers = getHeaders($config);
    $streamUrlEndpoint = "{$config['stalkerUrl']}server/load.php?type=itv&action=create_link&cmd=ffrt%20http://localhost/ch/{$channelId}&JsHttpRequest=1-xml";
    
    $data = executeCurl($streamUrlEndpoint, $headers);
    
    if (!isset($data['js']['cmd'])) {
        $config['authorizationToken'] = "Bearer " . generate_token();
        $headers = getHeaders($config);
        $data = executeCurl($streamUrlEndpoint, $headers);
    }
    
    return $data['js']['cmd'] ?? die("Failed to retrieve stream URL for channel ID: {$channelId}.");
}

// Get Headers function (for play)
function getHeaders($config) {
    return [
        "Cookie: timezone=GMT; stb_lang=en; mac={$config['macAddress']}",
        "Referer: {$config['stalkerUrl']}",
        "Accept: */*",
        "User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3",
        "X-User-Agent: Model: MAG250; Link: WiFi",
        "Authorization: {$config['authorizationToken']}",
        "Host: " . parse_url($config['stalkerUrl'], PHP_URL_HOST),
        "Connection: Keep-Alive",
        "Accept-Encoding: gzip"
    ];
}

// Execute Curl function (for play)
function executeCurl($url, $headers) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => 'gzip',
    ]);
    
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        die("cURL Error: " . curl_error($ch));
    }
    curl_close($ch);
    
    return json_decode($response, true);
}

// Main logic - check if ID parameter is present
if (isset($_GET['id']) && !empty($_GET['id'])) {
    // Play functionality - stream the channel
    $Bearer_token = (file_exists($tokenFile) && filesize($tokenFile)) ? file_get_contents($tokenFile) : generate_token();
    
    $config = [
        'stalkerUrl' => "http://$host/stalker_portal/",
        'macAddress' => $mac,
        'authorizationToken' => "Bearer $Bearer_token",
    ];
    
    $channelId = $_GET['id'];
    $streamUrl = fetchStreamUrl($config, $channelId);
    header("Location: $streamUrl");
    exit;
} else {
    // Playlist functionality - generate M3U
    $Bearer_token = (file_exists($tokenFile) && filesize($tokenFile)) ? file_get_contents($tokenFile) : generate_token();
    
    $playlist_path = $directories["playlist"];
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $server = $_SERVER['HTTP_HOST'] ?? '';
    $currentScript = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    
    $playlist_file = "$playlist_path/$host.m3u";
    
    if (file_exists($playlist_file)) {
        $playlistContent = file_get_contents($playlist_file);
        $playPath = str_replace("guu.php", "", $currentScript);
        $playlistContent = preg_replace('/^(?!#).*\//m', "{$protocol}{$server}{$playPath}", $playlistContent);
        header('Content-Type: audio/x-mpegurl');
        header('Content-Disposition: inline; filename="playlist.m3u"');
        echo $playlistContent;  
    } else {
        $Playlist_url = "http://$host/stalker_portal/server/load.php?type=itv&action=get_all_channels&JsHttpRequest=1-xml";
    
        $Playlist_HED = [
            "User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3",
            "Authorization: Bearer " . generate_token(),
            "X-User-Agent: Model: MAG250; Link: WiFi",
            "Referer: http://$host/stalker_portal/c/",
            "Accept: */*",
            "Host: $host",
            "Connection: Keep-Alive",
            "Accept-Encoding: gzip",
        ];
    
        $playlist_result = Info($Playlist_url, $Playlist_HED);
        $playlist_result_data = $playlist_result["Info_arr"]["data"];
        $playlist_json_data = json_decode($playlist_result_data,true);
        $timestamp = date('l jS \of F Y h:i:s A');
        $tvCategories = group_title();
    
        if (!empty($playlist_json_data)) {    
            $playlistContent = "#EXTM3U\n#DATE:- $timestamp\n" . PHP_EOL;   
            foreach ($playlist_json_data["js"]["data"] as $channel) {        
                foreach ($tvCategories as $genreId => $categoryName) {              
                    if ($channel['tv_genre_id'] == $genreId) { 
                        $cmd = $channel['cmd'];
                        $id = generateId($cmd);                                       
                        $playPath = str_replace("guu.php", "guu.php?id=" . $id , $currentScript);                                                  
                        $playlistContent .= '#EXTINF:-1 tvg-id="' . $id . '" tvg-logo="' . getImageUrl($channel, $host) . '" group-title="' . $categoryName . '",' . $channel['name'] . "\r\n";
                        $playlistContent .= "{$protocol}{$server}{$playPath}" . PHP_EOL . PHP_EOL;
                    }
                }
            }
            
            header('Content-Type: audio/x-mpegurl');
            header('Content-Disposition: inline; filename="playlist.m3u"');
            echo $playlistContent;    
            file_put_contents("$playlist_path/$host.m3u", $playlistContent);
        } else {    
            echo 'Empty or invalid response from the server.';    
        }
    }
}
?>
