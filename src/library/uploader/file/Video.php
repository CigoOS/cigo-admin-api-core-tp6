<?php

namespace cigoadmin\library\uploader\file;

use cigoadmin\library\uploader\FileType;
use cigoadmin\library\uploader\Uploader;

/**
 * 视频上传接口
 *
 * Class Video
 * @package app\common\library\uploader
 */
class Video extends Uploader
{
    protected function getConfigFileLimit($configs)
    {
        return $configs['fileLimit']['video'];
    }

    protected function getFileType()
    {
        return FileType::VIDEO;
    }
}

