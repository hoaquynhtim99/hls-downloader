<?php

define('NV_ROOTDIR', pathinfo(str_replace(DIRECTORY_SEPARATOR, '/', __FILE__), PATHINFO_DIRNAME));
date_default_timezone_set('Asia/Ho_Chi_Minh');

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
$m3u8 = NV_ROOTDIR . '/tmp.m3u8';
file_put_contents($m3u8, $m3u8Contents, LOCK_EX);

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

$timestart = microtime(true);

echo "Begin download segments:\n";

$outputFile = NV_ROOTDIR . '/tmp.download';
$totalOffset = sizeof($urls);
$offset = 0;

$speed = '0b/s';
$size = 0;

foreach ($urls as $url) {
    $percent = round($offset / $totalOffset * 100, 2);

    $line = "SEG: \033[0;35m" . str_pad($offset . "/" . $totalOffset, 9, ' ', STR_PAD_RIGHT) . "\033[0m";
    $line .= "PER_TT: \033[0;34m" . str_pad($percent . "%", 12, ' ', STR_PAD_RIGHT) . "\033[0m";
    $line .= "SP: \033[0;34m" . str_pad($speed, 15, ' ', STR_PAD_RIGHT) . "\033[0m";
    $line .= "SZ: \033[0;32m" . str_pad(nv_convertfromBytes($size), 15, ' ', STR_PAD_RIGHT) . "\033[0m";

    echo $line . "\n";

    $partContent = file_get_contents($url);
    if (empty ($partContent)) {
        echo "\033[0;31mCan not download segment " . number_format($offset, 0, ',', '.') . "!\033[0m";
        exit (1);
    }

    $size_i = file_put_contents($outputFile, $partContent, FILE_APPEND);
    $offset++;
    $size += $size_i;
    $speed = strtolower(nv_convertfromBytes(intval($size / (microtime(true) - $timestart)))) . '/s';
}

echo "Finish download segments!";

exit(2);

/**
 * @param int $size
 * @return string
 */
function nv_convertfromBytes($size)
{
    if ($size <= 0) {
        return '0 bytes';
    }
    if ($size == 1) {
        return '1 byte';
    }
    if ($size < 1024) {
        return $size . ' bytes';
    }

    $i = 0;
    $iec = [
        'bytes',
        'KB',
        'MB',
        'GB',
        'TB',
        'PB',
        'EB',
        'ZB',
        'YB'
    ];

    while (($size / 1024) > 1) {
        $size = $size / 1024;
        ++$i;
    }

    return number_format($size, 2) . ' ' . $iec[$i];
}
