<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureEmployeeAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('employee_id')) {
            return redirect()->route('employee.portal.login');
        }
        return $next($request);
    }
}
