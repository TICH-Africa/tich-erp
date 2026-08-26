<?php

namespace App\Http\Controllers\Ict\Content;

use App\Http\Controllers\Controller;
use App\Models\Portal\AboutContentBlock;
use App\Services\AboutContentService;
use App\Services\AuditService;
use App\Services\StoredFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AboutController extends Controller
{
    public function __construct(
        protected AboutContentService $about,
        protected StoredFileService $files,
        protected AuditService $audit,
    ) {}

    public function index(): View
    {
        return view('ict.content.about.index', [
            'blocks' => $this->about->allBlocksForAdmin(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedPayload($request);
        $staffId = $request->user()?->staff_id;

        $nextOrder = (int) AboutContentBlock::query()->max('display_order') + 1;

        $block = AboutContentBlock::query()->create([
            'block_key' => $this->uniqueBlockKey($validated['title']),
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'body' => $validated['body'],
            'display_order' => $nextOrder,
            'is_active' => $request->boolean('is_active'),
            'featured_image_path' => $request->hasFile('image')
                ? $this->files->replace(null, $request->file('image'), 'about', 'public', null, true)
                : null,
            'created_by' => $staffId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->audit->log(
            'portal.about.created',
            'about_content_blocks',
            $block->id,
            null,
            $block->only(['title', 'block_key', 'is_active']),
            null,
            'success',
            $request->user()?->id,
            $request
        );

        return back()->with('status', 'About section added.');
    }

    public function update(Request $request, AboutContentBlock $block): RedirectResponse
    {
        $validated = $this->validatedPayload($request);
        $staffId = $request->user()?->staff_id;
        $old = $block->only(['title', 'body', 'featured_image_path', 'is_active']);

        $updates = [
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'body' => $validated['body'],
            'is_active' => $request->boolean('is_active'),
            'updated_by' => $staffId,
            'updated_at' => now(),
        ];

        if ($request->boolean('remove_image') && $block->featured_image_path) {
            $this->files->delete($block->featured_image_path, 'public');
            $updates['featured_image_path'] = null;
        } elseif ($request->hasFile('image')) {
            $updates['featured_image_path'] = $this->files->replace(
                $block->featured_image_path,
                $request->file('image'),
                'about',
                'public',
                null,
                true,
            );
        }

        $block->update($updates);

        $this->audit->log(
            'portal.about.updated',
            'about_content_blocks',
            $block->id,
            $old,
            $block->only(['title', 'body', 'featured_image_path', 'is_active']),
            null,
            'success',
            $request->user()?->id,
            $request
        );

        return back()->with('status', $block->title.' section updated.');
    }

    public function destroy(Request $request, AboutContentBlock $block): RedirectResponse
    {
        if ($block->featured_image_path) {
            $this->files->delete($block->featured_image_path, 'public');
        }

        $id = $block->id;
        $title = $block->title;
        $block->delete();

        $this->audit->log(
            'portal.about.deleted',
            'about_content_blocks',
            $id,
            ['title' => $title],
            null,
            null,
            'success',
            $request->user()?->id,
            $request
        );

        return back()->with('status', 'About section deleted.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer', 'exists:about_content_blocks,id'],
        ]);

        $ids = collect($validated['order'])->unique()->values();

        if ($ids->count() !== AboutContentBlock::query()->count()) {
            return response()->json(['message' => 'Invalid section order.'], 422);
        }

        DB::transaction(function () use ($ids) {
            foreach ($ids as $index => $id) {
                AboutContentBlock::query()->whereKey($id)->update([
                    'display_order' => $index + 1,
                    'updated_at' => now(),
                ]);
            }
        });

        return response()->json(['status' => 'ok']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:300'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:20000'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,gif,webp'],
            'remove_image' => ['nullable', 'boolean'],
        ]);
    }

    private function uniqueBlockKey(string $title): string
    {
        $base = Str::slug($title) ?: 'section';
        $key = $base;
        $i = 2;

        while (AboutContentBlock::query()->where('block_key', $key)->exists()) {
            $key = $base.'-'.$i;
            $i++;
        }

        return Str::limit($key, 100, '');
    }
}
