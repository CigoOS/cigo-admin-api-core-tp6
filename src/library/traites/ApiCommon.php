<?php
declare (strict_types=1);

namespace cigoadmin\library\traites;

use cigoadmin\library\ErrorCode;
use cigoadmin\library\HttpReponseCode;

/**
 * Api接口基类公共方法
 *
 * Trait ApiCommon
 * @package cigoadmin\library\traites
 */
trait ApiCommon
{
    /**
     * 封装统一返回数据格式
     *
     * @param string $msg
     * @param array $data
     * @param int $errorCode
     * @param int $httpCode
     * @return false|string
     */
    protected function makeApiReturn($msg = "ok", $data = [], $errorCode = 0, $httpCode = 200)
    {
        $msg_data = [
            "msg" => $msg,
            "data" => $data,
            "error_code" => $errorCode
        ];
        return json($msg_data, $httpCode);
    }

    /**
     * @param string $msg
     * @param array $data
     * @param int $errorCode
     * @param int $httpCode
     * @return false|string
     */
    protected function error($msg = "", $data = [], $errorCode = ErrorCode::ClientError_ArgsWrong, $httpCode = HttpReponseCode::ClientError_BadRequest)
    {
        return $this->makeApiReturn($msg, $data, $errorCode, $httpCode);
    }

    /**
     * @param string $msg
     * @param array $data
     * @param int $errorCode
     * @param int $httpCode
     * @return false|string
     */
    protected function success($msg = "", $data = [], $errorCode = ErrorCode::OK, $httpCode = HttpReponseCode::Success_OK)
    {
        return $this->makeApiReturn($msg, $data, $errorCode, $httpCode);
    }

    protected function makeStatusTips($disableTips = '禁用成功', $successTips = '启用成功', $deleteTips = '删除成功')
    {
        $tips = '';
        switch ($this->args['status']) {
            case 0:
                $tips = $disableTips;
                break;
            case 1:
                $tips = $successTips;
                break;
            case -1:
                $tips = $deleteTips;
                break;
        }
        return $tips;
    }
}
