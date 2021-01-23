<?php
declare (strict_types=1);

namespace cigoadmin\validate;

use cigoadmin\library\ApiBaseValidate;

class AddComment extends ApiBaseValidate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'type' => 'requireCallback:checkMode',
        'comment' => 'require',
        'mode' => 'require|in:0,1,2',
        'target_id' => 'require',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [
        'type.requireCallback' => '请提供被评论内容编号',
        'comment.require' => '评论内容不能为空',
        'mode.require' => '请提供评论模式',
        'mode.in' => '评论模式错误',
        'target_id.require' => '目标编号错误',
    ];

    public function checkMode($value, $data)
    {
        return $data['mode'] == 0;
    }
}
