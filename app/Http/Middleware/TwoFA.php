<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Closure;
use PragmaRX\Google2FALaravel\Support\Authenticator;
use Symfony\Component\HttpFoundation\Response;

class TwoFA
{
    public function handle($request, Closure $next, string ...$guards): Response
    {
        $authenticator = app(Authenticator::class)->boot($request);

        if ($authenticator->isAuthenticated()) {
            return $next($request);
        }

        return redirect('google2fa/index');
    }
}