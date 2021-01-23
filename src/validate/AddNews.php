<?php
declare (strict_types=1);

namespace cigoadmin\validate;

use cigoadmin\library\ApiBaseValidate;

class AddNews extends ApiBaseValidate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'img' => 'require|number|min:0',
        'title' => 'require',
        'source' => 'require',
        'summary' => 'require',
        'content' => 'require',
        'num_view' => 'require|number|min:0',
        'sort' => 'require|number|min:0',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [
        'img.require' => '请上传新闻图片',
        'img.number' => '图片字段为数字',
        'img.min' => '图片字段为正',
        'title.require' => '请填写新闻标题',
        'source.require' => '请填写新闻来源',
        'summary.require' => '请填写新闻简介',
        'content.require' => '请填写新闻详情',
        'num_view.require' => '请填写基础浏览量',
        'num_view.number' => '基础浏览量为数字',
        'num_view.min' => '基础浏览量为正数',
        'sort.require' => '请填写排序',
        'sort.number' => '排序为数字',
        'sort.min' => '排序为正数',
    ];
}
