<?php

namespace cigoadmin\library;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

/**
 * 接入阿里云服务（短信服务）
 *
 * Class AliCloud
 * @package cigoadmin\library
 */
class AliCloud
{
    protected $accessKeyId;
    protected $accessKeySecret;

    public $signName;
    public $codeCachePrefix;
    public $expireTime;
    public $templateCode;

    protected $msgCode = "";

    public function __construct()
    {
        $this->accessKeyId = config("cigoadmin.ali_cloud.access_key");
        $this->accessKeySecret = config("cigoadmin.ali_cloud.access_secret");
        $this->signName = config("cigoadmin.ali_cloud.sms.sign_name");
        $this->codeCachePrefix = config("cigoadmin.ali_cloud.sms.code_cache_prefix");
        $this->expireTime = config("cigoadmin.ali_cloud.sms.expire_time");
        $this->templateCode = config("cigoadmin.ali_cloud.sms.tpl.user_verify");
    }

    /**
     * 获取新实例
     *
     * @return static
     */
    public static function instance()
    {
        return new static();
    }


    /**
     * 随机生成验证码
     *
     * @return $this
     */
    public function createCode()
    {
        $this->msgCode = rand(1000, 9999) . '';
        return $this;
    }

    /**
     * 获取验证码
     *
     * @return string
     */
    public function getCode()
    {
        return $this->msgCode;
    }

    /**
     * 发送短信验证码
     *
     * @param $phone
     * @param $code
     * @return array
     * @throws ClientException
     * @throws ServerException
     */
    public function sendSmsCode($phone = "", $code = "")
    {
        AlibabaCloud::accessKeyClient($this->accessKeyId, $this->accessKeySecret)
            ->regionId('cn-hangzhou')
            ->asDefaultClient();

        $result = AlibabaCloud::rpc()
            ->product('Dysmsapi')
            ->scheme('https') // https | http
            ->version('2017-05-25')
            ->action('SendSms')
            ->method('POST')
            ->host('dysmsapi.aliyuncs.com')
            ->options([
                'query' => [
                    'RegionId' => "cn-hangzhou",
                    'PhoneNumbers' => $phone,
                    'SignName' => $this->signName,
                    'TemplateCode' => $this->templateCode,
                    'TemplateParam' => json_encode(['code' => $code], JSON_UNESCAPED_UNICODE),
                ],
            ])
            ->request();
        return $result->toArray();
    }
}
