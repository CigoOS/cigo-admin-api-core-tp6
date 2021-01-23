<?php
declare (strict_types=1);

namespace cigoadmin\validate;

use cigoadmin\library\ApiBaseValidate;

class BindCodeCheck extends ApiBaseValidate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'bindCode' => 'require'
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [
        'bindCode.require' => '请提供绑定码',
    ];
}
