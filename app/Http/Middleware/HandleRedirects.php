<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves administrator/slug-change 301 redirects before routing so renamed
 * vehicles and landing pages never 404.
 */
class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') || $request->expectsJson()) {
            return $next($request);
        }

        if ($request->is('admin') || $request->is('admin/*') || $request->is('api/*')) {
            return $next($request);
        }

        $redirect = Redirect::findForPath('/'.$request->path());

        if ($redirect && $redirect->new_path !== '/'.$request->path()) {
            $redirect->recordHit();

            return redirect($redirect->new_path, $redirect->status_code);
        }

        return $next($request);
    }
}
