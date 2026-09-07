<?php

namespace App\Http\Controllers\Upload;

use App\Http\Controllers\Controller;
use App\Models\AR\ArEmailTemplate;
use App\Models\User;
use App\Services\AR\ArEmailTemplatePlaceholders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ArEmailTemplateController extends Controller
{
    private function getAuthenticatedUploadUser(): ?User
    {
        $user = Auth::guard('upload-portal')->user();
        if (! $user) {
            return null;
        }

        return User::with('role')->find($user->id);
    }

    private function ensurePermission(?User $user, string $permission): bool
    {
        if (! $user) {
            return false;
        }

        return Gate::forUser($user)->allows('sp-access', $permission)
            || Gate::forUser($user)->allows('access', $permission);
    }

    public function index(Request $request)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (! $this->ensurePermission($user, 'upload-portal-ar-email-template.index')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view email templates',
            ], 403);
        }

        $validated = $request->validate([
            'template_type' => 'sometimes|nullable|in:invoice,statement',
            'subject' => 'sometimes|nullable|string|max:500',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $query = ArEmailTemplate::query()
            ->orderBy('template_type')
            ->orderByDesc('is_default')
            ->orderBy('name');

        if (! empty($validated['template_type'] ?? null)) {
            $query->where('template_type', $validated['template_type']);
        }

        $subjectFilter = trim((string) ($validated['subject'] ?? ''));
        if ($subjectFilter !== '') {
            $term = addcslashes($subjectFilter, '%_\\');
            $query->where('subject', 'like', '%'.$term.'%');
        }

        $perPage = (int) ($validated['per_page'] ?? 25);
        $perPage = min(max($perPage, 1), 100);

        $templates = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'templates' => $templates,
            'placeholders' => [
                'invoice' => ArEmailTemplatePlaceholders::definitionsForTemplateType('invoice'),
                'statement' => ArEmailTemplatePlaceholders::definitionsForTemplateType('statement'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (! $this->ensurePermission($user, 'upload-portal-ar-email-template.add')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create email templates',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'template_type' => 'required|in:invoice,statement',
            'is_default' => 'sometimes|boolean',
            'subject' => 'nullable|string|max:500',
            'body' => 'nullable|string|max:65535',
        ]);

        $template = DB::transaction(function () use ($validated) {
            $isDefault = (bool) ($validated['is_default'] ?? false);

            if ($isDefault) {
                ArEmailTemplate::query()
                    ->where('template_type', $validated['template_type'])
                    ->update(['is_default' => false]);
            }

            return ArEmailTemplate::query()->create([
                'name' => $validated['name'],
                'template_type' => $validated['template_type'],
                'is_default' => $isDefault,
                'subject' => $validated['subject'] ?? null,
                'body' => $validated['body'] ?? null,
            ]);
        });

        return response()->json([
            'success' => true,
            'template' => $template,
            'message' => 'Email template created',
        ]);
    }

    public function update(Request $request, int $id)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (! $this->ensurePermission($user, 'upload-portal-ar-email-template.edit')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit email templates',
            ], 403);
        }

        $template = ArEmailTemplate::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'template_type' => 'required|in:invoice,statement',
            'is_default' => 'sometimes|boolean',
            'subject' => 'nullable|string|max:500',
            'body' => 'nullable|string|max:65535',
        ]);

        DB::transaction(function () use ($template, $validated) {
            $isDefault = (bool) ($validated['is_default'] ?? false);

            if ($isDefault) {
                ArEmailTemplate::query()
                    ->where('template_type', $validated['template_type'])
                    ->where('id', '!=', $template->id)
                    ->update(['is_default' => false]);
            }

            $template->update([
                'name' => $validated['name'],
                'template_type' => $validated['template_type'],
                'is_default' => $isDefault,
                'subject' => $validated['subject'] ?? null,
                'body' => $validated['body'] ?? null,
            ]);
        });

        $template->refresh();

        return response()->json([
            'success' => true,
            'template' => $template,
            'message' => 'Email template updated',
        ]);
    }

    public function destroy(int $id)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (! $this->ensurePermission($user, 'upload-portal-ar-email-template.delete')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete email templates',
            ], 403);
        }

        $template = ArEmailTemplate::query()->findOrFail($id);
        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Email template deleted',
        ]);
    }
}
