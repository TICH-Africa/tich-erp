<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Portal\BlogPost;
use App\Models\Portal\CmsPage;
use App\Models\Portal\Event;
use App\Models\Portal\ResearchPartner;
use App\Models\Portal\ResearchProject;
use App\Models\Portal\ResearchResource;
use App\Services\AboutContentService;
use App\Services\HomepageService;
use App\Services\PrintDocumentService;
use App\Support\KenyaDataProtectionNotice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HomeController extends Controller
{
    public function __construct(
        protected HomepageService $homepageService,
        protected AboutContentService $aboutContent,
        protected PrintDocumentService $printDocuments,
    ) {}

    public function index(): View
    {
        $homepage = $this->homepageService->getPayload();

        return view('home', $homepage);
    }

    public function about(): View
    {
        return view('pages.about', [
            'blocks' => $this->aboutContent->activeBlocks(),
        ]);
    }

    public function research(): View
    {
        $projects = collect();

        if (Schema::hasTable('research_projects')) {
            $projects = ResearchProject::query()
                ->published()
                ->orderByRaw("case when status in ('upcoming','ongoing') then 0 else 1 end")
                ->orderByDesc('is_featured')
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->get();

            // A study posted before the colour picker still gets the page colour its cover needs.
            if (Schema::hasColumn('research_projects', 'bg_color')) {
                foreach ($projects as $project) {
                    $project->syncCoverTone();
                }
            }
        }

        $partners = collect();

        if (Schema::hasTable('research_partners')) {
            $partners = ResearchPartner::query()->active()->orderByRaw('name IS NULL, name')->get();

            // A partner posted before its logo was sampled still gets the card colour its logo needs.
            if (Schema::hasColumn('research_partners', 'bg_color')) {
                foreach ($partners as $partner) {
                    if ($partner->logo_path && $partner->getAttribute('bg_color') === null) {
                        $partner->syncLogoTone();
                    }
                }
            }
        }

        // Four resources to a page, so a long list stays the length the visitor can scan at a glance.
        $resources = Schema::hasTable('research_resources')
            ? ResearchResource::query()->active()->orderByDesc('year')->orderByDesc('id')->paginate(4)->withQueryString()
            : new LengthAwarePaginator(collect(), 0, 4);

        return view('pages.research', [
            'projects' => $projects,
            'partners' => $partners,
            'resources' => $resources,
            'featured' => $this->homepageService->getFeaturedResearch(),
        ]);
    }

    public function events(): View
    {
        $events = $this->homepageService->getPublicEvents();

        return view('pages.events', [
            'events' => $events,
            'countdownEvent' => $this->homepageService->getCountdownEvent(),
            'usingFallback' => ['events' => $events->isEmpty()],
        ]);
    }

    public function eventShow(Event $event): View|RedirectResponse
    {
        abort_unless($event->is_public, 404);

        if (preg_match('#^events/\d+$#', request()->path()) && $event->slug) {
            return redirect()->route('events.show', $event, 301);
        }

        return view('pages.events-show', [
            'event' => $this->homepageService->mapEventForPublic($event),
        ]);
    }

    public function blog(): View
    {
        $posts = $this->homepageService->getPublishedBlogPosts();

        return view('pages.blog.index', [
            'blogPosts' => $posts,
            'usingFallback' => ['blogPosts' => $posts->isEmpty()],
        ]);
    }

    public function blogShow(string $slug): View
    {
        $post = BlogPost::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->firstOrFail();

        if (Schema::hasColumn('blog_posts', 'view_count')) {
            BlogPost::query()->whereKey($post->id)->increment('view_count');
        }

        return view('pages.blog.show', [
            'post' => $this->homepageService->mapBlogPostForPublic($post),
        ]);
    }

    public function blogPdf(string $slug): StreamedResponse
    {
        $post = BlogPost::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->firstOrFail();

        $mapped = $this->homepageService->mapBlogPostForPublic($post);
        $filename = Str::slug($post->title ?: 'article').'.pdf';

        return $this->printDocuments->downloadPdf('pages.blog.pdf', [
            'post' => $mapped,
        ], $filename);
    }

    public function support(): View
    {
        return view('pages.support');
    }

    public function hefGuide(): View
    {
        return view('pages.hef-guide', [
            'steps' => $this->hefSteps(),
            'portals' => [
                ['label' => 'www.hef.co.ke', 'url' => 'https://www.hef.co.ke'],
                ['label' => 'www.universitiesfund.go.ke', 'url' => 'https://www.universitiesfund.go.ke'],
                ['label' => 'www.helb.co.ke', 'url' => 'https://www.helb.co.ke'],
            ],
        ]);
    }

    /**
     * Transcribed from the Higher Education Finance Application guide issued by the fund.
     * Keep the wording exactly as printed - officers re-issue the document and the page must match it.
     *
     * @return array<int, array{title: string, notes: array<int, string>, branches?: array<int, array{label: string, notes: array<int, string>}>}>
     */
    private function hefSteps(): array
    {
        return [
            [
                'title' => 'Visit www.hef.co.ke or www.universitiesfund.go.ke or www.helb.co.ke and click on "Student Portal"',
                'notes' => [],
            ],
            [
                'title' => 'Account Creation. Specify if you have a National ID Number below to proceed',
                'notes' => [
                    'Select whether you have a national ID or not.',
                    'If you are already registered, click on Login to access your account',
                    'Do you have a National ID Number?',
                    'If you don\'t have an account, click on register to create account',
                ],
            ],
            [
                'title' => 'Account Creation. Specify if you have an ID Number below to proceed',
                'notes' => [],
                'branches' => [
                    [
                        'label' => 'Yes',
                        'notes' => [
                            'Please enter your National ID if you have',
                            'ID Number - e.g 12345678. Enter ID number',
                            'First Name as per your ID - e.g Kevin',
                            'Type of ID Card: Kenyan Citizen (with Kenya ID card) / Foreign Citizen (with Alien ID card). Click on Kenyan Citizen',
                            'Validate. Click to validate your information',
                        ],
                    ],
                    [
                        'label' => 'No',
                        'notes' => [
                            'Please enter your KCSE Index number if you don\'t have a National ID',
                            'KCSE Exam Year. Select the year you sat for your KCSE exam',
                            'KCSE Index Number. Type your KCSE index number',
                            'KUCCPS Programme Code. Enter the Program code as indicated in your College/University admission letter',
                            'Validate. Click to validate your information',
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Complete Registration by Filling Form Below.',
                'notes' => [
                    'Shown as "ID Number Found!" when you registered with a National ID, or "KCSE Index Number Found!" when you registered with your KCSE index number.',
                    'All Fields Marked with Asterisks ( * ) are required',
                    'FullNames',
                    'Valid Email Address / Confirm Email Address',
                    'Enter Password / Confirm Password',
                    'Confirm you are human',
                    'Ensure that you can login to the email address entered here',
                    'Enter a valid email address and password',
                    'Already Have An Account? Login Here',
                ],
            ],
            [
                'title' => 'You have successfully created an account. Check your email (Inbox or Spam/ Junk) to activate your account',
                'notes' => [],
            ],
            [
                'title' => 'Log in after email activation',
                'notes' => [
                    'Your account has been Activated, Log in with your email and password.',
                    'Enter your email address',
                    'Enter your password',
                    'Click to log in',
                ],
            ],
            [
                'title' => 'Enter valid Telephone number where a verification code will be sent',
                'notes' => [
                    'If the verification code expires, click resend button to get a new verification code.',
                ],
            ],
            ['title' => 'Fill your profile & Update your Biodata', 'notes' => []],
            [
                'title' => 'Give consent to collection of your personal data',
                'notes' => [
                    'Read through the consent form to understand why we are collecting your personal data. Tick the checkbox to agree with terms of use',
                ],
            ],
            ['title' => 'Fill in your residence details. Then save to continue.', 'notes' => []],
            ['title' => 'Add your Secondary and Primary schools\' details.', 'notes' => []],
            ['title' => 'Add your University/ College details.', 'notes' => []],
            ['title' => 'Fill in your parents\' details', 'notes' => []],
            ['title' => 'Submit your scholarship/loan request', 'notes' => []],
            [
                'title' => 'LOAN APPLICATION FOR STUDENT WITH ID',
                'notes' => ['Consent to processing of your data.'],
            ],
            ['title' => 'Accept loan terms and conditions.', 'notes' => []],
            ['title' => 'Updated guarantor details.', 'notes' => []],
            ['title' => 'Provide loan payment information as per the fields provided (Bank or mobile payment)', 'notes' => []],
            [
                'title' => 'Submit your loan application',
                'notes' => ['Click to submit your application'],
            ],
        ];
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function privacy(): View
    {
        return $this->legalPage(CmsPage::SLUG_PRIVACY, 'Privacy Policy');
    }

    public function terms(): View
    {
        return $this->legalPage(CmsPage::SLUG_TERMS, 'Terms and Conditions');
    }

    private function legalPage(string $slug, string $fallbackTitle): View
    {
        $page = null;

        if (Schema::hasTable('cms_pages')) {
            $page = CmsPage::publishedBySlug($slug);
        }

        if (! $page) {
            $body = $slug === CmsPage::SLUG_PRIVACY
                ? KenyaDataProtectionNotice::htmlBody()
                : '<p>This page is being updated. Please check back shortly.</p>';

            $page = (object) [
                'slug' => $slug,
                'title' => $fallbackTitle,
                'body' => $body,
                'seo_meta_title' => $fallbackTitle,
                'seo_meta_description' => $slug === CmsPage::SLUG_PRIVACY
                    ? 'How TICH processes personal data under the Kenya Data Protection Act, 2019.'
                    : null,
                'updated_at' => $slug === CmsPage::SLUG_PRIVACY ? now() : null,
            ];
        }

        return view('pages.legal.show', [
            'page' => $page,
        ]);
    }
}
