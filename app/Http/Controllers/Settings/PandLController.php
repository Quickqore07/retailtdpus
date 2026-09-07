<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\PandlConfiguration;
use App\Models\Settings\PandlConfigurationDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PandLController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('access', 'pandl.index');
        $collection = PandlConfiguration::with(['createdBy', 'updatedBy', 'details'])->filter();
        return to_json([
            'collection' => $collection,
        ]);
    }
    public function search(Request $request)
    {
        $this->authorize('access', 'pandl.index');
        $search = request('query');

        $collection = PandlConfiguration::when($search!==null, function ($query) use ($search) {
            $query->where('label', 'like', '%'.$search.'%');
        })->select('id', 'label')->get();
        return to_json([
            'collection' => $collection,
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'pandl.create');
        $form = [
            'label' => '',
            'income_items' => [
                ['ledgers' => [''], 'label' => ''],
            ],
            'cogs_items' => [
                ['ledgers' => [''], 'cogs_type' => null, 'label' => ''],
            ],
            'expense_items' => [
                ['ledgers' => [''], 'label' => ''],
            ],
        ];
        return to_json([
            'form' => $form,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'pandl.create');
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'income_items' => 'required|array|min:1',
            'income_items.*.ledgers' => 'required|array',
            'income_items.*.ledgers.*' => 'nullable|string',
            'income_items.*.label' => 'required|string|max:255',
            'cogs_items' => 'required|array|min:1',
            'cogs_items.*.ledgers' => 'required|array',
            'cogs_items.*.ledgers.*' => 'nullable|string',
            'cogs_items.*.cogs_type' => 'nullable|string|in:Food Purchase,Labor Cost,Franchise fee,Retail',
            'cogs_items.*.label' => 'required|string|max:255',
            'expense_items' => 'required|array|min:1',
            'expense_items.*.ledgers' => 'required|array',
            'expense_items.*.ledgers.*' => 'nullable|string',
            'expense_items.*.label' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        $user = Auth::user();
        try {
            $pandl = PandlConfiguration::create([
                'label' => $validated['label'],
                'created_by' => $user->id,
            ]);

            $this->saveDetails($pandl->id, $validated['income_items'], 'Income');
            $this->saveDetails($pandl->id, $validated['cogs_items'], 'COGS');
            $this->saveDetails($pandl->id, $validated['expense_items'], 'Expense');

            DB::commit();
            return to_json([
                'saved' => true,
                'id' => $pandl->id,
                'message' => 'P&L configuration created successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'P&L configuration creation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $this->authorize('access', 'pandl.show');
        $model = PandlConfiguration::with(['createdBy', 'updatedBy'])->findOrFail($id);
        
        $formattedDetails = [
            'income_items' => $model->details->where('type', 'Income')->map(function ($detail) {
                return [
                    'ledgers' => explode(',', $detail->ledgers),
                    'label' => $detail->label,
                ];
            })->values(),
            'cogs_items' => $model->details->where('type', 'COGS')->map(function ($detail) {
                return [
                    'ledgers' => explode(',', $detail->ledgers),
                    'cogs_type' => $detail->cogs_type,
                    'label' => $detail->label,
                ];
            })->values(),
            'expense_items' => $model->details->where('type', 'Expense')->map(function ($detail) {
                return [
                    'ledgers' => explode(',', $detail->ledgers),
                    'label' => $detail->label,
                ];
            })->values(),
        ];
        $model->ledgerDetails = (object)$formattedDetails;
        return to_json([
            'model' => $model
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'pandl.update');
        $item = PandlConfiguration::with('details')->findOrFail($id);
        
        $form = [
            'label' => $item->label,
            'income_items' => $item->details->where('type', 'Income')->map(function ($detail) {
                return [
                    'ledgers' => explode(',', $detail->ledgers),
                    'label' => $detail->label,
                ];
            })->values()->toArray(),
            'cogs_items' => $item->details->where('type', 'COGS')->map(function ($detail) {
                return [
                    'ledgers' => explode(',', $detail->ledgers),
                    'cogs_type' => $detail->cogs_type ? ['id' => $detail->cogs_type, 'name' => $detail->cogs_type] : null,
                    'label' => $detail->label,
                ];
            })->values()->toArray(),
            'expense_items' => $item->details->where('type', 'Expense')->map(function ($detail) {
                return [
                    'ledgers' => explode(',', $detail->ledgers),
                    'label' => $detail->label,
                ];
            })->values()->toArray(),
        ];

        if (empty($form['income_items'])) {
            $form['income_items'] = [['ledgers' => [''], 'label' => '']];
        }
        if (empty($form['cogs_items'])) {
            $form['cogs_items'] = [['ledgers' => [''], 'cogs_type' => null, 'label' => '']];
        }
        if (empty($form['expense_items'])) {
            $form['expense_items'] = [['ledgers' => [''], 'label' => '']];
        }

        return to_json([
            'form' => $form,
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('access', 'pandl.update');
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'income_items' => 'required|array|min:1',
            'income_items.*.ledgers' => 'required|array',
            'income_items.*.ledgers.*' => 'nullable|string',
            'income_items.*.label' => 'required|string|max:255',
            'cogs_items' => 'required|array|min:1',
            'cogs_items.*.ledgers' => 'required|array',
            'cogs_items.*.ledgers.*' => 'nullable|string',
            'cogs_items.*.cogs_type' => 'nullable|string|in:Food Purchase,Labor Cost,Franchise fee,Retail',
            'cogs_items.*.label' => 'required|string|max:255',
            'expense_items' => 'required|array|min:1',
            'expense_items.*.ledgers' => 'required|array',
            'expense_items.*.ledgers.*' => 'nullable|string',
            'expense_items.*.label' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        $user = Auth::user();
        try {
            $pandl = PandlConfiguration::findOrFail($id);
            $pandl->update([
                'label' => $validated['label'],
                'updated_by' => $user->id,
            ]);

            PandlConfigurationDetail::where('pandl_id', $pandl->id)->delete();

            $this->saveDetails($pandl->id, $validated['income_items'], 'Income');
            $this->saveDetails($pandl->id, $validated['cogs_items'], 'COGS');
            $this->saveDetails($pandl->id, $validated['expense_items'], 'Expense');

            DB::commit();
            return to_json([
                'saved' => true,
                'id' => $pandl->id,
                'message' => 'P&L configuration updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'P&L configuration update failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $this->authorize('access', 'pandl.delete');
        $pandl = PandlConfiguration::findOrFail($id);
        $pandl->delete();
        return to_json([
            'deleted' => true,
            'message' => 'P&L configuration deleted successfully',
        ]);
    }

    private function saveDetails($pandlId, $items, $type)
    {
        foreach ($items as $item) {
            $ledgers = array_filter($item['ledgers'], function($ledger) {
                return trim((string) $ledger) !== '';
            });
            
            if (empty($ledgers) || trim((string) $item['label']) === '') {
                continue;
            }

            $data = [
                'pandl_id' => $pandlId,
                'ledgers' => implode(',', $ledgers),
                'type' => $type,
                'label' => $item['label'],
            ];

            if ($type === 'COGS' && isset($item['cogs_type']) && !empty($item['cogs_type'])) {
                $data['cogs_type'] = $item['cogs_type'];
            }

            PandlConfigurationDetail::create($data);
        }
    }
}
