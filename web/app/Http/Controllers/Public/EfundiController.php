<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\EfundiOpportunity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class EfundiController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->query('type');
        $opportunities = collect();

        if (Schema::hasTable('efundi_opportunities')) {
            $opportunities = EfundiOpportunity::query()
                ->with('jobVacancy.department')
                ->visibleOnPublic()
                ->when($type, fn ($q) => $q->where('opportunity_type', $type))
                ->orderByDesc('published_at')
                ->orderByDesc('created_at')
                ->get();
        }

        return view('pages.e-fundi.index', [
            'opportunities' => $opportunities,
            'types' => EfundiOpportunity::types(),
            'filterType' => $type,
        ]);
    }

    public function show(EfundiOpportunity $opportunity): View
    {
        abort_unless($opportunity->is_published, 404);

        $opportunity->load('jobVacancy.department');

        return view('pages.e-fundi.show', [
            'opportunity' => $opportunity,
        ]);
    }
}