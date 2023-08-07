<?php

namespace Nebula\Middleware\Http;

use Nebula\Interfaces\Http\{Response, Request};
use Nebula\Interfaces\Middleware\Middleware;
use Closure;

class RateLimit implements Middleware
{
  public function handle(Request $request, Closure $next): Response
  {
    if (env("REDIS_ENABLED")) {
      $result = $this->rateLimit($request);
      if (!$result) {
        $response = app()->get(Response::class);
        $response->setStatusCode(429);
        $response->setContent('Rate limit exceeded');
        return $response;
      }
    }
    $response = $next($request);

    return $response;
  }

  private function rateLimit(Request $request): bool
  {
    $config = config('redis');
    $client = new \Predis\Client($config);


    $ipAddress = $request->server()['REMOTE_ADDR'];
    $rateLimit = 100; // Number of requests allowed per minute
    $ipKey = "ip:$ipAddress";

    // Add the current timestamp to the Redis Sorted Set
    $timestamp = time();
    $client->zAdd($ipKey, $timestamp, $timestamp);

    // Remove any timestamps that exceed the rate limit window
    $windowStart = $timestamp - 60; // 60 seconds = 1 minute window
    $client->zRemRangeByScore($ipKey, 0, $windowStart);

    // Get the number of requests made from the IP address in the window
    $requestsInWindow = $client->zCard($ipKey);

    if ($requestsInWindow > $rateLimit) {
      // IP address has exceeded the rate limit, return an error response
      return false;
    }

    return true; 
  }
}
