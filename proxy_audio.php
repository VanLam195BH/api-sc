<?php
header("Content-Type: audio/mpeg");

if (!isset($_GET["id"])) {
    http_response_code(400);
    echo "Missing id";
    exit;
}

$track_id = $_GET["id"];
$client_id = "xwYTVSni6n4FghaI0c4uJ8T9c4pyJ3rh";

// 1. Lấy transcoding list
$info_url = "https://api-v2.soundcloud.com/tracks/$track_id?client_id=$client_id";
$info     = json_decode(file_get_contents($info_url), true);

if (!isset($info["media"]["transcodings"])) {
    http_response_code(400);
    echo "No transcoding";
    exit;
}

// 2. Tìm protocol progressive (mp3)
$mp3_api = null;
foreach ($info["media"]["transcodings"] as $t) {
    if ($t["format"]["protocol"] === "progressive") {
        $mp3_api = $t["url"];
        break;
    }
}

if (!$mp3_api) {
    echo "No progressive mp3";
    exit;
}

// 3. Lấy link mp3 thật
$final_json = json_decode(
    file_get_contents($mp3_api . "?client_id=$client_id"), true
);

if (!isset($final_json["url"])) {
    echo "Cannot get mp3";
    exit;
}

$mp3 = $final_json["url"];

// 4. STREAM MP3 RA ESP32 (CỰC QUAN TRỌNG)
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $mp3);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0");

// Truyền trực tiếp đến ESP32
$fp = fopen('php://output', 'wb');

curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) use ($fp) {
    fwrite($fp, $data);
    return strlen($data);
});

curl_exec($ch);
curl_close($ch);
fclose($fp);
?>
