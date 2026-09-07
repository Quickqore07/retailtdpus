<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\BankCategoryRule;
use App\Models\Settings\BankCategoryRuleCondition;
use App\Services\BankCategoryRule as BankCategoryRuleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankCategoryRuleController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('access', 'bank-category-rule.index');
        $collection = BankCategoryRule::with(['createdBy', 'updatedBy'])->filter();
        return to_json([
            'collection' => $collection,
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'bank-category-rule.create');
        $form = [
            'name' => '',
            'label' => '',
            'condition' => 'All',
            'conditions' => [
                ['condition_type' => 'Contains', 'value' => ''],
            ],
        ];
        return to_json([
            'form' => $form,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'bank-category-rule.create');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'label' => 'required|string|max:255',
            'condition' => 'required|string|in:some,All',
            'conditions' => 'required|array|min:1',
            'conditions.*.condition_type' => 'required|string|max:255',
            'conditions.*.value' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $rule = BankCategoryRule::create([
                'name' => $validated['name'],
                'label' => $validated['label'],
                'condition' => $validated['condition'],
            ]);

            foreach ($validated['conditions'] as $c) {
                if (trim((string) $c['value']) === '') {
                    continue;
                }
                BankCategoryRuleCondition::create([
                    'bank_category_rule_id' => $rule->id,
                    'condition_type' => $c['condition_type'],
                    'value' => $c['value'],
                ]);
            }

            DB::commit();
            
            // Clear the cache after creating a rule
            BankCategoryRuleService::clearCache();
            
            return to_json([
                'saved' => true,
                'id' => $rule->id,
                'message' => 'Bank category rule created successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'Bank category rule creation failed',
            ], 500);
        }
    }

    public function show($id, Request $request)
    {
        $this->authorize('access', 'bank-category-rule.show');
        $model = BankCategoryRule::with(['conditions', 'createdBy', 'updatedBy'])->findOrFail($id);
        return to_json([
            'model' => $model,
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'bank-category-rule.update');
        $item = BankCategoryRule::with(['conditions'])->findOrFail($id);
        $form = [
            'name' => $item->name,
            'label' => $item->label,
            'condition' => $item->condition,
            'conditions' => $item->conditions->isEmpty()
                ? [['condition_type' => 'Contains', 'value' => '']]
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
        $this->authorize('access', 'bank-category-rule.update');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'label' => 'required|string|max:255',
            'condition' => 'required|string|in:some,All',
            'conditions' => 'required|array|min:1',
            'conditions.*.condition_type' => 'required|string|max:255',
            'conditions.*.value' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $rule = BankCategoryRule::findOrFail($id);
            $rule->update([
                'name' => $validated['name'],
                'label' => $validated['label'],
                'condition' => $validated['condition'],
            ]);

            BankCategoryRuleCondition::where('bank_category_rule_id', $rule->id)->delete();
            foreach ($validated['conditions'] as $c) {
                if (trim((string) $c['value']) === '') {
                    continue;
                }
                BankCategoryRuleCondition::create([
                    'bank_category_rule_id' => $rule->id,
                    'condition_type' => $c['condition_type'],
                    'value' => $c['value'],
                ]);
            }

            DB::commit();
            
            // Clear the cache after updating a rule
            BankCategoryRuleService::clearCache();
            
            return to_json([
                'saved' => true,
                'id' => $rule->id,
                'message' => 'Bank category rule updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'Bank category rule update failed',
            ], 500);
        }
    }

    public function destroy($id)
    {
        $this->authorize('access', 'bank-category-rule.delete');
        $rule = BankCategoryRule::findOrFail($id);
        $rule->delete();
        
        // Clear the cache after deleting a rule
        BankCategoryRuleService::clearCache();
        
        return to_json([
            'deleted' => true,
            'message' => 'Bank category rule deleted successfully',
        ]);
    }
}
