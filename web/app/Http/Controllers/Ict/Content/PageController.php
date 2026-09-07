<?php

namespace App\Http\Controllers\Ict\Content;

use App\Http\Controllers\Controller;
use App\Models\Portal\CmsPage;
use App\Services\AuditService;
use App\Services\StoredFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(
        protected StoredFileService $files,
        protected AuditService $audit,
    ) {}

    public function index(): View
    {
        $pages = CmsPage::query()
            ->whereIn('slug', CmsPage::managedSlugs())
            ->orderByRaw("CASE slug WHEN 'privacy' THEN 1 WHEN 'terms' THEN 2 ELSE 99 END")
            ->get();

        return view('ict.content.pages.index', [
            'pages' => $pages,
        ]);
    }

    public function edit(CmsPage $page): View
    {
        abort_unless(in_array($page->slug, CmsPage::managedSlugs(), true), 404);

        return view('ict.content.pages.edit', [
            'page' => $page,
            'statuses' => ['draft', 'published', 'archived'],
        ]);
    }

    public function update(Request $request, CmsPage $page): RedirectResponse
    {
        abort_unless(in_array($page->slug, CmsPage::managedSlugs(), true), 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:300'],
            'body' => ['required', 'string', 'max:500000'],
            'status' => ['required', 'in:draft,published,archived'],
            'seo_meta_title' => ['nullable', 'string', 'max:300'],
            'seo_meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['body'] = $this->sanitizeBody($validated['body']);
        $staffId = $request->user()?->staff_id;
        $old = $page->only(['title', 'status']);

        $updates = [
            ...$validated,
            'updated_by' => $staffId,
            'updated_at' => now(),
        ];

        if (($validated['status'] ?? '') === 'published') {
            $updates['published_at'] = $page->published_at ?? now();
        }

        $page->update($updates);

        $this->audit->log(
            'portal.cms_page.updated',
            'cms_pages',
            $page->id,
            $old,
            $page->only(['title', 'status']),
            null,
            'success',
            $request->user()?->id,
            $request,
        );

        return redirect()
            ->route('ict.content.pages.edit', $page)
            ->with('status', 'Page updated.');
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
        ]);

        $path = $this->files->store($request->file('image'), 'cms-pages/inline', 'public');

        return response()->json([
            'url' => $this->files->url($path, 'public'),
            'path' => $path,
        ]);
    }

    private function sanitizeBody(string $html): string
    {
        $html = preg_replace('#<(script|iframe|object|embed|form|link|meta|style)[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $html = preg_replace('#<(script|iframe|object|embed|form|link|meta)\b[^>]*/?>#is', '', $html) ?? $html;
        $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;
        $html = preg_replace('/javascript:/i', '', $html) ?? $html;

        return $html;
    }
}
