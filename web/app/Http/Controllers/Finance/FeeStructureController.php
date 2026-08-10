<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\FeeStructure;
use App\Services\Finance\FeeStructureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeeStructureController extends Controller
{
    public function __construct(
        protected FeeStructureService $feeStructures,
    ) {}

    public function index(Request $request): View
    {
        return view('finance.fee-structures.index', [
            'feeStructures' => $this->feeStructures->paginated($request->integer('program_id') ?: null),
            'programs' => AcademicProgram::query()->orderBy('program_name')->get(['id', 'program_name']),
        ]);
    }

    public function create(): View
    {
        return view('finance.fee-structures.create', [
            'programs' => AcademicProgram::query()->orderBy('program_name')->get(),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $feeStructure = $this->feeStructures->create($validated);

        return redirect()->route('finance.fee-structures.show', $feeStructure)->with('success', 'Fee structure created.');
    }

    public function show(FeeStructure $feeStructure): View
    {
        $feeStructure->load(['program.department', 'academicYear', 'approver']);

        return view('finance.fee-structures.show', compact('feeStructure'));
    }

    public function edit(FeeStructure $feeStructure): View
    {
        return view('finance.fee-structures.edit', [
            'feeStructure' => $feeStructure,
            'programs' => AcademicProgram::query()->orderBy('program_name')->get(),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
        ]);
    }

    public function update(Request $request, FeeStructure $feeStructure): RedirectResponse
    {
        $this->feeStructures->update($feeStructure, $this->validated($request));

        return redirect()->route('finance.fee-structures.show', $feeStructure)->with('success', 'Fee structure updated.');
    }

    public function approve(Request $request, FeeStructure $feeStructure): RedirectResponse
    {
        $staffId = (int) ($request->user()->staff_id ?? \App\Models\Staff::query()->value('id'));
        $this->feeStructures->approve($feeStructure, $staffId);

        return back()->with('success', 'Fee structure approved.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'program_id' => 'required|exists:academic_programs,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'effective_from' => 'required|date',
            'application_fee' => 'required|numeric|min:0',
            'tuition_fee' => 'required|numeric|min:0',
            'caution_fee' => 'nullable|numeric|min:0',
            'computer_lab_fee' => 'nullable|numeric|min:0',
            'transport_fee' => 'nullable|numeric|min:0',
            'accommodation_fee' => 'nullable|numeric|min:0',
            'partnership_fee' => 'nullable|numeric|min:0',
            'id_card_fee' => 'nullable|numeric|min:0',
            'student_union_fee' => 'nullable|numeric|min:0',
            'emergency_fund_fee' => 'nullable|numeric|min:0',
            'library_fee' => 'nullable|numeric|min:0',
            'examination_external_fee' => 'nullable|numeric|min:0',
            'attachment_fee' => 'nullable|numeric|min:0',
            'qa_annual_fee' => 'required|numeric|min:0',
            'indexing_nck_fee' => 'nullable|numeric|min:0',
            'graduation_fee' => 'required|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $validated['requires_indexing_nck'] = $request->boolean('requires_indexing_nck');
        $validated['transport_optional'] = true;
        $validated['accommodation_optional'] = true;

        if (! $validated['requires_indexing_nck']) {
            $validated['indexing_nck_fee'] = null;
        }

        return $validated;
    }
}
