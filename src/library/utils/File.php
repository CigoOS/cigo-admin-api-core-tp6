<?php

namespace cigoadmin\library\utils;

class File
{
    public static function fileSizeFormat($size = 0, $dec = 2)
    {
        $unit = array("B", "KB", "MB", "GB", "TB", "PB");
        $pos = 0;
        while ($size >= 1024) {
            $size /= 1024;
            $pos++;
        }
        $result['size'] = round($size, $dec);
        $result['unit'] = $unit[$pos];
        return $result['size'] . $result['unit'];
    }
}
