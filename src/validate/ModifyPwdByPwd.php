<?php
declare (strict_types=1);

namespace cigoadmin\validate;

use cigoadmin\library\ApiBaseValidate;

class ModifyPwdByPwd extends ApiBaseValidate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'old' => 'require',
        'new' => 'require|min:6|max:20',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [
        'old.require' => '请提供原密码',
        'new.require' => '请提供新密码',
        'new.min' => '密码最少4位',
        'new.max' => '密码最多20位',
    ];
}
