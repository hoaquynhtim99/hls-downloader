<?php

define('NV_ROOTDIR', pathinfo(str_replace(DIRECTORY_SEPARATOR, '/', __FILE__), PATHINFO_DIRNAME));
require NV_ROOTDIR . '/functions.php';

$file_error = NV_ROOTDIR . '/meta/error.txt';

// Lấy và check số thread
$segOffset = intval($argv[1] ?? -1);
if ($segOffset < 1 or $segOffset > 3) {
    $error = "Error segment offset";
    file_put_contents($file_error, $error, LOCK_EX);
    die("\033[0;31m" . $error . "!\033[0m");
}

// Kiểm tra file chứa các urls
$file_json = NV_ROOTDIR . '/meta/thread_' . $segOffset . '.json';
if (!file_exists($file_json)) {
    $error = "Error segment json not exists";
    file_put_contents($file_error, $error, LOCK_EX);
    die("\033[0;31m" . $error . "!\033[0m");
}

// Đọc json sang array
$urls = json_decode(file_get_contents($file_json), true);
if (empty($urls) or !is_array($urls)) {
    $error = "Segment data of thread " . $segOffset . " not json or empty";
    file_put_contents($file_error, $error, LOCK_EX);
    die("\033[0;31m" . $error . "!\033[0m");
}

// Số segment tổng cộng
$totalOffset = 0;
$total_file = NV_ROOTDIR . '/meta/segs_total.txt';
if (file_exists($total_file)) {
    $totalOffset = intval(file_get_contents($total_file));
}
$totalInThread = sizeof($urls);

// Thời gian bắt đầu
$startTime = time();
if (file_exists(NV_ROOTDIR . '/meta/start.txt')) {
    $startTime = intval(file_get_contents(NV_ROOTDIR . '/meta/start.txt'));
}

// Lấy dung lượng đã tải về trước đó
$size_old = getTotalFileSize(NV_ROOTDIR . '/data');

// Tải từng segment về
$offset = 0;
foreach ($urls as $index => $url) {
    $offset++;
    $segment_file = NV_ROOTDIR . '/data/seg_' . $index;

    // Bỏ qua các phân đoạn đã tải về thành công
    $segExistsSize = file_exists($segment_file) ? filesize($segment_file) : 0;
    if ($segExistsSize > 0) {
        continue;
    }

    // Tải về phân đoạn
    $segContent = file_get_contents($url);
    if (empty ($segContent)) {
        $error = "Segment " . number_format($index, 0) . " can not download";
        file_put_contents($file_error, $error, LOCK_EX);
        die("\033[0;31m" . $error . "!\033[0m");
    }

    // Ghi phân đoạn ra file
    $sizeWrited = file_put_contents($segment_file, $segContent, LOCK_EX);
    if (empty ($sizeWrited)) {
        $error = "Segment " . number_format($index, 0) . " downloaded but can not write to disk";
        file_put_contents($file_error, $error, LOCK_EX);
        die("\033[0;31m" . $error . "!\033[0m");
    }

    $totalSegsDownloaded = countFilesInFolder(NV_ROOTDIR . '/data');
    $percent_thread = number_format(round($offset / $totalInThread * 100, 2), 2);
    $percent_total = number_format(round($totalSegsDownloaded / $totalOffset * 100, 2), 2);
    $size = getTotalFileSize(NV_ROOTDIR . '/data') - $size_old;
    $speed = strtolower(nv_convertfromBytes(intval($size / (time() - $startTime)))) . '/s';

    // Tính toán dữ liệu xuất ra màn hình
    $line = "Thread " . $segOffset . " SEG: \033[0;35m" . str_pad($offset . "/" . $totalInThread, 9, ' ', STR_PAD_RIGHT) . "\033[0m";
    $line .= "PER_TH: \033[0;34m" . str_pad($percent_thread . "%", 10, ' ', STR_PAD_RIGHT) . "\033[0m";
    $line .= "PER_TT: \033[0;34m" . str_pad($percent_total . "%", 10, ' ', STR_PAD_RIGHT) . "\033[0m";
    $line .= "SP: \033[0;34m" . str_pad($speed, 12, ' ', STR_PAD_RIGHT) . "\033[0m";
    $line .= "SZ: \033[0;32m" . str_pad(nv_convertfromBytes($size), 12, ' ', STR_PAD_RIGHT) . "\033[0m";

    echo $line . "\n";
}
