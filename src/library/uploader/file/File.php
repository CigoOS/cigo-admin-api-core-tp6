<?php

namespace cigoadmin\library\uploader\file;

use cigoadmin\library\uploader\FileType;
use cigoadmin\library\uploader\Uploader;

/**
 * 文件上传接口
 *
 * Class File
 * @package app\common\library\uploader
 */
class File extends Uploader
{

    protected function getConfigFileLimit($configs)
    {
        return $configs['fileLimit']['file'];
    }

    protected function getFileType()
    {
        return FileType::FILE;
    }
}

