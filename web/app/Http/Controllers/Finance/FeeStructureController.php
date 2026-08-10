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
        return $request->validate([
            'program_id' => 'required|exists:academic_programs,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester_number' => 'required|integer|min:1|max:12',
            'tuition_fee' => 'required|numeric|min:0',
            'examination_fee' => 'nullable|numeric|min:0',
            'library_fee' => 'nullable|numeric|min:0',
            'activity_fee' => 'nullable|numeric|min:0',
            'hostel_fee' => 'nullable|numeric|min:0',
            'medical_insurance_fee' => 'nullable|numeric|min:0',
            'nursing_clinical_fee' => 'nullable|numeric|min:0',
            'graduation_fee' => 'nullable|numeric|min:0',
            'registration_fee' => 'nullable|numeric|min:0',
            'effective_from' => 'required|date',
            'is_active' => 'sometimes|boolean',
        ]);
    }
}
