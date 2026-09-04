<?php

use App\Http\Controllers\WorkshopLoanController;
use App\Http\Controllers\WorkshopLoanReturnController;
use App\Models\Loan;
use App\Models\LoanItem;
use Illuminate\Support\Facades\Route;

Route::bind(
    'loan',
    fn (string $value): Loan =>
        Loan::query()->withoutGlobalScopes()->findOrFail($value)
);

Route::bind(
    'loanItem',
    fn (string $value): LoanItem =>
        LoanItem::query()->withoutGlobalScopes()->findOrFail($value)
);

$loanRoutes = [
    [['GET', 'HEAD'], 'loans', 'loans.index', WorkshopLoanController::class, 'index'],
    [['GET', 'HEAD'], 'loans/create', 'loans.create', WorkshopLoanController::class, 'create'],
    [['POST'], 'loans', 'loans.store', WorkshopLoanController::class, 'store'],
    [['POST'], 'loans/{loan}/approve', 'loans.approve', WorkshopLoanController::class, 'approve'],
    [['POST'], 'loans/{loan}/reject', 'loans.reject', WorkshopLoanController::class, 'reject'],
    [['POST'], 'loans/{loan}/checkout', 'loans.checkout', WorkshopLoanController::class, 'checkout'],
    [['POST'], 'loans/{loan}/complete', 'loans.complete', WorkshopLoanController::class, 'complete'],
    [['POST'], 'loans/{loan}/cancel', 'loans.cancel', WorkshopLoanController::class, 'cancel'],
    [['GET', 'HEAD'], 'loan-returns', 'loans.returns.index', WorkshopLoanReturnController::class, 'index'],
    [['GET', 'HEAD'], 'loans/{loan}/return', 'loans.return-form', WorkshopLoanReturnController::class, 'form'],
    [['POST'], 'loans/{loan}/return', 'loans.return', WorkshopLoanReturnController::class, 'process'],
    [['POST'], 'loans/{loan}/items/{loanItem}/return', 'loans.items.return', WorkshopLoanReturnController::class, 'returnItem'],
    [['POST'], 'loans/{loan}/items/{loanItem}/replace', 'loans.items.replace', WorkshopLoanController::class, 'replaceAsset'],
    [['POST'], 'loans/{loan}/extend', 'loans.extend', WorkshopLoanController::class, 'extend'],
    [['GET', 'HEAD'], 'loans/{loan}/permit', 'loans.permit', WorkshopLoanController::class, 'permit'],
    [['GET', 'HEAD'], 'loans/{loan}', 'loans.show', WorkshopLoanController::class, 'show'],
];

$matched = [];

foreach (Route::getRoutes() as $route) {
    foreach ($loanRoutes as [$methods, $uri, $name, $controller, $method]) {
        $sameName = $route->getName() === $name;
        $sameUri = trim($route->uri(), '/') === $uri;
        $sameMethod = array_intersect($methods, $route->methods()) !== [];

        if (! $sameName && ! ($sameUri && $sameMethod)) {
            continue;
        }

        $uses = $controller.'@'.$method;
        $action = $route->getAction();
        $action['uses'] = $uses;
        $action['controller'] = $uses;
        $route->setAction($action);
        $matched[$name] = true;
    }
}

foreach ($loanRoutes as [$methods, $uri, $name, $controller, $method]) {
    if (isset($matched[$name])) {
        continue;
    }

    $route = Route::match(
        array_values(array_filter($methods, fn (string $m): bool => $m !== 'HEAD')),
        '/'.$uri,
        [$controller, $method]
    )->middleware('auth')->name($name);

    if (str_contains($uri, '{loan}')) {
        $route->whereNumber('loan');
    }

    if (str_contains($uri, '{loanItem}')) {
        $route->whereNumber('loanItem');
    }
}

unset($loanRoutes, $matched, $route);

/*
|--------------------------------------------------------------------------
| Replacement Requests (Pengajuan Penggantian Alat Rusak)
|--------------------------------------------------------------------------
*/

Route::bind('replacementRequest', function (string $value): \App\Models\LoanItemReplacementRequest {
    return \App\Models\LoanItemReplacementRequest::query()->whereKey($value)->firstOrFail();
});

Route::middleware('auth')->group(function (): void {
    Route::get('/loans/replacement-requests', [\App\Http\Controllers\LoanReplacementController::class, 'index'])
        ->name('loans.replacement-requests.index');

    Route::post('/loans/{loan}/items/{loanItem}/request-replacement', [\App\Http\Controllers\LoanReplacementController::class, 'requestReplacement'])
        ->whereNumber(['loan', 'loanItem'])
        ->name('loans.items.request-replacement');

    Route::post('/loans/replacement-requests/{replacementRequest}/fulfill', [\App\Http\Controllers\LoanReplacementController::class, 'fulfill'])
        ->whereNumber('replacementRequest')
        ->name('loans.replacement-requests.fulfill');

    Route::post('/loans/replacement-requests/{replacementRequest}/cancel', [\App\Http\Controllers\LoanReplacementController::class, 'cancel'])
        ->whereNumber('replacementRequest')
        ->name('loans.replacement-requests.cancel');
});
