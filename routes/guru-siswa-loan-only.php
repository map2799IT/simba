<?php

use App\Http\Controllers\BorrowerAwareDashboardController;
use App\Http\Controllers\WorkshopLoanController;
use App\Models\Loan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Final override Guru/Siswa peminjam saja
|--------------------------------------------------------------------------
|
| File ini wajib dimuat paling akhir pada routes/web.php.
|
*/

Route::bind(
    'loan',
    fn (string $value): Loan =>
        Loan::query()
            ->withoutGlobalScopes()
            ->findOrFail($value)
);

$definitions = [
    [
        ['GET', 'HEAD'],
        'dashboard',
        'dashboard',
        BorrowerAwareDashboardController::class,
        'index',
    ],
    [
        ['GET', 'HEAD'],
        'loans',
        'loans.index',
        WorkshopLoanController::class,
        'index',
    ],
    [
        ['GET', 'HEAD'],
        'loans/create',
        'loans.create',
        WorkshopLoanController::class,
        'create',
    ],
    [
        ['POST'],
        'loans',
        'loans.store',
        WorkshopLoanController::class,
        'store',
    ],
    [
        ['POST', 'DELETE'],
        'loans/{loan}/cancel',
        'loans.cancel',
        WorkshopLoanController::class,
        'cancel',
    ],
    [
        ['GET', 'HEAD'],
        'loans/{loan}',
        'loans.show',
        WorkshopLoanController::class,
        'show',
    ],
];

$matched = [];

foreach (Route::getRoutes() as $route) {
    foreach (
        $definitions
        as [
            $methods,
            $uri,
            $name,
            $controller,
            $controllerMethod,
        ]
    ) {
        $sameName = $route->getName() === $name;
        $sameUri = trim($route->uri(), '/') === $uri;
        $sameMethod = array_intersect(
            $methods,
            $route->methods()
        ) !== [];

        if (! $sameName && ! ($sameUri && $sameMethod)) {
            continue;
        }

        $uses = $controller.'@'.$controllerMethod;
        $action = $route->getAction();
        $action['uses'] = $uses;
        $action['controller'] = $uses;
        $route->setAction($action);
        $matched[$name] = true;
    }
}

foreach (
    $definitions
    as [
        $methods,
        $uri,
        $name,
        $controller,
        $controllerMethod,
    ]
) {
    if (isset($matched[$name])) {
        continue;
    }

    $route = Route::match(
        array_values(
            array_filter(
                $methods,
                static fn (string $method): bool =>
                    $method !== 'HEAD'
            )
        ),
        '/'.$uri,
        [$controller, $controllerMethod]
    )
        ->middleware('auth')
        ->name($name);

    if (str_contains($uri, '{loan}')) {
        $route->whereNumber('loan');
    }
}

unset($definitions, $matched, $route);
