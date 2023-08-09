<?php

/**
 * NEBULA -- a powerful PHP micro-framework
 * Github: https://github.com/libra-php/nebula
 * Created: william.hleucka@gmail.com
 * License: MIT
 */
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__ . "/../bootstrap/app.php";

// Attribute-based-routing is enabled by default
// However, you can also use the $app->route() method
$app->route('GET', '/test', function() {
    return "Hello! From Nebula";
}, middleware: ['cached']);

$app->run(Nebula\Interfaces\Http\Kernel::class);
