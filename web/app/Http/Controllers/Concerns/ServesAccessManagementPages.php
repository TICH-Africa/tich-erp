<?php

namespace App\Http\Controllers\Concerns;

use App\Support\AccessManagementContext;

trait ServesAccessManagementPages
{
    protected function accessContext(): AccessManagementContext
    {
        return AccessManagementContext::admin();
    }
}
