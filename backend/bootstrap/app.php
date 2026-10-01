<?php

use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(null);
        $middleware->alias([
            'active.user' => EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            if (! $request->is('api/*') || $response->getStatusCode() < 400) {
                return $response;
            }

            $status = $response->getStatusCode();
            $original = json_decode((string) $response->getContent(), true);
            $original = is_array($original) ? $original : [];
            $message = match (true) {
                $status >= 500 => 'Terjadi kesalahan pada layanan. Silakan coba lagi.',
                $status === 404 => 'Data atau endpoint tidak ditemukan.',
                $status === 405 => 'Metode HTTP tidak didukung.',
                $status === 401 && ($original['message'] ?? '') === 'Unauthenticated.' => 'Autentikasi diperlukan.',
                $status === 403 && ($original['message'] ?? '') === 'This action is unauthorized.' => 'Anda tidak berwenang mengakses data ini.',
                default => $original['message'] ?? 'Permintaan tidak dapat diproses.',
            };
            $body = ['success' => false, 'message' => $message];
            if ($status === 422 && isset($original['errors']) && is_array($original['errors'])) {
                $body['errors'] = $original['errors'];
            }

            return response()->json($body, $status, $response->headers->all());
        });
    })->create();
