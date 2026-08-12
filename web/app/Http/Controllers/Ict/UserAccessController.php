<?php

namespace App\Http\Controllers\Ict;

use App\Http\Controllers\Admin\UserAccessController as AdminUserAccessController;
use App\Support\AccessManagementContext;

class UserAccessController extends AdminUserAccessController
{
    protected function accessContext(): AccessManagementContext
    {
        return AccessManagementContext::ict();
    }
}
