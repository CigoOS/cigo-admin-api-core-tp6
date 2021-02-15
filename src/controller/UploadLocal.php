<?php

namespace cigoadmin\controller;

use cigoadmin\library\traites\ApiCommon;
use cigoadmin\library\uploader\UploadMg;

/**
 * Trait UploadLocal
 * @package cigoadmin\controller
 */
trait UploadLocal
{
    use ApiCommon;

    /**
     * 文件上传
     */
    private function localUpload()
    {
        //1. 实例化上传类，并创建文件上传实例
        $upMg = new UploadMg();
        $upMg->init()->makeFileUploader();

        //2. 执行上传操作
        $upMg->doUpload();
    }
}
