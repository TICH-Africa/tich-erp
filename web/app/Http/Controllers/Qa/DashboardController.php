<?php

namespace App\Http\Controllers\Qa;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('qa.dashboard');
    }
}
