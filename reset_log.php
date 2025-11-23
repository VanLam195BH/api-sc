<?php
header("Content-Type: text/plain; charset=utf-8");

// ========== HÀM GHI LOG ==========
function write_log($level, $msg) {
    $time = date("Y-m-d H:i:s");
    file_put_contents("log.txt", "[$time][$level] $msg\n", FILE_APPEND);
}
// File log
$log_file = "log.txt";

// Xóa nội dung (ghi chuỗi rỗng)
file_put_contents($log_file, "");

// Ghi thêm 1 dòng đánh dấu
write_log("INFO", "=== Log đã được reset ===");

// Trả về cho AJAX
echo "OK";
?>
