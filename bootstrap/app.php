<?php

use App\Support\UploadLimit;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            $limit = UploadLimit::formatBytes(UploadLimit::postMaxBytes());
            $message = "Upload too large. The total request size must stay under {$limit}.";

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 413);
            }

            return back()
                ->withInput()
                ->withErrors(['upload' => $message]);
        });
    })->create();
