<?php

namespace App\Http\Controllers;

use App\Models\AcademicProgram;
use App\Models\JobVacancy;
use App\Models\Portal\BlogPost;
use App\Models\Portal\Event;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            ['loc' => route('home'), 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => route('about'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => route('research'), 'changefreq' => 'monthly', 'priority' => '0.7'],
            ['loc' => route('support'), 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => route('contact'), 'changefreq' => 'monthly', 'priority' => '0.7'],
            ['loc' => route('programs.index'), 'changefreq' => 'weekly', 'priority' => '0.9'],
            ['loc' => route('events'), 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['loc' => route('blog'), 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['loc' => route('careers.index'), 'changefreq' => 'daily', 'priority' => '0.8'],
            ['loc' => route('apply.index'), 'changefreq' => 'weekly', 'priority' => '0.7'],
        ];

        if (Schema::hasTable('academic_programs')) {
            AcademicProgram::query()
                ->where('status', 'active')
                ->orderBy('program_code')
                ->get(['program_code', 'updated_at', 'created_at'])
                ->each(function (AcademicProgram $program) use (&$urls) {
                    $urls[] = [
                        'loc' => route('programs.show', $program->program_code),
                        'lastmod' => optional($program->updated_at ?? $program->created_at)->toAtomString(),
                        'changefreq' => 'monthly',
                        'priority' => '0.8',
                    ];
                });
        }

        if (Schema::hasTable('events')) {
            Event::query()
                ->where('is_public', true)
                ->whereNotNull('slug')
                ->where('slug', '!=', '')
                ->orderByDesc('start_datetime')
                ->limit(500)
                ->get(['id', 'slug', 'updated_at', 'created_at', 'start_datetime'])
                ->each(function (Event $event) use (&$urls) {
                    $urls[] = [
                        'loc' => route('events.show', $event),
                        'lastmod' => optional($event->updated_at ?? $event->created_at ?? $event->start_datetime)->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.7',
                    ];
                });
        }

        if (Schema::hasTable('blog_posts')) {
            BlogPost::query()
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->orderByDesc('published_at')
                ->limit(500)
                ->get(['slug', 'updated_at', 'published_at', 'created_at'])
                ->each(function (BlogPost $post) use (&$urls) {
                    $urls[] = [
                        'loc' => route('blog.show', $post->slug),
                        'lastmod' => optional($post->updated_at ?? $post->published_at ?? $post->created_at)->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.7',
                    ];
                });
        }

        if (Schema::hasTable('job_vacancies')) {
            JobVacancy::query()
                ->where('is_published', true)
                ->where(function ($query) {
                    $query->where('is_closed', false)
                        ->orWhere('closing_date', '>=', now()->toDateString());
                })
                ->orderByDesc('published_on')
                ->limit(200)
                ->get(['id', 'published_on', 'created_at'])
                ->each(function (JobVacancy $vacancy) use (&$urls) {
                    $urls[] = [
                        'loc' => route('careers.show', $vacancy),
                        'lastmod' => optional($vacancy->published_on ?? $vacancy->created_at)->toAtomString(),
                        'changefreq' => 'daily',
                        'priority' => '0.7',
                    ];
                });
        }

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
