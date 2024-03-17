<?php

define('NV_ROOTDIR', pathinfo(str_replace(DIRECTORY_SEPARATOR, '/', __FILE__), PATHINFO_DIRNAME));
require NV_ROOTDIR . '/functions.php';

$url = $argv[1] ?? '';
if (empty($url)) {
    echo "\033[0;31mNo url specified!\033[0m";
    exit (1);
}

echo "Begin get url info\n";

$headers = get_headers($url);
if (empty ($headers)) {
    echo "\033[0;31mUnable to collect header from specified url!\033[0m";
    exit (1);
}

// Đọc định dạng
$contentType = '';
foreach ($headers as $line) {
    $line = trim($line);
    if (preg_match('/^Content\-Type[\s]*\:[\s]*(.+)/i', $line, $m)) {
        $contentType = strtolower(trim($m[1]));
    }
}
if (empty ($contentType)) {
    echo "\033[0;31mUnable to detect Content-Type!\033[0m";
    exit (1);
}

$contentTypeAccepted = [
    'application/x-mpegurl' => ['code' => 2, 'name' => 'MPEG transport stream']
];
if (!isset ($contentTypeAccepted[$contentType])) {
    echo "\033[0;31mContent-Type " . $contentType . " is not allowed!\033[0m";
    exit (1);
}

echo "Content-Type: \033[0;34m" . $contentTypeAccepted[$contentType]['name'] . "\033[0m\n";

$m3u8Contents = file_get_contents($url);
if (empty ($m3u8Contents)) {
    echo "\033[0;31mCan not download m3u8 from url!\033[0m";
    exit (1);
}

// Ghi ra file tạm
$m3u8 = NV_ROOTDIR . '/meta/list.m3u8';
$ffmpeg_mode = NV_ROOTDIR . '/meta/ffmpeg_mode.txt';
file_put_contents($m3u8, $m3u8Contents, LOCK_EX);
file_put_contents($ffmpeg_mode, $contentTypeAccepted[$contentType]['code'], LOCK_EX);

// Đọc file
$fp = fopen($m3u8, 'r');
if (!$fp) {
    echo "\033[0;31mCan not open m3u8 downloaded file!\033[0m";
    exit (1);
}

$urls = [];

while (($buffer = fgets($fp, 4096)) !== false) {
    $line = trim($buffer);

    // Bỏ qua các dòng trống
    if (empty ($line)) {
        continue;
    }

    if (preg_match('/^http(s)*\:\/\//', $line)) {
        $urls[] = $line;
    }
}
if (!feof($fp)) {
    echo "\033[0;31mError: unexpected fgets() fail!\033[0m";
    exit (1);
}
fclose($fp);

if (empty ($urls)) {
    echo "\033[0;31mNo segment to download!\033[0m";
    exit (1);
}

$chunksize = ceil(sizeof($urls) / 3);
if ($chunksize < 2) {
    echo "\033[0;31mLess segments, please run single thread download!\033[0m";
    exit (1);
}
$chunks = array_chunk($urls, $chunksize, true);
if (sizeof($chunks) !== 3) {
    echo "\033[0;31mMake chunks error!\033[0m";
    exit (1);
}

echo "Make chunks complete\n";

file_put_contents(NV_ROOTDIR . '/meta/thread_1.json', json_encode($chunks[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
file_put_contents(NV_ROOTDIR . '/meta/thread_2.json', json_encode($chunks[1], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
file_put_contents(NV_ROOTDIR . '/meta/thread_3.json', json_encode($chunks[2], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
file_put_contents(NV_ROOTDIR . '/meta/segs_total.txt', sizeof($urls), LOCK_EX);
