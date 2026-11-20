<?php

namespace Database\Seeders;

use App\Models\EfundiOpportunity;
use App\Models\JobVacancy;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class EfundiOpportunityContentSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('efundi_opportunities')) {
            return;
        }

        $posterId = User::query()->whereIn('email', [
            'admin@tich.ac.ke',
            'osumbaevans21@gmail.com',
        ])->value('id');

        $internalVacancyId = null;
        if (Schema::hasTable('job_vacancies')) {
            $internalVacancyId = JobVacancy::query()
                ->where('vacancy_number', 'VAC-2026-006')
                ->value('id');
        }

        $rows = [
            [
                'slug' => 'graduate-software-engineer-safaricom',
                'title' => 'Graduate Software Engineer — Safaricom PLC',
                'opportunity_type' => EfundiOpportunity::TYPE_JOB,
                'opportunity_scope' => EfundiOpportunity::SCOPE_EXTERNAL,
                'organization_name' => 'Safaricom PLC',
                'location' => 'Nairobi, Kenya (hybrid)',
                'summary' => 'Entry-level engineering role on digital products; training in cloud, APIs, and agile delivery.',
                'description' => "Safaricom's graduate programme places recent ICT graduates on product squads supporting M-Pesa and enterprise platforms.\n\n"
                    ."You will pair with senior engineers, contribute to backend services, and complete a structured onboarding curriculum.\n\n"
                    ."Minimum: Bachelor's in Computer Science, IT, or related field (or final-year students graduating within six months).",
                'application_url' => 'https://www.safaricom.co.ke/careers',
                'application_email' => null,
                'application_deadline' => now()->addMonths(2)->toDateString(),
                'is_published' => true,
                'posted_via' => 'marketing',
            ],
            [
                'slug' => 'health-informatics-internship-aga-khan',
                'title' => 'Health Informatics Internship — Aga Khan University Hospital',
                'opportunity_type' => EfundiOpportunity::TYPE_INTERNSHIP,
                'opportunity_scope' => EfundiOpportunity::SCOPE_EXTERNAL,
                'organization_name' => 'Aga Khan University Hospital, Nairobi',
                'location' => 'Nairobi',
                'summary' => 'Three-month internship supporting electronic health records, data quality, and clinical reporting.',
                'description' => "The internship is open to diploma and degree students in health information, nursing informatics, or health records.\n\n"
                    ."Interns rotate through the HIM department and ICT support, with a mentor and weekly learning clinics.\n\n"
                    ."Submit a CV, recommendation letter, and proof of enrolment.",
                'application_url' => null,
                'application_email' => 'careers@aku.edu',
                'application_deadline' => now()->addWeeks(6)->toDateString(),
                'is_published' => true,
                'posted_via' => 'ict',
            ],
            [
                'slug' => 'masters-scholarship-taita-taveta-county',
                'title' => 'County Masters Scholarship — Taita Taveta (Education & Health)',
                'opportunity_type' => EfundiOpportunity::TYPE_SCHOLARSHIP,
                'opportunity_scope' => EfundiOpportunity::SCOPE_EXTERNAL,
                'organization_name' => 'Taita Taveta County Government',
                'location' => 'Taita Taveta / accredited Kenyan universities',
                'summary' => 'Partial scholarships for residents pursuing Masters in Education, Public Health, or Community Development.',
                'description' => "The bursary covers tuition up to KES 120,000 per academic year for eligible county residents.\n\n"
                    ."Applicants must have a First Class or Upper Second undergraduate degree and a letter of admission from a recognised university.\n\n"
                    ."Priority is given to candidates willing to serve in county institutions after graduation.",
                'application_url' => null,
                'application_email' => 'bursaries@taitataveta.go.ke',
                'application_deadline' => now()->addMonth()->toDateString(),
                'is_published' => true,
                'posted_via' => 'marketing',
            ],
            [
                'slug' => 'cybersecurity-analyst-intern-computer-gallery',
                'title' => 'Cybersecurity Analyst Intern — Computer Gallery Africa',
                'opportunity_type' => EfundiOpportunity::TYPE_INTERNSHIP,
                'opportunity_scope' => EfundiOpportunity::SCOPE_EXTERNAL,
                'organization_name' => 'Computer Gallery Africa',
                'location' => 'Mombasa Road, Nairobi',
                'summary' => 'SOC monitoring, vulnerability scans, and incident documentation for SME clients.',
                'description' => "Ideal for students who have completed CCNA Security, CompTIA Security+, or equivalent coursework at TICH.\n\n"
                    ."Shift rotation includes evenings; transport allowance provided.\n\n"
                    ."Send application to careers with subject line \"SOC Intern — [Your name]\".",
                'application_url' => 'https://computergallery.co.ke',
                'application_email' => 'hr@computergallery.co.ke',
                'application_deadline' => now()->addWeeks(3)->toDateString(),
                'is_published' => true,
                'posted_via' => 'ict',
            ],
            [
                'slug' => 'unicef-kenya-youth-innovation-fellowship',
                'title' => 'UNICEF Kenya Youth Innovation Fellowship',
                'opportunity_type' => EfundiOpportunity::TYPE_OTHER,
                'opportunity_scope' => EfundiOpportunity::SCOPE_EXTERNAL,
                'organization_name' => 'UNICEF Kenya',
                'location' => 'Nairobi (with field visits)',
                'summary' => 'Six-month fellowship prototyping digital solutions for child protection and education outcomes.',
                'description' => "Teams of two to four young innovators receive stipends, coaching, and access to UNICEF programme data.\n\n"
                    ."Focus areas: attendance tracking, maternal messaging, and climate-resilient WASH in ASAL counties.\n\n"
                    ."Open to Kenyans aged 18–28. No deadline extension beyond the published date.",
                'application_url' => 'https://www.unicef.org/kenya',
                'application_email' => null,
                'application_deadline' => now()->addWeeks(5)->toDateString(),
                'is_published' => true,
                'posted_via' => 'marketing',
            ],
            [
                'slug' => 'open-call-research-assistant-tich',
                'title' => 'Research Assistant (Part-time) — TICH Research Directorate',
                'opportunity_type' => EfundiOpportunity::TYPE_JOB,
                'opportunity_scope' => EfundiOpportunity::SCOPE_EXTERNAL,
                'organization_name' => 'Taita Taveta Institute of Commerce and Health',
                'location' => 'Voi campus',
                'summary' => 'Support qualitative coding, literature reviews, and ethics submissions for community health studies.',
                'description' => "The Research Directorate is recruiting a part-time assistant for a 12-month CHW digital literacy study.\n\n"
                    ."Duties include transcribing interviews, maintaining REDCap records, and coordinating field logistics with supervisors.\n\n"
                    ."Degree students in public health, social sciences, or ICT may apply.",
                'application_url' => null,
                'application_email' => 'research@tich.ac.ke',
                'application_deadline' => null,
                'is_published' => true,
                'posted_via' => 'ict',
            ],
        ];

        if ($internalVacancyId) {
            $rows[] = [
                'slug' => 'tich-payroll-administrator',
                'title' => 'Payroll Administrator — TICH (internal vacancy)',
                'opportunity_type' => EfundiOpportunity::TYPE_JOB,
                'opportunity_scope' => EfundiOpportunity::SCOPE_INTERNAL,
                'job_vacancy_id' => $internalVacancyId,
                'organization_name' => 'Taita Taveta Institute of Commerce and Health',
                'location' => 'Main campus, Voi',
                'summary' => 'Permanent role in Finance/HR: monthly payroll, statutory returns, and staff queries.',
                'description' => "This listing mirrors the official HR vacancy. Apply through the recruitment portal linked to this post.\n\n"
                    ."Diploma in HR, finance, or business with payroll experience. KRA, NSSF, and SHA compliance knowledge required.",
                'application_url' => null,
                'application_email' => null,
                'application_deadline' => null,
                'is_published' => true,
                'posted_via' => 'ict',
            ];
        }

        foreach ($rows as $row) {
            $slug = $row['slug'];
            unset($row['slug']);

            $published = (bool) ($row['is_published'] ?? false);

            EfundiOpportunity::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    ...$row,
                    'published_at' => $published ? now()->subDays(rand(1, 14)) : null,
                    'posted_by_user_id' => $posterId,
                    'students_notified_at' => $published ? now()->subDays(rand(0, 7)) : null,
                ]
            );
        }
    }
}