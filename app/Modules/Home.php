<?php

namespace App\Modules;

use Nebula\Backend\Module;

class Home extends Module
{
    public function __construct()
    {
        parent::__construct("home");
    }

    protected function getTableTemplate(): string
    {
        return "backend/home.latte";
    }
}