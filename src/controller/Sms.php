<?php

namespace cigoadmin\controller;

use cigoadmin\library\ApiAliCloud;
use cigoadmin\library\traites\ApiCommon;
use cigoadmin\validate\PhoneCheck;

/**
 * Trait Sms
 * @package cigoadmin\controller
 */
trait Sms
{
    use ApiCommon;

    /**
     * 发送短信验证码
     */
    private function sendVerifyCode()
    {
        //1. 检测手机号码
        (new PhoneCheck())->runCheck();

        //2. 发送验证码
        $aliCloudApi = ApiAliCloud::instance();
        $aliCloudApi->sendSmsCode(input('phone'));
        return $this->makeApiReturn('发送成功');
    }
}
