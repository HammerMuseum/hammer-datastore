<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\ResponseCache\Middlewares\CacheResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PublicCache
 * @package App\Http\Middleware
 * @license PublicDomain
 */
class PublicCache extends CacheResponse
{
    public function handle(Request $request, Closure $next, ...$args): Response
    {
        $response = parent::handle($request, $next, ...$args);
        $response->headers->addCacheControlDirective('public');

        $lifetimeInSeconds = $this->getLifetime($args);
        if ($lifetimeInSeconds !== null) {
            $response->headers->addCacheControlDirective('max-age', $lifetimeInSeconds);
        }

        return $response;
    }
}
