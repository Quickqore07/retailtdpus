<?php

namespace App\Models;

use App\Support\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use Filterable {
        scopeFilter as filterableScopeFilter;
    }

    protected $table = 'activity_logs';

    protected $defaultSortColumn = 'created_at';
    protected $defaultSortDirection = 'desc';

    protected $sortable = [
        'action',
        'auditable_type',
        'auditable_id',
        'index_value',
        'user_id',
        'created_at',
    ];

    protected $searchable = [
        'description',
        'auditable_type',
        'action',
        'index_value',
        'ip_address',
        'old_values',
        'new_values',
    ];

    protected $searchableColumns = [
        'description',
        'auditable_type',
        'action',
        'index_value',
        'ip_address',
        'old_values',
        'new_values',
        'user.name',
        'user.username',
    ];

    protected $allowedFilters = [
        'action',
        'auditable_type',
        'auditable_id',
        'index_value',
        'user_id',
        'description',
        'ip_address',
        'created_at',
        'old_values',
        'new_values',
        'json_field',
        'json_value',
        'json_field_value',
    ];

    protected $fillable = [
        'action',
        'user_id',
        'auditable_type',
        'auditable_id',
        'index_value',
        'old_values',
        'new_values',
        'description',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_APPROVE = 'approve';
    public const ACTION_BULK_IMPORT = 'bulk_import';
    public const ACTION_PRINTING = 'printing';
    public const ACTION_REVIEW = 'review';

    public static function actions(): array
    {
        return [
            self::ACTION_CREATE,
            self::ACTION_UPDATE,
            self::ACTION_DELETE,
            self::ACTION_APPROVE,
            self::ACTION_BULK_IMPORT,
            self::ACTION_PRINTING,
            self::ACTION_REVIEW,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeFilter($query, $cols = null, $options = [])
    {
        $filters = request('f', []);
        $virtualColumns = ['json_field', 'json_value', 'json_field_value'];
        $remaining = [];

        foreach ($filters as $filter) {
            $column = $filter['column'] ?? '';

            if (! in_array($column, $virtualColumns, true)) {
                $remaining[] = $filter;
                continue;
            }

            match ($column) {
                'json_field' => $this->applyJsonFieldFilter($query, $filter, request('filter_match', 'and')),
                'json_value' => $this->applyJsonValueFilter($query, $filter, request('filter_match', 'and')),
                'json_field_value' => $this->applyJsonFieldValueFilter($query, $filter, request('filter_match', 'and')),
            };
        }

        if ($filters !== $remaining) {
            if (empty($remaining)) {
                request()->replace(request()->except('f'));
            } else {
                request()->merge(['f' => array_values($remaining)]);
            }
        }

        return $this->filterableScopeFilter($query, $cols, $options);
    }

    protected function applyJsonFieldFilter($query, array $filter, string $match): void
    {
        $field = trim((string) ($filter['query_1'] ?? ''));
        if ($field === '') {
            return;
        }

        $pattern = '%"' . addcslashes($field, '%_\\') . '":%';
        $this->applyJsonMatch($query, $match, function ($q) use ($pattern, $field) {
            $q->where('old_values', 'like', $pattern)
                ->orWhere('new_values', 'like', $pattern)
                ->orWhereRaw('JSON_CONTAINS_PATH(old_values, \'one\', ?)', ['$.' . $field])
                ->orWhereRaw('JSON_CONTAINS_PATH(new_values, \'one\', ?)', ['$.' . $field]);
        });
    }

    protected function applyJsonValueFilter($query, array $filter, string $match): void
    {
        $value = trim((string) ($filter['query_1'] ?? ''));
        if ($value === '') {
            return;
        }

        $operator = $filter['operator'] ?? 'contains';

        if ($operator === 'equal_to') {
            $this->applyJsonMatch($query, $match, function ($q) use ($value) {
                $q->where('old_values', 'like', '%' . addcslashes('"' . $value . '"', '%_\\') . '%')
                    ->orWhere('new_values', 'like', '%' . addcslashes('"' . $value . '"', '%_\\') . '%')
                    ->orWhere('old_values', 'like', '%' . addcslashes(':' . $value . ',', '%_\\') . '%')
                    ->orWhere('new_values', 'like', '%' . addcslashes(':' . $value . ',', '%_\\') . '%')
                    ->orWhere('old_values', 'like', '%' . addcslashes(':' . $value . '}', '%_\\') . '%')
                    ->orWhere('new_values', 'like', '%' . addcslashes(':' . $value . '}', '%_\\') . '%');
            });

            return;
        }

        $pattern = '%' . addcslashes($value, '%_\\') . '%';
        $this->applyJsonMatch($query, $match, function ($q) use ($pattern) {
            $q->where('old_values', 'like', $pattern)
                ->orWhere('new_values', 'like', $pattern);
        });
    }

    protected function applyJsonFieldValueFilter($query, array $filter, string $match): void
    {
        $input = trim((string) ($filter['query_1'] ?? ''));
        if ($input === '' || ! str_contains($input, ':')) {
            return;
        }

        [$field, $value] = array_map('trim', explode(':', $input, 2));
        if ($field === '' || $value === '') {
            return;
        }

        $jsonPath = '$.' . $field;
        $likePattern = '%' . addcslashes($value, '%_\\') . '%';

        $this->applyJsonMatch($query, $match, function ($q) use ($jsonPath, $likePattern, $field, $value) {
            $q->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(old_values, ?)) LIKE ?', [$jsonPath, $likePattern])
                ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(new_values, ?)) LIKE ?', [$jsonPath, $likePattern])
                ->orWhere(function ($inner) use ($field, $value) {
                    $keyPattern = '%"' . addcslashes($field, '%_\\') . '":%' . addcslashes($value, '%_\\') . '%';
                    $inner->where('old_values', 'like', $keyPattern)
                        ->orWhere('new_values', 'like', $keyPattern);
                });
        });
    }

    protected function applyJsonMatch($query, string $match, callable $callback): void
    {
        if ($match === 'or') {
            $query->orWhere($callback);

            return;
        }

        $query->where($callback);
    }

    /**
     * Display field config from the auditable model, if defined.
     * @return array<int, array<string, mixed>>|null
     */
    public function activityFields(): ?array
    {
        $modelClass = $this->resolveAuditableModel();
        if (!$modelClass || !method_exists($modelClass, 'getActivityFields')) {
            return null;
        }

        return (new $modelClass)->getActivityFields();
    }

    /**
     * Resolve the auditable model instance (if still exists).
     */
    public function auditable()
    {
        if (!$this->auditable_type || !$this->auditable_id) {
            return null;
        }

        $model = $this->resolveAuditableModel();
        if (!$model) {
            return null;
        }

        return $model::find($this->auditable_id);
    }

    /**
     * Resolve model class from auditable_type (table name or model class).
     */
    public function resolveAuditableModel(): ?string
    {
        $type = $this->auditable_type;

        // If it's already a model class
        if (class_exists($type)) {
            return $type;
        }

        $map = [
            'users' => User::class,
            'roles' => Role::class,
            'employee' => Employee::class,
            'state' => \App\Models\Settings\State::class,
            'region' => \App\Models\Settings\Region::class,
            'area' => \App\Models\Settings\Area::class,
            'county' => \App\Models\Settings\County::class,
            'workgroup' => \App\Models\Settings\Workgroup::class,
            'company' => \App\Models\Settings\Company::class,
            'company_groups' => \App\Models\Settings\CompanyGroup::class,
            'office' => \App\Models\Settings\Office::class,
            'employee_roles' => \App\Models\Settings\EmployeeRoles::class,
            'employee_sub_role' => \App\Models\Settings\EmployeeSubRoles::class,
            'employee_sub_roles' => \App\Models\Settings\EmployeeSubRoles::class,
            'minimum_wages' => \App\Models\Settings\MinimumWage::class,
            'ledgers' => \App\Models\Settings\Ledger::class,
            'ledger_details' => \App\Models\Settings\LedgerDetails::class,
            'ledger_vouchers' => \App\Models\LedgerVouchers::class,
            'settings' => \App\Models\Settings\Setting::class,
            'check_master' => \App\Models\Settings\CheckMaster::class,
            'pj_calendars' => \App\Models\Settings\PjCalendar::class,
            'fund_requirements' => \App\Models\Settings\FundRequirement::class,
            'bank_rules' => \App\Models\Settings\BankRule::class,
            'bank_category_rules' => \App\Models\Settings\BankCategoryRule::class,
            'pandl_configurations' => \App\Models\Settings\PandlConfiguration::class,

            'employee_hours' => \App\Models\Payroll\EmployeeHours::class,
            'employee_hours_requests' => \App\Models\Onboarding\EmployeeHoursRequest::class,
            'employee_inactive' => \App\Models\Payroll\EmployeeInactive::class,
            'employee_rates' => \App\Models\Payroll\EmployeeRates::class,
            'employee_rate_requests' => \App\Models\Onboarding\EmployeeRateRequest::class,
            'employee_weekly_summary' => \App\Models\Payroll\EmployeeWeeklySummary::class,
            'payroll_check_amounts' => \App\Models\Payroll\PayrollCheckAmount::class,
            'payroll_check_amount' => \App\Models\Payroll\PayrollCheckAmount::class,
            'mwa' => \App\Models\MWA::class,

            'onboarding_list' => \App\Models\Onboarding\OnboardingList::class,
            'onboarding_handbook' => \App\Models\Onboarding\OnboardingHandbook::class,
            'i9_forms' => \App\Models\Onboarding\FormI9::class,
            'w4_forms' => \App\Models\Onboarding\FormW4::class,
            'i9_w4' => \App\Models\Onboarding\I9W4::class,
            'manual_i9' => \App\Models\Onboarding\ManualI9::class,
            'employee_confirmations' => \App\Models\Onboarding\EmployeeConfirmation::class,
            'employee_documents' => \App\Models\Onboarding\EmployeeDocument::class,
            'emergency_contacts' => \App\Models\Onboarding\EmergencyContact::class,
            'benefit_enrollments' => \App\Models\Onboarding\BenefitEnrollment::class,
            'digital_signature' => \App\Models\Onboarding\DigitalSignature::class,
            'final_submission' => \App\Models\Onboarding\FinalSubmission::class,

            'pj_payments' => \App\Models\AR\PjPayment::class,
            'fees_uploads' => \App\Models\AR\FeesUpload::class,
            'ideal_cost' => \App\Models\DataEntry\IdealCost::class,
            'ideal-cost' => \App\Models\DataEntry\IdealCost::class,
            'daily_sales' => \App\Models\DataEntry\DailySale::class,
            'daily-sales' => \App\Models\DataEntry\DailySale::class,
            'bank_deposits' => \App\Models\DataEntry\BankDeposit::class,
            'bank-deposits' => \App\Models\DataEntry\BankDeposit::class,
            'shortage' => \App\Models\DataEntry\Shortage::class,
            'shortages' => \App\Models\DataEntry\Shortage::class,
            'food_purchase' => \App\Models\DataEntry\FoodPurchase::class,
            'drivers' => \App\Models\DataEntry\Drivers::class,
            'bank_uploads' => \App\Models\DataEntry\BankUpload::class,
            'bank_entries' => \App\Models\DataEntry\BankEntry::class,
            'bank_entry_child_amounts' => \App\Models\DataEntry\BankEntryChildAmount::class,
            'payroll_journals' => \App\Models\DataEntry\PayrollJournal::class,
            'wc_entries' => \App\Models\DataEntry\WcEntry::class,

            'ap_purchase_invoices' => \App\Models\AP\PurchaseInvoice::class,
            'ap_vendors' => \App\Models\AP\Vendor::class,
            'ap_expense_types' => \App\Models\AP\ExpenseType::class,
            'chargebacks' => \App\Models\ChargeBack\Chargeback::class,
            'chargeback_reason_codes' => \App\Models\ChargeBack\ChargebackReasonCode::class,
            'chargeback_entry_modes' => \App\Models\ChargeBack\ChargebackEntryMode::class,
            'ttm_reports' => \App\Models\Ttm\TtmReport::class,
            'upload_folders' => \App\Models\Upload\UploadFolder::class,
            'royalty_fees' => \App\Models\DataEntry\RoyaltyFee::class,
        ];

        return $map[$type] ?? null;
    }
}
