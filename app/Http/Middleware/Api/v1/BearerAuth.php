<?php

namespace App\Http\Middleware\Api\v1;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BearerAuth
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param \Closure(Request): (Response) $next
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array(needle: $request->bearerToken(), haystack: config('auth.tokens.bearer'))) {
            abort(Response::HTTP_UNAUTHORIZED, 'Вы не авторизованы');
        }

        return $next($request);
    }
}
