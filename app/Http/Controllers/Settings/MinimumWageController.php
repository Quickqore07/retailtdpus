<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\MinimumWage;
use App\Models\Settings\MinimumWageItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MinimumWageController extends Controller
{
    public function index()
    {
        $this->authorize('access', 'minimum-wage.index');
        $minimumWages = MinimumWage::with(['state', 'county', 'items'])
            ->filter();

        return to_json([
            'collection' => $minimumWages,
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'minimum-wage.create');
        $item = [
            'state_id' => null,
            'state' => null,
            'county_id' => null,
            'county' => null,
            'items' => [
                [
                    'effective_date' => null,
                    'minimum_wage' => null,
                    'tipped_minimum_wage' => 0,
                ],
            ],
        ];

        return to_json([
            'form' => $item,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'minimum-wage.create');
        $validated = $this->validatePayload($request);

        if (MinimumWage::where('state_id', $validated['state_id'])->exists()) {
            return to_json([
                'saved' => false,
                'message' => 'Minimum wage already exists for this state',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $minimumWage = MinimumWage::create([
                'state_id' => $validated['state_id'],
                'county_id' => $validated['county_id'] ?? null,
            ]);

            $this->syncItems($minimumWage, $validated['items']);

            DB::commit();

            return to_json([
                'saved' => true,
                'id' => $minimumWage->id,
                'message' => 'Minimum wage created successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return to_json([
                'saved' => false,
                'message' => 'Minimum wage creation failed',
            ], 500);
        }
    }

    public function show($id)
    {
        $this->authorize('access', 'minimum-wage.show');
        $item = MinimumWage::with(['state', 'county', 'items'])->findOrFail($id);

        return to_json([
            'model' => $item,
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'minimum-wage.update');
        $item = MinimumWage::with(['state', 'county', 'items'])->findOrFail($id);

        return to_json([
            'form' => $item,
        ]);
    }

    public function update($id, Request $request)
    {
        $this->authorize('access', 'minimum-wage.update');
        $validated = $this->validatePayload($request, (int) $id);

        if (MinimumWage::where('state_id', $validated['state_id'])
            ->where('id', '!=', $id)
            ->exists()) {
            return to_json([
                'saved' => false,
                'message' => 'Minimum wage already exists for this state',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $item = MinimumWage::findOrFail($id);
            $item->update([
                'state_id' => $validated['state_id'],
                'county_id' => $validated['county_id'] ?? null,
            ]);

            MinimumWageItem::where('minimum_wage_id', $item->id)->delete();
            $this->syncItems($item, $validated['items']);

            DB::commit();

            return to_json([
                'saved' => true,
                'id' => $item->id,
                'message' => 'Minimum wage updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return to_json([
                'saved' => false,
                'message' => 'Minimum wage update failed',
            ], 500);
        }
    }

    public function destroy($id)
    {
        $this->authorize('access', 'minimum-wage.delete');
        try {
            $item = MinimumWage::findOrFail($id);
            $item->delete();

            return to_json([
                'deleted' => true,
                'id' => $item->id,
                'message' => 'Minimum wage deleted successfully',
            ]);
        } catch (\Exception $e) {
            return to_json([
                'deleted' => false,
                'message' => 'Minimum wage deletion failed',
            ], 500);
        }
    }

    private function validatePayload(Request $request, ?int $id = null): array
    {
        $stateUniqueRule = Rule::unique('minimum_wages', 'state_id');
        if ($id) {
            $stateUniqueRule->ignore($id);
        }

        return $request->validate([
            'state_id' => ['required', 'exists:state,id', $stateUniqueRule],
            'county_id' => 'nullable|exists:county,id',
            'items' => 'required|array|min:1',
            'items.*.effective_date' => 'required|date',
            'items.*.minimum_wage' => 'required|numeric|min:0',
            'items.*.tipped_minimum_wage' => 'required|numeric|min:0',
        ], [
            'state_id.unique' => 'Minimum wage already exists for this state',
        ]);
    }

    private function syncItems(MinimumWage $minimumWage, array $items): void
    {
        $now = now();
        $insertItems = collect($items)->map(function ($item) use ($minimumWage, $now) {
            return [
                'minimum_wage_id' => $minimumWage->id,
                'effective_date' => $item['effective_date'],
                'minimum_wage' => $item['minimum_wage'],
                'tipped_minimum_wage' => $item['tipped_minimum_wage'] ?? 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        MinimumWageItem::insert($insertItems);
    }
}
