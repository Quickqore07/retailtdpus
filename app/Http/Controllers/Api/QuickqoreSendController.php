<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Settings\Workgroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class QuickqoreSendController extends Controller
{
    public function idealCosts(Request $request): JsonResponse
    {
        $expectedKey = (string) config('app.quickqore_security_key', '');
        if ($expectedKey === '') {
            return response()->json([
                'success' => false,
                'message' => 'Quickqore integration is not configured',
            ], 503);
        }

        $givenKey = (string) $request->header('X-Security-Key', '');
        if (!hash_equals($expectedKey, $givenKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $request->merge([
            'workgroup' => strtoupper(trim((string) $request->input('workgroup'))),
        ]);

        $validator = Validator::make($request->all(), [
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'workgroup' => ['required', 'string', Rule::in(['PA', 'DC'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $workgroup = Workgroup::query()
            ->where('name', $validated['workgroup'])
            ->first();

        if (!$workgroup) {
            return response()->json([
                'success' => false,
                'message' => "Workgroup {$validated['workgroup']} was not found",
            ], 404);
        }

        try {
            $rows = DB::table('ideal_cost_items as item')
                ->join('ideal_cost as cost', 'cost.id', '=', 'item.ideal_cost_id')
                ->join('company', 'company.id', '=', 'item.company_id')
                ->where('company.workgroup_id', $workgroup->id)
                ->whereBetween('cost.date', [$validated['start_date'], $validated['end_date']])
                ->orderBy('cost.date')
                ->orderBy('cost.id')
                ->orderBy('company.store_number')
                ->select([
                    'cost.id as ideal_cost_id',
                    'cost.date as entry_date',
                    'item.id as ideal_cost_item_id',
                    'item.company_id',
                    'company.store_number as company_code',
                    'company.name as company_name',
                    'item.ideal_cost',
                    'item.mileage',
                    'item.delivery',
                ])
                ->get();

            $data = $rows
                ->groupBy('ideal_cost_id')
                ->map(function ($items) use ($workgroup) {
                    $first = $items->first();

                    return [
                        'ideal_cost_id' => (int) $first->ideal_cost_id,
                        'entry_date' => $first->entry_date,
                        'workgroup_name' => $workgroup->name,
                        'total_ideal_cost' => round($items->sum(fn ($item) => (float) $item->ideal_cost), 2),
                        'total_mileage' => round($items->sum(fn ($item) => (float) $item->mileage), 2),
                        'total_delivery' => round($items->sum(fn ($item) => (float) $item->delivery), 2),
                        'items' => $items->map(fn ($item) => [
                            'ideal_cost_item_id' => (int) $item->ideal_cost_item_id,
                            'company_id' => (int) $item->company_id,
                            'company_code' => $item->company_code,
                            'company_name' => $item->company_name,
                            'ideal_cost' => round((float) $item->ideal_cost, 2),
                            'mileage' => round((float) $item->mileage, 2),
                            'delivery' => round((float) $item->delivery, 2),
                        ])->values()->all(),
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'filters' => [
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'workgroup' => $workgroup->name,
                ],
                'record_count' => $data->count(),
                'item_count' => $rows->count(),
                'data' => $data->all(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve ideal cost data',
            ], 500);
        }
    }
}
