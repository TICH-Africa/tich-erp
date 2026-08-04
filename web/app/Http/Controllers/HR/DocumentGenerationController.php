<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StaffDocumentTemplate;
use App\Services\DocumentGenerationService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Mpdf\Mpdf;

class DocumentGenerationController extends Controller
{
    public function __construct(
        protected DocumentGenerationService $documentService,
    ) {}

    public function index(): View
    {
        $templates = StaffDocumentTemplate::orderBy('type')->get();

        return view('hr.documents.templates.index', ['templates' => $templates]);
    }

    public function create(): View
    {
        $types = $this->documentService->getAvailableTemplates();

        return view('hr.documents.templates.create', ['types' => $types, 'templateStructure' => $this->documentService->getTemplateStructure()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'type' => 'required|string|max:100',
            'content' => 'required|string',
        ]);

        StaffDocumentTemplate::create($validated + ['created_by' => $request->user()->staff_id ?? Staff::first()?->id]);

        return redirect()->route('hr.documents.templates.index')->with('success', 'Template created successfully.');
    }

    public function edit(int $id): View
    {
        $template = StaffDocumentTemplate::findOrFail($id);
        $types = $this->documentService->getAvailableTemplates();

        return view('hr.documents.templates.edit', ['template' => $template, 'types' => $types, 'templateStructure' => $this->documentService->getTemplateStructure()]);
    }

    public function update(Request $request, int $id)
    {
        $template = StaffDocumentTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'type' => 'required|string|max:100',
            'content' => 'required|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $template->update($validated);

        return redirect()->route('hr.documents.templates.index')->with('success', 'Template updated successfully.');
    }

    public function destroy(Request $request, int $id)
    {
        $template = StaffDocumentTemplate::findOrFail($id);
        $template->delete();

        return redirect()->route('hr.documents.templates.index')->with('success', 'Template deleted successfully.');
    }

    public function preview(Request $request, int $templateId)
    {
        $template = StaffDocumentTemplate::findOrFail($templateId);
        $staff = Staff::findOrFail($request->integer('staff_id'));

        $content = $this->documentService->populateTemplate($template, $staff);
        $html = $this->documentService->renderDocument($content, $template->name, strtoupper($template->name));

        return view('hr.documents.templates.preview', [
            'html' => $html,
            'template' => $template,
            'staff' => $staff,
        ]);
    }

    public function generate(Request $request, int $templateId)
    {
        $template = StaffDocumentTemplate::findOrFail($templateId);
        $staff = Staff::findOrFail($request->integer('staff_id'));

        $content = $this->documentService->populateTemplate($template, $staff);
        $html = $this->documentService->renderDocument($content, $template->name, strtoupper($template->name));
        $downloadUrl = route('hr.documents.templates.download', ['template' => $template, 'staff_id' => $staff->id]);

        return view('hr.documents.templates.print', [
            'html' => $html,
            'template' => $template,
            'staff' => $staff,
            'downloadUrl' => $downloadUrl,
        ]);
    }

    public function download(Request $request, int $templateId)
    {
        $template = StaffDocumentTemplate::findOrFail($templateId);
        $staff = Staff::findOrFail($request->integer('staff_id'));

        $content = $this->documentService->populateTemplate($template, $staff);
        $html = $this->documentService->renderDocument($content, $template->name, strtoupper($template->name));
        $filename = $template->name . ' - ' . $staff->fullName() . '.pdf';

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
        ]);

        $mpdf->WriteHTML($html);
        $mpdf->Output($filename, 'D');
    }
}
