<?php

namespace App\Http\Controllers\Qa;

use App\Http\Controllers\Controller;
use App\Models\Qa\QaCapacitySession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CapacityController extends Controller
{
    public function index(): View
    {
        $sessions = QaCapacitySession::query()
            ->orderByDesc('scheduled_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('qa.capacity.index', compact('sessions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:5000'],
            'scheduled_at' => ['nullable', 'date'],
            'audience' => ['nullable', 'string', 'max:300'],
            'location' => ['nullable', 'string', 'max:300'],
            'status' => ['required', 'in:scheduled,completed,cancelled'],
        ]);

        QaCapacitySession::query()->create([
            ...$validated,
            'created_by' => $request->user()?->staff_id,
        ]);

        return back()->with('status', 'Capacity-building session recorded.');
    }
}
