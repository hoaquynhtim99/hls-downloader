<?php

/**
 * Báo cáo các phân đoạn chưa tải được
 * Exit code: 0 đủ phân đoạn, 1 thiếu một phần, 2 thiếu toàn bộ, 3 lỗi đọc dữ liệu
 */

define('NV_ROOTDIR', pathinfo(str_replace(DIRECTORY_SEPARATOR, '/', __FILE__), PATHINFO_DIRNAME));

$missing = [];
$total = 0;
$sizeDownloaded = 0;

for ($thread = 1; $thread <= 4; $thread++) {
    $file_json = NV_ROOTDIR . '/meta/thread_' . $thread . '.json';
    $urls = file_exists($file_json) ? json_decode(file_get_contents($file_json), true) : [];
    if (empty($urls) or !is_array($urls)) {
        echo "Segment data of thread " . $thread . " not json or empty";
        exit(3);
    }

    foreach ($urls as $index => $url) {
        $total++;
        $segment_file = NV_ROOTDIR . '/data/seg_' . $index;
        $size = file_exists($segment_file) ? filesize($segment_file) : 0;
        if ($size > 0) {
            $sizeDownloaded += $size;
        } else {
            $missing[] = $index;
        }
    }
}

$totalMissing = sizeof($missing);
if ($totalMissing == 0) {
    exit(0);
}

// Gộp các phân đoạn liền nhau cho dễ đọc: 10225-10435
$ranges = [];
$from = $to = $missing[0];
foreach (array_slice($missing, 1) as $index) {
    if ($index != $to + 1) {
        $ranges[] = $from == $to ? $from : $from . '-' . $to;
        $from = $index;
    }
    $to = $index;
}
$ranges[] = $from == $to ? $from : $from . '-' . $to;

// Ước lượng dung lượng theo kích thước trung bình của các phân đoạn đã tải
$totalDownloaded = $total - $totalMissing;
$estimate = 'unknown';
if ($totalDownloaded > 0) {
    $estimate = '~' . number_format($sizeDownloaded / $totalDownloaded * $totalMissing / 1048576, 1) . ' MB';
}

echo "Missing segments: " . $totalMissing . "/" . $total . " (" . number_format($totalMissing / $total * 100, 2) . "%)\n";
echo "Estimated size: " . $estimate . "\n";
echo "Segments: " . implode(', ', $ranges);

exit($totalDownloaded > 0 ? 1 : 2);
