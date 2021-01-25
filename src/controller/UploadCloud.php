<?php

namespace cigoadmin\controller;

use cigoadmin\library\ErrorCode;
use cigoadmin\library\HttpReponseCode;
use cigoadmin\library\traites\ApiCommon;
use cigoadmin\model\Files;
use cigoadmin\validate\MakeQiniuToken;
use Qiniu\Auth;
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
    private function makeQiniuyunToken()
    {
        //检查参数
        (new MakeQiniuToken())->runCheck();
        $qiniuConfig = Config::get('cigoadmin.qiniu_cloud');

        if (!in_array(
            $this->args['bucket'], [
            env('qiniu-cloud.cdn-bucket-open', ''),
            env('qiniu-cloud.cdn-bucket-img', ''),
            env('qiniu-cloud.cdn-bucket-img', ''),
        ])) {
            return $this->makeApiReturn('存储空间不存在', [], ErrorCode::ClientError_ArgsWrong, HttpReponseCode::ClientError_BadRequest);
        }
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
            $this->args['bucket'],
            null,
            $qiniuConfig['tokenExpireTime'],
            $policy,
            true
        );

        return $this->makeApiReturn('获取成功', [
            'token' => $token,
            'upload_host' => $qiniuConfig['host']
        ]);
    }

    private function getCdnDomain($bucket = '')
    {
        empty($bucket) ? '' : false;
        $bucketDomain = array_search($bucket, [
            'cdn_open_domain' => env('qiniu-cloud.cdn-bucket-open', 'cdn_open_domain'),
            'cdn_img_domain' => env('qiniu-cloud.cdn-bucket-img', 'cdn_img_domain'),
            'cdn_video_domain' => env('qiniu-cloud.cdn-bucket-img', 'cdn_video_domain'),
        ]);
        return Request::scheme().'://'. env('qiniu-cloud.' . $bucketDomain, $bucketDomain);
    }

    /**
     * 七牛云文件上传通知
     */
    private function qiniuNotify()
    {
        Log::record('------------------------------------');
        Log::record(json_encode($this->args), JSON_UNESCAPED_UNICODE);
        Log::record('------------------------------------');

        //开始对七牛回调进行鉴权
        $qiniuConfig = Config::get('cigoadmin.qiniu_cloud');
        $auth = new Auth($qiniuConfig['AccessKey'], $qiniuConfig['SecretKey']);
        $authorization = $_SERVER['HTTP_AUTHORIZATION'];
        $callbackBody = file_get_contents('php://input');//获取回调的body信息
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
                        : 'file'
                    );
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
            // 生成访问防盗链链接
            $baseUrl = $this->getCdnDomain($this->args['bucket']) . '/' . $this->args['key'];
            if (stripos($this->args['bucket'], '_open') !== false) {
                // 私有空间中的防盗链外链
                $baseUrl .= '?e=' . (time() + $qiniuConfig['tokenExpireTime']);
            }
            $signedUrl = $auth->privateDownloadUrl($baseUrl);

            return $this->makeApiReturn('上传成功', [
                'id' => $file->id,
                'platform' => $file->platform,
                'platform_bucket' => $file->platform_bucket,
                'platform_key' => $file->platform_key,
                'name' => $file->name,
                'prefix' => $file->name,
                'ext' => $file->ext,
                'mime' => $file->mime,
                'hash' => $file->hash,
                'size' => $file->size,
                'create_time' => $file->create_time,
                'signed_url' => $signedUrl,
                'callbackBody' => $callbackBody
            ]);
        } catch (\Exception $exception) {
            return $this->makeApiReturn($exception->getMessage(), json_encode($exception), JSON_UNESCAPED_UNICODE);
        }
    }

    /******************************= 七牛云：结束 =*********************************/

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
            case 'qiniu'://七牛云
                {
                    // 生成访问防盗链链接
                    $qiniuConfig = Config::get('cigoadmin.qiniu_cloud');
                    $auth = new Auth($qiniuConfig['AccessKey'], $qiniuConfig['SecretKey']);
                    $baseUrl = $this->getCdnDomain($info['platform_bucket']) . '/' . $info['platform_key'];
                    if (stripos($info['platform_bucket'], '_open') !== false) {
                        // 私有空间中的防盗链外链
                        $baseUrl .= '?e=' . (time() + $qiniuConfig['tokenExpireTime']);
                    }
                    $info['signed_url'] = $auth->privateDownloadUrl($baseUrl);
                }
                break;
            case 'tencent'://腾讯云
            case 'aliyun'://阿里云
            case 'local'://本地服务器
            default:
                break;
        }

        return $info;
    }
}
