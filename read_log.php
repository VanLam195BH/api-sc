<?php
$logFile = "log.txt";

if (!file_exists($logFile)) {
    echo json_encode(["lines"=>[], "content"=>""]);
    exit;
}

$lines = file($logFile, FILE_IGNORE_NEW_LINES);
$parsed = [];

foreach ($lines as $line) {
    // Format: [2025-02-15 13:05:44][OK] Serving audio...
    preg_match('/\[(.*?)\]\[(.*?)\] (.*)/', $line, $m);

    $parsed[] = [
        "time"  => $m[1] ?? "",
        "level" => $m[2] ?? "INFO",
        "msg"   => $m[3] ?? $line
    ];
}

echo json_encode([
    "content" => implode("\n", $lines),
    "lines"   => $parsed
]);
?>
