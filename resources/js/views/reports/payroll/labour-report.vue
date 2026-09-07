<template>
    <div class="weekly-payroll-report p-2 bg-gray-50 dark:bg-gray-900">
        <!-- Header Section -->
        
        <!-- Filter Panel -->
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Labour Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    View and analyze labour data for the selected period
                </p>
            </div>

            <!-- Year and Period Selection -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Select Year -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select Year
                    </label>
                    <select
                        v-model="filters.selectedYear"
                        @change="onYearChange"
                        class="form-select"
                    >
                        <option v-for="year in availableYears" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                </div>

                <!-- Select Period -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select Period
                    </label>
                    <select
                        v-model="filters.selectedPeriod"
                        @change="onPeriodChange"
                        class="form-select"
                    >
                        <option v-for="period in availablePeriods" :key="period.value" :value="period.value">
                            {{ period.label }}
                        </option>
                    </select>
                </div>
            </div>


            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-3">
                    <Button
                        icon-left="refresh"
                        icon-size="sm"
                        variant="primary"
                        size="sm"
                        @click="applyFilters"
                        :loading="loading"
                    >
                        Apply Filters
                    </Button>
                    <Button
                        icon-left="upload"
                        icon-size="sm"
                        variant="secondary"
                        size="sm"
                        @click="exportReport"
                        :loading="exportLoading"
                    >
                        Export to Excel
                    </Button>
                </div>

            </div>
        </Panel>

        <!-- Summary Cards -->
        <div class="grid  xl:grid-cols-8 gap-4 mb-6">
            <div class="xl:col-span-6 ">
                <MultiStatCard
                    label="Summary"
                    :items="DataSummaryStats"
                    icon="chart"
                    icon-color="indigo"
                    format-type="number"
                />
            </div>

            <div class="xl:col-span-2">
                <MultiStatCard
                    label="Payment"
                    :items="paymentMethodsStats"
                    icon="dollar"
                    icon-color="indigo"
                    description="Distribution by method"
                    format-type="number"
                />
            </div>
        </div>

        <!-- Data Tables - Company Wise -->
        <div v-if="payrollData.length > 0" class="space-y-6 max-h-[calc(100vh-250px)] relative overflow-y-auto">
            <template v-for="company in payrollData" :key="company.id" >
                <Panel v-if="company?.labour_data && company.labour_data?.length" >
                <!-- Company Header -->
                <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col md:flex-row items-center justify-between">
                            <h3 class="text-center md:text-left !text-sm !md:text-xl font-bold text-gray-900 dark:text-white  !mb-0">
                                {{ company.name }}
                            </h3>
                            <p class="text-sm text-center md:text-left text-gray-600 dark:text-gray-400 mt-1">
                                Store Number: {{ company.store_number }} | Contact: {{ company.contact_person }}
                            </p>
                    </div>
                </div>

                <div class="overflow-x-auto max-h-[calc(100vh-150px)] relative ">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900 sticky top-0 !z-[101]">
                            <tr>
                                <Th class="md:sticky md:left-0 bg-white dark:bg-gray-900 !z-[101]">
                                    <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort(company.id, 'sr_no')">
                                        <span>Sr. No.</span>
                                        <SvgIcon 
                                            v-if="getSortDirection(company.id, 'sr_no')" 
                                            :name="getSortDirection(company.id, 'sr_no') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                            size="sm" 
                                            class="text-blue-600 dark:text-blue-400"
                                        />
                                    </div>
                                </Th>
                                <Th class="md:sticky md:left-13 bg-white dark:bg-gray-900 !z-[101]">
                                    <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort(company.id, 'employee_id')">
                                        <span>Employee ID</span>
                                        <SvgIcon 
                                            v-if="getSortDirection(company.id, 'employee_id')" 
                                            :name="getSortDirection(company.id, 'employee_id') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                            size="sm" 
                                            class="text-blue-600 dark:text-blue-400"
                                        />
                                    </div>
                                </Th>
                                <Th class="md:sticky md:left-36 bg-white dark:bg-gray-900 !z-[101]">
                                    <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort(company.id, 'employee_name')">
                                        <span>Employee Name</span>
                                        <SvgIcon 
                                            v-if="getSortDirection(company.id, 'employee_name')" 
                                            :name="getSortDirection(company.id, 'employee_name') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                            size="sm" 
                                            class="text-blue-600 dark:text-blue-400"
                                        />
                                    </div>
                                </Th>
                                <Th>
                                    <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort(company.id, 'pay_rate')">
                                        <span>Pay Rate</span>
                                        <SvgIcon 
                                            v-if="getSortDirection(company.id, 'pay_rate')" 
                                            :name="getSortDirection(company.id, 'pay_rate') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                            size="sm" 
                                            class="text-blue-600 dark:text-blue-400"
                                        />
                                    </div>
                                </Th>
                                <Th v-for="role in company.roles" :key="role">
                                    <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort(company.id, `role_rate_${role}`)">
                                        <span>{{ role }} Rate</span>
                                        <SvgIcon 
                                            v-if="getSortDirection(company.id, `role_rate_${role}`)" 
                                            :name="getSortDirection(company.id, `role_rate_${role}`) === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                            size="sm" 
                                            class="text-blue-600 dark:text-blue-400"
                                        />
                                    </div>
                                </Th>
                                <Th>
                                    <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort(company.id, 'regular_hours')">
                                        <span>Regular Hours</span>
                                        <SvgIcon 
                                            v-if="getSortDirection(company.id, 'regular_hours')" 
                                            :name="getSortDirection(company.id, 'regular_hours') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                            size="sm" 
                                            class="text-blue-600 dark:text-blue-400"
                                        />
                                    </div>
                                </Th>
                                <Th v-for="role in company.roles" :key="role">
                                    <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort(company.id, `role_regular_hours_${role}`)">
                                        <span>{{ role }} Hours</span>
                                        <SvgIcon 
                                            v-if="getSortDirection(company.id, `role_regular_hours_${role}`)" 
                                            :name="getSortDirection(company.id, `role_regular_hours_${role}`) === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                            size="sm" 
                                            class="text-blue-600 dark:text-blue-400"
                                        />
                                    </div>
                                </Th>
                                <Th>
                                    <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort(company.id, 'overtime_hours')">
                                        <span>Overtime Hours</span>
                                        <SvgIcon 
                                            v-if="getSortDirection(company.id, 'overtime_hours')" 
                                            :name="getSortDirection(company.id, 'overtime_hours') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                            size="sm" 
                                            class="text-blue-600 dark:text-blue-400"
                                        />
                                    </div>
                                </Th>
                                <Th v-for="role in company.roles" :key="role">
                                    <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort(company.id, `role_overtime_hours_${role}`)">
                                        <span>{{ role }} Overtime Hours</span>
                                        <SvgIcon 
                                            v-if="getSortDirection(company.id, `role_overtime_hours_${role}`)" 
                                            :name="getSortDirection(company.id, `role_overtime_hours_${role}`) === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                            size="sm" 
                                            class="text-blue-600 dark:text-blue-400"
                                        />
                                    </div>
                                </Th>
                                <Th>
                                    <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort(company.id, 'total_hours')">
                                        <span>Total Hours</span>
                                        <SvgIcon 
                                            v-if="getSortDirection(company.id, 'total_hours')" 
                                            :name="getSortDirection(company.id, 'total_hours') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                            size="sm" 
                                            class="text-blue-600 dark:text-blue-400"
                                        />
                                    </div>
                                </Th>
                                <Th>
                                    <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort(company.id, 'net_payroll')">
                                        <span>Net Payroll</span>
                                        <SvgIcon 
                                            v-if="getSortDirection(company.id, 'net_payroll')" 
                                            :name="getSortDirection(company.id, 'net_payroll') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                            size="sm" 
                                            class="text-blue-600 dark:text-blue-400"
                                        />
                                    </div>
                                </Th>
                                <Th>
                                    <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort(company.id, 'tips')">
                                        <span>Tips</span>
                                        <SvgIcon 
                                            v-if="getSortDirection(company.id, 'tips')" 
                                            :name="getSortDirection(company.id, 'tips') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                            size="sm" 
                                            class="text-blue-600 dark:text-blue-400"
                                        />
                                    </div>
                                </Th>
                                <Th>
                                    <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort(company.id, 'mwa_amount')">
                                        <span>MWA Amount</span>
                                        <SvgIcon 
                                            v-if="getSortDirection(company.id, 'mwa_amount')" 
                                            :name="getSortDirection(company.id, 'mwa_amount') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                            size="sm" 
                                            class="text-blue-600 dark:text-blue-400"
                                        />
                                    </div>
                                </Th>
                                <Th>
                                    <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort(company.id, 'tips_due')">
                                        <span>Tips Due</span>
                                        <SvgIcon 
                                            v-if="getSortDirection(company.id, 'tips_due')" 
                                            :name="getSortDirection(company.id, 'tips_due') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                            size="sm" 
                                            class="text-blue-600 dark:text-blue-400"
                                        />
                                    </div>
                                </Th>
                                <Th>
                                    <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort(company.id, 'mileage_due')">
                                        <span>Mileage Due</span>
                                        <SvgIcon 
                                            v-if="getSortDirection(company.id, 'mileage_due')" 
                                            :name="getSortDirection(company.id, 'mileage_due') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                            size="sm" 
                                            class="text-blue-600 dark:text-blue-400"
                                        />
                                    </div>
                                </Th>
                                <Th>
                                    <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort(company.id, 'gross')">
                                        <span>Gross</span>
                                        <SvgIcon 
                                            v-if="getSortDirection(company.id, 'gross')" 
                                            :name="getSortDirection(company.id, 'gross') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                            size="sm" 
                                            class="text-blue-600 dark:text-blue-400"
                                        />
                                    </div>
                                </Th>
                                <Th>
                                    <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort(company.id, 'hr_pay')">
                                        <span>HR Pay</span>
                                        <SvgIcon 
                                            v-if="getSortDirection(company.id, 'hr_pay')" 
                                            :name="getSortDirection(company.id, 'hr_pay') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                            size="sm" 
                                            class="text-blue-600 dark:text-blue-400"
                                        />
                                    </div>
                                </Th>
                                <Th>
                                    <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort(company.id, 'payroll')">
                                        <span>Payroll</span>
                                        <SvgIcon 
                                            v-if="getSortDirection(company.id, 'payroll')" 
                                            :name="getSortDirection(company.id, 'payroll') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                            size="sm" 
                                            class="text-blue-600 dark:text-blue-400"
                                        />
                                    </div>
                                </Th>
                                <Th v-if="!isDC">
                                    <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort(company.id, 'check')">
                                        <span>Check</span>
                                        <SvgIcon 
                                            v-if="getSortDirection(company.id, 'check')" 
                                            :name="getSortDirection(company.id, 'check') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                            size="sm" 
                                            class="text-blue-600 dark:text-blue-400"
                                        />
                                    </div>
                                </Th>
                                <Th v-if="!isDC">
                                    <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort(company.id, 'instant')">
                                        <span>Instant</span>
                                        <SvgIcon 
                                            v-if="getSortDirection(company.id, 'instant')" 
                                            :name="getSortDirection(company.id, 'instant') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                            size="sm" 
                                            class="text-blue-600 dark:text-blue-400"
                                        />
                                    </div>
                                </Th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 !z-[100]" v-if="getSortedLabourData(company) && getSortedLabourData(company).length > 0">
                        <!-- Loop through each labor record directly -->
                        <tr 
                            v-for="(item, index) in getSortedLabourData(company)" 
                            :key="`${item.employee_id}-${item.role_id}-${index}`"
                            class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                            @dblclick="navigateToEmployee(item)"
                        >
                            <Td color="secondary" class="md:sticky md:left-0 bg-white dark:bg-gray-900 !z-[100] ">{{ index + 1 }}</Td>
                            <Td  
                                color="secondary"
                                class="border-r-2 border-gray-300 dark:border-gray-600 md:sticky md:left-13 bg-white dark:bg-gray-900 !z-[100]"
                            >
                                {{ item.employee?.employee_id }}
                            </Td>
                            <Td  
                                weight="medium"
                                color="primary"
                                class="border-r-2 border-gray-300 dark:border-gray-600 max-w-96 truncate md:sticky md:left-36 bg-white dark:bg-gray-900 !z-[100]"
                                :title="item.employee?.pos_name"
                            >
                                {{ item.employee?.pos_name }}
                            </Td>
                            <Td color="secondary">${{ formatNumber(item.rate ?? 0) }}</Td>
                            <Td color="secondary" v-for="role in company.roles" :key="role">{{ formatNumber(item.roles[role]?.rate ?? 0) }} hrs</Td>
                            <Td color="secondary">{{ formatNumber(item.regular_hours) }} hrs</Td>
                            <Td color="secondary" v-for="role in company.roles" :key="role">{{ formatNumber(item.roles[role]?.regular_hours ?? 0) }} hrs</Td>
                            <Td color="secondary">{{ formatNumber(item.overtime_hours) }} hrs</Td>
                            <Td color="secondary" v-for="role in company.roles" :key="role">{{ formatNumber(item.roles[role]?.overtime_hours ?? 0) }} hrs</Td>
                            <Td color="secondary">{{ formatNumber(item.total_hours) }} hrs</Td>
                            <Td weight="medium" color="primary">${{ formatNumber(item.gross_pay) }}</Td>
                            <Td color="secondary">${{ formatNumber(item.tips) }}</Td>
                            <Td color="secondary">${{ formatNumber(item.mwa_amount) }}</Td>
                            <Td color="secondary">${{ formatNumber(item.tips_due) }}</Td>
                            <Td color="secondary">${{ formatNumber(item.mileage_due) }}</Td>
                            <Td weight="medium" color="primary">${{ formatNumber(item.total_earnings) }}</Td>
                            <Td weight="medium" color="primary">${{ formatNumber(item.hr_pay) }}</Td>
                            <Td weight="medium" color="primary" :customClass="item.min_wage_due > 0 ? '!text-red-500' : ''"
                                :title="item.min_wage_due > 0 ? 'Minimum wage due: $' + formatNumber(item.min_wage_due)+'\nMinimum wage: $'+ formatNumber(item.min_wage_hourly) +' X ' + formatNumber(item.total_hours) +' = $'+formatNumber(item.min_wage_weekly): ''"
                            >${{ formatNumber(item.payroll_methods) }}</Td>
                            <Td v-if="!isDC"
                                weight="medium" 
                                color="primary"
                                class="cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors relative"
                            >
                                <span >${{ formatNumber(item.check_methods) }}</span>
                            </Td>
                            <Td v-if="!isDC" weight="medium" color="primary">${{ formatNumber(item.instant_methods) }}</Td>
                        </tr>
                        
                    </tbody>
                    <tbody class="bg-white dark:bg-gray-800" v-else-if="!company.labour_data || company.labour_data.length === 0">
                        <tr>
                            <td :colspan="(isDC ? 14 : 16) + company.roles.length">
                                <NoData
                                    icon="files"
                                    icon-color="blue"
                                    title="No Labor Data Found"
                                    message="There is no labor data for this company in the selected period."
                                    size="sm"
                                    icon-size="lg"
                                    :show-action="false"
                                />
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <Td class="md:sticky md:left-0 bg-white dark:bg-gray-900 z-10" colspan="3"> COMPANY TOTAL</Td>
                            <Td :colspan="1 + company.roles.length" weight="bold" color="primary"></Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(getCompanyTotals(company.labour_data).regularHours) }} hrs</Td>
                            <Td weight="bold" color="secondary" v-for="role in company.roles" :key="role">{{ formatNumber(getCompanyTotals(company.labour_data).roles[role]?.regular_hours ?? 0) }} hrs</Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(getCompanyTotals(company.labour_data).overtimeHours) }} hrs</Td>
                            <Td weight="bold" color="secondary" v-for="role in company.roles" :key="role">{{ formatNumber(getCompanyTotals(company.labour_data).roles[role]?.overtime_hours ?? 0) }} hrs</Td>
                            <Td weight="bold" color="primary">{{ formatNumber(getCompanyTotals(company.labour_data).totalHours) }} hrs</Td>
                            <Td weight="bold" color="primary">${{ formatNumber(getCompanyTotals(company.labour_data).grossPay) }}</Td>
                            <Td weight="bold" color="secondary">${{ formatNumber(getCompanyTotals(company.labour_data).tips) }}</Td>
                            <Td weight="bold" color="secondary">${{ formatNumber(getCompanyTotals(company.labour_data).mwa_amount) }}</Td>
                            <Td weight="bold" color="secondary">${{ formatNumber(getCompanyTotals(company.labour_data).tips_due) }}</Td>
                            <Td weight="bold" color="secondary">${{ formatNumber(getCompanyTotals(company.labour_data).mileage_due) }}</Td>
                            <Td weight="bold" color="primary">${{ formatNumber(getCompanyTotals(company.labour_data).totalEarnings) }}</Td>
                            <Td>-</Td>
                            <Td weight="bold" color="primary">${{ formatNumber(getCompanyTotals(company.labour_data).payrollMethods) }}</Td>
                            <Td v-if="!isDC" weight="bold" color="primary">${{ formatNumber(getCompanyTotals(company.labour_data).checkMethods) }}</Td>
                            <Td v-if="!isDC" weight="bold" color="primary">${{ formatNumber(getCompanyTotals(company.labour_data).instantMethods) }}</Td>
                        </tr>
                    </tfoot>
                </table>  
                </div>
            </Panel>
            </template>

        </div>

        <!-- No Data Panel -->
        <Panel v-else>
            <div class="py-12">
                <NoData
                    icon="files"
                    icon-color="blue"
                    title="No Payroll Data Found"
                    message="There is no payroll data for the selected period. Please select a different date range or ensure data has been entered."
                    size="lg"
                    icon-size="xl"
                    :show-action="true"
                    action-text="Apply Filters"
                    action-icon="refresh"
                    @action="applyFilters"
                />
            </div>
        </Panel>

        <!-- Grand Totals Panel -->
        <Panel v-if="payrollData.length > 0" class="mt-6">
            <h3 class="text-center md:text-left !text-sm !md:text-xl font-bold text-gray-900 dark:text-white  !mb-0">Grand Totals (All Companies)</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <Th>Regular Hours</Th>
                            <Th>Overtime Hours</Th>
                            <Th>Total Hours</Th>
                            <Th>Net Payroll</Th>
                            <Th>Tips</Th>
                            <Th>MWA Amount</Th>
                            <Th>Tips Due</Th>
                            <Th>Mileage Due</Th>
                            <Th>Gross</Th>
                            <Th>Payroll</Th>
                            <Th v-if="!isDC">Check</Th>
                            <Th v-if="!isDC">Instant</Th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800">
                        <tr class="bg-blue-50 dark:bg-blue-900/20">
                            <Td weight="bold" color="primary">{{ formatNumber(totals.regularHours) }} hrs</Td>
                            <Td weight="bold" color="primary">{{ formatNumber(totals.overtimeHours) }} hrs</Td>
                            <Td weight="bold" color="primary">{{ formatNumber(totals.totalHours) }} hrs</Td>
                            <Td weight="bold" color="primary">${{ formatNumber(totals.grossPay) }}</Td>
                            <Td weight="bold" color="primary">${{ formatNumber(totals.tips) }}</Td>
                            <Td weight="bold" color="primary">${{ formatNumber(totals.mwa_amount) }}</Td>
                            <Td weight="bold" color="primary">${{ formatNumber(totals.tips_due) }}</Td>
                            <Td weight="bold" color="primary">${{ formatNumber(totals.mileage_due) }}</Td>
                            <Td weight="bold" color="primary">${{ formatNumber(totals.totalEarnings) }}</Td>
                            <Td weight="bold" color="primary">${{ formatNumber(totals.payrollMethods) }}</Td>
                            <Td v-if="!isDC" weight="bold" color="primary">${{ formatNumber(totals.checkMethods) }}</Td>
                            <Td v-if="!isDC" weight="bold" color="primary">${{ formatNumber(totals.instantMethods) }}</Td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-4 text-sm text-gray-600 dark:text-gray-400 text-center">
                Period: {{ currentPeriodLabel }} | Total Companies: {{ payrollData.length }}
            </div>
        </Panel>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import MultiStatCard from '@/components/ui/multi-stat-card.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import NoData from '@/components/ui/no-data.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { formatNumber } from '@/utils/number'
import { applyPayPeriodDefaults, savePayPeriodSelection } from '@/utils/payPeriodSelection'
import { useAuthStore } from '@/stores/auth'


const message = useMessage()
const authStore = useAuthStore()
const filters = ref({
    selectedYear: 2026,
    selectedPeriod: '',
    company: '',
    status: ''
})

const isDC = computed(() => {
    return authStore.isDC || false
})

// Available Years (current year and past 5 years)
const availableYears = computed(() => {
    const currentYear = new Date().getFullYear()
    const years = []
    for (let i = 0; i <= 5; i++) {
        years.push(currentYear - i)
    }
    return years
})
const payrollData = ref([])
const loading = ref(false)
const exportLoading = ref(false)

// Sorting state for each company
const sortState = ref({}) // { companyId: { field: 'fieldName', direction: 'asc'|'desc' } }
// Generate bi-weekly periods (Monday to Sunday) for the selected year
const availablePeriods = computed(() => {
    const year = filters.value.selectedYear
    const periods = []
    
    // Generate bi-weekly periods for the entire year using UTC to avoid timezone issues
    let startDate = new Date(Date.UTC(year, 0, 1)) // January 1st UTC
    
    // Adjust to start on Monday (Monday = 1, Sunday = 0)
    const dayOfWeek = startDate.getUTCDay()
    let daysToMonday = 0;
    if(isDC.value){
        daysToMonday = dayOfWeek === 0 ? 0 : 1 - dayOfWeek // If Sunday, go back 6 days; otherwise go to previous/current Monday
    }else{
        daysToMonday = dayOfWeek === 0 ? -6 : 1 - dayOfWeek - 7 // If Sunday, go back 6 days; otherwise go to previous/current Monday
    }
    startDate.setUTCDate(startDate.getUTCDate() + daysToMonday)
    
    let periodNumber = 1
    
    while (startDate.getUTCFullYear() === year || periodNumber === 1) {
        const endDate = new Date(startDate)
        endDate.setUTCDate(endDate.getUTCDate() + 13) // 14 days (Monday to Sunday of second week)
        
        // Stop if we've gone too far into next year
        if (endDate.getUTCFullYear() > year && endDate.getUTCMonth() > 0) {
            break
        }
        
        const formatDate = (date) => {
            const month = String(date.getUTCMonth() + 1).padStart(2, '0')
            const day = String(date.getUTCDate()).padStart(2, '0')
            const year = date.getUTCFullYear()
            return `${month}-${day}-${year}`
        }
        
        const label = `${formatDate(startDate)} To ${formatDate(endDate)}`
        const value = `${startDate.toISOString()} to ${endDate.toISOString()}`
        
        periods.push({ label, value, startDate: new Date(startDate), endDate: new Date(endDate) })
        
        // Move to next bi-weekly period (14 days)
        startDate.setUTCDate(startDate.getUTCDate() + 14)
        periodNumber++
    }
    
    return periods
})

const DataSummaryStats = computed(() => {
    return [
        {
            label: 'Total Employees',
            value: summaryStats.value.totalEmployees
        },
        {
            label: 'Toal Regular Hours',
            value: totals.value.regularHours
        },
        {
            label: 'Total Overtime Hours',
            value: totals.value.overtimeHours
        },
        {
            label: 'Total Hours',
            value: totals.value.totalHours
        },
        { 
            label: 'Net Payroll',
            value: totals.value.grossPay,
            type: 'amount'
        },
        {
            label: 'Total tips',
            value: totals.value.tips,
            type: 'amount'
        },
        {
            label: 'Total MWA Amount',
            value: totals.value.mwa_amount,
            type: 'amount'
        },
        {
            label: 'Total tips due',
            value: totals.value.tips_due,
            type: 'amount'
        },
        {
            label: 'Total mileage due',
            value: totals.value.mileage_due,
            type: 'amount'
        },
        {
            label: 'Gross',
            value: totals.value.totalEarnings,
            type: 'amount'
        },
    ]
})
// Current period label for display
const currentPeriodLabel = computed(() => {
    if (!filters.value.selectedPeriod) return 'No period selected'
    const period = availablePeriods.value.find(p => p.value === filters.value.selectedPeriod)
    return period ? period.label : 'No period selected'
})


// Get totals for a specific company
const getCompanyTotals = (laborData) => {
    if (!laborData || laborData.length === 0) {
        return {
            totalHours: 0,
            regularHours: 0,
            overtimeHours: 0,
            grossPay: 0,
            tips: 0,
            mwa_amount: 0,
            mileage: 0,
            tips_due: 0,
            mileage_due: 0,
            totalEarnings: 0,
            payrollMethods: 0,
            checkMethods: 0,
            instantMethods: 0,
            roles: {}
        }
    }
    
    return laborData.reduce((acc, item) => {
        acc.totalHours += parseFloat(item.total_hours ?? 0)
        acc.regularHours += parseFloat(item.regular_hours ?? 0)
        
        acc.overtimeHours += parseFloat(item.overtime_hours ?? 0)
        acc.grossPay += parseFloat(item.gross_pay ?? 0)
        acc.tips += parseFloat(item.tips ?? 0)
        acc.mwa_amount += parseFloat(item.mwa_amount ?? 0)
        acc.mileage += parseFloat(item.mileage_excess ?? 0)
        acc.tips_due += parseFloat(item.tips_due ?? 0)
        acc.mileage_due += parseFloat(item.mileage_due ?? 0)
        acc.totalEarnings += parseFloat(item.total_earnings ?? 0)
        acc.payrollMethods += parseFloat(item.payroll_methods ?? 0)
        acc.checkMethods += parseFloat(item.check_methods ?? 0)
        acc.instantMethods += parseFloat(item.instant_methods ?? 0)
        if(item.roles){
            Object.keys(item.roles).forEach(role => {
                if(!acc.roles[role]){
                    acc.roles[role] = {
                        regular_hours: 0,
                        overtime_hours: 0
                    }
                }
                acc.roles[role].regular_hours += parseFloat(item.roles[role]?.regular_hours ?? 0)
                acc.roles[role].overtime_hours += parseFloat(item.roles[role]?.overtime_hours ?? 0)
            })
        }
        return acc
    }, {
        totalHours: 0,
        regularHours: 0,
        overtimeHours: 0,
        grossPay: 0,
        tips: 0,
        mwa_amount: 0,
        mileage: 0,
        tips_due: 0,
        mileage_due: 0,
        totalEarnings: 0,
        payrollMethods: 0,
        checkMethods: 0,
        instantMethods: 0,
        roles: {}
    })
}

const totals = computed(() => {
    return payrollData.value.reduce((acc, company) => {
        if (company.labour_data && company.labour_data.length > 0) {
            company.labour_data.forEach(item => {
                acc.totalHours += parseFloat(item.total_hours ?? 0)
                acc.regularHours += parseFloat(item.regular_hours ?? 0)
                acc.overtimeHours += parseFloat(item.overtime_hours ?? 0)
                acc.grossPay += parseFloat(item.gross_pay ?? 0)
                acc.tips += parseFloat(item.tips ?? 0)
                acc.mwa_amount += parseFloat(item.mwa_amount ?? 0)
                acc.mileage += parseFloat(item.mileage_excess ?? 0)
                acc.tips_due += parseFloat(item.tips_due ?? 0)
                acc.mileage_due += parseFloat(item.mileage_due ?? 0)
                acc.totalEarnings += parseFloat(item.total_earnings ?? 0)
                acc.payrollMethods += parseFloat(item.payroll_methods ?? 0)
                acc.checkMethods += parseFloat(item.check_methods ?? 0)
                acc.instantMethods += parseFloat(item.instant_methods ?? 0)
                if(item.roles){
                    Object.keys(item.roles).forEach(role => {
                        if(!acc.roles[role]){
                            acc.roles[role] = {
                                regular_hours: 0,
                                overtime_hours: 0
                            }
                        }
                    })
                    Object.keys(item.roles).forEach(role => {
                        acc.roles[role].regular_hours += parseFloat(item.roles[role]?.regular_hours ?? 0)
                        acc.roles[role].overtime_hours += parseFloat(item.roles[role]?.overtime_hours ?? 0)
                        acc.regularHours += parseFloat(item.roles[role]?.regular_hours ?? 0)
                        acc.overtimeHours += parseFloat(item.roles[role]?.overtime_hours ?? 0)
                    })
                }
            })
        }
        return acc
    }, {
        totalHours: 0,
        regularHours: 0,
        overtimeHours: 0,
        grossPay: 0,
        tips: 0,
        mwa_amount: 0,
        mileage: 0,
        tips_due: 0,
        mileage_due: 0,
        totalEarnings: 0,
        payrollMethods: 0,
        checkMethods: 0,
        instantMethods: 0,
        roles: {}
    })
})

const summaryStats = computed(() => {
    // Count unique employees across all companies
    const uniqueEmployees = new Set()
    payrollData.value.forEach(company => {
        if (company.labour_data && company.labour_data.length > 0) {
            company.labour_data.forEach(item => {
                uniqueEmployees.add(item.employee_id)
            })
        }
    })
    
    return {
        totalEmployees: uniqueEmployees.size,
        totalHours: totals.value.totalHours,
        grossPayroll: totals.value.totalEarnings,
        avgPayRate: totals.value.totalHours > 0 ? totals.value.totalEarnings / totals.value.totalHours : 0
    }
})

const paymentMethodsStats = computed(() => {
    return [
        {
            label: 'Payroll',
            value: totals.value.payrollMethods,
            type: 'amount'
        },
        ...(isDC.value ? [] : [{
            label: 'Check',
            value: totals.value.checkMethods,
            type: 'amount'
        }]),
        ...(isDC.value ? [] : [{
            label: 'Instant',
            value: totals.value.instantMethods,
            type: 'amount'
        }]),
    ]
})

// Methods


const onYearChange = () => {
    filters.value.selectedPeriod = ''
    if (availablePeriods.value.length > 0) {
        filters.value.selectedPeriod = availablePeriods.value[0].value
    }
    savePayPeriodSelection(filters.value.selectedYear, filters.value.selectedPeriod)
    applyFilters()
}

const onPeriodChange = () => {
    savePayPeriodSelection(filters.value.selectedYear, filters.value.selectedPeriod)
    applyFilters()
}

const applyFilters = async () => {
    try {
        loading.value = true;
        const response = await useRequest('post', '/reports/payroll/labour', {
            start_date: filters.value.selectedPeriod.split('to')[0],
            end_date: filters.value.selectedPeriod.split('to')[1],
        })
        payrollData.value = response.data

        payrollData.value.forEach(company => {
            company.roles = [];
            company.labour_data.forEach(item => {
                company.roles =[...company.roles, ...(typeof item.roles === 'object' ? Object.keys(item.roles) : [])];
            })
            company.roles = Array.from(new Set(company.roles));
        })
        console.log(payrollData.value)
    } catch (error) {
        console.log(error)
        message.error(error.response?.data?.message)
    } finally{
        loading.value = false
    }
}


const exportReport = async () => {
    try {
        // Check if there's data to export
        if (!payrollData.value || payrollData.value.length === 0) {
            message.error('No data to export')
            return
        }

        exportLoading.value = true

        // Import XLSX library dynamically
        await import('xlsx').then((XLSX) => {
            // Prepare data for export
            const exportData = []
            
            // Add header information
            exportData.push(['Labour Report'])
            exportData.push(['Period:', currentPeriodLabel.value])
            exportData.push(['Generated:', new Date().toLocaleString()])
            exportData.push(['Total Companies:', payrollData.value.length])
            exportData.push([]) // Empty row

            const allExportRoles = Array.from(new Set(
                payrollData.value.flatMap(company => company.roles || [])
            )).sort()
            
            // Process each company
            payrollData.value.forEach(company => {
                if (!company.labour_data || company.labour_data.length === 0) return
                
                // Add company header
                exportData.push([])
                exportData.push([`Company: ${company.name}`])
                exportData.push([`Store Number: ${company.store_number} | Contact: ${company.contact_person}`])
                exportData.push([])
                
                // Build dynamic column headers based on company roles
                const headerRow = [
                    'Sr. No.',
                    'Store',
                    'Employee ID',
                    'Employee Name',
                    'Pay Rate'
                ]
                
                // Add role rate columns
                allExportRoles.forEach(role => {
                    headerRow.push(`${role} Rate`)
                })
                
                headerRow.push('Regular Hours')
                
                // Add role regular hours columns
                allExportRoles.forEach(role => {
                    headerRow.push(`${role} Hours`)
                })
                
                headerRow.push('Overtime Hours')
                
                // Add role overtime hours columns
                allExportRoles.forEach(role => {
                    headerRow.push(`${role} Overtime Hours`)
                })
                
                headerRow.push(
                    'Total Hours',
                    'Net Payroll',
                    'Tips',
                    'MWA Amount',
                    'Tips Due',
                    'Mileage Due',
                    'Gross',
                    'HR Pay',
                    'Payroll',
                    ...(isDC.value ? [] : ['Check']),
                    ...(isDC.value ? [] : ['Instant'])
                )
                
                exportData.push(headerRow)
                
                // Add each employee row
                company.labour_data.forEach((item, index) => {
                    const row = [
                        index + 1,
                        company.store_number + ' - ' + company.name,
                        item.employee?.employee_id || '',
                        item.employee?.pos_name || '',
                        parseFloat(item.rate || 0)
                    ]
                    
                    // Add role rates
                    allExportRoles.forEach(role => {
                        row.push(parseFloat(item.roles?.[role]?.rate || 0))
                    })
                    
                    row.push(parseFloat(item.regular_hours || 0))
                    
                    // Add role regular hours
                    allExportRoles.forEach(role => {
                        row.push(parseFloat(item.roles?.[role]?.regular_hours || 0))
                    })
                    
                    row.push(parseFloat(item.overtime_hours || 0))
                    
                    // Add role overtime hours
                    allExportRoles.forEach(role => {
                        row.push(parseFloat(item.roles?.[role]?.overtime_hours || 0))
                    })
                    
                    row.push(
                        parseFloat(item.total_hours || 0),
                        parseFloat(item.gross_pay || 0),
                        parseFloat(item.tips || 0),
                        parseFloat(item.mwa_amount || 0),
                        parseFloat(item.tips_due || 0),
                        parseFloat(item.mileage_due || 0),
                        parseFloat(item.total_earnings || 0),
                        parseFloat(item.hr_pay || 0),
                        parseFloat(item.payroll_methods || 0),
                        ...(isDC.value ? [] : [parseFloat(item.check_methods || 0)]),
                        ...(isDC.value ? [] : [parseFloat(item.instant_methods || 0)])
                    )
                    
                    exportData.push(row)
                })
                
                // Add company total row
                const companyTotals = getCompanyTotals(company.labour_data)
                const totalRow = [
                    '', (company.store_number + ' - ' + company.name), '','',
                    'COMPANY TOTAL'
                ]
                
                // Skip role rate columns in totals
                for (let i = 0; i < allExportRoles.length; i++) {
                    totalRow.push('')
                }
                
                totalRow.push(companyTotals.regularHours)
                
                // Add role regular hours totals
                allExportRoles.forEach(role => {
                    totalRow.push(companyTotals.roles?.[role]?.regular_hours || 0)
                })
                
                totalRow.push(companyTotals.overtimeHours)
                
                // Add role overtime hours totals
                allExportRoles.forEach(role => {
                    totalRow.push(companyTotals.roles?.[role]?.overtime_hours || 0)
                })
                
                totalRow.push(
                    companyTotals.totalHours,
                    companyTotals.grossPay,
                    companyTotals.tips,
                    companyTotals.mwa_amount,
                    companyTotals.tips_due,
                    companyTotals.mileage_due,
                    companyTotals.totalEarnings,
                    '-',
                    companyTotals.payrollMethods,
                    ...(isDC.value ? [] : [companyTotals.checkMethods]),
                    ...(isDC.value ? [] : [companyTotals.instantMethods])
                )
                
                exportData.push(totalRow)
                exportData.push([]) // Empty row after company
            })
            
            // Add grand totals section
            exportData.push([])
            exportData.push(['GRAND TOTALS (All Companies)'])
            exportData.push([])
            
            const grandTotalHeaders = [
                'Regular Hours',
                'Overtime Hours',
                'Total Hours',
                'Net Payroll',
                'Tips',
                'MWA Amount',
                'Tips Due',
                'Mileage Due',
                'Gross',
                'Payroll',
                ...(isDC.value ? [] : ['Check']),
                ...(isDC.value ? [] : ['Instant'])
            ]
            
            const grandTotalValues = [
                totals.value.regularHours,
                totals.value.overtimeHours,
                totals.value.totalHours,
                totals.value.grossPay,
                totals.value.tips,
                totals.value.mwa_amount,
                totals.value.tips_due,
                totals.value.mileage_due,
                totals.value.totalEarnings,
                totals.value.payrollMethods,
                ...(isDC.value ? [] : [totals.value.checkMethods]),
                ...(isDC.value ? [] : [totals.value.instantMethods]),
            ]
            
            exportData.push(grandTotalHeaders)
            exportData.push(grandTotalValues)
            
            // Create worksheet
            const ws = XLSX.utils.aoa_to_sheet(exportData)
            
            // Auto-size columns (basic implementation)
            const colWidths = []
            exportData.forEach(row => {
                row.forEach((cell, colIndex) => {
                    const cellLength = cell ? cell.toString().length : 10
                    if (!colWidths[colIndex] || colWidths[colIndex] < cellLength) {
                        colWidths[colIndex] = Math.min(cellLength + 2, 30)
                    }
                })
            })
            
            ws['!cols'] = colWidths.map(width => ({ wch: width }))
            
            // Create workbook
            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'Labour Report')
            
            // Generate filename
            const filename = `Labour_Report_${filters.value.selectedYear}_${new Date().getTime()}.xlsx`
            
            // Save file
            XLSX.writeFile(wb, filename)
            
            message.success('Report exported successfully!')
        }).catch((error) => {
            console.error('Failed to load XLSX library:', error)
            message.error('Failed to export report. Please try again.')
        }).finally(() => {
            exportLoading.value = false
        })
    } catch (error) {
        console.error('Export error:', error)
        message.error('An error occurred while exporting the report')
        exportLoading.value = false
    }
}

const setDefaultPeriod = () => {
    applyPayPeriodDefaults(filters.value, {
        availableYears: availableYears.value,
        getAvailablePeriods: () => availablePeriods.value,
    })
}
onMounted(() => {
    setDefaultPeriod()
    applyFilters()
})

const navigateToEmployee = (item) => {
    if(item.employee && item.employee.employee_type == 'New'){
        window.open(`/onboarding/employee/new/${item.employee_id}`, '_blank')
    }else if(item.employee && item.employee.employee_type == 'Existing'){
        window.open(`/onboarding/employee/existing/${item.employee_id}`, '_blank')
    }else {
        window.open(`/employee/${item.employee_id}`, '_blank')
    }
}

// Sorting functions
const handleSort = (companyId, field) => {
    if (!sortState.value[companyId]) {
        sortState.value[companyId] = { field: null, direction: 'asc' }
    }
    
    const currentSort = sortState.value[companyId]
    
    // If clicking the same field, toggle direction
    if (currentSort.field === field) {
        currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc'
    } else {
        // New field, start with ascending
        currentSort.field = field
        currentSort.direction = 'asc'
    }
}

const getSortDirection = (companyId, field) => {
    const sort = sortState.value[companyId]
    if (!sort || sort.field !== field) return null
    return sort.direction
}

const getSortedLabourData = (company) => {
    if (!company.labour_data || company.labour_data.length === 0) {
        return []
    }
    
    const sort = sortState.value[company.id]
    if (!sort || !sort.field) {
        return company.labour_data
    }
    
    const sorted = [...company.labour_data]
    const field = sort.field
    const direction = sort.direction
    
    sorted.sort((a, b) => {
        let aValue, bValue
        
        // Handle different field types
        if (field === 'sr_no') {
            // This will be handled by index, but we'll sort by employee_id as fallback
            aValue = a.employee_id || 0
            bValue = b.employee_id || 0
        } else if (field === 'employee_id') {
            aValue = a.employee?.employee_id || ''
            bValue = b.employee?.employee_id || ''
        } else if (field === 'employee_name') {
            aValue = (a.employee?.pos_name || '').toLowerCase()
            bValue = (b.employee?.pos_name || '').toLowerCase()
        } else if (field.startsWith('role_rate_')) {
            const role = field.replace('role_rate_', '')
            aValue = parseFloat(a.roles?.[role]?.rate || 0)
            bValue = parseFloat(b.roles?.[role]?.rate || 0)
        } else if (field === 'pay_rate') {
            aValue = parseFloat(a.rate || 0)
            bValue = parseFloat(b.rate || 0)
        } else if (field === 'regular_hours') {
            aValue = parseFloat(a.regular_hours || 0)
            bValue = parseFloat(b.regular_hours || 0)
        } else if (field.startsWith('role_regular_hours_')) {
            const role = field.replace('role_regular_hours_', '')
            aValue = parseFloat(a.roles?.[role]?.regular_hours || 0)
            bValue = parseFloat(b.roles?.[role]?.regular_hours || 0)
        } else if (field === 'overtime_hours') {
            aValue = parseFloat(a.overtime_hours || 0)
            bValue = parseFloat(b.overtime_hours || 0)
        } else if (field.startsWith('role_overtime_hours_')) {
            const role = field.replace('role_overtime_hours_', '')
            aValue = parseFloat(a.roles?.[role]?.overtime_hours || 0)
            bValue = parseFloat(b.roles?.[role]?.overtime_hours || 0)
        } else if (field === 'total_hours') {
            aValue = parseFloat(a.total_hours || 0)
            bValue = parseFloat(b.total_hours || 0)
        } else if (field === 'net_payroll') {
            aValue = parseFloat(a.gross_pay || 0)
            bValue = parseFloat(b.gross_pay || 0)
        } else if (field === 'tips') {
            aValue = parseFloat(a.tips || 0)
            bValue = parseFloat(b.tips || 0)
        } else if (field === 'tips_due') {
            aValue = parseFloat(a.tips_due || 0)
            bValue = parseFloat(b.tips_due || 0)
        } else if (field === 'mileage_due') {
            aValue = parseFloat(a.mileage_due || 0)
            bValue = parseFloat(b.mileage_due || 0)
        } else if (field === 'gross') {
            aValue = parseFloat(a.total_earnings || 0)
            bValue = parseFloat(b.total_earnings || 0)
        } else if (field === 'hr_pay') {
            aValue = parseFloat(a.hr_pay || 0)
            bValue = parseFloat(b.hr_pay || 0)
        } else if (field === 'payroll') {
            aValue = parseFloat(a.payroll_methods || 0)
            bValue = parseFloat(b.payroll_methods || 0)
        } else if (field === 'check') {
            aValue = parseFloat(a.check_methods || 0)
            bValue = parseFloat(b.check_methods || 0)
        } else if (field === 'instant') {
            aValue = parseFloat(a.instant_methods || 0)
            bValue = parseFloat(b.instant_methods || 0)
        } else {
            // Default: try to get the value directly
            aValue = a[field] || ''
            bValue = b[field] || ''
        }
        
        // Compare values
        if (typeof aValue === 'string' && typeof bValue === 'string') {
            return direction === 'asc' 
                ? aValue.localeCompare(bValue)
                : bValue.localeCompare(aValue)
        } else {
            return direction === 'asc' 
                ? aValue - bValue
                : bValue - aValue
        }
    })
    
    return sorted
}
</script>
