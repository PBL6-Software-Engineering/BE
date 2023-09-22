<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckUserRole
{
    public function handle($request, Closure $next, ...$roles)
    {
        $user = Auth::user();

        if (!$user) {
            return response('Unauthorized', 401);
            return response()->json(['status' => 'Unauthorized'], 401);
        }

        // Kiểm tra xem người dùng có vai trò nằm trong danh sách $roles không
        if (in_array($user->role, $roles)) {
            return $next($request);
        }

        return response()->json(['status' => 'Forbidden'], 403);
    }
}
