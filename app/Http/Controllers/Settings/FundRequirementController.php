<?php

namespace App\Http\Controllers\Settings;

use App\Models\Settings\FundRequirement;
use App\Models\Settings\FundRequirementCompany;
use App\Models\Settings\Company;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FundRequirementController extends Controller
{
    public function index()
    {
        $this->authorize('fund-requirement-access');
        $fundRequirements = FundRequirement::with(['companies.company'])
            ->orderBy('order', 'asc')
            ->filter();

        return to_json([
            'collection' => $fundRequirements,
        ]);
    }

    public function create()
    {
        $this->authorize('fund-requirement-access');
        $companies = Company::selectRaw('id, concat(store_number, " - ", name) as name,workgroup_id')->whereHas('workgroup', function ($query) {
            $query->where('active', true);
        })->get();
        $item = [
            'label' => '',
            'type' => 'fixed',
            'condition_type' => 'monthly',
            'condition_value' => null,
            'amount' => null,
            'active' => true,
            'companies' => $companies->map(function ($company) {
                return [
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'workgroup_id' => $company->workgroup_id,
                    'amount' => null,
                ];
            }),
        ];

        return to_json([
            'form' => $item,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('fund-requirement-access');

        $request->validate([
            'label' => 'required|string|max:255',
            'type' => 'required|in:company wise,fixed',
            'condition_type' => 'required|in:monthly,weekly,bi-weekly',
            'condition_value' => 'required|date',
            'amount' => 'required_if:type,fixed|nullable|numeric|min:0',
            'active' => 'boolean',
            'companies' => 'required_if:type,company wise|array',
            'companies.*.company_id' => 'required_if:type,company wise|exists:company,id',
        ]);

        DB::beginTransaction();
        try {
            $data = $request->only(['label', 'type', 'condition_type', 'condition_value', 'amount']);
            $data['active'] = $request->active ?? true;

            if ($data['type'] === 'company wise') {
                $totalAmount = array_sum(array_column($request->companies, 'amount'));
                $data['amount'] = $totalAmount;
            }

            // Set the order to the highest order + 1
            $maxOrder = FundRequirement::max('order') ?? -1;
            $data['order'] = $maxOrder + 1;

            $fundRequirement = FundRequirement::create($data);

            $companiesData = [];
            if ($request->type === 'company wise' && $request->has('companies')) {
                foreach ($request->companies as $companyData) {
                    if($companyData['amount'] > 0) {
                       $companiesData[] = [
                            'fund_requirement_id' => $fundRequirement->id,
                            'company_id' => $companyData['company_id'],
                            'amount' => $companyData['amount']
                        ];
                    }
                }
            }

            FundRequirementCompany::insert($companiesData);

            DB::commit();

            return to_json([
                'saved' => true,
                'id' => $fundRequirement->id,
                'message' => 'Fund requirement created successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'Fund requirement creation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $this->authorize('fund-requirement-access');
        $item = FundRequirement::with(['companies.company', 'createdBy', 'updatedBy'])->findOrFail($id);

        if (!$item) {
            return to_json([
                'model' => null,
                'message' => 'Fund requirement not found',
            ], 404);
        }

        return to_json([
            'model' => $item
        ]);
    }

    public function edit($id)
    {
        $this->authorize('fund-requirement-access');
        $item = FundRequirement::findOrFail($id);
        $itemCompanies = FundRequirementCompany::where('fund_requirement_id', $id)->get()->keyBy('company_id');

        $companies = Company::selectRaw('id, concat(store_number, " - ", name) as name,workgroup_id')->whereHas('workgroup', function ($query) {
            $query->where('active', true);
        })->get();

        $item['companies'] = $companies->map(function ($company) use ($itemCompanies) {
            return [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'workgroup_id' => $company->workgroup_id,
                'amount' => $itemCompanies->has($company->id) ? $itemCompanies->get($company->id)->amount : null,
            ];
        });

        if (!$item) {
            return to_json([
                'form' => null,
                'message' => 'Fund requirement not found',
            ], 404);
        }

        return to_json([
            'form' => $item
        ]);
    }

    public function update($id, Request $request)
    {
        $this->authorize('fund-requirement-access');

        $request->validate([
            'label' => 'required|string|max:255',
            'type' => 'required|in:company wise,fixed',
            'condition_type' => 'required|in:monthly,weekly,bi-weekly',
            'condition_value' => 'required|date',
            'amount' => 'required_if:type,fixed|nullable|numeric|min:0',
            'active' => 'boolean',
            'companies' => 'required_if:type,company wise|array',
            'companies.*.company_id' => 'required_if:type,company wise|exists:company,id',
        ]);

        $item = FundRequirement::findOrFail($id);

        if (!$item) {
            return to_json([
                'saved' => false,
                'message' => 'Fund requirement not found',
            ], 404);
        }

        DB::beginTransaction();
        try {
            $data = $request->only(['label', 'type', 'condition_type', 'condition_value', 'amount']);
            $data['active'] = $request->active ?? true;

            if ($data['type'] === 'company wise') {
                $totalAmount = array_sum(array_column($request->companies, 'amount'));
                $data['amount'] = $totalAmount;
            }

            $item->update($data);

            FundRequirementCompany::where('fund_requirement_id', $id)->delete();

            $companiesData = [];
            if ($request->type === 'company wise' && $request->has('companies')) {
                foreach ($request->companies as $companyData) {
                    if($companyData['amount'] > 0) {
                        $companiesData[] = [
                            'fund_requirement_id' => $id,
                            'company_id' => $companyData['company_id'],
                            'amount' => $companyData['amount'],
                        ];
                    }
                }
            }
            
            FundRequirementCompany::insert($companiesData);
            DB::commit();

            return to_json([
                'saved' => true,
                'id' => $item->id,
                'message' => 'Fund requirement updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'Fund requirement update failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $this->authorize('fund-requirement-access');
        $item = FundRequirement::findOrFail($id);

        if (!$item) {
            return to_json([
                'deleted' => false,
                'message' => 'Fund requirement not found',
            ], 404);
        }

        DB::beginTransaction();
        try {
            $item->delete();
            DB::commit();

            return to_json([
                'deleted' => true,
                'message' => 'Fund requirement deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'deleted' => false,
                'message' => 'Fund requirement deletion failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function search()
    {
        $search = request('query');
        $column = request('column') ?? 'label';

        $fundRequirements = FundRequirement::where(function ($query) use ($column, $search) {
            if ($search) {
                return $query->where($column, 'like', '%' . $search . '%');
            }
            return $query;
        })
            ->select('id', 'label', 'type', 'condition_type', 'condition_value', 'amount')
            ->get();

        return to_json([
            'collection' => $fundRequirements
        ]);
    }

    public function getActiveFundRequirements()
    {
        $fundRequirements = FundRequirement::with(['companies.company'])
            ->active()
            ->orderBy('order', 'asc')
            ->get();

        return to_json([
            'collection' => $fundRequirements,
            'message' => 'Active fund requirements fetched successfully'
        ]);
    }

    public function reorder(Request $request)
    {
        $this->authorize('fund-requirement-access');

        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:fund_requirements,id',
            'items.*.order' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->items as $item) {
                FundRequirement::where('id', $item['id'])->update([
                    'order' => $item['order']
                ]);
            }

            DB::commit();

            return to_json([
                'saved' => true,
                'message' => 'Fund requirements reordered successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'Failed to reorder fund requirements: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateOrder(Request $request)
    {
        $this->authorize('fund-requirement-access');

        $request->validate([
            'order' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            // Get all fund requirements and create a map of label to id
            $fundRequirements = FundRequirement::all()->keyBy('label');
            
            // Fixed categories occupy positions 1, 2, 3 (food, adv, royalty)
            // So dynamic fund requirements start from position 4
            $startingOrder = 4;
            
            foreach ($request->order as $index => $label) {
                if ($fundRequirements->has($label)) {
                    $fundRequirement = $fundRequirements->get($label);
                    $fundRequirement->update([
                        'order' => $startingOrder + $index
                    ]);
                }
            }

            DB::commit();

            return to_json([
                'saved' => true,
                'message' => 'Fund requirements order updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'Failed to update order: ' . $e->getMessage(),
            ], 500);
        }
    }
}
