<?php

declare(strict_types=1);

namespace cigoadmin\validate;

use cigoadmin\library\ApiBaseValidate;

class CtrlContent extends ApiBaseValidate
{

    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'id' => 'require',
        'ctrlType' => 'require|in:collection,like,view,report',
        'flag' => 'require|in:0,1',
        'content_type' => '',
        'reason' => '',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->rule['content_type'] = 'require|in:' . env('cigo_admin.ctrl_content_types');
        $this->rule['reason'] = 'requireIf:ctrlType,report|in:' . env('cigo_admin.report_reason_types');
    }

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [
        'id.require' => '请提供内容编号',
        'ctrlType.require' => '请提供操作类型',
        'ctrlType.in' => '操作类型错误',
        'flag.require' => '请提供操作标识',
        'flag.in' => '操作标识错误',
        'content_type.require' => '请提供内容类型',
        'content_type.in' => '内容类型错误',
        'reason.requireIf' => '请选择举报原因',
        'reason.in' => '举报类型不存在',
    ];
}
