<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DashboardAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('dashboard_authed')) {
            return redirect()->route('dashboard.login');
        }
        return $next($request);
    }
}