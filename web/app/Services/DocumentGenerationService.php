<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\StaffDocumentTemplate;
use Illuminate\Support\Str;

class DocumentGenerationService
{
    public function __construct(
        protected SiteSettingsService $siteSettings,
    ) {}

    public function getAvailableTemplates(): array
    {
        return [
            'contract' => 'Employment Contract',
            'probation_letter' => 'Probation Letter',
            'transfer_letter' => 'Transfer Letter',
            'exit_clearance' => 'Exit Clearance Checklist',
        ];
    }

    public function getTemplateStructure(): string
    {
        return 'Use the modern header/footer system from DocumentGenerationService. Call $this->header("Title", "Subtitle") and $this->footer() to wrap your content. Available CSS classes: doc-meta, doc-meta-item, doc-meta-label, doc-meta-value, signature-block, signature-box, signature-line.';
    }

    public function getTemplate(string $type): ?StaffDocumentTemplate
    {
        return StaffDocumentTemplate::where('type', $type)
            ->where('is_active', 1)
            ->first();
    }

    public function populateTemplate(StaffDocumentTemplate $template, Staff $staff, array $extraData = []): string
    {
        $data = array_merge($this->buildStaffContext($staff), $extraData);
        $content = $template->content;

        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value ?? '', $content);
            $content = str_replace('{{ ' . $key . ' }}', $value ?? '', $content);
        }

        return $content;
    }

    public function renderDocument(
        string $content,
        string $title,
        string $subtitle = '',
        bool $forPdf = false,
        ?string $downloadUrl = null,
    ): string {
        return $this->header($title, $subtitle, $forPdf, $downloadUrl) . $content . $this->footer();
    }

    public function buildStaffContext(Staff $staff): array
    {
        return [
            'staff_full_name' => $staff->fullName(),
            'staff_first_name' => $staff->first_name,
            'staff_middle_name' => $staff->middle_name ?? '',
            'staff_surname' => $staff->surname,
            'staff_employee_number' => $staff->employee_number,
            'staff_job_title' => $staff->job_title,
            'staff_department' => $staff->department->dept_name ?? '',
            'staff_campus' => $staff->campus->campus_name ?? '',
            'staff_employment_category' => ucfirst($staff->employment_category),
            'staff_employment_start_date' => $staff->employment_start_date?->format('F j, Y') ?? '',
            'staff_contract_end_date' => $staff->contract_end_date?->format('F j, Y') ?? 'Ongoing',
            'staff_gross_monthly_salary' => number_format($staff->gross_monthly_salary, 2),
            'staff_consolidated_gross_pay' => number_format($staff->gross_monthly_salary, 2),
            'staff_kra_pin' => $staff->kra_pin ?? '',
            'staff_nssf_number' => $staff->nssf_number ?? '',
            'staff_sha_number' => $staff->sha_number ?? '',
            'staff_helb_number' => $staff->helb_number ?? '',
            'staff_phone_number' => $staff->phone_number,
            'staff_primary_email' => $staff->primary_email,
            'staff_organisation_email' => $staff->organisation_email,
            'staff_postal_address' => $staff->postal_address ?? '',
            'staff_physical_address' => $staff->physical_address ?? '',
            'staff_date_of_birth' => $staff->date_of_birth?->format('F j, Y') ?? '',
            'staff_gender' => $staff->gender,
            'staff_marital_status' => $staff->marital_status ?? '',
            'staff_national_id' => $staff->national_id_number ?? '',
            'staff_line_manager' => $staff->lineManager?->fullName() ?? '',
            'institution_name' => $this->siteSettings->siteMeta()['institution_name'],
            'current_date' => now()->format('F j, Y'),
            'current_year' => now()->format('Y'),
        ];
    }

    private function header(string $title, string $subtitle = '', bool $forPdf = false, ?string $downloadUrl = null): string
    {
        $branding = $this->siteSettings->documentBranding($forPdf);
        $institutionName = htmlspecialchars($branding['name'], ENT_QUOTES, 'UTF-8');
        $subtitleText = htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8');
        $titleText = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $downloadHref = htmlspecialchars($downloadUrl ?? '#', ENT_QUOTES, 'UTF-8');

        if (! empty($branding['logo_src'])) {
            $logoHtml = '<img src="'.htmlspecialchars($branding['logo_src'], ENT_QUOTES, 'UTF-8').'" alt="'.htmlspecialchars($branding['short_name'], ENT_QUOTES, 'UTF-8').'" class="doc-logo-image">';
        } else {
            $logoHtml = '<div class="doc-logo-fallback">'.htmlspecialchars($branding['brand_initial'], ENT_QUOTES, 'UTF-8').'</div>';
        }

        $toolbar = $forPdf ? '' : <<<HTML
    <div class="no-print" style="padding: 20px 40px; background: #f3f4f6; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <button onclick="window.print()" style="background: #1e40af; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600;">Print / Save as PDF</button>
            <a href="{$downloadHref}" style="background: #059669; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; text-decoration: none; margin-left: 10px; display: inline-block;">Download</a>
        </div>
        <button onclick="window.close()" style="background: #6b7280; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer;">Close</button>
    </div>
HTML;

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{$titleText}</title>
    <style>
        @page { margin: 2cm; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            max-width: 900px;
            margin: 0 auto;
            padding: 0;
            background: #ffffff;
        }
        .doc-container {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .doc-header {
            background: #1e40af;
            color: white;
            padding: 30px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
        }
        .doc-header-text h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .doc-header-text p {
            margin: 5px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .doc-logo-image {
            max-width: 80px;
            max-height: 80px;
            width: auto;
            height: auto;
            object-fit: contain;
            background: #ffffff;
            border-radius: 8px;
            padding: 8px;
            display: block;
        }
        .doc-logo-fallback {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1e40af;
            font-weight: bold;
            font-size: 28px;
        }
        .doc-body {
            padding: 40px;
        }
        .doc-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            background: #f8fafc;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 30px;
            border-left: 4px solid #3b82f6;
        }
        .doc-meta-item {
            display: flex;
            flex-direction: column;
        }
        .doc-meta-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .doc-meta-value {
            font-weight: 600;
            color: #111827;
        }
        h2 {
            color: #1e40af;
            font-size: 18px;
            font-weight: 600;
            margin-top: 25px;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e5e7eb;
        }
        p {
            margin: 0 0 12px;
            color: #374151;
        }
        ul {
            margin: 0 0 15px;
            padding-left: 20px;
        }
        li {
            margin-bottom: 6px;
            color: #374151;
        }
        .signature-block {
            margin-top: 50px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
        .signature-box {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 20px;
            background: #fafafa;
        }
        .signature-line {
            border-top: 1px solid #9ca3af;
            padding-top: 10px;
            margin-top: 50px;
        }
        .no-print { display: none; }
        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
            .doc-container { border: none; box-shadow: none; }
        }
    </style>
</head>
<body>
{$toolbar}
    <div class="doc-container">
        <div class="doc-header">
            <div class="doc-header-text">
                <h1>{$institutionName}</h1>
                <p>{$subtitleText}</p>
            </div>
            {$logoHtml}
        </div>
        <div class="doc-body">
HTML;
    }

    private function footer(): string
    {
        return <<<'HTML'
        </div>
    </div>
</body>
</html>
HTML;
    }

    public function createDefaultTemplates(int $createdBy): void
    {
        $templates = [
            [
                'name' => 'Standard Employment Contract',
                'type' => 'contract',
                'content' => $this->contractTemplate(),
                'variables' => array_keys($this->buildStaffContext(new Staff())),
            ],
            [
                'name' => 'Probation Letter',
                'type' => 'probation_letter',
                'content' => $this->probationLetterTemplate(),
                'variables' => array_keys($this->buildStaffContext(new Staff())),
            ],
            [
                'name' => 'Transfer Letter',
                'type' => 'transfer_letter',
                'content' => $this->transferLetterTemplate(),
                'variables' => array_keys($this->buildStaffContext(new Staff())),
            ],
            [
                'name' => 'Exit Clearance Checklist',
                'type' => 'exit_clearance',
                'content' => $this->exitClearanceTemplate(),
                'variables' => array_keys($this->buildStaffContext(new Staff())),
            ],
        ];

        foreach ($templates as $template) {
            StaffDocumentTemplate::updateOrCreate(
                ['type' => $template['type']],
                array_merge($template, ['created_by' => $createdBy])
            );
        }
    }

    private function contractTemplate(): string
    {
        $html = '<div class="doc-meta">';
        $html .= '<div class="doc-meta-item"><span class="doc-meta-label">Contract Reference</span><span class="doc-meta-value">{{staff_employee_number}}/{{current_year}}</span></div>';
        $html .= '<div class="doc-meta-item"><span class="doc-meta-label">Date</span><span class="doc-meta-value">{{current_date}}</span></div>';
        $html .= '<div class="doc-meta-item"><span class="doc-meta-label">Employee</span><span class="doc-meta-value">{{staff_full_name}}</span></div>';
        $html .= '<div class="doc-meta-item"><span class="doc-meta-label">Position</span><span class="doc-meta-value">{{staff_job_title}}</span></div>';
        $html .= '</div>';
        $html .= '<h2>1. Parties</h2>';
        $html .= '<p>This Employment Contract is entered into between <strong>{{institution_name}}</strong> (hereinafter referred to as "the Employer") and <strong>{{staff_full_name}}</strong> (hereinafter referred to as "the Employee").</p>';
        $html .= '<h2>2. Position and Duties</h2>';
        $html .= '<p>The Employee is employed as <strong>{{staff_job_title}}</strong> in the <strong>{{staff_department}}</strong> department. The Employee shall perform such duties as are reasonably assigned by the Employer.</p>';
        $html .= '<h2>3. Employment Category</h2>';
        $html .= '<p>The Employee is engaged as a <strong>{{staff_employment_category}}</strong> employee.</p>';
        $html .= '<h2>4. Commencement Date</h2>';
        $html .= '<p>The employment shall commence on <strong>{{staff_employment_start_date}}</strong>.</p>';
        $html .= '<h2>5. Contract Duration</h2>';
        $html .= '<p>This contract shall remain in force until <strong>{{staff_contract_end_date}}</strong>, unless terminated earlier in accordance with the terms herein.</p>';
        $html .= '<h2>6. Probation Period</h2>';
        $html .= '<p>The Employee shall be on probation for a period of <strong>three (3) months</strong> from the commencement date. During this period, the Employer may terminate the contract with one (1) week\'s notice.</p>';
        $html .= '<h2>7. Remuneration</h2>';
        $html .= '<p>The Employee shall be paid a gross monthly salary of <strong>KES {{staff_gross_monthly_salary}}</strong>, payable on the last working day of each month.</p>';
        $html .= '<h2>8. Statutory Deductions</h2>';
        $html .= '<p>The Employer shall deduct statutory contributions as required by law, including but not limited to:</p>';
        $html .= '<ul><li>KRA PAYE (PIN: {{staff_kra_pin}})</li><li>NSSF (Number: {{staff_nssf_number}})</li><li>SHA (Number: {{staff_sha_number}})</li><li>HELB (Number: {{staff_helb_number}})</li></ul>';
        $html .= '<h2>9. Leave Entitlement</h2>';
        $html .= '<p>The Employee shall be entitled to annual leave as provided under the Employment Act and the institution\'s leave policy.</p>';
        $html .= '<h2>10. Termination</h2>';
        $html .= '<p>Either party may terminate this contract by giving the notice period required by law or as specified in the employment contract.</p>';
        $html .= '<h2>11. Governing Law</h2>';
        $html .= '<p>This contract shall be governed by the laws of Kenya.</p>';
        $html .= '<div class="signature-block">';
        $html .= '<div class="signature-box"><p><strong>Employer Representative</strong></p><div class="signature-line"><p>Signature: _____________________</p><p>Name: _____________________</p><p>Date: _____________________</p></div></div>';
        $html .= '<div class="signature-box"><p><strong>Employee</strong></p><div class="signature-line"><p>Signature: _____________________</p><p>Name: {{staff_full_name}}</p><p>Date: _____________________</p></div></div>';
        $html .= '</div>';

        return $html;
    }

    private function probationLetterTemplate(): string
    {
        $html = '<div class="doc-meta">';
        $html .= '<div class="doc-meta-item"><span class="doc-meta-label">Date</span><span class="doc-meta-value">{{current_date}}</span></div>';
        $html .= '<div class="doc-meta-item"><span class="doc-meta-label">Employee</span><span class="doc-meta-value">{{staff_full_name}}</span></div>';
        $html .= '<div class="doc-meta-item"><span class="doc-meta-label">Position</span><span class="doc-meta-value">{{staff_job_title}}</span></div>';
        $html .= '<div class="doc-meta-item"><span class="doc-meta-label">Department</span><span class="doc-meta-value">{{staff_department}}</span></div>';
        $html .= '</div>';
        $html .= '<h2>Subject: Confirmation of Probationary Period</h2>';
        $html .= '<p>Dear {{staff_first_name}},</p>';
        $html .= '<p>We are pleased to confirm your appointment as <strong>{{staff_job_title}}</strong> at <strong>{{institution_name}}</strong>. Your employment commenced on <strong>{{staff_employment_start_date}}</strong> and you are currently serving a probationary period.</p>';
        $html .= '<h2>Probation Period</h2>';
        $html .= '<p>During this period, your performance will be reviewed against agreed objectives.</p>';
        $html .= '<h2>Terms and Conditions</h2>';
        $html .= '<p>During the probationary period, either party may terminate the employment by giving one (1) week\'s written notice. Upon successful completion of the probationary period, your employment will be confirmed in accordance with the institution\'s policies.</p>';
        $html .= '<h2>Next Steps</h2>';
        $html .= '<p>Your line manager will conduct a performance review before the end of your probationary period. You will be notified of the outcome in writing.</p>';
        $html .= '<p>We wish you success in your role and look forward to your contributions to the institution.</p>';
        $html .= '<p>Yours sincerely,</p><br><br>';
        $html .= '<p>_________________________</p>';
        $html .= '<p><strong>HR Manager</strong></p>';
        $html .= '<p>{{institution_name}}</p>';

        return $html;
    }

    private function transferLetterTemplate(): string
    {
        $html = '<div class="doc-meta">';
        $html .= '<div class="doc-meta-item"><span class="doc-meta-label">Date</span><span class="doc-meta-value">{{current_date}}</span></div>';
        $html .= '<div class="doc-meta-item"><span class="doc-meta-label">Employee</span><span class="doc-meta-value">{{staff_full_name}}</span></div>';
        $html .= '<div class="doc-meta-item"><span class="doc-meta-label">Employee No</span><span class="doc-meta-value">{{staff_employee_number}}</span></div>';
        $html .= '<div class="doc-meta-item"><span class="doc-meta-label">Current Position</span><span class="doc-meta-value">{{staff_job_title}}</span></div>';
        $html .= '</div>';
        $html .= '<h2>Subject: Transfer of Station / Department</h2>';
        $html .= '<p>Dear {{staff_first_name}},</p>';
        $html .= '<p>Following a review of institutional requirements, you are hereby transferred from your current position to a new role within the institution.</p>';
        $html .= '<h2>Transfer Details</h2>';
        $html .= '<ul><li><strong>Current Department:</strong> {{staff_department}}</li><li><strong>Current Job Title:</strong> {{staff_job_title}}</li><li><strong>Effective Date:</strong> {{staff_employment_start_date}}</li></ul>';
        $html .= '<h2>Terms</h2>';
        $html .= '<p>All terms and conditions of your employment shall remain unchanged except as modified by this transfer. Your salary, benefits, and leave entitlements shall continue uninterrupted.</p>';
        $html .= '<h2>Reporting Line</h2>';
        $html .= '<p>You shall report to <strong>{{staff_line_manager}}</strong> in your new position.</p>';
        $html .= '<p>Please acknowledge receipt of this letter by signing below.</p>';
        $html .= '<p>Yours sincerely,</p><br><br>';
        $html .= '<p>_________________________</p>';
        $html .= '<p><strong>HR Manager</strong></p>';
        $html .= '<p>{{institution_name}}</p>';
        $html .= '<div style="margin-top: 50px; padding: 20px; background: #f8fafc; border-radius: 6px;">';
        $html .= '<p><strong>Acknowledged by Employee:</strong></p>';
        $html .= '<p>Signature: _____________________</p>';
        $html .= '<p>Name: {{staff_full_name}}</p>';
        $html .= '<p>Date: _____________________</p>';
        $html .= '</div>';

        return $html;
    }

    private function exitClearanceTemplate(): string
    {
        $html = '<div class="doc-meta">';
        $html .= '<div class="doc-meta-item"><span class="doc-meta-label">Employee</span><span class="doc-meta-value">{{staff_full_name}}</span></div>';
        $html .= '<div class="doc-meta-item"><span class="doc-meta-label">Employee No</span><span class="doc-meta-value">{{staff_employee_number}}</span></div>';
        $html .= '<div class="doc-meta-item"><span class="doc-meta-label">Department</span><span class="doc-meta-value">{{staff_department}}</span></div>';
        $html .= '<div class="doc-meta-item"><span class="doc-meta-label">Job Title</span><span class="doc-meta-value">{{staff_job_title}}</span></div>';
        $html .= '</div>';
        $html .= '<h2>Clearance Checklist</h2>';
        $html .= '<table><thead><tr><th>Done</th><th>Department / Item</th><th>Signatory</th><th>Date</th><th>Remarks</th></tr></thead><tbody>';
        $html .= '<tr><td style="text-align: center;">☐</td><td>Return of ID / Access Card</td><td>HR</td><td></td><td></td></tr>';
        $html .= '<tr><td style="text-align: center;">☐</td><td>Return of Laptop / Equipment</td><td>ICT</td><td></td><td></td></tr>';
        $html .= '<tr><td style="text-align: center;">☐</td><td>Library Books Returned</td><td>Library</td><td></td><td></td></tr>';
        $html .= '<tr><td style="text-align: center;">☐</td><td>Cash Advances / Imprest Cleared</td><td>Finance</td><td></td><td></td></tr>';
        $html .= '<tr><td style="text-align: center;">☐</td><td>SACCO Obligations Cleared</td><td>SACCO</td><td></td><td></td></tr>';
        $html .= '<tr><td style="text-align: center;">☐</td><td>Handover of Files / Documents</td><td>Supervisor</td><td></td><td></td></tr>';
        $html .= '<tr><td style="text-align: center;">☐</td><td>Final Pay Processed</td><td>Finance</td><td></td><td></td></tr>';
        $html .= '</tbody></table>';
        $html .= '<div class="signature-block" style="margin-top: 40px;">';
        $html .= '<div class="signature-box"><p><strong>HR Officer:</strong></p><div class="signature-line"><p>Signature: _____________________</p><p>Date: _____________________</p></div></div>';
        $html .= '</div>';

        return $html;
    }
}
