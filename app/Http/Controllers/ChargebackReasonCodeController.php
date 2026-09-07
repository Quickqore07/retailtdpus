<?php

namespace App\Http\Controllers;

use App\Models\ChargeBack\Chargeback;
use App\Models\ChargeBack\ChargebackReasonCode;
use App\Services\ChargebackExportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChargebackReasonCodeController extends Controller
{
    public function index()
    {
        $this->authorize('access', 'charge-back-reason-code.index');

        return to_json([
            'collection' => ChargebackReasonCode::with(['createdBy', 'updatedBy'])->filter(),
        ]);
    }

    public function export(Request $request, ChargebackExportService $exportService)
    {
        $this->authorize('access', 'charge-back-reason-code.index');

        return $exportService->exportReasonCodes($request);
    }

    public function create()
    {
        $this->authorize('access', 'charge-back-reason-code.create');

        return to_json([
            'form' => $this->defaultForm(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'charge-back-reason-code.create');

        $validated = $request->validate($this->rules());

        $item = ChargebackReasonCode::create($validated);

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'Reason code created successfully',
        ]);
    }

    public function show($id)
    {
        $this->authorize('access', 'charge-back-reason-code.show');

        $item = ChargebackReasonCode::with(['createdBy', 'updatedBy'])->findOrFail($id);

        return to_json([
            'model' => $item,
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'charge-back-reason-code.update');

        $item = ChargebackReasonCode::findOrFail($id);

        return to_json([
            'form' => $item,
        ]);
    }

    public function update($id, Request $request)
    {
        $this->authorize('access', 'charge-back-reason-code.update');

        $validated = $request->validate($this->rules($id));

        $item = ChargebackReasonCode::findOrFail($id);
        $previousCode = $item->code;
        $item->fill($validated);
        $item->save();

        if ($previousCode !== $item->code) {
            Chargeback::query()
                ->where('reason_code', $previousCode)
                ->update(['reason_code' => $item->code]);
        }

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'Reason code updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $this->authorize('access', 'charge-back-reason-code.delete');

        $item = ChargebackReasonCode::findOrFail($id);

        if (Chargeback::where('reason_code', $item->code)->exists()) {
            return to_json([
                'deleted' => false,
                'message' => 'Reason code is used by chargebacks and cannot be deleted.',
            ], 422);
        }

        $item->delete();

        return to_json([
            'deleted' => true,
            'message' => 'Reason code deleted successfully',
        ]);
    }

    public function options()
    {
        $this->authorize('access', 'charge-back.index');

        return to_json([
            'reason_codes' => ChargebackReasonCode::query()
                ->orderBy('code')
                ->get()
                ->map(fn ($item) => [
                    'value' => $item->code,
                    'label' => "{$item->code} - {$item->description}",
                ])
                ->values(),
            'labels' => ChargebackReasonCode::labelsMap(),
        ]);
    }

    private function defaultForm(): array
    {
        return [
            'code' => '',
            'description' => '',
        ];
    }

    private function rules(?int $id = null): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('chargeback_reason_codes', 'code')->ignore($id),
            ],
            'description' => 'required|string|max:255',
        ];
    }
}
