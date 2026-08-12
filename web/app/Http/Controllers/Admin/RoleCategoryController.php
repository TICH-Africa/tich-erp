<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ServesAccessManagementPages;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\RoleCategory;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RoleCategoryController extends Controller
{
    use ServesAccessManagementPages;

    public function __construct(protected AuditService $auditService) {}

    public function index(): View
    {
        $roleCategories = RoleCategory::query()
            ->orderBy('display_order')
            ->orderBy('category_name')
            ->get();

        $rolesPerCategory = Role::query()
            ->select('role_category', DB::raw('count(*) as roles_count'))
            ->groupBy('role_category')
            ->pluck('roles_count', 'role_category');

        $rolesCount = Role::query()->count();
        $categoriesCount = RoleCategory::query()->count();

        return view($this->accessContext()->prefix.'.role-categories.index', compact('roleCategories', 'rolesPerCategory', 'rolesCount', 'categoriesCount') + [
            'access' => $this->accessContext(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_code' => ['required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_-]*$/', 'unique:role_categories,category_code'],
            'category_name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $nextOrder = (int) RoleCategory::query()->max('display_order') + 1;

        $category = RoleCategory::create([
            ...$validated,
            'display_order' => $nextOrder,
            'is_system' => false,
            'is_active' => true,
        ]);

        $this->auditService->log(
            'rbac.role_category.created',
            'role_categories',
            $category->id,
            null,
            $category->only(['category_code', 'category_name', 'description', 'display_order']),
            null,
            'success',
            $request->user()->id,
            $request
        );

        return redirect()
            ->route('admin.role-categories.index')
            ->with('status', 'Role category created successfully.');
    }

    public function update(Request $request, RoleCategory $roleCategory): RedirectResponse
    {
        $validated = $request->validate([
            'category_name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('is_active') === false && $roleCategory->rolesCount() > 0) {
            return redirect()
                ->route('admin.role-categories.index')
                ->withInput()
                ->withErrors([
                    'category' => "Cannot deactivate \"{$roleCategory->category_name}\" — {$roleCategory->rolesCount()} role(s) still use it.",
                ]);
        }

        $old = $roleCategory->only(['category_name', 'description', 'is_active']);
        $roleCategory->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->auditService->log(
            'rbac.role_category.updated',
            'role_categories',
            $roleCategory->id,
            $old,
            $roleCategory->only(['category_name', 'description', 'is_active']),
            null,
            'success',
            $request->user()->id,
            $request
        );

        return redirect()
            ->route('admin.role-categories.index')
            ->with('status', 'Role category updated successfully.');
    }

    public function destroy(Request $request, RoleCategory $roleCategory): RedirectResponse
    {
        if ($roleCategory->is_system) {
            return redirect()
                ->route('admin.role-categories.index')
                ->withErrors(['category' => 'System role categories cannot be deleted.']);
        }

        $rolesCount = $roleCategory->rolesCount();

        if ($rolesCount > 0) {
            return redirect()
                ->route('admin.role-categories.index')
                ->withErrors([
                    'category' => "Cannot delete \"{$roleCategory->category_name}\" — {$rolesCount} role(s) still use it. Reassign those roles first.",
                ]);
        }

        $snapshot = $roleCategory->only(['category_code', 'category_name']);
        $categoryId = $roleCategory->id;
        $roleCategory->delete();

        $this->auditService->log(
            'rbac.role_category.deleted',
            'role_categories',
            $categoryId,
            $snapshot,
            null,
            null,
            'success',
            $request->user()->id,
            $request
        );

        return redirect()
            ->route('admin.role-categories.index')
            ->with('status', 'Role category deleted successfully.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer', 'exists:role_categories,id'],
        ]);

        $ids = collect($validated['order'])->unique()->values();

        if ($ids->count() !== RoleCategory::query()->count()) {
            return response()->json(['message' => 'Invalid category order.'], 422);
        }

        DB::transaction(function () use ($ids) {
            foreach ($ids as $index => $id) {
                RoleCategory::query()
                    ->whereKey($id)
                    ->update(['display_order' => $index + 1]);
            }
        });

        $this->auditService->log(
            'rbac.role_category.reordered',
            'role_categories',
            null,
            null,
            ['order' => $ids->all()],
            null,
            'success',
            $request->user()->id,
            $request
        );

        return response()->json(['status' => 'ok']);
    }
}
