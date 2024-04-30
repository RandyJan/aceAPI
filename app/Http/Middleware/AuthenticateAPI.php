<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthenticateAPI
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $auth = $request->header('Authorization');
 
        if ($auth) {
            $credentials = base64_decode(substr($auth, 6));
            list($username, $password) = explode(':', $credentials, 2);

            if ($username === 'your_username' && $password === 'your_password') {
                return $next($request);
            }
        }

        return response()->json(['message' => 'Unauthorized'], 401);
    }
    }

