<?php
header("Content-Type: text/plain; charset=utf-8");

if (!isset($_GET["id"])) { echo ""; exit; }

$id = intval($_GET["id"]);
$client_id = "xwYTVSni6n4FghaI0c4uJ8T9c4pyJ3rh";

// ============================
// 1) Lấy thông tin bài hát
// ============================
$info_url = "https://api-v2.soundcloud.com/tracks/$id?client_id=$client_id";
$info_raw = @file_get_contents($info_url);

if (!$info_raw || substr(trim($info_raw), 0, 1) != "{") {
    echo "";
    exit;
}

$info = json_decode($info_raw, true);

$title  = $info["title"] ?? "Unknown Title";
$artist = $info["user"]["username"] ?? "Unknown Artist";




// ============================
// Lyric ảo nếu không có lyric thật
// ============================
function fake_lrc($title, $artist) {
    $lines = [
        "Chúc bạn nghe nhạc vui vẻ!",
        "Chúc bạn có một ngày thật tuyệt!",
        "Âm nhạc giúp tâm hồn thư giãn!",
        "Cảm ơn bạn đã sử dụng XiaoZhi!",
    ];

    $lrc  = "[00:00.00]《$title - $artist\n";

    $time = 1;
    foreach ($lines as $line) {
        $mm = floor($time / 60);
        $ss = floor($time % 60);
        $ms = "00";
        $lrc .= sprintf("[%02d:%02d.%s]%s\n", $mm, $ss, $ms, $line);
        $time += 4;
    }

    return $lrc;
}


// ============================
// 2) Lấy lyric thật từ SoundCloud
// ============================
$lyric_url = "https://api-v2.soundcloud.com/tracks/$id/lyrics?client_id=$client_id";
$lyric_raw = @file_get_contents($lyric_url);

// Nếu không có lyric → trả lyric ảo
if (!$lyric_raw || substr(trim($lyric_raw), 0, 1) != "{") {
    echo fake_lrc($title, $artist);
    exit;
}

$lj = json_decode($lyric_raw, true);
if (!isset($lj["lyrics"]["lines"])) {
    echo fake_lrc($title, $artist);
    exit;
}


// ============================
// 3) Ghép lyric thật + header
// ============================

foreach ($lj["lyrics"]["lines"] as $line) {

    if (!isset($line["start"]) || !isset($line["text"])) continue;

    $ms = intval($line["start"]);
    $text = $line["text"];

    $m  = floor($ms/60000);
    $s  = floor(($ms%60000)/1000);
    $ms2 = floor(($ms%1000)/10);

    $lrc .= sprintf("[%02d:%02d.%02d]%s\n", $m, $s, $ms2, $text);
}

echo $lrc;
exit;
?>
