<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;

class HandleCmsRedirects
{
    public function handle(Request $request, Closure $next)
    {
        $path = '/'.ltrim($request->getPathInfo(), '/');
        if ($path !== '/' && ! str_ends_with($path, '/')) {
            $path .= '/';
        }

        $redirect = Redirect::query()->where('active', true)->where('from_path', $path)->first();
        if (! $redirect && $path !== '/') {
            $redirect = Redirect::query()->where('active', true)->where('from_path', rtrim($path, '/'))->first();
        }

        if ($redirect && $redirect->to_url && $redirect->to_url !== $path) {
            $to = $redirect->to_url;
            $hop = Redirect::query()->where('active', true)->where('from_path', $to)->first();
            if ($hop && $hop->to_url !== $redirect->from_path) {
                $to = $hop->to_url;
            }

            return redirect($to, $redirect->status_code ?: 301);
        }

        return $next($request);
    }
}
