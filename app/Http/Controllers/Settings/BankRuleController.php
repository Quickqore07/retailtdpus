<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\BankRule;
use App\Models\Settings\BankRuleCondition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankRuleController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('access', 'bank-rule.index');
        $collection = BankRule::with(['createdBy', 'updatedBy','ledger.ledgerDetails'=>function($query) use ($request){
            $query->where('company_id', $request->session()->get('company'))->select('id', 'code','ledger_id');
        }])->filter();
        return to_json([
            'collection' => $collection,
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'bank-rule.create');
        $form = [
            'name' => '',
            'ledger_id' => null,
            'ledger' => null,
            'condition' => 'All',
            'conditions' => [
                ['condition_type' => 'Matches', 'value' => ''],
            ],
        ];
        return to_json([
            'form' => $form,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'bank-rule.create');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ledger_id' => 'nullable|integer|exists:ledgers,id',
            'condition' => 'required|string|in:some,All',
            'conditions' => 'required|array|min:1',
            'conditions.*.condition_type' => 'required|string|max:255',
            'conditions.*.value' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $rule = BankRule::create([
                'name' => $validated['name'],
                'ledger_id' => $validated['ledger_id'] ?? null,
                'condition' => $validated['condition'],
            ]);

            foreach ($validated['conditions'] as $c) {
                if (trim((string) $c['value']) === '') {
                    continue;
                }
                BankRuleCondition::create([
                    'bank_rule_id' => $rule->id,
                    'condition_type' => $c['condition_type'],
                    'value' => $c['value'],
                ]);
            }

            DB::commit();
            return to_json([
                'saved' => true,
                'id' => $rule->id,
                'message' => 'Bank rule created successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'Bank rule creation failed',
            ], 500);
        }
    }

    public function show($id, Request $request)
    {
        $this->authorize('access', 'bank-rule.show');
        $model = BankRule::with([ 'conditions', 'createdBy', 'updatedBy','ledger.ledgerDetails'=>function($query) use ($request){
            $query->where('company_id', $request->session()->get('company'))->select('id', 'code','ledger_id');
        }])->findOrFail($id);
        return to_json([
            'model' => $model,
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'bank-rule.update');
        $item = BankRule::with(['ledger', 'conditions'])->findOrFail($id);
        $form = [
            'name' => $item->name,
            'ledger_id' => $item->ledger_id,
            'ledger' => $item->ledger,
            'condition' => $item->condition,
            'conditions' => $item->conditions->isEmpty()
                ? [['condition_type' => 'Description', 'value' => '']]
                : $item->conditions->map(fn ($c) => [
                    'condition_type' => $c->condition_type,
                    'value' => $c->value,
                ])->toArray(),
        ];
        return to_json([
            'form' => $form,
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('access', 'bank-rule.update');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ledger_id' => 'nullable|integer|exists:ledgers,id',
            'condition' => 'required|string|in:some,All',
            'conditions' => 'required|array|min:1',
            'conditions.*.condition_type' => 'required|string|max:255',
            'conditions.*.value' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $rule = BankRule::findOrFail($id);
            $rule->update([
                'name' => $validated['name'],
                'ledger_id' => $validated['ledger_id'] ?? null,
                'condition' => $validated['condition'],
            ]);

            BankRuleCondition::where('bank_rule_id', $rule->id)->delete();
            foreach ($validated['conditions'] as $c) {
                if (trim((string) $c['value']) === '') {
                    continue;
                }
                BankRuleCondition::create([
                    'bank_rule_id' => $rule->id,
                    'condition_type' => $c['condition_type'],
                    'value' => $c['value'],
                ]);
            }

            DB::commit();
            return to_json([
                'saved' => true,
                'id' => $rule->id,
                'message' => 'Bank rule updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'Bank rule update failed',
            ], 500);
        }
    }

    public function destroy($id)
    {
        $this->authorize('access', 'bank-rule.delete');
        $rule = BankRule::findOrFail($id);
        $rule->delete();
        return to_json([
            'deleted' => true,
            'message' => 'Bank rule deleted successfully',
        ]);
    }
}
