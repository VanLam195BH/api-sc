<?php
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-cache, no-store, must-revalidate");

function write_log($level, $msg) {
    $time = date("Y-m-d H:i:s");
    file_put_contents("log.txt", "[$time][$level] $msg\n", FILE_APPEND);
}

if (!isset($_GET["song"]) || trim($_GET["song"]) == "") {
    write_log("ERROR", "Missing song parameter");
    echo json_encode(["error"=>"missing_song"]);
    exit;
}

$q = trim($_GET["song"]);
write_log("SEARCH", "Searching: '$q'");

$client_id = "Nhập Client ID";

$search_url = "https://api-v2.soundcloud.com/search/tracks?q=" . urlencode($q) . "&client_id=$client_id&limit=1";


// ====== SỬA LỚN: DÙNG CURL + TIMEOUT + RETRY ======
function safe_get($url, $timeout = 5, $retry = 3) {
    for ($i = 0; $i < $retry; $i++) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => "ESP32-Music-Server",
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($result !== false && $httpCode == 200) {
            return $result;
        }

        write_log("RETRY", "Retry $i HTTP $httpCode url=$url");
        usleep(200000); // wait 200ms
    }
    return false;
}

$data_raw = safe_get($search_url);
if (!$data_raw) {
    write_log("ERROR", "SoundCloud timeout URL=$search_url");
    echo json_encode(["error"=>"api_timeout"]);
    exit;
}

$data = json_decode($data_raw, true);

if (!isset($data["collection"][0])) {
    write_log("WARN", "No result for: $q");
    echo json_encode(["error"=>"not_found"]);
    exit;
}

$track = $data["collection"][0];

$track_id = $track["id"];
$title    = $track["title"];
$artist   = $track["user"]["username"];
$thumb    = $track["artwork_url"] ?? "";

if ($thumb) {
    $thumb = str_replace(["t50x50","t120x120","large"], "t500x500", $thumb);
}

write_log("FOUND", "ID: $track_id | $title - $artist | Audio: /proxy_audio?id=$track_id  | Lyric: /proxy_lyric?id=$track_id");

write_log("RETURN", "Returning song with RELATIVE paths => Audio: /proxy_audio?id=$track_id");
// ====== TRẢ VỀ JSON CHO ESP32 ======
echo json_encode([
    "title"     => $title,
    "artist"    => $artist,
    "audio_url" => "/proxy_audio?id=$track_id",
    "lyric_url" => "/proxy_lyric?id=$track_id",
    "thumbnail" => $thumb,
    "duration"  => intval($track["duration"] / 1000),
    "language"  => "vietnamese"
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
