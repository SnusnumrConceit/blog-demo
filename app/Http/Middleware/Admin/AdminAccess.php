<?php

namespace App\Http\Middleware\Admin;

use App\Enums\User\StatusEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AdminAccess
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
        if (! auth()->user()->hasRole(StatusEnum::ACTIVE->value)) {
            throw new AccessDeniedHttpException('Доступ запрещён');
        }

        return $next($request);
    }
}
