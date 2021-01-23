<?php
declare (strict_types=1);

namespace cigoadmin\validate;

use cigoadmin\library\ApiAliCloud;
use cigoadmin\library\ApiBaseValidate;

class SmsCodeCheck extends ApiBaseValidate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        "code" => "require|number|length:4|checkMsgCode"
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [
        'code.require' => '请输入短信验证码',
        'code.number' => '短信验证码格式不符',
        'code.length' => '短信验证码长度不符',
        'code.checkMsgCode' => '短信验证码错误',
    ];

    protected function checkMsgCode($value, $rule, $data = [])
    {
        return ApiAliCloud::instance()->checkCode($data['phone'], $value);
    }
}
