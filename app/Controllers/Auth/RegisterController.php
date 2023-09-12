<?php

namespace App\Controllers\Auth;

use App\Models\Factories\UserFactory;
use Nebula\Controller\Controller;
use Nebula\Validation\Validate;
use StellarRouter\{Get, Post};

final class RegisterController extends Controller
{
  public function __construct()
  {
    // Disable registration if the config is set to false
    if (!config("auth.register_enabled")) {
      redirectRoute("sign-in.index");
    }
  }

  #[Get("/register", "register.index")]
  public function index(): string
  {
    return latte("auth/register.latte");
  }

  #[Get("/register/part", "register.part")]
  public function index_part(): string
  {
    return latte("auth/register.latte", [
      "name" => request()->get("name"),
    ], "body");
  }

  #[Post("/register", "register.post", ["rate_limit"])]
  public function post(): string
  {
    // Provide a custom message for the unique rule
    Validate::$messages["unique"] = "An account already exists for this email address";
    if ($this->validate([
      "name" => ["required"],
      "email" => ["required", "unique=users", "email"],
      "password" => [
        "required",
        "min_length=8",
        "uppercase=1",
        "lowercase=1",
        "symbol=1"
      ],
      // Note: you can change the label so that it
      // doesn't say Password_match in the UI
      "password_match" => ["Password" => ["required", "match"]]
    ])) {
      $factory = app()->get(UserFactory::class);
      $user = $factory->create(
        request()->name,
        request()->email,
        request()->password
      );
      if ($user) {
        // Set the user session
        session()->set("user", $user->uuid);
        // Redirect to the dashboard
        return redirectRoute("dashboard.index");
      }
    } 
    // Validation failed, show the register form
    return $this->index_part();
  }
}