<?php

namespace App\Http\Controllers;

use App\Services\AuthorizationService;
use Illuminate\Support\Facades\Auth;
use Response;

/**
 * This class should be parent class for other API controllers
 * Class AppBaseController
 */
class AppBaseController extends Controller
{
    /**
     * Authorization service bound to the current user. Used by management
     * controllers to scope index() queries as defense-in-depth behind the
     * fund.full route middleware. (#85)
     */
    protected function authz(): AuthorizationService
    {
        return new AuthorizationService(Auth::user());
    }

    /**
     * Re-assert the fund.full management capability inside a controller, for
     * index pages whose model has no fund/account column to scope on (goals,
     * matching rules, assets, scheduled jobs). The route already carries the
     * RequireFullFundAccess middleware; this guard keeps the page denying
     * non-privileged users even if that middleware is ever removed or
     * misconfigured. (#85)
     */
    protected function requireFullFundAccessSurface(): void
    {
        abort_unless($this->authz()->canAccessManagementSurface(), 403);
    }

    public function sendResponse($result, $message)
    {
        return Response::json([
            'success' => true,
            'data'    => $result,
            'message' => $message,
        ]);
    }

    public function sendError($error, $code = 404)
    {
        return Response::json([
            'success' => false,
            'message' => $error,
        ], $code);
    }

    public function sendSuccess($message)
    {
        return Response::json([
            'success' => true,
            'message' => $message
        ], 200);
    }
}
