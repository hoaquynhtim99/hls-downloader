<?php

date_default_timezone_set('Asia/Ho_Chi_Minh');

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

function getTotalFileSize($folderPath)
{
    $totalSize = 0;

    // Open the directory
    $dir = opendir($folderPath);

    // Iterate through each file and directory
    while ($file = readdir($dir)) {
        // Skip special directories
        if ($file == '.' || $file == '..') {
            continue;
        }

        $filePath = $folderPath . '/' . $file;

        // If the current item is a directory, recursively call the function
        if (is_dir($filePath)) {
            $totalSize += getTotalFileSize($filePath);
        }
        // If it's a file, add its size to the total
        elseif (is_file($filePath)) {
            $totalSize += filesize($filePath);
        }
    }

    // Close the directory handle
    closedir($dir);

    return $totalSize;
}

function countFilesInFolder($folderPath)
{
    $fileCount = 0;

    // Open the directory
    $dir = opendir($folderPath);

    // Iterate through each file and directory
    while ($file = readdir($dir)) {
        // Skip special directories
        if ($file == '.' || $file == '..') {
            continue;
        }

        $filePath = $folderPath . '/' . $file;

        // If the current item is a directory, recursively call the function
        if (is_dir($filePath)) {
            $fileCount += countFilesInFolder($filePath);
        }
        // If it's a file, increment the file count
        elseif (is_file($filePath)) {
            $fileCount++;
        }
    }

    // Close the directory handle
    closedir($dir);

    return $fileCount;
}
