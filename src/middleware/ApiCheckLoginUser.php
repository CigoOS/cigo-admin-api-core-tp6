<?php
declare (strict_types=1);

namespace cigoadmin\middleware;

use cigoadmin\library\traites\ApiCommon;
use cigoadmin\model\User;
use Closure;
use think\Request;
use think\Response;

/**
 * 检查权限
 *
 * Class ApiCheckAuth
 * @package cigoadmin\middleware
 */
class ApiCheckLoginUser
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
        if (!empty($request->token)) {
            $userInfo = User::where([
                ['token', '=', $request->token],
                ['status', '=', 1]
            ])->findOrEmpty();
            if (!$userInfo->isEmpty()) {
                $request->userInfo = $userInfo;
            }
        }

        return $next($request);
    }
}
