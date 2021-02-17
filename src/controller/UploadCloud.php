<?php

namespace cigoadmin\controller;

use cigoadmin\library\ErrorCode;
use cigoadmin\library\HttpReponseCode;
use cigoadmin\library\traites\ApiCommon;
use cigoadmin\model\Files;
use Qiniu\Auth;
use Qcloud\Cos\Client;
use think\facade\Config;
use think\facade\Log;
use think\facade\Request;
use think\Model;

/**
 * Trait UploadCloud
 * @package cigoadmin\controller
 */
trait UploadCloud
{
    use ApiCommon;

    /******************************= 七牛云：开始 =**********************************/
    /**
     * 创建七牛云上传凭证
     */
    private function makeCloudQiniuToken()
    {
        //检查参数
        $qiniuConfig = Config::get('cigoadmin.qiniu_cloud');

        if (!isset($this->args['bucketType']) ||  !in_array($this->args['bucketType'], ['img', 'video', 'open'])) {
            return $this->makeApiReturn('存储空间不存在', [], ErrorCode::ClientError_ArgsWrong, HttpReponseCode::ClientError_BadRequest);
        }
        $bucket = $qiniuConfig['bucketList'][$this->args['bucketType']];
        $auth = new Auth($qiniuConfig['AccessKey'], $qiniuConfig['SecretKey']);
        $policy = $qiniuConfig['enableCallbackServer']
            ? [
                'callbackUrl' => $qiniuConfig['callbackUrl'],
                'callbackBodyType' => $qiniuConfig['callbackBodyType'],
                'callbackBody' => $qiniuConfig['callbackBody'],
            ]
            : [
                'returnBody' => $qiniuConfig['returnBody']
            ];

        $token = $auth->uploadToken(
            $bucket,
            null,
            $qiniuConfig['tokenDuration'],
            $policy,
            true
        );

        return $this->makeApiReturn('获取成功', [
            'token' => $token,
            'platform' => env('cigo-admin.file-save-type', 'cloudQiniu'),
            'upload_host' => $qiniuConfig['host']
        ]);
    }

    /**
     * 七牛云文件上传通知
     */
    private function cloudQiniuNotify()
    {
        Log::record('------------------------------------');
        Log::record(json_encode($this->args), JSON_UNESCAPED_UNICODE);
        Log::record('------------------------------------');

        //开始对七牛回调进行鉴权
        $qiniuConfig = Config::get('cigoadmin.qiniu_cloud');
        $auth = new Auth($qiniuConfig['AccessKey'], $qiniuConfig['SecretKey']);
        $authorization = $_SERVER['HTTP_AUTHORIZATION'];
        $callbackBody = file_get_contents('php://input'); //获取回调的body信息
        $isQiniuCallback = $auth->verifyCallback($qiniuConfig['callbackBodyType'], $authorization, $qiniuConfig['callbackUrl'], $callbackBody);
        if (!$isQiniuCallback) {
            $this->args['isQiniuCallback'] = $isQiniuCallback;
            $this->args['authorization'] = $authorization;
            $this->args['callbackBody'] = $callbackBody;

            return $this->makeApiReturn('七牛回调鉴权失败', $this->args);
        }

        try {
            //保存文件信息到数据库
            $file = Files::where([
                ['platform', '=', 'qiniu'],
                ['platform_bucket', '=', $this->args['bucket']],
                ['platform_key', '=', $this->args['key']],
                ['name', '=', $this->args['fname']],
                ['hash', '=', $this->args['hash']],
            ])->findOrEmpty();
            if ($file->isEmpty()) {
                $ext = pathinfo($this->args['fname'], PATHINFO_EXTENSION);
                $type = in_array($ext, ['png', 'jpg', 'jpeg', 'bmp', 'gif'])
                    ? 'img'
                    : (in_array($ext, ['mp4', 'rmvb', 'mov'])
                        ? 'video'
                        : 'file');
                $file = Files::create([
                    'platform' => 'qiniu',
                    'platform_bucket' => $this->args['bucket'],
                    'platform_key' => $this->args['key'],
                    'type' => $type,
                    'name' => $this->args['fname'],
                    'prefix' => $this->args['fprefix'],
                    'ext' => $ext,
                    'name_saved' => $this->args['key'],
                    'mime' => $this->args['mimeType'],
                    'hash' => $this->args['hash'],
                    'size' => $this->args['fsize'],
                    'create_time' => time(),
                ]);
            }

            $fileInfo = [
                'id' => $file->id,
                'platform' => $file->platform,
                'platform_bucket' => $file->platform_bucket,
                'platform_key' => $file->platform_key,
                'name' => $file->name,
                'prefix' => $file->prefix,
                'ext' => $file->ext,
                'mime' => $file->mime,
                'hash' => $file->hash,
                'size' => $file->size,
                'create_time' => $file->create_time,
                'callbackBody' => $callbackBody
            ];

            // 补充文件信息：生成访问防盗链链接
            $this->appendFileInfoCloudQiniu($fileInfo, $this->args['bucket'], $this->args['key']);

            return $this->makeApiReturn('上传成功', $fileInfo);
        } catch (\Exception $exception) {
            return $this->makeApiReturn($exception->getMessage(), json_encode($exception), JSON_UNESCAPED_UNICODE);
        }
    }

    /******************************= 七牛云：结束 =*********************************/

    /******************************= 腾讯云：开始 =*********************************/

    /**
     * 创建腾讯云上传凭证
     */
    private function makeCloudTencentToken()
    {
        //检查参数
        $tencentConfig = Config::get('cigoadmin.tencent_cloud');
        $cosClient = new Client(
            array(
                'region' => $tencentConfig['region'],
                'schema' => Request::scheme(), //协议头部，默认为http
                'credentials' => array(
                    'secretId'  => $tencentConfig['SecretId'],
                    'secretKey' => $tencentConfig['SecretKey']
                )
            )
        );
    }

    /**
     * 腾讯云文件上传通知
     */
    private function cloudTencentNotify()
    {
    }

    /******************************= 腾讯云：结束 =*********************************/

    /******************************= 阿里云：开始 =*********************************/

    /**
     * 创建腾讯云上传凭证
     */
    private function makeCloudAliyunToken()
    {
        return $this->makeApiReturn('测试腾讯云存储', [
            'token' => "tencent-token",
            'upload_host' => "tencent-host"
        ]);
    }

    /**
     * 腾讯云文件上传通知
     */
    private function cloudAliyunNotify()
    {
    }
    /******************************= 阿里云：结束 =*********************************/

    /**
     * 获取文件信息
     * @param int $fileId
     * @return array|Model|null
     */
    private function getFileInfo($fileId = 0)
    {
        if (empty($fileId)) {
            return null;
        }
        $info = Files::where('id', $fileId)->findOrEmpty();
        if ($info->isEmpty()) {
            return null;
        }
        switch ($info['platform']) {
            case 'qiniu': //七牛云
                $this->appendFileInfoCloudQiniu($info, $info['platform_bucket'], $info['platform_key']);
                break;
            case 'tencent': //腾讯云
            case 'aliyun': //阿里云
            case 'local': //本地服务器
            default:
                break;
        }

        return $info;
    }

    private function appendFileInfoCloudQiniu(&$info = [], $bucket = "", $key = "")
    {
        // 生成访问防盗链链接
        $qiniuConfig = Config::get('cigoadmin.qiniu_cloud');
        $auth = new Auth($qiniuConfig['AccessKey'], $qiniuConfig['SecretKey']);
        $bucketDomain = array_search($bucket, $qiniuConfig['domainLinkBucket']);
        $signedUrl = Request::scheme() . '://' . $qiniuConfig['domainList'][$bucketDomain] . '/' . $key;
        if (stripos($bucket, '_open') == false) {
            // 私有空间中的防盗链外链
            $signedUrl = $auth->privateDownloadUrl($signedUrl, time() + $qiniuConfig['linkTimeout']);
        }
        $info['signed_url'] = $signedUrl;
    }
}
