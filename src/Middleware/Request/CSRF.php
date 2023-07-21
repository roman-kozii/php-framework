<?php

namespace Nebula\Middleware\Request;

use Symfony\Component\HttpFoundation\Request;
use Nebula\Middleware\Middleware;

/**
 * CSRF (Cross-Site Request Forgery) Middleware
 *
 * Generates a csrf token in the session
 * Validates request methods: POST, PUT, PATCH, DELETE which require CSRF token
 */
class CSRF extends Middleware
{
    /**
     *
     */
    public function handle(Request $request): Request
    {
        $this->init();
        $this->regenerate();
        $request = $this->validate($request);

        return $request;
    }

    /**
     * Generate a CSRF token
     */
    private function init(): void
    {
        $csrf_token = session()->get("csrf_token");
        if (is_null($csrf_token)) {
            session()->set("csrf_token", bin2hex(random_bytes(32)));
        }
    }

    /**
     * Validate CSRF token
     */
    private function validate(Request $request): ?Request
    {
        if (
            in_array($request->getMethod(), ["POST", "PUT", "PATCH", "DELETE"])
    ) {
            if (
                !empty($request->get("csrf_token")) &&
                hash_equals(
                    session()->get("csrf_token"),
                    $request->get("csrf_token")
                )
            ) {
                // CSRF token is valid
                return $request;
            } else {
                // CSRF token is invalid
                error_log(
                    "CSRF token missing or invalid: " .
                        $request->server->get("REMOTE_ADDR")
                );
                app()->forbidden();
            }
        }
        return $request;
    }

    /**
     * Regenerate CSRF token every hour
     */
    private function regenerate(): void
    {
        $csrf_ts = session()->get("csrf_token_timestamp");
        if (is_null($csrf_ts) || $csrf_ts + 3600 < time()) {
            session()->set("csrf_token", bin2hex(random_bytes(32)));
            session()->set("csrf_token_timestamp", time());
        }
    }
}
