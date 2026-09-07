<?php

namespace App\Http\Controllers;

use App\Models\ChargeBack\Chargeback;
use App\Models\ChargeBack\ChargebackEntryMode;
use App\Services\ChargebackExportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChargebackEntryModeController extends Controller
{
    public function index()
    {
        $this->authorize('access', 'charge-back-entry-mode.index');

        return to_json([
            'collection' => ChargebackEntryMode::with(['createdBy', 'updatedBy'])->filter(),
        ]);
    }

    public function export(Request $request, ChargebackExportService $exportService)
    {
        $this->authorize('access', 'charge-back-entry-mode.index');

        return $exportService->exportEntryModes($request);
    }

    public function create()
    {
        $this->authorize('access', 'charge-back-entry-mode.create');

        return to_json([
            'form' => $this->defaultForm(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'charge-back-entry-mode.create');

        $validated = $request->validate($this->rules());

        $item = ChargebackEntryMode::create($validated);

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'Entry mode created successfully',
        ]);
    }

    public function show($id)
    {
        $this->authorize('access', 'charge-back-entry-mode.show');

        $item = ChargebackEntryMode::with(['createdBy', 'updatedBy'])->findOrFail($id);

        return to_json([
            'model' => $item,
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'charge-back-entry-mode.update');

        $item = ChargebackEntryMode::findOrFail($id);

        return to_json([
            'form' => $item,
        ]);
    }

    public function update($id, Request $request)
    {
        $this->authorize('access', 'charge-back-entry-mode.update');

        $validated = $request->validate($this->rules($id));

        $item = ChargebackEntryMode::findOrFail($id);
        $previousName = $item->name;
        $item->fill($validated);
        $item->save();

        if ($previousName !== $item->name) {
            Chargeback::query()
                ->where('entry_mode', $previousName)
                ->update(['entry_mode' => $item->name]);
        }

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'Entry mode updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $this->authorize('access', 'charge-back-entry-mode.delete');

        $item = ChargebackEntryMode::findOrFail($id);

        if (Chargeback::where('entry_mode', $item->name)->exists()) {
            return to_json([
                'deleted' => false,
                'message' => 'Entry mode is used by chargebacks and cannot be deleted.',
            ], 422);
        }

        $item->delete();

        return to_json([
            'deleted' => true,
            'message' => 'Entry mode deleted successfully',
        ]);
    }

    public function options()
    {
        $this->authorize('access', 'charge-back.index');

        return to_json([
            'entry_modes' => ChargebackEntryMode::query()
                ->orderBy('name')
                ->get()
                ->map(fn ($item) => [
                    'value' => $item->name,
                    'label' => $item->name,
                ])
                ->values(),
        ]);
    }

    private function defaultForm(): array
    {
        return [
            'name' => '',
        ];
    }

    private function rules(?int $id = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('chargeback_entry_modes', 'name')->ignore($id),
            ],
        ];
    }
}
