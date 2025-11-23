<?php
// Đọc log
$logFile = "log.txt";
$logData = file_exists($logFile) ? file($logFile) : [];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>ESP32 Log Viewer</title>

<style>
    body {
        background:#0f0f0f; 
        color:#eee; 
        font-family: Consolas, monospace;
        padding:20px;
    }

    .toolbar {
        margin-bottom:15px;
    }

    button {
        padding:8px 14px; 
        margin-right:10px;
        background:#222; 
        color:#0f0; 
        border:1px solid #0f0; 
        cursor:pointer;
    }

    button:hover { background:#0f0; color:#000; }

    table {
        width:100%;
        border-collapse: collapse;
        font-size:14px;
    }

    th {
        background:#111;
        padding:8px;
        color:#0f0;
        border-bottom:1px solid #333;
    }

    td {
        padding:6px;
        border-bottom:1px solid #222;
        white-space: nowrap;
    }

    /* === MÀU THEO LEVEL === */
    .info { color:#00eaff; }
    .ok   { color:#00ff6a; }
    .warn { color:#ffe600; }
    .err  { color:#ff3c3c; }

    /* === HIGHLIGHT KEYWORD === */
    .kw { background:#302000; color:#ffcc00; padding:2px 0px; border-radius:3px; }
</style>

</head>
<body>

<h2>📜 ESP32 Realtime Log Viewer</h2>

<div class="toolbar">
    <button onclick="resetLog()">🗑 Xóa log</button>
    <button onclick="toggleAuto()">🔄 Auto Refresh: <span id="auto_state">ON</span></button>
</div>

<table id="logTable">
    <thead>
        <tr>
            <th width="180">⏱ Timestamp</th>
            <th width="100">Level</th>
            <th>Nội dung</th>
        </tr>
    </thead>
    <tbody id="logBody"></tbody>
</table>

<script>
let auto = true;
let lastContent = "";

// Gửi AJAX lấy log
function loadLog() {
    if (!auto) return;

    fetch("read_log.php")
        .then(res => res.json())
        .then(data => {
            if (data.content === lastContent) return; // không cần reload
            lastContent = data.content;

            renderLog(data.lines);
        });
}

function renderLog(lines) {
    let tbody = document.getElementById("logBody");
    tbody.innerHTML = "";

    lines.forEach(line => {
        let levelClass = "info";
        if (line.level === "OK") levelClass = "ok";
        if (line.level === "ERROR") levelClass = "err";
        if (line.level === "WARN") levelClass = "warn";

        // Highlight keyword
        let msg = line.msg
            .replace(/song/gi, '<span class="kw">song</span>')
            .replace(/id=/gi, '<span class="kw">id=</span>')
            .replace(/cache/gi, '<span class="kw">cache</span>')
            .replace(/audio/gi, '<span class="kw">audio</span>')
            .replace(/lyric/gi, '<span class="kw">lyric</span>');

        tbody.innerHTML += `
        <tr>
            <td>${line.time}</td>
            <td class="${levelClass}">${line.level}</td>
            <td>${msg}</td>
        </tr>`;
    });
}

// Khi xóa log → load lại ngay lập tức
function resetLog() {
    fetch("reset_log.php")
    .then(() => {
        setTimeout(loadLog, 200); // LOAD NGAY
    });
}

// Clear log
function clearLog() {
    fetch("clear_log.php")
        .then(() => {
            lastContent = "";
            loadLog();
        });
}

// Toggle auto-refresh
function toggleAuto() {
    auto = !auto;
    document.getElementById("auto_state").innerText = auto ? "ON" : "OFF";
}

// Auto refresh mỗi 1.5 giây
setInterval(loadLog, 1500);

// Load lần đầu
loadLog();
</script>

</body>
</html>
