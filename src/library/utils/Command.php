<?php

namespace cigoadmin\library\utils;

use think\console\Output;

class  Command
{
    public static function output(Output $output, String $msg = "", String $type = "info")
    {
        switch ($type) {
            case 'error':
                $output->error($msg);
                break;
            case 'comment':
                $output->comment($msg);
                break;
            case 'warning':
                $output->warning($msg);
                break;
            case 'highlight':
                $output->highlight($msg);
                break;
            case 'question':
                $output->question($msg);
                break;
            case 'info':
            default:
                $output->info($msg);
                break;
        }
    }

    public static function printLogo($input, $output)
    {
        $logoContent = file_get_contents('./assets/cigoadmin.txt');
        Command::output($output, $logoContent);
    }
}
