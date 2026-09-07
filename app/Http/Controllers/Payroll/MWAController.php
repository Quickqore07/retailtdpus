<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\MWA;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\ActivityLogService;
use App\Services\WeeklySummaryRecalculationService;

class MWAController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('access', 'mwa.index');

        $data = MWA::query()
            ->select('eow', DB::raw('COUNT(*) as total_entries'), DB::raw('SUM(amount) as total_amount'), DB::raw('MIN(created_at) as created_at'))
            ->authorizedCompanies('company_id')
            ->groupBy('eow')
            ->orderBy('eow', 'desc')->filter();

        return to_json([
            'collection' => $data,
        ]);
    }

    public function grouped(Request $request)
    {
        $this->authorize('access', 'mwa.index');

        $query = MWA::query()
            ->select('eow', DB::raw('COUNT(*) as total_entries'), DB::raw('SUM(amount) as total_amount'), DB::raw('MIN(created_at) as created_at'))
            ->authorizedCompanies('company_id')
            ->groupBy('eow')
            ->orderBy('eow', 'desc');

        // Apply filters
        $filters = $request->input('filters', []);
        
        foreach ($filters as $filter) {
            $field = $filter['field'] ?? null;
            $operator = $filter['operator'] ?? '=';
            $value = $filter['value'] ?? null;

            if (!$field || $value === null || $value === '') {
                continue;
            }

            switch ($field) {
                case 'eow':
                    if ($operator === '=') {
                        $query->where('eow', $value);
                    } elseif ($operator === 'between' && is_array($value) && count($value) === 2) {
                        $query->whereBetween('eow', $value);
                    }
                    break;
                case 'created_at':
                    if ($operator === 'between' && is_array($value) && count($value) === 2) {
                        $query->havingRaw('MIN(created_at) BETWEEN ? AND ?', $value);
                    }
                    break;
            }
        }

        // Apply sorting
        $sortBy = $request->input('sort_by', 'eow');
        $sortOrder = $request->input('sort_order', 'desc');
        
        if (in_array($sortBy, ['eow', 'total_entries', 'total_amount', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Get paginated results
        $perPage = $request->input('per_page', 15);
        $results = $query->paginate($perPage);

        return to_json([
            'data' => $results->items(),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ],
        ]);
    }

    public function entries(Request $request)
    {
        $this->authorize('access', 'mwa.index');

        $eow = $request->input('eow');

        if (!$eow) {
            return to_json([
                'message' => 'EOW is required',
                'data' => [],
            ], 422);
        }

        $mwaEntries = MWA::where('eow', $eow)
            ->with(['employee:id,pos_name,employee_id', 'company:id,name,store_number', 'role:id,name'])
            ->authorizedCompanies('company_id')
            ->orderBy('created_at', 'desc')
            ->get();

        $total_entries = $mwaEntries->count();
        $total_amount = $mwaEntries->sum('amount');

        return to_json([
            'data' => $mwaEntries,
            'total_entries' => $total_entries,
            'total_amount' => $total_amount,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'mwa.create');

        try {
            $validated = $request->validate([
                'employee_id' => 'required|exists:employee,id',
                'company_id' => 'required|exists:company,id',
                'role_id' => 'required|exists:employee_roles,id',
                'eow' => 'required|date',
                'amount' => 'required|numeric|min:0',
            ]);

            DB::beginTransaction();

            // Check for duplicate entry
            $exists = MWA::where('employee_id', $validated['employee_id'])
                ->where('company_id', $validated['company_id'])
                ->where('role_id', $validated['role_id'])
                ->where('eow', $validated['eow'])
                ->exists();

            if ($exists) {
                return to_json([
                    'message' => 'MWA entry already exists for this combination of Employee, Company, Role, and EOW',
                    'saved' => false,
                ], 422);
            }

            $mwa = MWA::create($validated);

            ActivityLogService::logCreate('mwa', $mwa->id,  $mwa->toArray(), "MWA entry created for employee: {$mwa->employee->pos_name}");

            // Recalculate weekly summary
            WeeklySummaryRecalculationService::recalculateForEmployee([
                'start_date' => $validated['eow'],
                'end_date' => $validated['eow'],
                'mwa_data' => [
                    [
                        'employee_id' => $validated['employee_id'],
                        'company_id' => $validated['company_id'],
                        'role_id' => $validated['role_id'],
                        'eow' => $validated['eow'],
                        'amount' => $validated['amount'],
                    ]
                ]
            ]);

            DB::commit();

            return to_json([
                'saved' => true,
                'message' => 'MWA entry created successfully',
                'data' => $mwa->load(['employee:id,pos_name,employee_id', 'company:id,name,store_number', 'role:id,name']),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return to_json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
                'saved' => false,
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('MWA Store Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return to_json(['message' => $e->getMessage(), 'saved' => false], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $this->authorize('access', 'mwa.update');

        try {
            $validated = $request->validate([
                'employee_id' => 'required|exists:employee,id',
                'company_id' => 'required|exists:company,id',
                'role_id' => 'required|exists:employee_roles,id',
                'eow' => 'required|date',
                'amount' => 'required|numeric|min:0',
            ]);

            DB::beginTransaction();

            $mwa = MWA::findOrFail($id);

            // Check for duplicate entry (excluding current record)
            $exists = MWA::where('employee_id', $validated['employee_id'])
                ->where('company_id', $validated['company_id'])
                ->where('role_id', $validated['role_id'])
                ->where('eow', $validated['eow'])
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return to_json([
                    'message' => 'MWA entry already exists for this combination of Employee, Company, Role, and EOW',
                    'saved' => false,
                ], 422);
            }

            $oldData = $mwa->toArray();
            $mwa->update($validated);

            ActivityLogService::logUpdate('mwa', $mwa->id, $oldData, $mwa->toArray(), "MWA entry updated for employee: {$mwa->employee->pos_name}");

            // Recalculate weekly summary for both old and new dates if EOW changed
            $dates = [$validated['eow']];
            if ($oldData['eow'] != $validated['eow']) {
                $dates[] = $oldData['eow'];
            }

            WeeklySummaryRecalculationService::recalculateForEmployee([
                'start_date' => min($dates),
                'end_date' => max($dates),
                'mwa_data' => [
                    [
                        'employee_id' => $validated['employee_id'],
                        'company_id' => $validated['company_id'],
                        'role_id' => $validated['role_id'],
                        'eow' => $validated['eow'],
                        'amount' => $validated['amount'],
                    ]
                ]
            ]);

            DB::commit();

            return to_json([
                'saved' => true,
                'message' => 'MWA entry updated successfully',
                'data' => $mwa->load(['employee:id,pos_name,employee_id', 'company:id,name,store_number', 'role:id,name']),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return to_json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
                'saved' => false,
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('MWA Update Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return to_json(['message' => $e->getMessage(), 'saved' => false], 500);
        }
    }

    public function destroy($id)
    {
        $this->authorize('access', 'mwa.delete');

        try {
            DB::beginTransaction();

            $mwa = MWA::findOrFail($id);
            $mwaData = $mwa->toArray();
            
            $mwa->delete();

            ActivityLogService::logDelete('mwa', $id, $mwaData, "MWA entry deleted for employee: {$mwaData['employee_id']}");

            // Recalculate weekly summary
            WeeklySummaryRecalculationService::recalculateForEmployee([
                'start_date' => $mwaData['eow'],
                'end_date' => $mwaData['eow'],
                'mwa_data' => []
            ]);

            DB::commit();

            return to_json([
                'deleted' => true,
                'message' => 'MWA entry deleted successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('MWA Delete Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return to_json(['message' => $e->getMessage(), 'deleted' => false], 500);
        }
    }

    public function getEmployees(Request $request)
    {
        $this->authorize('access', 'mwa.index');

        $employees = Employee::select('id', 'pos_name', 'employee_id')
            ->where('active', true)
            ->authorizedCompanies('company_id')
            ->orderBy('pos_name')
            ->get();

        return to_json([
            'data' => $employees,
        ]);
    }

    public function getCompaniesByEmployee(Request $request, $employeeId)
    {
        $this->authorize('access', 'mwa.index');

        $companies = DB::table('employee_rates')
            ->join('company', 'employee_rates.company_id', '=', 'company.id')
            ->where('employee_rates.employee_id', $employeeId)
            ->whereIn('employee_rates.company_id', authorizedCompanies())
            ->select('company.id', 'company.name', 'company.store_number')
            ->selectRaw('CONCAT(company.store_number, " - ", company.name) as display_name')
            ->distinct()
            ->orderBy('company.store_number')
            ->get();

        return to_json([
            'data' => $companies,
        ]);
    }

    public function getRolesByEmployeeCompany(Request $request, $employeeId, $companyId)
    {
        $this->authorize('access', 'mwa.index');

        $roles = DB::table('employee_rates')
            ->join('employee_roles', 'employee_rates.role_id', '=', 'employee_roles.id')
            ->where('employee_rates.employee_id', $employeeId)
            ->where('employee_rates.company_id', $companyId)
            ->select('employee_roles.id', 'employee_roles.name', 'employee_roles.code')
            ->distinct()
            ->orderBy('employee_roles.name')
            ->get();

        return to_json([
            'data' => $roles,
        ]);
    }
}
