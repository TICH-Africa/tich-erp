<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Imports\AlumniGraduatesImport;
use App\Models\AcademicProgram;
use App\Models\Campus;
use App\Models\Student;
use App\Services\AlumniService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Admissions registers the school's past-year graduates. Everything written here lands on the
 * student record the school already has, and Academics reads the result from its own Alumni section.
 */
class AlumniController extends Controller
{
    private const STORE_DIR = 'imports/alumni';

    public function __construct(
        protected AlumniService $alumni,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'graduation_year' => ['nullable', 'integer', 'min:1980', 'max:2100'],
            'program_id' => ['nullable', 'integer'],
        ]);

        $students = $this->alumni->list($filters);

        return view('administration.alumni.index', [
            'students' => $students,
            'filters' => $filters,
            'years' => $this->alumni->graduationYears(),
            'programs' => AcademicProgram::query()->orderBy('program_name')->get(['id', 'program_name', 'program_code']),
            'total' => Student::query()->alumni()->count(),
        ]);
    }

    /** Find the person to register: current students only, since a graduate is one of them. */
    public function create(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $students = Student::query()
            ->whereNotIn('enrollment_status', AlumniService::STATUSES)
            ->where(function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where('registration_number', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('middle_name', 'like', $like)
                    ->orWhere('surname', 'like', $like);
            })
            ->with(['program:id,program_name,program_code'])
            ->orderBy('surname')
            ->limit(60)
            ->get();

        return view('administration.alumni.create', [
            'search' => $search,
            'students' => $students,
        ]);
    }

    /**
     * A past-year graduate the school never captured: the person is not on the student list at all,
     * so this form creates the record already finished instead of asking for it to be added twice.
     */
    public function createGraduate(Request $request): View
    {
        return view('administration.alumni.add', [
            'programs' => AcademicProgram::query()
                ->where('status', 'active')
                ->orderBy('program_name')
                ->get(['id', 'program_name', 'program_code']),
            'campuses' => Campus::query()
                ->where('is_active', 1)
                ->orderBy('campus_name')
                ->get(['id', 'campus_name', 'campus_type']),
            'prefill' => array_filter([
                'registration_number' => trim((string) $request->query('registration_number', '')),
                'full_name' => trim((string) $request->query('name', '')),
                'graduation_year' => trim((string) $request->query('graduation_year', '')),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'registration_number' => ['required', 'string', 'max:50', 'unique:students,registration_number'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'surname' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'program_id' => ['required', 'integer', 'exists:academic_programs,id'],
            'campus_id' => ['required', 'integer', 'exists:campuses,id'],
            'year_joined' => ['required', 'integer', 'min:1960', 'max:2100'],
            'graduation_year' => ['required', 'integer', 'min:1960', 'max:2100', 'gte:year_joined'],
            'graduation_date' => ['nullable', 'date'],
            'graduation_award' => ['nullable', 'string', 'max:120'],
            'gpa' => ['nullable', 'numeric', 'min:0', 'max:4'],
            'current_year' => ['nullable', 'integer', 'min:1', 'max:4'],
            'status' => ['nullable', 'in:graduated,alumni'],
            // Extension rather than MIME: the host's fileinfo often reports .jpg as other types.
            'photo' => ['nullable', 'image', 'max:2048'],
        ], [
            'graduation_year.gte' => 'They cannot finish before the year they joined.',
        ]);

        if (! empty($data['graduation_date'])
            && (int) date('Y', strtotime((string) $data['graduation_date'])) !== (int) $data['graduation_year']) {
            return back()
                ->withErrors(['graduation_date' => 'The graduation date year must match the year they ended.'])
                ->withInput();
        }

        $student = $this->alumni->create($data, $request->user(), $request);

        if ($request->hasFile('photo')) {
            $student->photo_path = $request->file('photo')->store("students/{$student->id}", 'public');
            $student->save();
        }

        return redirect()
            ->route('administration.alumni.index')
            ->with('status', $student->displayName().' ('.$student->registration_number.') has been added to the alumni register.');
    }

    public function edit(Student $student): View
    {
        abort_unless($student->exists, 404);

        return view('administration.alumni.register', [
            'student' => $student,
        ]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $data = $request->validate([
            'graduation_year' => ['nullable', 'integer', 'min:1980', 'max:2100'],
            'graduation_date' => ['nullable', 'date'],
            'graduation_award' => ['nullable', 'string', 'max:120'],
            'gpa' => ['nullable', 'numeric', 'min:0', 'max:4'],
            'status' => ['nullable', 'in:graduated,alumni'],
        ]);

        if (empty($data['graduation_year']) && empty($data['graduation_date'])) {
            return back()->withErrors(['graduation_year' => 'A graduate needs at least the year they finished.']);
        }

        if ($this->alumni->isAlumnus($student) && $data['graduation_year'] === null && $data['graduation_date'] === null) {
            return back()->withErrors(['graduation_year' => 'Keep the year or date they were registered with.']);
        }

        $this->alumni->register($student, $data, $request->user(), $request);

        return redirect()
            ->route('administration.alumni.index')
            ->with('status', $student->displayName().' is on the alumni register.');
    }

    public function undo(Request $request, Student $student): RedirectResponse
    {
        $this->alumni->undo($student, $request->user(), $request);

        return redirect()
            ->route('administration.alumni.index')
            ->with('status', $student->displayName().' is back on the current student list.');
    }

    public function upload(): View
    {
        return view('administration.alumni.upload');
    }

    /** Download the exact column headings the upload expects. */
    public function template(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'wb');
            fputcsv($out, ['registration_number', 'full_name', 'graduation_year', 'graduation_date', 'graduation_award']);
            fclose($out);
        }, 'alumni-list-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function preview(Request $request): RedirectResponse
    {
        $stored = $this->storeUpload($request);

        if ($stored instanceof RedirectResponse) {
            return $stored;
        }

        [$relative, $extension] = $stored;

        try {
            $result = (new AlumniGraduatesImport)->preview(Storage::disk('local')->path($relative), $extension);
        } catch (\Throwable $exception) {
            report($exception);
            Storage::disk('local')->delete($relative);

            return back()->withErrors(['file' => $exception->getMessage()])->withInput();
        }

        if ($result['errors'] !== []) {
            Storage::disk('local')->delete($relative);

            return back()->withErrors(['file' => implode(' ', $result['errors'])])->withInput();
        }

        // Only the sheet handle travels between the two steps; the rows are re-read and re-matched
        // on confirm so nothing posted back from the browser decides who gets marked.
        session([
            'alumni_import.token' => $this->tokenFromPath($relative),
            'alumni_import.preview' => $result,
        ]);

        return redirect()->route('administration.alumni.review');
    }

    public function showPreview(): View|RedirectResponse
    {
        $preview = session('alumni_import.preview');
        $token = (string) session('alumni_import.token', '');

        if (! is_array($preview) || $token === '') {
            return redirect()->route('administration.alumni.upload');
        }

        return view('administration.alumni.preview', [
            'preview' => $preview,
            'token' => $token,
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $token = (string) $request->input('token', '');

        if (! preg_match('/^[0-9a-f]{32}$/', $token)) {
            return redirect()->route('administration.alumni.upload');
        }

        $disk = Storage::disk('local');
        $files = $disk->files(self::STORE_DIR);
        $relative = null;

        foreach ($files as $file) {
            if (! str_starts_with(basename($file), $token.'.')) {
                continue;
            }

            $relative = $file;
            $extension = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
            break;
        }

        if ($relative === null) {
            return redirect()
                ->route('administration.alumni.upload')
                ->withErrors(['file' => 'That upload has expired. Choose the sheet again.']);
        }

        try {
            $result = (new AlumniGraduatesImport)->preview($disk->path($relative), $extension);
            $applied = $this->alumni->applyImport($result['rows'], $request->user(), $request);
        } finally {
            $disk->delete($relative);
            session()->forget(['alumni_import.token', 'alumni_import.preview']);
        }

        $message = $applied['marked'] === 0
            ? 'Nothing was registered - '.$applied['skipped'].' line(s) did not match a current student.'
            : $applied['marked'].' graduate(s) added to the alumni register.';

        return redirect()
            ->route('administration.alumni.index')
            ->with('status', $message)
            ->with('import_problems', array_slice($applied['problems'], 0, 8));
    }

    /**
     * @return array{0: string, 1: string}|RedirectResponse
     */
    private function storeUpload(Request $request): array|RedirectResponse
    {
        $request->validate([
            // Extension, not MIME: Linux fileinfo reports .xlsx as application/zip and mimes: fails.
            'file' => ['required', 'file', 'extensions:xlsx,xls,csv', 'max:10240'],
        ]);

        /** @var UploadedFile $upload */
        $upload = $request->file('file');
        $extension = strtolower((string) $upload->getClientOriginalExtension());
        $token = bin2hex(random_bytes(16));
        $relative = self::STORE_DIR.'/'.$token.'.'.$extension;
        $stored = $upload->storeAs(self::STORE_DIR, $token.'.'.$extension, 'local');

        if (! $stored) {
            return back()->withErrors(['file' => 'The sheet could not be stored. Check that storage/app/private is writable.'])->withInput();
        }

        return [$relative, $extension];
    }

    private function tokenFromPath(string $relative): string
    {
        return (string) pathinfo($relative, PATHINFO_FILENAME);
    }
}
