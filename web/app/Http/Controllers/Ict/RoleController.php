<?php

namespace App\Http\Controllers\Ict;

use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Support\AccessManagementContext;

class RoleController extends AdminRoleController
{
    protected function accessContext(): AccessManagementContext
    {
        return AccessManagementContext::ict();
    }
}
