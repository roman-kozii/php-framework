<?php

namespace Nebula\Controllers\Admin;

use Nebula\Admin\Auth;
use Nebula\Controllers\Controller;

use StellarRouter\{Get, Post};

class AuthController extends Controller
{
    #[Get("/admin/sign-out", "auth.sign_out")]
    public function sign_out(): void
    {
        Auth::signOut();
    }

    #[Get("/admin/sign-in", "auth.sign_in")]
    public function sign_in(): string
    {
        return twig("admin/sign-in.html");
    }

    #[Get("/admin/register", "auth.register")]
    public function register(): string
    {
        return twig("admin/register.html");
    }

    #[Post("/admin/sign-in", "auth.sign_in_post")]
    public function sign_in_post(): string
    {
        $request = $this->validate([
            "email" => ["required", "string", "email"],
            "password" => ["required", "string"],
        ]);
        return $this->sign_in();
    }

    #[Post("/admin/register", "auth.register_post")]
    public function register_post(): string
    {
        $request = $this->validate([
            "name" => ["required", "string"],
            "email" => ["required", "string", "email"],
            "password" => [
                "required",
                "string",
                "min_length=8",
                "uppercase=1",
                "lowercase=1",
                "symbol=1",
            ],
            "password_check" => ["required", "match"],
        ]);
        if ($request) {
            $user = Auth::register($request);
            if ($user) {
                Auth::signIn($user);
            }
        }
        return $this->register();
    }
}
