<?php

namespace cigoadmin\library\utils;

class Env
{
    public static function saveArrayToIni($data = [], $jsonItems = [])
    {
        $headerTips = PHP_EOL .
            PHP_EOL .
            "# -------------------------------------" . PHP_EOL .
            "# 注意：请将下面 ****** 替换为您项目对应的值" . PHP_EOL .
            "# --------------------------------------" . PHP_EOL .
            PHP_EOL;
        $noGroupData = "";
        $groupData = "";
        ksort($data);
        foreach ($data as $key => $item) {
            if (is_string($item)) {
                $jsonFlag = in_array($key, $jsonItems) ? "'" : '';
                $noGroupData .= $key . ' = ' . $jsonFlag . $item . $jsonFlag . PHP_EOL;
            } else if (is_array($item)) {
                $groupData .= PHP_EOL . '[' . $key . ']' . PHP_EOL;
                ksort($item);
                foreach ($item as $keySub => $itemSub) {
                    if (is_string($itemSub)) {
                        $jsonFlag = in_array($keySub, $jsonItems) ? "'" : '';
                        $groupData .= $keySub . ' = ' . $jsonFlag . $itemSub . $jsonFlag . PHP_EOL;
                    }
                }
            }
        }
        return $headerTips . $noGroupData . $groupData;
    }
}
