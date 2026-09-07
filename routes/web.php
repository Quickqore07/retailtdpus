<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ChargebackController;
use App\Http\Controllers\ChargebackReasonCodeController;
use App\Http\Controllers\ChargebackEntryModeController;
use App\Http\Controllers\Api\QuickqoreSendController;
use App\Http\Controllers\AR\PjLedgerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\Payroll\EmployeeHoursController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Onboarding\EmployeeRateRequestController;
use App\Http\Controllers\Payroll\MWAController;
use App\Http\Controllers\Payroll\EmployeeHoursRequestController;
use App\Http\Controllers\Payroll\EmployeeNewRateRequestController;
use App\Http\Controllers\Onboarding\OnboardingController;
use App\Http\Controllers\Payroll\EmployeeInactiveController;
use App\Http\Controllers\Reports\PayrollReportController;
use App\Http\Controllers\Reports\IdealCostReportController;
use App\Http\Controllers\Reports\SalesReportController;
use App\Http\Controllers\Reports\CashReportController;
use App\Http\Controllers\Reports\APReportController;
use App\Http\Controllers\Reports\HRReportController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AppNotificationController;
use App\Http\Controllers\AR\DepositReportController;
use App\Http\Controllers\Settings\EmployeeRoleController;
use App\Http\Controllers\Settings\StateController;
use App\Http\Controllers\Settings\CountyController;
use App\Http\Controllers\Settings\RegionController;
use App\Http\Controllers\Settings\AreaController;
use App\Http\Controllers\Settings\WorkgroupController;
use App\Http\Controllers\Settings\OfficeController;
use App\Http\Controllers\Settings\CompanyController;
use App\Http\Controllers\Settings\RoleController;
use App\Http\Controllers\Settings\MinimumWageController;
use App\Http\Controllers\Settings\LedgerController;
use App\Http\Controllers\Settings\BankRuleController;
use App\Http\Controllers\Settings\BankCategoryRuleController;
use App\Http\Controllers\Settings\CheckMasterController;
use App\Http\Controllers\Settings\FundRequirementController;
use App\Http\Controllers\Settings\PandLController;
use App\Http\Controllers\Settings\ActivityLogController;
use App\Http\Controllers\AP\VendorsController;
use App\Http\Controllers\AP\ExpenseTypesController;
use App\Http\Controllers\AP\PurchaseInvoiceController;
use App\Http\Controllers\AP\PurchaseInvoicePaymentController;
use App\Http\Controllers\AR\PjPaymentController;
use App\Http\Controllers\Settings\PjCalendarController;
use App\Http\Controllers\AR\FeesUploadController;
use App\Http\Controllers\AR\WeeklyNetworkArReportController;
use App\Http\Controllers\Caching\QQCachingController;
use App\Http\Controllers\DataEntry\BankDepositController;
use App\Http\Controllers\DataEntry\DailySaleController;
use App\Http\Controllers\DataEntry\OtherDailySaleController;
use App\Http\Controllers\DataEntry\FoodPurchaseController;
use App\Http\Controllers\DataEntry\IdealCostController;
use App\Http\Controllers\DataEntry\RoyaltyFeeController;
use App\Http\Controllers\DataEntry\WcEntryController;
use App\Http\Controllers\DataEntry\PayrollJournalController;
use App\Http\Controllers\DataEntry\DriversController;
use App\Http\Controllers\DataEntry\ShortageController;
use App\Http\Controllers\DataEntry\BankEntryController;
use App\Http\Controllers\DataEntry\BankUploadController;
use App\Http\Controllers\Onboarding\BenefitsController;
use App\Http\Controllers\Onboarding\EmployeeConfirmationController;
use App\Http\Controllers\Ttm\TtmUploadController;
use App\Http\Controllers\Onboarding\PendingApplicationsController;
use App\Http\Controllers\Onboarding\I9ReviewController;
use App\Http\Controllers\Reports\FoodCostReportController;
use App\Http\Controllers\Reports\FundReportController;
use App\Http\Controllers\Reports\FinanceReportController;
use App\Http\Controllers\Reports\TTMReportController;
use App\Http\Controllers\Reports\AttendanceReportController;
use App\Http\Controllers\Upload\UploadUserController;
use App\Http\Controllers\Upload\UploadWorkgroupController;
use App\Http\Controllers\Upload\UploadCompanyController;
use App\Http\Controllers\Upload\UploadFolderController;
use App\Http\Controllers\Upload\UploadPortalController;
use App\Http\Controllers\Upload\UploadPortalCustomerController;
use App\Http\Controllers\Upload\CustomerPaymentController;
use App\Http\Controllers\Upload\SalesInvoiceController;
use App\Http\Controllers\Upload\ArEmailTemplateController;
use App\Http\Controllers\Upload\ArInvoiceSettingsController;
use App\Http\Controllers\Upload\InvoiceController;
use App\Http\Controllers\Upload\ReportController;
use App\Http\Controllers\Upload\UploadDocumentController;
use App\Services\MailService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;



// Upload Portal Routes (No authentication required for login)
Route::group(['prefix' => 'upload-portal', 'middleware' => ['web']], function () {
    Route::get('login', [UploadPortalController::class, 'showLogin'])
        ->name('upload-portal.login')
        ->middleware(\App\Http\Middleware\RedirectIfUploadAuthenticated::class);
    
    Route::post('login', [UploadPortalController::class, 'login'])
        ->name('upload-portal.login.submit')
        ->middleware(\App\Http\Middleware\RedirectIfUploadAuthenticated::class);
    
        Route::middleware(['App\Http\Middleware\UploadPortalAuth'])->group(function () {
        Route::get('/', [UploadPortalController::class, 'index'])->name('upload-portal.index');
        Route::get('logout', [UploadPortalController::class, 'logout'])->name('upload-portal.logout');
        
        Route::group(['prefix' => 'api'], function () {
            Route::get('me', [UploadPortalController::class, 'getUser']);
            Route::get('folders', [UploadPortalController::class, 'getFolders']);
            Route::get('dashboard/stats', [UploadPortalController::class, 'getDashboardStats']);
            Route::get('workgroups', [\App\Http\Controllers\Upload\UploadDocumentController::class, 'getWorkgroups']);
            Route::get('companies', [\App\Http\Controllers\Upload\UploadDocumentController::class, 'getCompanies']);
            Route::get('all-companies', [\App\Http\Controllers\Upload\UploadDocumentController::class, 'getAllCompanies']);
            
            Route::get('documents', [\App\Http\Controllers\Upload\UploadDocumentController::class, 'getAllDocuments']);
            Route::post('documents/print-checks-pdf', [\App\Http\Controllers\Upload\UploadDocumentController::class, 'printChecksPdf']);
            Route::get('folder/{folderId}', [UploadPortalController::class, 'getFolder']);
            Route::get('folders/{folderId}/documents', [\App\Http\Controllers\Upload\UploadDocumentController::class, 'index']);
            Route::post('documents', [\App\Http\Controllers\Upload\UploadDocumentController::class, 'store']);
            Route::put('documents/{id}', [\App\Http\Controllers\Upload\UploadDocumentController::class, 'update']);
            Route::delete('documents/{id}', [\App\Http\Controllers\Upload\UploadDocumentController::class, 'destroy']);
            Route::get('documents/{id}/download', [\App\Http\Controllers\Upload\UploadDocumentController::class, 'download']);

            Route::post('vendors', [InvoiceController::class, 'getVendors']);
            Route::post('vendors/create', [InvoiceController::class, 'storeVendor']);
            Route::post('expense-ledgers', [InvoiceController::class, 'getExpenseLedgers']);
            Route::post('signs', [InvoiceController::class, 'getSigns']);
            Route::post('ap-invoices', [InvoiceController::class, 'storeApInvoice']);
            Route::group(['prefix' => 'ap-invoices'], function () {
                Route::get('{documentId}', [InvoiceController::class, 'showApInvoice']);
                Route::put('{documentId}', [InvoiceController::class, 'updateApInvoice']);
                Route::post('{documentId}/approve', [InvoiceController::class, 'approveApInvoice']);
                Route::post('{documentId}/mark-paid', [InvoiceController::class, 'markApInvoicePaid']);
            });
            Route::post('vendor-bills', [InvoiceController::class, 'getVendorBills']);
            Route::post('ap-payments', [InvoiceController::class, 'storeApPayment']);
            Route::group(['prefix' => 'ap-payments'], function () {
                Route::get('{documentId}', [InvoiceController::class, 'showApPayment']);
                Route::put('{documentId}', [InvoiceController::class, 'updateApPayment']);
            });
            Route::post('banks', [InvoiceController::class, 'getBanks']);
            Route::post('latest-check-number', [InvoiceController::class, 'getLatestCheckNumber']);
            Route::post('business-units', [InvoiceController::class, 'getBusinessUnits']);

            Route::group(['prefix' => 'ar-customers'], function () {
                Route::get('/', [UploadPortalCustomerController::class, 'index']);
                Route::post('/', [UploadPortalCustomerController::class, 'store']);
                Route::get('export', [UploadPortalCustomerController::class, 'exportCsv']);
                Route::post('import', [UploadPortalCustomerController::class, 'importCsv']);
                Route::get('statements', [UploadPortalCustomerController::class, 'statements']);
                Route::get('statements/pdf', [UploadPortalCustomerController::class, 'statementsPdf']);
                Route::get('{id}/statement/email-compose', [UploadPortalCustomerController::class, 'statementEmailCompose']);
                Route::post('{id}/statement/email-preview', [UploadPortalCustomerController::class, 'statementEmailPreview']);
                Route::post('{id}/statement/email-send', [UploadPortalCustomerController::class, 'statementEmailSend']);
                Route::get('{id}/statement/pdf', [UploadPortalCustomerController::class, 'statementPdf']);
                Route::get('{id}/statement', [UploadPortalCustomerController::class, 'statement']);
                Route::get('{id}', [UploadPortalCustomerController::class, 'show']);
                Route::put('{id}', [UploadPortalCustomerController::class, 'update']);
                Route::delete('{id}', [UploadPortalCustomerController::class, 'destroy']);
            });

            Route::group(['prefix' => 'customer-payments'], function () {
                Route::get('/', [CustomerPaymentController::class, 'index']);
                Route::get('customer/{customerId}/credit-balance', [CustomerPaymentController::class, 'customerCreditBalance']);
                Route::post('/', [CustomerPaymentController::class, 'store']);
                Route::get('{id}', [CustomerPaymentController::class, 'show']);
                Route::put('{id}', [CustomerPaymentController::class, 'update']);
                Route::delete('{id}', [CustomerPaymentController::class, 'destroy']);
            });

            Route::group(['prefix' => 'sales-invoices'], function () {
                Route::get('next-number', [SalesInvoiceController::class, 'nextInvoiceNumber']);
                Route::post('compute-due-date', [SalesInvoiceController::class, 'computeDueDate']);
                Route::get('/', [SalesInvoiceController::class, 'index']);
                Route::post('/', [SalesInvoiceController::class, 'store']);
                Route::get('{id}/email-compose', [SalesInvoiceController::class, 'emailCompose']);
                Route::post('{id}/email-preview', [SalesInvoiceController::class, 'emailPreview']);
                Route::post('{id}/email-send', [SalesInvoiceController::class, 'emailSend']);
                Route::get('{id}/pdf', [SalesInvoiceController::class, 'downloadPdf']);
                Route::get('{id}', [SalesInvoiceController::class, 'show']);
                Route::put('{id}', [SalesInvoiceController::class, 'update']);
                Route::put('{id}/mark-as-sent', [SalesInvoiceController::class, 'markAsSent']);
                Route::delete('{id}', [SalesInvoiceController::class, 'destroy']);
            });

            Route::get('ar-aging-report', [ReportController::class, 'arAgingReport']);
            Route::get('ar-customer-balance-report', [ReportController::class, 'arCustomerBalanceReport']);

            Route::group(['prefix' => 'ar-email-templates'], function () {
                Route::get('/', [ArEmailTemplateController::class, 'index']);
                Route::post('/', [ArEmailTemplateController::class, 'store']);
                Route::put('{id}', [ArEmailTemplateController::class, 'update']);
                Route::delete('{id}', [ArEmailTemplateController::class, 'destroy']);
            });

            Route::get('ar-invoice-settings', [ArInvoiceSettingsController::class, 'show']);
            Route::put('ar-invoice-settings', [ArInvoiceSettingsController::class, 'update']);
        });
        
        Route::get('{vue?}', [UploadPortalController::class, 'index'])
            ->where('vue', '[\/\w\.-]*')
            ->name('upload-portal.app');
    });
});


Route::post('/logout-on-close', function () {
    Auth::logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return response()->json(['success' => true]);
});
Route::group(['prefix' => 'api'], function () {
    Route::get('me', [PageController::class, 'getUser']);
    Route::get('notifications', [AppNotificationController::class, 'index']);
    Route::get('notifications/unread-count', [AppNotificationController::class, 'unreadCount']);
    Route::patch('notifications/read', [AppNotificationController::class, 'markManyRead']);
    Route::patch('notifications/{app_notification}/read', [AppNotificationController::class, 'markRead']);
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('dashboard/workbright-data', [DashboardController::class, 'workbrightData']);
    Route::get('dashboard/workbright-in-progress-data', [DashboardController::class, 'workbrightInProgressData']);
    Route::get('dashboard/workbright-waiting-for-approval-data', [DashboardController::class, 'workbrightWaitingForApprovalData']);
    Route::get('dashboard/workbright-waiting-for-section2-verification-data', [DashboardController::class, 'workbrightWaitingForSection2VerificationData']);
    Route::get('dashboard/workbright-waiting-for-employee-authorized-data', [DashboardController::class, 'workbrightWaitingForEmployeeAuthorizedData']);
    // Route::get('dashboard/workbright-waiting-for-internal-review-data', [DashboardController::class, 'workbrightWaitingForInternalReviewData']);
    Route::get('dashboard/workbright-tnc-data', [DashboardController::class, 'workbrightTncData']);
    Route::get('dashboard/workbright-completed-data', [DashboardController::class, 'workbrightCompletedData']);
    Route::get('dashboard/tnc-employees', [DashboardController::class, 'tncEmployees']);
    Route::get('dashboard/workbright-export', [DashboardController::class, 'workbrightExport']);
    Route::get('dashboard/workbright-completed-export', [DashboardController::class, 'workbrightCompletedExport']);
    Route::get('dashboard/workbright-logs', [DashboardController::class, 'workbrightLogs']);
    Route::get('dashboard/workbright-logs-export', [DashboardController::class, 'workbrightLogsExport']);
    Route::post('dashboard/workbright-refresh-status', [DashboardController::class, 'refreshWorkbrightStatus']);
    Route::post('dashboard/workbright-authorize-employees', [DashboardController::class, 'authorizeWorkbrightEmployees']);
    Route::get('dashboard/bi-weekly-payroll-chart', [DashboardController::class, 'biWeeklyPayrollChart']);
    Route::get('dashboard/workbright-stats', [DashboardController::class, 'workbrightStats']);
    Route::get('dashboard/fund-requirement', [DashboardController::class, 'fundRequirement']);
    
    Route::group(['prefix' => 'settings'], function () {
        Route::get('/', [\App\Http\Controllers\Settings\SettingsController::class, 'getSettings']);
        Route::post('/', [\App\Http\Controllers\Settings\SettingsController::class, 'updateSettings']);
        Route::post('/verify-password', [\App\Http\Controllers\Settings\SettingsController::class, 'verifyPassword']);
        Route::get('/general', [\App\Http\Controllers\Settings\SettingsController::class, 'getGeneralSettings']);
        Route::post('settings-items', [\App\Http\Controllers\Settings\SettingsController::class, 'getSettingsItems']);
        Route::get('users/sp-permissions', [UserController::class, 'getSPPermissions']);
        Route::resource('users', UserController::class)->whereNumber('user');
        Route::get('roles/permissions', [RoleController::class, 'getPermissions']);
        Route::resource('roles', RoleController::class);
        Route::resource('states', StateController::class);
        Route::resource('counties', CountyController::class);
        Route::resource('regions', RegionController::class);
        Route::resource('areas', AreaController::class);
        Route::resource('workgroups', WorkgroupController::class);
        Route::resource('offices', OfficeController::class);
        Route::resource('companies', CompanyController::class);
        Route::get('companies-template', [CompanyController::class, 'downloadTemplate']);
        Route::get('companies-export', [CompanyController::class, 'export']);
        Route::post('companies-import', [CompanyController::class, 'import']);
        Route::resource('company-groups', \App\Http\Controllers\Settings\CompanyGroupController::class);
        Route::get('company-groups/{id}/available-companies', [\App\Http\Controllers\Settings\CompanyGroupController::class, 'getAvailableCompanies']);
        Route::get('companies-by-groups', [\App\Http\Controllers\Settings\CompanyGroupController::class, 'getCompaniesByGroups']);
        Route::resource('employee-roles', EmployeeRoleController::class);
        Route::resource('minimum-wages', MinimumWageController::class);
        Route::resource('ledgers', LedgerController::class);
        Route::resource('bank-rules', BankRuleController::class);
        Route::resource('bank-category-rules', BankCategoryRuleController::class);
        Route::get('fund-requirements-active', [FundRequirementController::class, 'getActiveFundRequirements']);
        Route::get('fund-requirements-workgroups', [FundRequirementController::class, 'getWorkgroups']);
        Route::post('fund-requirements-workgroup-companies', [FundRequirementController::class, 'getWorkgroupCompanies']);
        Route::post('fund-requirements-reorder', [FundRequirementController::class, 'reorder']);
        Route::post('fund-requirements/update-order', [FundRequirementController::class, 'updateOrder']);
        Route::resource('fund-requirements', FundRequirementController::class);
        Route::resource('pandl', PandLController::class);
        Route::get('activity-logs', [ActivityLogController::class, 'index']);
        Route::get('activity-logs/{id}', [ActivityLogController::class, 'show']);
        Route::resource('pj-calendars', PjCalendarController::class);

    });

    Route::group(['prefix' => 'upload'], function () {
        Route::get('users/sp-permissions', [UploadUserController::class, 'getSPPermissions']);
        Route::resource('users', UploadUserController::class)->whereNumber('user');
        Route::resource('workgroups', UploadWorkgroupController::class);
        Route::resource('companies', UploadCompanyController::class);
        Route::resource('folders', UploadFolderController::class);
    });

    Route::group(['prefix' => 'search'], function () {
        Route::get('roles', [RoleController::class, 'search']);
        Route::get('employee-roles', [EmployeeRoleController::class, 'search']);
        Route::get('states', [StateController::class, 'search']);
        Route::get('counties', [CountyController::class, 'search']);
        Route::get('regions', [RegionController::class, 'search']);
        Route::get('areas', [AreaController::class, 'search']);
        Route::get('workgroups', [WorkgroupController::class, 'search']);
        Route::get('offices', [OfficeController::class, 'search']);
        Route::get('document-workgroups', [UploadWorkgroupController::class, 'searchDocumentWorkgroups']);
        Route::get('companies', [CompanyController::class, 'search']);
        Route::get('company-groups', [\App\Http\Controllers\Settings\CompanyGroupController::class, 'search']);
        Route::get('employees', [EmployeeController::class, 'search']);
        Route::get('employee-confirmation-employees', [EmployeeConfirmationController::class, 'availableEmployees']);
        Route::get('banks', [LedgerController::class, 'getBanks']);
        Route::get('ledgers', [LedgerController::class, 'search']);
        Route::get('users', [UserController::class, 'search']);
        Route::get('fund-requirements', [FundRequirementController::class, 'search']);
        Route::get('upload-workgroups', [UploadWorkgroupController::class, 'search']);
        Route::get('upload-companies', [UploadCompanyController::class, 'search']);
        Route::get('upload-folders', [UploadFolderController::class, 'search']);
        Route::get('upload-users', [UploadUserController::class, 'search']);
        Route::get('pandl', [PandLController::class, 'search']);
        Route::get('vendors', [VendorsController::class, 'search']);
        Route::get('expense-types', [ExpenseTypesController::class, 'search']);
        Route::get('pj-calendars', [PjCalendarController::class, 'search']);

    });
    
    Route::post('employee/upload-profile-picture', [EmployeeController::class, 'uploadProfilePicture']);
    Route::post('employee/payroll-info', [EmployeeController::class, 'getPayrollInfo']);
    Route::post('employee/{id}/inactive', [EmployeeController::class, 'inactive']);
    Route::get('employee-export', [EmployeeController::class, 'export']);
    Route::resource('employee', EmployeeController::class);
    Route::get('employee/documents/{id}', [EmployeeController::class, 'getDocuments']);
    Route::post('employee/documents', [EmployeeController::class, 'storeDocument']);
    Route::delete('employee/documents/{document}', [EmployeeController::class, 'destroyDocument']);

    Route::group(['prefix' => 'payroll'], function () { 
        Route::post('employee-hours/delete-multiple', [EmployeeHoursController::class, 'destroyMultiple']);
        Route::get('employee-hours-export', [EmployeeHoursController::class, 'export']);
        Route::post('employee-hours/missing/delete', [EmployeeHoursController::class, 'deleteMissingCompany']);
        Route::get('employee-hours/missing/deleted', [EmployeeHoursController::class, 'deletedMissingCompanies']);
        Route::resource('employee-hours', EmployeeHoursController::class);
        Route::post('employee-hours/upload', [EmployeeHoursController::class, 'upload']);
        Route::post('employee-hours/date-range', [EmployeeHoursController::class, 'getByDateRange']);

        
        Route::post('employee-inactive/delete-multiple', [EmployeeInactiveController::class, 'destroyMultiple']);
        Route::resource('employee-inactive', EmployeeInactiveController::class);
        
        Route::get('employee-hours-request/lookup', [EmployeeHoursRequestController::class, 'lookup']);
        Route::get('employee-hours-request/roles-for-selection', [EmployeeHoursRequestController::class, 'rolesForSelection']);
        
        Route::resource('employee-hours-request', EmployeeHoursRequestController::class)->only(['index', 'show', 'destroy', 'create', 'store']);
        Route::post('employee-hours-request/{id}/approve', [EmployeeHoursRequestController::class, 'approve']);
        
        Route::resource('employee-new-rate-request', EmployeeNewRateRequestController::class);
        Route::post('employee-new-rate-request/{id}/approve', [EmployeeNewRateRequestController::class, 'approve']);
        Route::post('employee-new-rate-request/{id}/reject', [EmployeeNewRateRequestController::class, 'reject']);

        Route::get('mwa', [MWAController::class, 'index']);
        Route::post('mwa/entries', [MWAController::class, 'entries']);
        Route::get('mwa/employees', [MWAController::class, 'getEmployees']);
        Route::get('mwa/employees/{employeeId}/companies', [MWAController::class, 'getCompaniesByEmployee']);
        Route::get('mwa/employees/{employeeId}/companies/{companyId}/roles', [MWAController::class, 'getRolesByEmployeeCompany']);
        Route::resource('mwa', MWAController::class);

        
    });

    Route::get('export-template/{template}', [FeesUploadController::class, 'downloadTemplate']);
    Route::group(['prefix' => 'ar'], function () {
        Route::get('pj-payments-export', [PjPaymentController::class, 'export']);
        Route::resource('pj-payments', PjPaymentController::class);
        Route::post('pj-payments/upload', [PjPaymentController::class, 'upload']);
        // Route::post('pj-payments/missing-visa-report', [PjPaymentController::class, 'getMissingVisaAmountReport']);
        // Route::post('pj-payments/update-settle-visa-amounts', [PjPaymentController::class, 'updateAndSettleVisaAmounts']);
        Route::post('pj-ledgers', [PjLedgerController::class, 'index']);
        Route::post('deposit-report', [DepositReportController::class, 'index']);
        Route::post('deposit-report/unsettle', [DepositReportController::class, 'unsettleDepositItem']);
        Route::post('missing-bank-amount-report', [DepositReportController::class, 'missingBankAmountReport']);
        Route::post('missing-bank-amount-report/update-settle-bank-amounts', [DepositReportController::class, 'updateAndSettleBankAmounts']);
        Route::post('missing-bank-amount-report/available-items', [DepositReportController::class, 'getAvailableItemsForManualSettlement']);
        Route::post('missing-bank-amount-report/manual-settlement', [DepositReportController::class, 'manualSettlement']);
        Route::post('missing-bank-amount-report/transfer-to-account', [DepositReportController::class, 'transferToAccount']);
        Route::post('weekly-network-ar-report', [WeeklyNetworkArReportController::class, 'index']);
        Route::post('balance-due-network-report', [WeeklyNetworkArReportController::class, 'dueNetworkReport']);
        Route::post('weekly-network-ar-report/update-fees', [WeeklyNetworkArReportController::class, 'updateFees']);
        
        Route::get('fees-uploads-export', [FeesUploadController::class, 'export']);
        Route::post('fees-uploads/upload', [FeesUploadController::class, 'upload']);
        Route::resource('fees-uploads', FeesUploadController::class);

    });

    Route::group(['prefix' => 'ap'], function () {
        Route::resource('expense-types', ExpenseTypesController::class);
        Route::resource('vendors', VendorsController::class);
        Route::get('vendors/{id}/bills', [VendorsController::class, 'getVendorBills']);
        Route::post('purchase-invoices/{id}/approve', [PurchaseInvoiceController::class, 'approve']);
        Route::get('purchase-invoices/{id}/payments', [PurchaseInvoicePaymentController::class, 'index']);
        Route::post('purchase-invoices/{id}/payments', [PurchaseInvoicePaymentController::class, 'store']);
        Route::put('purchase-invoices/{id}/payments/{paymentId}', [PurchaseInvoicePaymentController::class, 'update']);
        Route::delete('purchase-invoices/{id}/payments/{paymentId}', [PurchaseInvoicePaymentController::class, 'destroy']);
        Route::post('purchase-invoices/{id}/cancel', [PurchaseInvoiceController::class, 'cancel']);
        Route::post('purchase-invoices/{id}/draft', [PurchaseInvoiceController::class, 'moveToDraft']);
        Route::resource('purchase-invoices', PurchaseInvoiceController::class);
    });
    Route::get('charge-backs', [ChargebackController::class, 'index']);
    Route::get('charge-backs-export', [ChargebackController::class, 'export']);
    Route::get('charge-backs/summary', [ChargebackController::class, 'summary']);
    Route::get('charge-backs/create', [ChargebackController::class, 'create']);
    Route::post('charge-backs', [ChargebackController::class, 'store']);
    Route::put('charge-backs/{chargeback}', [ChargebackController::class, 'update']);
    Route::post('charge-backs/{chargeback}/upload-sales-receipt', [ChargebackController::class, 'uploadSalesReceipt']);
    Route::post('charge-backs/{chargeback}/submit', [ChargebackController::class, 'submit']);
    Route::post('charge-backs/{chargeback}/mark-as-credited', [ChargebackController::class, 'markAsCredited']);
    Route::get('charge-back-reason-codes/options', [ChargebackReasonCodeController::class, 'options']);
    Route::get('charge-back-reason-codes-export', [ChargebackReasonCodeController::class, 'export']);
    Route::resource('charge-back-reason-codes', ChargebackReasonCodeController::class);
    Route::get('charge-back-entry-modes/options', [ChargebackEntryModeController::class, 'options']);
    Route::get('charge-back-entry-modes-export', [ChargebackEntryModeController::class, 'export']);
    Route::resource('charge-back-entry-modes', ChargebackEntryModeController::class);
    Route::group(['prefix' => 'data-entry'], function () {
        Route::get('daily-sales-export', [DailySaleController::class, 'export']);
        Route::resource('daily-sales', DailySaleController::class);
        Route::post('daily-sales/upload', [DailySaleController::class, 'upload']);
        Route::resource('other-daily-sales', OtherDailySaleController::class);
        Route::get('bank-deposits-export', [BankDepositController::class, 'export']);
        Route::resource('bank-deposits', BankDepositController::class);
        Route::resource('shortages', ShortageController::class);
        
        Route::get('bank-entries-export', [BankEntryController::class, 'export']);
        Route::resource('bank-entries', BankEntryController::class);
        Route::post('bank-entries/upload', [BankEntryController::class, 'upload']);
        Route::post('bank-entries/delete-multiple', [BankEntryController::class, 'destroyMultiple']);
        
        Route::resource('bank-uploads', BankUploadController::class)->only(['index', 'show', 'destroy']);
        Route::post('bank-uploads/upload', [BankUploadController::class, 'upload']);
        Route::post('bank-uploads/delete-multiple', [BankUploadController::class, 'destroyMultiple']);
        Route::post('bank-uploads/delete-group', [BankUploadController::class, 'destroyGroup']);
        Route::post('bank-uploads/detail-records', [BankUploadController::class, 'detailRecords']);
        Route::post('bank-uploads/store-opening-balance', [BankUploadController::class, 'storeOpeningBalance']);
        Route::put('bank-uploads/update/{id}', [BankUploadController::class, 'update']);
        Route::delete('bank-uploads/delete/{id}', [BankUploadController::class, 'delete']);

        Route::resource('ttm-uploads', TtmUploadController::class)->only(['index']);
        Route::post('ttm-uploads/upload', [TtmUploadController::class, 'upload']);
        Route::post('ttm-uploads/delete-period', [TtmUploadController::class, 'deletePeriod']);
        
        Route::get('food-purchases-export', [FoodPurchaseController::class, 'export']);
        Route::resource('food-purchases', FoodPurchaseController::class);
        
        Route::get('ideal-costs-export', [IdealCostController::class, 'export']);
        Route::resource('ideal-costs', IdealCostController::class);
        Route::post('ideal-costs/upload', [IdealCostController::class, 'upload']);
        
        Route::resource('wc-entries', WcEntryController::class);
        Route::get('royalty-fees-export', [RoyaltyFeeController::class, 'export']);
        Route::post('royalty-fees/import-pdf', [RoyaltyFeeController::class, 'importPdf']);
        Route::post('royalty-fees/import-excel', [RoyaltyFeeController::class, 'importExcel']);
        Route::get('royalty-fees/import-excel-sample', [RoyaltyFeeController::class, 'downloadExcelSample']);
        Route::resource('royalty-fees', RoyaltyFeeController::class);
        Route::resource('payroll-journals', PayrollJournalController::class)->only(['index']);
        Route::post('payroll-journals/upload', [PayrollJournalController::class, 'upload']);
        Route::post('payroll-journals/company-data', [PayrollJournalController::class, 'companyData']);

       
        Route::resource('driver', DriversController::class)->only(['index', 'destroy']);
        Route::post('driver/delete-multiple', [DriversController::class, 'destroyMultiple']);
        Route::post('driver/upload', [DriversController::class, 'upload']);
    });
    Route::group(['prefix' => 'onboarding'], function () {

        Route::post('send-workbright-email', [OnboardingController::class, 'sendWorkbrightEmail']);
        
        Route::resource('employee-rate-request', EmployeeRateRequestController::class);
        Route::post('employee-rate-request/{id}/approve', [EmployeeRateRequestController::class, 'approve']);
        Route::post('employee-rate-request/{id}/reject', [EmployeeRateRequestController::class, 'reject']);
       
        Route::post('send-mail-to-employee', [OnboardingController::class, 'sendMailToEmp']);
        Route::get('pending-applications', [PendingApplicationsController::class, 'index']);
        Route::post('pending-applications/{id}/reject', [PendingApplicationsController::class, 'reject']);
        Route::post('pending-applications/{id}/move-to-employee', [PendingApplicationsController::class, 'moveToEmployee']);
        
        // I-9 Review routes
        Route::get('i9-review', [I9ReviewController::class, 'index']);
        Route::get('i9-review/{id}', [I9ReviewController::class, 'show']);
        Route::post('i9-review/{id}/approve', [I9ReviewController::class, 'approve']);
        Route::post('i9-review/{id}/reject', [I9ReviewController::class, 'reject']);
        Route::post('i9-review/{id}/approve-w4', [I9ReviewController::class, 'approveW4']);
        Route::post('i9-review/{id}/update-status', [I9ReviewController::class, 'updateStatus']);
        Route::post('i9-review/{id}/update-employer-cert', [I9ReviewController::class, 'updateEmployerCert']);
        Route::get('i9-review/{id}/export-pdf', [I9ReviewController::class, 'exportPdf']);
        Route::get('i9-review/{id}/export-w4-pdf', [I9ReviewController::class, 'exportW4Pdf']);
        Route::get('i9-review/{id}/export-benefit-pdf', [I9ReviewController::class, 'exportBenefitPdf']);
        Route::get('i9-review/{id}/export-hire-form-pdf', [I9ReviewController::class, 'exportHireFormPdf']);
        Route::get('i9-review/{id}/export-direct-deposit-pdf', [I9ReviewController::class, 'exportDirectDepositPdf']);
        
        // Benefits routes
        Route::get('benefits', [BenefitsController::class, 'index']);
        Route::get('benefits-export', [BenefitsController::class, 'export']);
        Route::get('benefits/search', [BenefitsController::class, 'searchEmployees']);
        Route::post('benefits/employees', [BenefitsController::class, 'createEmployee']);
        Route::post('benefits/send', [BenefitsController::class, 'sendBenefitMail']);
        Route::post('benefits/download-current-year-benefit-enrollment-file', [OnboardingController::class, 'getCurrentYearBenefitEnrollmentFile']);

        // Employee Confirmation routes
        Route::get('employee-confirmation', [EmployeeConfirmationController::class, 'index']);
        Route::get('employee-confirmation/export', [EmployeeConfirmationController::class, 'export']);
        Route::get('employee-confirmation/available-employees', [EmployeeConfirmationController::class, 'availableEmployees']);
        Route::post('employee-confirmation/employees', [EmployeeConfirmationController::class, 'createEmployee']);
        Route::get('employee-confirmation/{id}', [EmployeeConfirmationController::class, 'show']);
        Route::post('employee-confirmation', [EmployeeConfirmationController::class, 'store']);
        Route::post('employee-confirmation/{id}/employee', [EmployeeConfirmationController::class, 'updateEmployee']);
        Route::post('employee-confirmation/{id}/step1', [EmployeeConfirmationController::class, 'submitStep1']);
        Route::post('employee-confirmation/{id}/step2', [EmployeeConfirmationController::class, 'submitStep2']);
        Route::post('employee-confirmation/{id}/review', [EmployeeConfirmationController::class, 'submitReview']);
        Route::post('employee-confirmation/{id}/reject', [EmployeeConfirmationController::class, 'reject']);
        Route::post('employee-confirmation/{id}/tnc', [EmployeeConfirmationController::class, 'markTnc']);
        Route::post('employee-confirmation/{id}/incorrect-i9', [EmployeeConfirmationController::class, 'markIncorrectI9']);
        Route::post('employee-confirmation/{id}/authorize', [EmployeeConfirmationController::class, 'authorizeEmployee']);
        Route::post('employee-confirmation/{id}/unauthorize', [EmployeeConfirmationController::class, 'unauthorizeEmployee']);
        Route::post('employee-confirmation/{id}/left', [EmployeeConfirmationController::class, 'markLeft']);
        Route::post('employee-confirmation/{id}/remove-left', [EmployeeConfirmationController::class, 'removeLeft']);
        Route::get('employee-confirmation/{id}/download-i9', [EmployeeConfirmationController::class, 'downloadI9']);
        Route::post('employee-confirmation/{id}/upload-i9-without-signatures', [EmployeeConfirmationController::class, 'uploadI9WithoutSignatures']);
    });


    Route::get('set-companies-cache', [QQCachingController::class, 'setCompaniesCacheAction']);
    Route::group(['prefix' => 'reports'], function () {
        Route::post('attendance', [AttendanceReportController::class, 'index']);
        Route::post('attendance/{attendance}/work-hours', [AttendanceReportController::class, 'updateWorkHours']);
        Route::post('attendance/breaks/{attendanceBreak}/break-hours', [AttendanceReportController::class, 'updateBreakHours']);

        Route::group(['prefix' => 'payroll'], function () {
            Route::post('weekly', [PayrollReportController::class, 'getWeeklyPayrollReport']);
            Route::post('dtm', [PayrollReportController::class, 'getDtmReport']);
            Route::post('payroll-journal', [PayrollReportController::class, 'getPayrollJournalReport']);
            Route::post('paychex', [PayrollReportController::class, 'getPaychexReport']);
            Route::post('review', [PayrollReportController::class, 'reviewPayroll']);
            Route::post('labour', [PayrollReportController::class, 'getLabourReport']);
            Route::post('network-check', [PayrollReportController::class, 'getNetworkCheckReport']);
            Route::post('import-quickqore', [CheckMasterController::class, 'importQuickqore']);
            Route::post('network-instant', [PayrollReportController::class, 'getNetworkInstantReport']);
            Route::post('mwa', [PayrollReportController::class, 'getMWAReport']);
            Route::post('mwa/update', [PayrollReportController::class, 'updateMWA']);
        });
        
        Route::group(['prefix' => 'hr'], function () {
            Route::post('network-summary', [HRReportController::class, 'getNetworkSummaryReport']);
            Route::post('network-hours', [HRReportController::class, 'getNetworkHoursReport']);
            Route::post('store-summary', [HRReportController::class, 'getStoreSummaryReport']);
            Route::post('employee-hours-anomaly', [HRReportController::class, 'getEmployeeHoursAnomalyReport']);
            Route::post('employee-payroll-info', [HRReportController::class, 'getEmployeePayrollInfoReport']);
        });
        Route::group(['prefix' => 'ideal-cost'], function () {
            Route::post('purchase', [IdealCostReportController::class, 'getPurchaseReport']);
            Route::post('ideal-cost', [IdealCostReportController::class, 'getIdealCostReport']);
            Route::post('sales', [IdealCostReportController::class, 'getSalesReport']);
            Route::post('ideal-cost-purchase-difference', [IdealCostReportController::class, 'getIdealCostPurchaseDifferenceReport']);
            Route::post('ideal-cost-summary', [IdealCostReportController::class, 'getIdealCostSummaryReport']);
            Route::post('food-truck', [IdealCostReportController::class, 'getFoodTruckReport']);
            Route::post('sales-projection', [IdealCostReportController::class, 'getSalesProjectionReport']);
            Route::post('sales-projection/submit', [IdealCostReportController::class, 'submitSalesProjectionReport']);
        });
        Route::group(['prefix' => 'sales'], function () {
            Route::post('network-weekly-sales', [SalesReportController::class, 'getNetworkWeeklySalesReport']);
            Route::post('network-monthly-sales', [SalesReportController::class, 'getNetworkMonthlySalesReport']);
        });
        Route::group(['prefix' => 'food-cost'], function () {
            Route::post('flm', [FoodCostReportController::class, 'getFlmReport']);
            Route::post('flm-t', [FoodCostReportController::class, 'getFlmTReport']);
            Route::post('flm-store-summary', [FoodCostReportController::class, 'getFlmStoreSummaryReport']);
        });
        Route::group(['prefix' => 'cash'], function () {
            Route::post('bank-deposit', [CashReportController::class, 'getBankDepositReport']);
            Route::post('ledger', [CashReportController::class, 'getCashLedgerReport']);
            Route::post('daily-cash', [CashReportController::class, 'getDailyCashReport']);
            Route::post('cash-short-sales', [CashReportController::class, 'getCashShortSalesReport']);
            Route::post('cash-payout-sales', [CashReportController::class, 'getCashPayoutSalesReport']);
            Route::post('pending-bank-deposit', [CashReportController::class, 'getPendingBankDepositReport']);
        });
        Route::group(['prefix' => 'fund'], function () {
            Route::get('labels', [FundReportController::class, 'getLabels']);
            Route::post('company-wise', [FundReportController::class, 'getCompanyWiseFundReport']);
            Route::post('bank-report', [FundReportController::class, 'getBankReport']);
        });
        Route::group(['prefix' => 'finance'], function () {
            Route::post('get-finance-report', [FinanceReportController::class, 'getFinanceReport']);
            Route::post('get-detailed-finance-report', [FinanceReportController::class, 'getDetailedFinanceReport']);
            Route::post('get-store-wise-finance-report', [FinanceReportController::class, 'getStoreWiseFinanceReport']);
            Route::post('get-store-wise-profit-and-loss-report', [FinanceReportController::class, 'getStoreWiseProfitAndLossReport']);
        });
        Route::group(['prefix' => 'ttm'], function () {
            Route::post('get-ttm-report', [TTMReportController::class, 'getTtmReport']);
        });
        Route::group(['prefix' => 'ap'], function () {
            Route::post('bill-wise', [APReportController::class, 'getBillWiseReport']);
            Route::post('monthly', [APReportController::class, 'getMonthlyReport']);
        });
    });
    Route::post('payroll/network-check-review', [CheckMasterController::class, 'store']);

    Route::post('switch-company', [CompanyController::class, 'switchCompany']);
    Route::get('get-latest-check-number/{ledger_id}/{company_id}', [CheckMasterController::class, 'getLatestCheckNumber']);
    Route::get('get-default-bank/{company_id}', [LedgerController::class, 'getdefaultBank']);
    Route::post('print-check', [CheckMasterController::class, 'printChecks']);
    Route::post('view-document', [UploadDocumentController::class, 'viewDocument']);
});
Route::get('get-current-company', [CompanyController::class, 'getCurrentCompany']);




Route::get('/onboarding-process', function () {
    $stepProcessIds = [];
    for ($i = 1; $i <= 10; $i++) {      
        $stepProcessIds[$i] = custom_encrypt((string) $i);
    }
    return view('onboarding-process', ['stepProcessIds' => $stepProcessIds]);
});
Route::post('/onboarding-process/verify-code', [OnboardingController::class, 'verifyOnboardingCode'])->name('onboarding-process.verify-code');
Route::get('/onboarding-process/data', [OnboardingController::class, 'getOnboardingData'])->name('onboarding-process.data');
Route::get('/onboarding-process/handbook-data', [OnboardingController::class, 'getHandbookData'])->name('onboarding-process.handbook-data');
Route::post('/onboarding-process/employee-handbook', [OnboardingController::class, 'saveEmployeeHandbook'])->name('save.employee.handbook');
Route::get('/onboarding-process/profile-data', [OnboardingController::class, 'getProfileData'])->name('onboarding-process.profile-data');
Route::post('/onboarding-process/save-profile', [OnboardingController::class, 'saveProfile'])->name('onboarding-process.save-profile');
Route::get('/onboarding-process/i9-data', [OnboardingController::class, 'getI9Data'])->name('onboarding-process.i9-data');
Route::post('/onboarding-process/save-i9', [OnboardingController::class, 'saveI9'])->name('onboarding-process.save-i9');
Route::get('/onboarding-process/w4-data', [OnboardingController::class, 'getW4Data'])->name('onboarding-process.w4-data');
Route::post('/onboarding-process/save-w4', [OnboardingController::class, 'saveW4'])->name('onboarding-process.save-w4');
Route::get('/onboarding-process/i9-w4-data', [OnboardingController::class, 'getI9W4Data'])->name('onboarding-process.i9-w4-data');
Route::post('/onboarding-process/save-i9-w4', [OnboardingController::class, 'saveI9W4'])->name('onboarding-process.save-i9-w4');
Route::post('/onboarding-process/upload-work-permit', [OnboardingController::class, 'uploadWorkPermit'])->name('onboarding-process.upload-work-permit');
Route::get('/onboarding-process/benefit-data', [OnboardingController::class, 'getBenefitData'])->name('onboarding-process.benefit-data');
Route::post('/onboarding-process/save-benefits-enroll', [OnboardingController::class, 'saveBenefitsEnroll'])->name('save.benefits-enroll');
Route::post('/onboarding-process/save-benefits-decline', [OnboardingController::class, 'saveBenefitsDecline'])->name('save.benefits-decline');
Route::post('/onboarding-process/save-benefits-waive-ack', [OnboardingController::class, 'saveBenefitsWaiveAck'])->name('save.benefits-waive-ack');
Route::get('/onboarding-process/direct-deposit-data', [OnboardingController::class, 'getDirectDepositData'])->name('onboarding-process.direct-deposit-data');
Route::post('/onboarding-process/direct-deposit', [OnboardingController::class, 'saveDirectDeposit'])->name('onboarding-process.save-direct-deposit');
Route::get('/onboarding-process/emergency-contact-data', [OnboardingController::class, 'getEmergencyContactData'])->name('onboarding-process.emergency-contact-data');
Route::post('/onboarding-process/save-emergency-contact', [OnboardingController::class, 'saveEmergencyContact'])->name('onboarding-process.save-emergency-contact');
Route::get('/onboarding-process/final-submission-data', [OnboardingController::class, 'getFinalSubmissionData'])->name('onboarding-process.final-submission-data');
Route::post('/onboarding-process/save-final-submission', [OnboardingController::class, 'saveFinalSubmission'])->name('onboarding-process.save-final-submission');
Route::get('/onboarding-process/digital-signature-data', [OnboardingController::class, 'getDigitalSignatureData'])->name('onboarding-process.digital-signature-data');
Route::post('/onboarding-process/save-digital-signature', [OnboardingController::class, 'saveDigitalSignature'])->name('onboarding-process.save-digital-signature');
Route::post('/update-onboarding-process', [OnboardingController::class, 'updateOnboardingProcess'])->name('update.onboarding.process');



Route::get('/benefit-enrollment', function () {
    return view('single-benefit');
});
Route::get('/benefit-enrollment/check-benefit-submitted', [BenefitsController::class, 'checkBenefitSubmitted'])->name('benefit-enrollment.check-benefit-submitted');
Route::post('/benefit-enrollment/verify-benefit-code', [BenefitsController::class, 'verifyBenefitCode'])->name('benefit-enrollment.verify-benefit-code');

Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
Route::post('/attendance/verify', [AttendanceController::class, 'verify'])->name('attendance.verify');
Route::get('/attendance/status', [AttendanceController::class, 'status'])->name('attendance.status');
Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.check-in');
Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut'])->name('attendance.check-out');
Route::post('/attendance/start-break', [AttendanceController::class, 'startBreak'])->name('attendance.start-break');
Route::post('/attendance/end-break', [AttendanceController::class, 'endBreak'])->name('attendance.end-break');

Route::get('get-workgroups', [WorkgroupController::class, 'getWorkgroups']);
Route::get('get-companies', [CompanyController::class, 'getCompanies']);






Route::post('login', [PageController::class, 'login'])->name('login');
Route::get('/authenticate', [PageController::class, 'authenticate'])->name('authenticate');
Route::post('otp', [PageController::class, 'OTP']);
Route::post('sendotp', [PageController::class, 'sendotp']);
Route::post('verifyOTP', [PageController::class, 'verifyOTP']);
Route::post('/setnewpass', [PageController::class, 'setnewpass']);
Route::get('/setpassword', [PageController::class, 'setpassword'])->name('setpassword');

Route::get('/forgotpassword', [PageController::class, 'forgotpassword'])->name('forgotpassword');
Route::post('/forgot', [PageController::class, 'forgot']);

Route::post('forgotpassword', [PageController::class, 'forgotpasswordsace'])->name('savepassword');
Route::get('passwordreset/{token}', [PageController::class, 'verifyAccount'])->name('updatepassword.verify');
Route::get('/check-token/{type}/{token}', [PageController::class, 'checkToken'])->name('check-token');
Route::get('twofactor', [PageController::class, 'twofactor']);

Route::get('logout', [PageController::class, 'logout']);

Route::post('verifyOTP', [PageController::class, 'verifyOTP']);



Route::post('register', [RegistrationController::class, 'store']);
Route::get('account/verify/{token}/{email}/{id}', [RegistrationController::class, 'verifyAccount'])->name('emailaddress.verifyuser');
Route::get('/verify-email', [RegistrationController::class, 'verifyEmail'])->name('verify-email');
Route::get('/register', [RegistrationController::class, 'create'])->name('register');
Route::post('/continue-google', [RegistrationController::class, 'googleLogin'])->name('google-login');


Route::get('chequeprint_sample', function () {
    return view('chequeprint_sample');
})->name('chequeprint_sample');


Route::get('shortage/{id}', [ShortageController::class, 'show'])->name('shortage.show');









Route::get('test-pdf', function () {
    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>body{font-family:DejaVu Sans,sans-serif;padding:24px;}</style></head><body>'
        . '<h1>DomPDF test</h1>'
        . '<p>This is a simple PDF for testing.</p>'
        . '<p>Generated at: ' . e(now()->toDateTimeString()) . '</p>'
        . '</body></html>';

        $pdfOptions = [
            'isRemoteEnabled' => true,
            'isFontSubsettingEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'chroot' => public_path(),
        ];

        return Pdf::loadView('pdfs.hire-form-pdf', [
            'onboarding' => (object) [],
            'emergencyContact' => (object) [],
        ])->setOptions($pdfOptions);
        return $pdf->stream('test.pdf');

})->name('test-pdf');



Route::get('send-api-mail', function () {
    $response = (new MailService())->sendMail(
        'quickqore7@gmail.com',
        'Login Credentials',
        'Test Email Content',
    );
    return $response;
})->name('send-api-mail');


Route::get('workbright-test', [OnboardingController::class, 'workbrightTest'])->name('workbright-test');



Route::get('aws-file-upload', function () {
    $query = request()->query();
    $path = $query['path'];
    $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
    $path = 'employee-documents/5765/i9.pdf';
    $adapter = Storage::disk($disk);
    /** @var \Illuminate\Contracts\Filesystem\FilesystemAdapter $adapter */
    if ($disk === 's3') {
        return $adapter->temporaryUrl(
            $path,
            now()->addMinutes(30)
        );
    }

    return $adapter->url($path);
})->name('aws-file-upload');

Route::get('{vue?}', [PageController::class, 'index'])
    ->where('vue', '[\/\w\.-]*')
    ->name('app');


