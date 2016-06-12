<?php

namespace Rubenwouters\CrmLauncher\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CanViewCRM
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!Auth::user()->canViewCRM) {
            return redirect('home');
        }

        return $next($request);
    }
}
