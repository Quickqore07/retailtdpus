<?php

namespace App\Http\Controllers\Upload;

use App\Http\Controllers\Controller;
use App\Models\Settings\Setting;
use App\Models\User;
use App\Services\DefaultSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ArInvoiceSettingsController extends Controller
{
    private const KEYS = [
        'invoice-prefix',
        'invoice-year-(0/1)',
        'invoice-starting-number',
        'invoice-length',
        'invoice-company-name',
        'invoice-company-address',
    ];

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

    /**
     * @return array<string, array<string, mixed>>
     */
    private function schemaByKey(): array
    {
        $map = [];
        foreach (DefaultSettings::schema() as $row) {
            $map[$row['key']] = $row;
        }

        return $map;
    }

    public function show()
    {
        $user = $this->getAuthenticatedUploadUser();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (! $this->ensurePermission($user, 'upload-portal-ar-settings.index')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view AR invoice settings',
            ], 403);
        }

        $schema = $this->schemaByKey();
        $db = Setting::whereIn('key', self::KEYS)->get()->keyBy('key');

        $get = function (string $key) use ($db, $schema) {
            if (isset($db[$key])) {
                return $db[$key]->value;
            }

            return $schema[$key]['default_value'] ?? null;
        };

        return response()->json([
            'success' => true,
            'settings' => [
                'invoice_prefix' => (string) ($get('invoice-prefix') ?? 'INV'),
                'invoice_year' => (int) ($get('invoice-year-(0/1)') ?? 1),
                'invoice_starting_number' => (int) ($get('invoice-starting-number') ?? 1),
                'invoice_length' => (int) ($get('invoice-length') ?? 5),
                'invoice_company_name' => (string) ($get('invoice-company-name') ?? 'Pie Investments LLC'),
                'invoice_company_address' => (string) ($get('invoice-company-address') ?? '8520 Tyco Rd, Ste A, Vienna, VA 22182'),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (! $this->ensurePermission($user, 'upload-portal-ar-settings.index')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update AR invoice settings',
            ], 403);
        }

        $validated = $request->validate([
            'invoice_prefix' => 'required|string|max:32',
            'invoice_year' => 'required|integer|in:0,1',
            'invoice_starting_number' => 'required|integer|min:1|max:1000000',
            'invoice_length' => 'required|integer|min:1|max:10',
            'invoice_company_name' => 'required|string|max:255',
            'invoice_company_address' => 'required|string|max:255',
        ]);

        $schema = $this->schemaByKey();

        $rows = [
            'invoice-prefix' => [
                'value' => $validated['invoice_prefix'],
                'value_type' => $schema['invoice-prefix']['value_type'] ?? 'text',
            ],
            'invoice-year-(0/1)' => [
                'value' => (string) $validated['invoice_year'],
                'value_type' => $schema['invoice-year-(0/1)']['value_type'] ?? 'number',
            ],
            'invoice-starting-number' => [
                'value' => (string) $validated['invoice_starting_number'],
                'value_type' => $schema['invoice-starting-number']['value_type'] ?? 'number',
            ],
            'invoice-length' => [
                'value' => (string) $validated['invoice_length'],
                'value_type' => $schema['invoice-length']['value_type'] ?? 'number',
            ],
            'invoice-company-name' => [
                'value' => $validated['invoice_company_name'],
                'value_type' => $schema['invoice-company-name']['value_type'] ?? 'text',
            ],
            'invoice-company-address' => [
                'value' => $validated['invoice_company_address'],
                'value_type' => $schema['invoice-company-address']['value_type'] ?? 'text',
            ],
        ];

        DB::beginTransaction();
        try {
            foreach ($rows as $key => $meta) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $meta['value'],
                        'value_type' => $meta['value_type'],
                        'active' => true,
                    ]
                );
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to save settings',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings saved',
        ]);
    }
}
