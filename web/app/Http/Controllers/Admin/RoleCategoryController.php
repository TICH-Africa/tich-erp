<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoleCategory;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RoleCategoryController extends Controller
{
    public function __construct(protected AuditService $auditService) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_code' => ['required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_-]*$/', 'unique:role_categories,category_code'],
            'category_name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $category = RoleCategory::create([
            ...$validated,
            'display_order' => $validated['display_order'] ?? 0,
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

        return back()->with('status', 'Role category created successfully.');
    }

    public function update(Request $request, RoleCategory $roleCategory): RedirectResponse
    {
        $validated = $request->validate([
            'category_name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('is_active') === false && $roleCategory->rolesCount() > 0) {
            return back()->withInput()->withErrors([
                'category' => "Cannot deactivate \"{$roleCategory->category_name}\" — {$roleCategory->rolesCount()} role(s) still use it.",
            ]);
        }

        $old = $roleCategory->only(['category_name', 'description', 'display_order', 'is_active']);
        $roleCategory->update([
            ...$validated,
            'display_order' => $validated['display_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->auditService->log(
            'rbac.role_category.updated',
            'role_categories',
            $roleCategory->id,
            $old,
            $roleCategory->only(['category_name', 'description', 'display_order', 'is_active']),
            null,
            'success',
            $request->user()->id,
            $request
        );

        return back()->with('status', 'Role category updated successfully.');
    }

    public function destroy(Request $request, RoleCategory $roleCategory): RedirectResponse
    {
        if ($roleCategory->is_system) {
            return back()->withErrors(['category' => 'System role categories cannot be deleted.']);
        }

        $rolesCount = $roleCategory->rolesCount();

        if ($rolesCount > 0) {
            return back()->withErrors([
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

        return back()->with('status', 'Role category deleted successfully.');
    }
}
