<?php

namespace App\Http\Controllers\Ict;

use App\Http\Controllers\Admin\RoleCategoryController as AdminRoleCategoryController;
use App\Support\AccessManagementContext;

class RoleCategoryController extends AdminRoleCategoryController
{
    protected function accessContext(): AccessManagementContext
    {
        return AccessManagementContext::ict();
    }
}
