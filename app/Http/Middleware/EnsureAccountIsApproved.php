<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && !$request->user()->is_approved) {
            auth()->logout();
            return redirect()->route('login')->with('status', 'Your account is pending Super Admin approval.');
        }

        return $next($request);
    }
}
