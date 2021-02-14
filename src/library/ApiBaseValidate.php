<?php

namespace cigoadmin\library;

use cigoadmin\library\traites\ApiCommon;
use think\facade\Request;
use think\Validate;

/**
 * Api验证器基类
 *
 * Class ApiBaseValidate
 * @package cigoadmin\library
 */
class ApiBaseValidate extends Validate
{
    use ApiCommon;

    /**
     * 获取新实例
     *
     * @return static
     */
    public static function instance()
    {
        return new static();
    }

    public function runCheck($callBack = '', $params = [], $abortFlag = true)
    {
        $request = Request::instance();
        if ($this->check(empty($params) ? input() : $params) == false) {
            if ($callBack) {
                $callBack($this->getError());
            }
            if (!$abortFlag) return;
            abort($this->makeApiReturn(
                $this->getError(),
                [],
                ErrorCode::ClientError_ArgsWrong,
                HttpReponseCode::ClientError_BadRequest
            ));
        }
    }
}
