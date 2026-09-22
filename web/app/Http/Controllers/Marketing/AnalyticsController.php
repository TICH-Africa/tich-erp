<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Marketing\Lead;
use App\Models\Marketing\LeadActivity;
use App\Models\Marketing\Report;
use App\Models\Student;
use App\Models\AcademicProgram;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(): View
    {
        // Leads overview
        $totalLeads = Lead::count();
        $leadsByStage = Lead::select('stage')
            ->selectRaw('count(*) as count')
            ->groupBy('stage')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->stage => $row->count]);
        $leadsBySource = Lead::select('source')
            ->selectRaw('count(*) as count')
            ->groupBy('source')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->source => $row->count]);
        $recentLeads = Lead::with('program')->latest()->take(5)->get();

        // Lead Activities overview
        $totalActivities = LeadActivity::count();
        $activitiesByType = LeadActivity::select('activity_type')
            ->selectRaw('count(*) as count')
            ->groupBy('activity_type')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->activity_type => $row->count]);
        $pendingActivities = LeadActivity::where('completed', false)->count();
        $completedActivities = LeadActivity::where('completed', true)->count();
        $overdueActivities = LeadActivity::where('completed', false)
            ->where('scheduled_date', '<', now()->toDateString())
            ->count();

        // Reports overview
        $totalReports = Report::count();
        $reportsByStatus = Report::select('status')
            ->selectRaw('count(*) as count')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->status => $row->count]);
        $reportsByType = Report::select('report_type')
            ->selectRaw('count(*) as count')
            ->groupBy('report_type')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->report_type => $row->count]);
        $recentReports = Report::with('preparedBy')->latest()->take(5)->get();

        // Enrolled Students overview
        $totalStudents = Student::where('is_active', true)->count();
        $studentsByProgram = Student::where('is_active', true)
            ->select('program_id')
            ->selectRaw('count(*) as count')
            ->groupBy('program_id')
            ->with('program:id,program_name')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->program?->program_name ?? 'Unknown' => $row->count]);
        $studentsByStatus = Student::where('is_active', true)
            ->select('enrollment_status')
            ->selectRaw('count(*) as count')
            ->groupBy('enrollment_status')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->enrollment_status => $row->count]);
        $recentIntakes = Student::where('is_active', true)
            ->select('cohort_intake')
            ->selectRaw('count(*) as count')
            ->groupBy('cohort_intake')
            ->orderByDesc('count')
            ->take(5)
            ->get()
            ->mapWithKeys(fn ($row) => [$row->cohort_intake => $row->count]);

        // Content overview (from marketing content tables)
        $aboutCount = \App\Models\Portal\AboutContentBlock::count();
        $blogCount = \App\Models\Portal\BlogPost::count();
        $pageCount = \App\Models\Portal\CmsPage::count();
        $eventCount = \App\Models\Portal\Event::count();
        $heroSlideCount = \App\Models\Portal\CarouselSlide::count();

        return view('marketing.analytics.index', [
            'totalLeads' => $totalLeads,
            'leadsByStage' => $leadsByStage,
            'leadsBySource' => $leadsBySource,
            'recentLeads' => $recentLeads,
            'totalActivities' => $totalActivities,
            'activitiesByType' => $activitiesByType,
            'pendingActivities' => $pendingActivities,
            'completedActivities' => $completedActivities,
            'overdueActivities' => $overdueActivities,
            'totalReports' => $totalReports,
            'reportsByStatus' => $reportsByStatus,
            'reportsByType' => $reportsByType,
            'recentReports' => $recentReports,
            'totalStudents' => $totalStudents,
            'studentsByProgram' => $studentsByProgram,
            'studentsByStatus' => $studentsByStatus,
            'recentIntakes' => $recentIntakes,
            'aboutCount' => $aboutCount,
            'blogCount' => $blogCount,
            'pageCount' => $pageCount,
            'eventCount' => $eventCount,
            'heroSlideCount' => $heroSlideCount,
        ]);
    }
}