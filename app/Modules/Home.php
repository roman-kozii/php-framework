<?php

namespace App\Modules;

use Nebula\Admin\Module;

class Home extends Module
{
    protected function customContent(): string
    {
        return latte("backend/home.latte");
    }
}
