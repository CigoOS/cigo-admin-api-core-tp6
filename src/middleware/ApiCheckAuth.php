<?php

declare(strict_types=1);

namespace cigoadmin\middleware;

use cigoadmin\library\ErrorCode;
use cigoadmin\library\HttpReponseCode;
use cigoadmin\library\traites\ApiCommon;
use cigoadmin\model\User;
use Closure;
use think\facade\Request as RequestAlias;
use think\Request;
use think\Response;

/**
 * 检查权限
 *
 * Class ApiCheckAuth
 * @package cigoadmin\middleware
 */
class ApiCheckAuth
{
    use ApiCommon;

    /**
     * 处理请求
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle($request, Closure $next)
    {
        $request->token = $request->header('Cigo-Token');
        if (empty($request->token)) {
            abort($this->makeApiReturn(
                '请登录并提供token',
                [],
                ErrorCode::ClientError_TokenError,
                HttpReponseCode::ClientError_Unauthorized
            ));
        }
        //        halt($this->moduleName); //TODO 检查管理员模块不允许普通用户登录
        $userInfo = (new User())->where([
            ['token', '=', $request->token],
            ['status', '=', 1]
        ])->findOrEmpty();
        if ($userInfo->isEmpty()) {
            abort($this->makeApiReturn(
                '无此用户或禁用',
                ['token' => $request->token],
                ErrorCode::ClientError_TokenError,
                HttpReponseCode::ClientError_BadRequest
            ));
        }

        //TODO 检查token是否超时
        RequestAlias::instance()->userInfo  = $userInfo;

        return $next($request);
    }
}
