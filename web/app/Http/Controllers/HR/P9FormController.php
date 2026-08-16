<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class P9FormController extends Controller
{
    public function index(): View
    {
        return view('hr.p9-forms.index');
    }
}
