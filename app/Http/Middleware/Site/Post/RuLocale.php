<?php

namespace App\Http\Middleware\Site\Post;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RuLocale
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
        $lang = $request->headers->get('Accept-Language');

        if (str_contains(haystack: $lang, needle: 'ru') || str_contains(haystack: $lang, needle: 'ru-RU')) {
            return $next($request);
        }

        abort(Response::HTTP_FORBIDDEN, 'Sorry, but your locale is not compatible.');
    }
}
