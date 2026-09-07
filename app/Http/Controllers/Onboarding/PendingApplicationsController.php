<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\Onboarding\OnboardingList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class PendingApplicationsController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('access', 'pending-applications.index');
        $pendingApplications = OnboardingList::with('employee', 'company')->whereDoesntHave('employee', function ($query) {
            $query->where('rejected', true);
        })->where('status', '!=', 'verified')->where('process_id', '!=', 10)->authorizedCompanies('company_id')->filter();
        return to_json([
            'collection' => $pendingApplications,
        ]);
    }

    public function reject(Request $request, $id)
    {
        $this->authorize('access', 'pending-applications.reject');
        
        DB::beginTransaction();
        try {
            $employeeList = OnboardingList::where('id', $id)->first();
            $employeeList->employee->update([
                'rejected' => true,
                'rejected_by' => Auth::id(),
                'rejected_at' => now(),
                'rejection_reason' => $request->reason,
            ]);
            DB::commit();

        }
        catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'message' => 'Failed to reject pending application',
                'error' => $e->getMessage(),
            ], 500);
        }


        return to_json([
            'message' => 'Pending application rejected successfully',
        ]);
    }

    public function moveToEmployee(Request $request, $id)
    {
        $this->authorize('access', 'pending-applications.index');

        $onboarding = OnboardingList::with('employee')
            ->authorizedCompanies('company_id')
            ->where('id', $id)
            ->first();

        if (! $onboarding || ! $onboarding->employee) {
            return to_json([
                'message' => 'Pending application not found',
            ], 404);
        }

        if ($onboarding->employee->rejected) {
            return to_json([
                'message' => 'Cannot move a rejected application to employee',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $onboarding->update([
                'status' => 'verified',
                'final_status' => 'approved',
            ]);

            $onboarding->employee->update([
                'employee_type' => 'Completed',
                'onboarding_status' => 'verified',
                'move_from_pending' => true,
            ]);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            return to_json([
                'message' => 'Failed to move application to employee',
                'error' => $e->getMessage(),
            ], 500);
        }

        return to_json([
            'message' => 'Employee updated successfully',
        ]);
    }
}