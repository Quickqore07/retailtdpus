<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\DataEntry\WcEntry;
use App\Models\Settings\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WcEntryController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('access', 'wc-entry.index');
        $currentCompany = $request->session()->get('company');
        
        $collection = WcEntry::join('company', 'wc_entries.company_id', '=', 'company.id')
            ->authorizedCompanies('company_id')
            ->select('wc_entries.*')
            ->with('company', 'createdBy', 'updatedBy')
            ->filter();

        return to_json([
            'collection' => $collection,
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('access', 'wc-entry.create');

        $companyId = $request->session()->get('company');
        $company = Company::select('id', 'name', 'store_number')
            ->where('id', $companyId)
            ->selectRaw('CONCAT(store_number, " - ", name) as name')
            ->first();

        return to_json([
            'form' => [
                'year' => (int) now()->format('Y'),
                'eow' => now()->endOfWeek()->toDateString(),
                'company_id' => $companyId,
                'company' => $company,
                'driver_pay' => 0,
                'non_driver_pay' => 0,
                'total_pay' => 0,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'wc-entry.create');
        $validated = $this->validatePayload($request);

        DB::beginTransaction();
        try {
            $entry = WcEntry::create($validated);

            DB::commit();
            return to_json([
                'saved' => true,
                'id' => $entry->id,
                'message' => 'WC Entry created successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'WC Entry creation failed',
            ], 500);
        }
    }

    public function show($id)
    {
        $this->authorize('access', 'wc-entry.show');

        $model = WcEntry::with('company', 'createdBy', 'updatedBy')
            ->authorizedCompanies('company_id')
            ->findOrFail($id);

        return to_json([
            'model' => $model,
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'wc-entry.update');

        $form = WcEntry::with('company')
            ->authorizedCompanies('company_id')
            ->findOrFail($id);

        return to_json([
            'form' => $form,
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('access', 'wc-entry.update');
        $validated = $this->validatePayload($request);

        DB::beginTransaction();
        try {
            $entry = WcEntry::authorizedCompanies('company_id')->findOrFail($id);
            $entry->update($validated);

            DB::commit();
            return to_json([
                'saved' => true,
                'id' => $entry->id,
                'message' => 'WC Entry updated successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'WC Entry update failed',
            ], 500);
        }
    }

    public function destroy($id)
    {
        $this->authorize('access', 'wc-entry.delete');

        DB::beginTransaction();
        try {
            $entry = WcEntry::authorizedCompanies('company_id')->findOrFail($id);
            $entry->delete();

            DB::commit();
            return to_json([
                'deleted' => true,
                'id' => $id,
                'message' => 'WC Entry deleted successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return to_json([
                'deleted' => false,
                'message' => 'WC Entry deletion failed',
            ], 500);
        }
    }

    private function validatePayload(Request $request): array
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'eow' => ['required', 'date'],
            'company_id' => ['required', 'integer', 'exists:company,id'],
            'driver_pay' => ['required', 'numeric', 'min:0'],
            'non_driver_pay' => ['required', 'numeric', 'min:0'],
            'total_pay' => ['required', 'numeric', 'min:0'],
        ]);

        $validated['driver_pay'] = (float) $validated['driver_pay'];
        $validated['non_driver_pay'] = (float) $validated['non_driver_pay'];
        $validated['total_pay'] = round($validated['driver_pay'] + $validated['non_driver_pay'], 2);

        return $validated;
    }
}
