<?php

use App\Http\Middleware\HandleCors;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: ['*',]);
        $middleware->web(append: [
            HandleInertiaRequests::class,
            HandleCors::class
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // Run monthly profit job on the 1st day of every month at 1 AM
        $schedule->job(new \App\Jobs\MonthlyProfitJob)->monthlyOn(1, '12:00');
        
        // অথবা আরো frequent test করার জন্য (প্রতিদিন ১ বার)
        // $schedule->job(new \App\Jobs\MonthlyProfitJob)->daily();
        
        // অথবা প্রতি ঘণ্টায় test করার জন্য
        // $schedule->job(new \App\Jobs\MonthlyProfitJob)->hourly();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();