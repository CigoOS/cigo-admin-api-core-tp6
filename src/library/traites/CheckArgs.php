<?php
declare (strict_types=1);

namespace cigoadmin\library\traites;

use cigoadmin\library\ErrorCode;
use cigoadmin\library\HttpReponseCode;

/**
 * 参数检查Trait
 *
 * Trait CheckArgs
 * @package cigoadmin\library\traites
 */
trait CheckArgs
{
    use ApiCommon;

    /**
     * 引用则自动检测，检测方式改变，子类自行重写
     */
    protected function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub

        $this->checkTimeStamp();
        $this->checkSign();
    }

    /**
     * 检查接口时间戳
     */
    protected function checkTimeStamp()
    {
        if (!env('cigo_admin.check_timestamp', true)) {
            return;
        }

        if (empty($this->request->header("Cigo-Timestamp")) ||
            intval($this->request->header("Cigo-Timestamp")) <= 1 ||
            strlen($this->request->header("Cigo-Timestamp")) !== 10) {
            abort($this->makeApiReturn(
                "时间戳错误", [],
                ErrorCode::ApiCheck_TimeStampError,
                HttpReponseCode::ClientError_BadRequest
            ));
        }

        if (abs(time() - intval($this->request->header("Cigo-Timestamp"))) > 60) {
            abort($this->makeApiReturn(
                "请求无效", [],
                ErrorCode::ApiCheck_TimeStampError,
                HttpReponseCode::ClientError_BadRequest
            ));
        }
    }

    /**
     * 检查接口签名
     */
    protected function checkSign()
    {
        if (!env('cigo_admin.check_sign', true)) {
            return;
        }

        if ($this->request->header("Cigo-Sign") == null) {
            abort($this->makeApiReturn(
                "请提供参数签名", [],
                ErrorCode::ApiCheck_SignError,
                HttpReponseCode::ClientError_BadRequest
            ));
        }
        //TODO 核对签名
        $sign = $this->createSign();

        if ($this->request->header("Cigo-Sign") != $sign) {
            abort($this->makeApiReturn(
                "签名错误", [],
                ErrorCode::ApiCheck_SignError,
                HttpReponseCode::ClientError_BadRequest
            ));
        }
    }

    /**
     * 生成签名
     */
    protected
    function createSign()
    {
        $sign_data = input();
        //Tips_Flag 去除无需签名字段，这点对于传输大数据字段且保证效率很有作用
        if (!empty($sign_data['cigo-nosign'])) {
            foreach ($sign_data['cigo-nosign'] as $paramKey) {
                unset($sign_data[$paramKey]);
            }
            unset($sign_data['cigo-nosign']);
        }
        unset($sign_data['version']);
        ksort($sign_data);
        $sign_data_str = http_build_query($sign_data);
        return md5($sign_data_str);
    }
}
