<?php
declare (strict_types=1);

namespace cigoadmin\validate;

use cigoadmin\library\ApiBaseValidate;

class TimeRange extends ApiBaseValidate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'start_time' => 'number',
        'start_end' => 'number',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [
        'start_time.in' => '开始时间错误',
        'end_time.in' => '结束时间错误',
    ];
}
