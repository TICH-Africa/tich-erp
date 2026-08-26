<?php

namespace App\Http\Controllers\Ict\Content;

use App\Http\Controllers\Controller;
use App\Models\Portal\BlogPost;
use App\Services\AuditService;
use App\Services\StoredFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function __construct(
        protected StoredFileService $files,
        protected AuditService $audit,
    ) {}

    public function index(): View
    {
        $posts = BlogPost::query()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        return view('ict.content.blogs.index', [
            'posts' => $posts,
            'statuses' => ['draft', 'published', 'archived'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $staffId = $request->user()?->staff_id;

        $post = BlogPost::query()->create([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['title']),
            'featured_image_path' => $request->hasFile('featured_image')
                ? $this->files->replace(null, $request->file('featured_image'), 'blog', 'public', null, true)
                : null,
            'author_staff_id' => $staffId,
            'published_at' => ($validated['status'] ?? '') === 'published'
                ? ($validated['published_at'] ?? now())
                : null,
            'reading_time_minutes' => max(1, (int) ceil(str_word_count(strip_tags($validated['body'])) / 200)),
            'created_by' => $staffId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->audit->log('portal.blog.created', 'blog_posts', $post->id, null, $post->only(['title', 'slug', 'status']), null, 'success', $request->user()?->id, $request);

        return back()->with('status', 'Blog post created.');
    }

    public function update(Request $request, BlogPost $post): RedirectResponse
    {
        $validated = $this->validated($request, $post);
        $staffId = $request->user()?->staff_id;
        $old = $post->only(['title', 'status', 'featured_image_path']);

        $updates = [
            ...$validated,
            'updated_by' => $staffId,
            'updated_at' => now(),
        ];

        if (($validated['status'] ?? '') === 'published' && ! $post->published_at) {
            $updates['published_at'] = $validated['published_at'] ?? now();
        }
        if (($validated['status'] ?? '') !== 'published') {
            $updates['published_at'] = $validated['published_at'] ?? $post->published_at;
        }

        $updates['reading_time_minutes'] = max(1, (int) ceil(str_word_count(strip_tags($validated['body'])) / 200));

        if ($request->boolean('remove_image') && $post->featured_image_path) {
            $this->files->delete($post->featured_image_path, 'public');
            $updates['featured_image_path'] = null;
        } elseif ($request->hasFile('featured_image')) {
            $updates['featured_image_path'] = $this->files->replace(
                $post->featured_image_path,
                $request->file('featured_image'),
                'blog',
                'public',
                null,
                true,
            );
        }

        $post->update($updates);

        $this->audit->log('portal.blog.updated', 'blog_posts', $post->id, $old, $post->only(['title', 'status', 'featured_image_path']), null, 'success', $request->user()?->id, $request);

        return back()->with('status', 'Blog post updated.');
    }

    public function destroy(Request $request, BlogPost $post): RedirectResponse
    {
        if ($post->featured_image_path) {
            $this->files->delete($post->featured_image_path, 'public');
        }

        $id = $post->id;
        $title = $post->title;
        $post->delete();

        $this->audit->log('portal.blog.deleted', 'blog_posts', $id, ['title' => $title], null, null, 'success', $request->user()?->id, $request);

        return back()->with('status', 'Blog post deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?BlogPost $post = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:300'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:50000'],
            'status' => ['required', 'in:draft,published,archived'],
            'published_at' => ['nullable', 'date'],
            'featured_image' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
        ]);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'post';
        $slug = $base;
        $i = 2;

        while (BlogPost::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
