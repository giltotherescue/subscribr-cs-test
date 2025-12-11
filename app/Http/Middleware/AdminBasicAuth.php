<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminBasicAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $username = config('assessment.admin.username');
        $password = config('assessment.admin.password');

        if (! $username || ! $password) {
            abort(500, 'Admin credentials not configured');
        }

        // Use hash_equals to prevent timing attacks
        $userMatches = hash_equals($username, $request->getUser() ?? '');
        $passMatches = hash_equals($password, $request->getPassword() ?? '');

        if (! $userMatches || ! $passMatches) {
            return response('Unauthorized', 401, [
                'WWW-Authenticate' => 'Basic realm="Admin Area"',
            ]);
        }

        return $next($request);
    }
}
