<?php

namespace App\Http\Middleware;

use App\Services\LoanJurusanRoutingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RouteLoanToJurusanToolman
{
    public function __construct(
        private readonly LoanJurusanRoutingService
            $routing
    ) {
    }

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $this->routing
            ->prepareRequest(
                $request
            );

        return $next($request);
    }
}
