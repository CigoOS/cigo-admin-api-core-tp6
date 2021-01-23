<?php

declare(strict_types=1);

namespace cigoadmin\model;

use cigoadmin\controller\UploadCloud;
use think\Model;

/**
 * @mixin Model
 */
class News  extends Model
{
    use UploadCloud;

    protected $table = 'cg_news';

    public function getImgInfoAttr($value, $data)
    {
        return $this->getFileInfo($data);
    }

    public function getNumViewShowAttr($value, $data)
    {
        return UserView::where([
            ['content_type', '=', 'news'],
            ['content_id', '=', $data['id']],
        ])
            ->count() + $data['num_view'];
    }
}
