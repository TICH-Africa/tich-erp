<?php

namespace App\Http\Controllers\Ict;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('ict.dashboard');
    }
}
