<template>
    <div class="weekly-payroll-report p-2 bg-gray-50 dark:bg-gray-900">
        <!-- Header Section -->
        
        <!-- Filter Panel -->
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="!text-lg !md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                     Payroll Report
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    View and analyze payroll data for bi-weekly periods (Monday to Sunday)
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
                    <Button
                        v-if="!isReviewed && canUpdate && !isDC && reviewAvaible"
                        icon-left="check"
                        icon-size="sm"
                        variant="success"
                        size="sm"
                        @click="reviewPayroll"
                        :loading="reviewLoading"
                        :disabled="!payrollData || payrollData.length === 0"
                    >
                        Review Payroll
                    </Button>
                </div>
                
                <div class="flex items-center gap-2">
                    <!-- Review Status Badge -->
                    <div v-if="isReviewed && !isDC" class="flex items-center gap-2 px-3 py-1.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg text-sm font-medium">
                        <SvgIcon name="check-circle" size="sm" class="text-green-600 dark:text-green-400" />
                        <span>Reviewed</span>
                    </div>
                    <div v-else-if="payrollData && payrollData.length > 0 && !isDC" class="flex items-center gap-2 px-3 py-1.5 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-lg text-sm font-medium">
                        <SvgIcon name="alert" size="sm" class="text-yellow-600 dark:text-yellow-400" />
                        <span>Not Reviewed</span>
                    </div>
                    <div v-if="!reviewAvaible && !isDC" class="flex items-center gap-2 px-3 py-1.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg text-sm font-medium">
                        <SvgIcon name="alert" size="sm" class="text-red-600 dark:text-red-400" />
                        <span>Review Not Available</span>
                    </div>
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

        <!-- Data Table -->
        <Panel>
            <div class="overflow-x-auto max-h-[calc(100vh-100px)] relative">

                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900 sticky top-0 !z-[101]">
                        <tr>
                            <Th class="md:sticky md:left-0 bg-white dark:bg-gray-900 !z-[101]">
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('employee_id')">
                                    <span>Employee ID</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('employee_id')" 
                                        :name="getSortDirection('employee_id') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th class="md:sticky md:left-23 bg-white dark:bg-gray-900 !z-[101] w-[200px]">
                                <div class="flex items-center w-[200px] gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('employee_name')">
                                    <span>Employee Name</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('employee_name')" 
                                        :name="getSortDirection('employee_name') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th class="md:sticky md:left-80 bg-white dark:bg-gray-900 !z-[101]">Role</Th>
                            <Th>EOW</Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('regular_hours')">
                                    <span>Regular Hours</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('regular_hours')" 
                                        :name="getSortDirection('regular_hours') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('overtime_hours')">
                                    <span>Overtime Hours</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('overtime_hours')" 
                                        :name="getSortDirection('overtime_hours') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('total_hours')">
                                    <span>Total Hours</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('total_hours')" 
                                        :name="getSortDirection('total_hours') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>Pay Rate</Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('gross_pay')">
                                    <span>Net Payroll</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('gross_pay')" 
                                        :name="getSortDirection('gross_pay') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('tips')">
                                    <span>Tips</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('tips')" 
                                        :name="getSortDirection('tips') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('mwa_amount')">
                                    <span>MWA Amount</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('mwa_amount')" 
                                        :name="getSortDirection('mwa_amount') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('tips_due')">
                                    <span>Tips Due</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('tips_due')" 
                                        :name="getSortDirection('tips_due') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('mileage_due')">
                                    <span>Mileage Due</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('mileage_due')" 
                                        :name="getSortDirection('mileage_due') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('total_earnings')">
                                    <span>Gross</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('total_earnings')" 
                                        :name="getSortDirection('total_earnings') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th>HR Pay</Th>
                            <Th>MWA</Th>
                            <Th>New Pay</Th>
                            <Th>
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('payroll_methods')">
                                    <span>Payroll</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('payroll_methods')" 
                                        :name="getSortDirection('payroll_methods') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th v-if="!isDC">
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('check_methods')">
                                    <span>Check</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('check_methods')" 
                                        :name="getSortDirection('check_methods') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                            <Th v-if="!isDC">
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="handleSort('instant_methods')">
                                    <span>Instant</span>
                                    <SvgIcon 
                                        v-if="getSortDirection('instant_methods')" 
                                        :name="getSortDirection('instant_methods') === 'asc' ? 'chevron-up' : 'chevron-down'" 
                                        size="sm" 
                                        class="text-blue-600 dark:text-blue-400"
                                    />
                                </div>
                            </Th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 !z-[100]" v-if="sortedEmployeeEntries.length > 0">
                        <!-- Loop through employees -->
                        <template v-for="([employeeId, employeeRoles], empIndex) in sortedEmployeeEntries" :key="`${employeeId}-${empIndex}`">
                            <!-- Loop through roles for each employee -->
                            <template v-for="(roleItems, roleId) in employeeRoles" :key="`${employeeId}-${roleId}`">
                                <!-- Loop through week periods for each role -->
                                <tr 
                                    v-for="(item, itemIndex) in roleItems" 
                                    :key="`${employeeId}-${roleId}-${itemIndex}`"
                                    class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 !z-[100]"
                                    @dblclick="navigateToEmployee(item)"
                                >
                                    <!-- Only show employee info on first row of each employee -->
                                    <!-- :rowspan="Object.values(employeeRoles).flat().length" -->
                                    <Td  
                                        color="secondary"
                                        class="border-r-2 border-gray-300 dark:border-gray-600 md:sticky left-0 bg-white dark:bg-gray-900 !z-[100]"
                                    >
                                        {{ item.employee?.employee_id }}
                                    </Td>
                                    <!-- :rowspan="Object.values(employeeRoles).flat().length" -->
                                    <Td  
                                        weight="medium"
                                        color="primary"
                                        class="border-r-2 border-gray-300 w-[200px] max-w-[200px] dark:border-gray-600 max-w-96 truncate md:sticky left-23 bg-white dark:bg-gray-900 !z-[100]"
                                        :title="item.employee?.pos_name"
                                    >
                                        {{ item.employee?.pos_name }}
                                        
                                    </Td>
                                    
                                    <!-- Role column - show on first row of each role -->
                                    <!-- :rowspan="roleItems.length" -->
                                    <Td 
                                        color="secondary"
                                        class="border-r border-gray-300 dark:border-gray-600 md:sticky left-80 bg-white dark:bg-gray-900 !z-[100]"
                                    >
                                        {{ item.role?.name ? item.role?.code + ' - ' + item.role?.name : 'N/A' }}
                                    </Td>
                                    
                                    <!-- Week period data -->
                                    <Td color="secondary">{{ item.week_period.split(' - ')[1] }}</Td>
                                    <Td color="secondary">{{ formatNumber(item.regular_hours) }} hrs</Td>
                                    <Td color="secondary">{{ formatNumber(item.overtime_hours) }} hrs</Td>
                                    <Td color="secondary">
                                        <div class="flex flex-col items-center gap-1">
                                            <span> {{ formatNumber(item.total_hours) }}</span>
                                            <small class="text-xs text-gray-500" v-if="item.rate_type == 'Payroll Slab' && item.payroll_hours > 0 "> PH: {{ formatNumber(item.payroll_hours) }}</small>
                                        </div>
                                        </Td>
                                    <Td color="secondary">${{ formatNumber(item.employee_rate?.rate ?? 0) }}</Td>
                                    <Td weight="medium" color="primary">${{ formatNumber(item.gross_pay) }}</Td>
                                    <Td color="secondary">${{ formatNumber(item.tips) }}</Td>
                                    <Td color="secondary">${{ formatNumber(item.mwa_amount) }}</Td>
                                    <Td color="secondary">${{ formatNumber(item.tips_due) }}</Td>
                                    <Td color="secondary">${{ formatNumber(item.mileage_due) }}</Td>
                                    <Td weight="medium" color="primary">${{ formatNumber(item.total_earnings) }}</Td>
                                    <Td weight="medium" color="primary">${{ formatNumber(item.hr_pay) }}</Td>
                                    <Td weight="medium" color="primary" :customClass="item.min_wage_due > 0 ? '!text-red-500' : ''">${{ formatNumber(item.min_wage_due) }}</Td>
                                    <Td weight="medium" color="primary" :customClass="item.min_wage_due > 0 ? '!text-red-500' : ''">${{ formatNumber(parseFloat(item.min_wage_due) + parseFloat(item.payroll_methods)) }} </Td>
                                    <Td weight="medium" color="primary" :customClass="item.min_wage_due > 0 ? '!text-red-500' : ''"
                                        :title="renderMinWageTitle(item)"
                                    >${{ formatNumber(item.payroll_methods) }}</Td>
                                    <Td 
                                        v-if="!isDC"
                                        weight="medium" 
                                        color="primary"
                                        class="cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors relative"
                                        @dblclick.stop="canUpdate ? editCheckAmount(item) : null"
                                        :title="'Double-click to edit'"
                                    >
                                        <input
                                            v-if="reviewAvaible && editingItem && editingItem.employee_id === item.employee_id && editingItem.role_id === item.role_id && editingItem.week_period === item.week_period"
                                            v-model="item.check_methods"
                                            @input="changeCheckAmount(item, $event)"
                                            type="number"
                                            step="0.01"
                                            class="min-w-[100px]  w-full px-2 py-1 border border-blue-500 dark:border-blue-400 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            ref="editInput"
                                        />
                                        <span v-else  :class="{ 'text-green-500': item.check_edited }">${{ formatNumber(item.check_methods) }}</span>
                                    </Td>
                                    <Td weight="medium" v-if="!isDC" color="primary" :class="item.is_1099 ? '!text-red-600 !dark:text-red-400' : ''">${{ formatNumber(item.instant_methods) }}</Td>
                                </tr>
                                
                                <!-- Role Subtotal Row -->
                                <tr class="bg-gray-100 dark:bg-gray-700 font-semibold" v-if="Object.keys(employeeRoles).length > 1">
                                    <Td class="md:sticky left-0 bg-gray-100 dark:bg-gray-700 !z-[100]"></Td>
                                    <Td class="md:sticky left-23 bg-gray-100 dark:bg-gray-700 !z-[100] w-[200px]"></Td>
                                    <Td  weight="semibold" color="secondary" class="text-right md:sticky left-80 bg-gray-100 dark:bg-gray-700 !z-[100]">
                                        Role Total:
                                    </Td>
                                    <Td weight="semibold" color="secondary" ></Td>
                                    <Td weight="semibold" color="secondary">
                                        {{ formatNumber(roleItems.reduce((sum, i) => sum + parseFloat(i.regular_hours), 0)) }} hrs
                                    </Td>
                                    <Td weight="semibold" color="secondary">
                                        {{ formatNumber(roleItems.reduce((sum, i) => sum + parseFloat(i.overtime_hours), 0)) }} hrs
                                    </Td>
                                    <Td weight="semibold" color="primary">
                                        <div class="flex flex-col items-center gap-1">
                                            <span> {{ formatNumber(roleItems.reduce((sum, i) => sum + parseFloat(i.total_hours), 0)) }}</span>
                                            <small class="text-xs text-gray-500" v-if="roleItems.some(i => i.rate_type == 'Payroll Slab' && i.payroll_hours > 0) "> PH: {{ formatNumber(roleItems.reduce((sum, i) => sum + parseFloat(i.payroll_hours), 0)) }} hrs</small>
                                        </div>
                                        
                                    </Td>
                                    <Td>-</Td>
                                    <Td weight="semibold" color="primary">
                                        ${{ formatNumber(roleItems.reduce((sum, i) => sum + parseFloat(i.gross_pay), 0)) }}
                                    </Td>
                                    <Td weight="semibold" color="secondary">
                                        ${{ formatNumber(roleItems.reduce((sum, i) => sum + parseFloat(i.tips), 0)) }}
                                    </Td>
                                    <Td weight="semibold" color="secondary">
                                        ${{ formatNumber(roleItems.reduce((sum, i) => sum + parseFloat(i.mwa_amount), 0)) }}
                                    </Td>
                                    <Td weight="semibold" color="secondary">
                                        ${{ formatNumber(roleItems.reduce((sum, i) => sum + parseFloat(i.tips_due), 0)) }}
                                    </Td>
                                    <Td weight="semibold" color="secondary">
                                        ${{ formatNumber(roleItems.reduce((sum, i) => sum + parseFloat(i.mileage_due), 0)) }}
                                    </Td>
                                    <Td weight="semibold" color="primary">
                                        ${{ formatNumber(roleItems.reduce((sum, i) => sum + parseFloat(i.total_earnings), 0)) }}
                                    </Td>
                                    <Td>-</Td>
                                    <Td>-</Td>
                                    <Td>-</Td>
                                    <Td weight="semibold" color="primary">
                                        ${{ formatNumber(roleItems.reduce((sum, i) => sum + parseFloat(i.payroll_methods), 0)) }}
                                    </Td>
                                    <Td weight="semibold" v-if="!isDC" color="primary">
                                        ${{ formatNumber(roleItems.reduce((sum, i) => sum + parseFloat(i.check_methods), 0)) }}
                                    </Td>
                                    <Td weight="semibold" v-if="!isDC" color="primary">
                                        ${{ formatNumber(roleItems.reduce((sum, i) => sum + parseFloat(i.instant_methods), 0)) }}
                                    </Td>
                                </tr>
                            </template>
                            
                            <!-- Employee Total Row -->
                            <tr class="bg-blue-50 dark:bg-blue-900/20 font-bold border-b-2 border-blue-300 dark:border-blue-700">
                                <Td class="md:sticky left-0 bg-blue-50 dark:bg-blue-900/20 !z-[100]"></Td>
                                <Td class="md:sticky left-23 bg-blue-50 dark:bg-blue-900/20 !z-[100] w-[200px]"></Td>
                                <Td weight="bold" color="primary" class="text-right md:sticky left-80 bg-blue-50 dark:bg-blue-900/20 !z-[100]">
                                    Employee Total:
                                </Td>
                                <Td weight="bold" color="secondary" ></Td>
                                <Td weight="bold" color="secondary">
                                    {{ formatNumber(Object.values(employeeRoles).flat().reduce((sum, i) => sum + parseFloat(i.regular_hours), 0)) }} hrs
                                </Td>
                                <Td weight="bold" color="secondary">
                                    {{ formatNumber(Object.values(employeeRoles).flat().reduce((sum, i) => sum + parseFloat(i.overtime_hours), 0)) }} hrs
                                </Td>
                                <Td weight="bold" color="primary">
                                    <div class="flex flex-col items-center gap-1">
                                        <span> {{ formatNumber(Object.values(employeeRoles).flat().reduce((sum, i) => sum + parseFloat(i.total_hours), 0)) }}</span>
                                        <small class="text-xs text-gray-500" v-if="Object.values(employeeRoles).flat().some(i => i.rate_type == 'Payroll Slab' && i.payroll_hours > 0) "> PH: {{ formatNumber(Object.values(employeeRoles).flat().reduce((sum, i) => sum + parseFloat(i.payroll_hours), 0)) }} hrs</small>
                                    </div>
                                </Td>
                                <Td>-</Td>
                                <Td weight="bold" color="primary">
                                    ${{ formatNumber(Object.values(employeeRoles).flat().reduce((sum, i) => sum + parseFloat(i.gross_pay), 0)) }}
                                </Td>
                                <Td weight="bold" color="secondary">
                                    ${{ formatNumber(Object.values(employeeRoles).flat().reduce((sum, i) => sum + parseFloat(i.tips), 0)) }}
                                </Td>
                                <Td weight="bold" color="secondary">
                                    ${{ formatNumber(Object.values(employeeRoles).flat().reduce((sum, i) => sum + parseFloat(i.mwa_amount), 0)) }}
                                </Td>
                                <Td weight="bold" color="secondary">
                                    ${{ formatNumber(Object.values(employeeRoles).flat().reduce((sum, i) => sum + parseFloat(i.tips_due), 0)) }}
                                </Td>
                                <Td weight="bold" color="secondary">
                                    ${{ formatNumber(Object.values(employeeRoles).flat().reduce((sum, i) => sum + parseFloat(i.mileage_due), 0)) }}
                                </Td>
                                <Td weight="bold" color="primary">
                                    ${{ formatNumber(Object.values(employeeRoles).flat().reduce((sum, i) => sum + parseFloat(i.total_earnings), 0)) }}
                                </Td>
                                <Td weight="bold" color="primary">
                                   -
                                </Td>
                                <Td weight="bold" color="primary">
                                   -
                                </Td>
                                <Td weight="bold" color="primary">
                                   -
                                </Td>
                                <Td weight="bold" color="primary">
                                    ${{ formatNumber(Object.values(employeeRoles).flat().reduce((sum, i) => sum + parseFloat(i.payroll_methods), 0)) }}
                                </Td>
                                <Td weight="bold" v-if="!isDC" color="primary">
                                    ${{ formatNumber(Object.values(employeeRoles).flat().reduce((sum, i) => sum + parseFloat(i.check_methods), 0)) }}
                                </Td>
                                <Td weight="bold" v-if="!isDC" color="primary">
                                    ${{ formatNumber(Object.values(employeeRoles).flat().reduce((sum, i) => sum + parseFloat(i.instant_methods), 0)) }}
                                </Td>
                            </tr>
                            <tr>
                                <Td :colspan="isDC ? 13 : 17" weight="bold" color="primary" class="h-7">

                                </Td>
                            </tr>
                        </template>
                        
                    </tbody>
                    <tbody class="bg-white dark:bg-gray-800" v-else>
                        <tr>
                            <td :colspan="isDC ? 18 : 22">
                                <NoData
                                    icon="files"
                                    icon-color="blue"
                                    title="No Payroll Data Found"
                                    message="There is no payroll data for the selected period. Please select a different date range or ensure data has been entered."
                                    size="sm"
                                    icon-size="lg"
                                    :show-action="false"
                                    action-text="Apply Filters"
                                    action-icon="refresh"
                                    @action="applyFilters"
                                />
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <Td class="md:sticky left-0 bg-gray-50 dark:bg-gray-900 !z-[101]"></Td>
                            <Td class="md:sticky left-23 bg-gray-50 dark:bg-gray-900 !z-[101]"></Td>
                            <Td  weight="bold" color="primary" class="text-right md:sticky left-80 bg-gray-50 dark:bg-gray-900 !z-[101]">TOTALS</Td>
                            <Td class=""></Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.regularHours) }} hrs</Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.overtimeHours) }} hrs</Td>
                            <Td weight="bold" color="primary">
                                <div class="flex flex-col items-center gap-1">
                                    <span> {{ formatNumber(totals.totalHours) }}</span>
                                    <small class="text-xs text-gray-500" v-if="totals.payrollHours > 0 "> PH: {{ formatNumber(totals.payrollHours) }} hrs</small>
                                </div>
                            </Td>
                            <Td>-</Td>
                            <Td weight="bold" color="primary">${{ formatNumber(totals.grossPay) }}</Td>
                            <Td weight="bold" color="secondary">${{ formatNumber(totals.tips) }}</Td>
                            <Td weight="bold" color="secondary">${{ formatNumber(totals.mwa_amount) }}</Td>
                            <Td weight="bold" color="secondary">${{ formatNumber(totals.tips_due) }}</Td>
                            <Td weight="bold" color="secondary">${{ formatNumber(totals.mileage_due) }}</Td>
                            <Td weight="bold" color="primary">${{ formatNumber(totals.totalEarnings) }}</Td>
                            <Td>-</Td>
                            <Td>-</Td>
                            <Td>-</Td>
                            <Td weight="bold" color="primary">${{ formatNumber(totals.payrollMethods) }}</Td>
                            <Td weight="bold" v-if="!isDC" color="primary">${{ formatNumber(totals.checkMethods) }}</Td>
                            <Td weight="bold" v-if="!isDC" color="primary">${{ formatNumber(totals.instantMethods) }}</Td>
                        </tr>
                    </tfoot>
                </table>  
            </div> 

            <!-- Pagination -->
            <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Showing <span class="font-medium">{{ payrollData.length }}</span> records
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Period: {{ currentPeriodLabel }}
                </div>
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
import SvgIcon from '@/components/SvgIcon.vue'
import NoData from '@/components/ui/no-data.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { mmddyyyyToYmd } from '@/utils/date'
import { formatNumber } from '@/utils/number'
import { applyPayPeriodDefaults, savePayPeriodSelection } from '@/utils/payPeriodSelection'
import { usePermission } from '@/composables/usePermission'
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
const isReviewed = ref(false)
const payrollData = ref([])
const loading = ref(false)
const reviewLoading = ref(false)
const exportLoading = ref(false)
const minWageReview = ref(false)
const reviewAvaible = ref(false)
const { can } = usePermission()

const renderMinWageTitle = (item) => {
    if(item.tipped){
        return 'Minimum tipped wage due: $' + formatNumber(item.min_wage_due)+'\nMinimum tipped wage: $'+ formatNumber(item.min_wage_hourly) +' X ' + formatNumber(item.total_hours) +' = $'+formatNumber(item.min_wage_weekly)
    }else{
        return 'Minimum wage due: $' + formatNumber(item.min_wage_due)+'\nMinimum wage: $'+ formatNumber(item.min_wage_hourly) +' X ' + formatNumber(item.total_hours) +' = $'+formatNumber(item.min_wage_weekly)
    }
}

// Sorting state
const sortField = ref(null)
const sortDirection = ref('asc') // 'asc' or 'desc'

const canUpdate = computed(() => {
    return can('payroll-report', 'update')
})



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
            label: 'Total Regular Hours',
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
            label: 'Total Net Payroll',
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
            label: 'Total Gross',
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

// Helper function to calculate employee totals (matches Employee Total row calculation)
const calculateEmployeeTotal = (employeeRoles, field) => {
    // Use the same calculation as the Employee Total row in the template
    // Object.values(employeeRoles).flat().reduce((sum, i) => sum + parseFloat(i.field), 0)
    const allItems = Object.values(employeeRoles).flat()
    return allItems.reduce((sum, item) => {
        const value = parseFloat(item[field] ?? 0)
        return sum + value
    }, 0)
}

// Return unsorted grouped data
const groupedByEmployee = computed(() => {
    return payrollData.value.reduce((acc, record) => {
        record.forEach(item => {
            if (!acc[item.employee?.pos_name]) {
                acc[item.employee?.pos_name] = {}
            }``
            if (!acc[item.employee?.pos_name][item.role_id + '-' + item.employee?.employee_id]) {
                acc[item.employee?.pos_name][item.role_id + '-' + item.employee?.employee_id] = []
            }
            acc[item.employee?.pos_name][item.role_id + '-' + item.employee?.employee_id].push(item)
        })
        return acc
    }, {})
})

// Return sorted array of employee IDs with their data
const sortedEmployeeEntries = computed(() => {
    const currentSortField = sortField.value
    const currentSortDirection = sortDirection.value
    
    
    const grouped = groupedByEmployee.value
    const entries = Object.entries(grouped)
    
    
    if (currentSortField) {
        entries.sort((a, b) => {
            const [employeeIdA, employeeRolesA] = a
            const [employeeIdB, employeeRolesB] = b
            
            // Handle special cases for employee ID and name
            if (currentSortField === 'employee_id') {
                const firstItemA = Object.values(employeeRolesA).flat()[0]
                const firstItemB = Object.values(employeeRolesB).flat()[0]
                const valA = firstItemA?.employee?.employee_id || ''
                const valB = firstItemB?.employee?.employee_id || ''
                return currentSortDirection === 'asc' 
                    ? valA.localeCompare(valB)
                    : valB.localeCompare(valA)
            }
            
            if (currentSortField === 'employee_name') {
                const firstItemA = Object.values(employeeRolesA).flat()[0]
                const firstItemB = Object.values(employeeRolesB).flat()[0]
                const valA = firstItemA?.employee?.pos_name || ''
                const valB = firstItemB?.employee?.pos_name || ''
                return currentSortDirection === 'asc'
                    ? valA.localeCompare(valB)
                    : valB.localeCompare(valA)
            }
            
            // Numeric sorting based on Employee Total (same calculation as template)
            const totalA = calculateEmployeeTotal(employeeRolesA, currentSortField)
            const totalB = calculateEmployeeTotal(employeeRolesB, currentSortField)
            
            const diff = totalA - totalB
            return currentSortDirection === 'asc' ? diff : -diff
        })
        
    }
    return entries
})

// For backwards compatibility - convert back to object
const groupByEmployee = computed(() => {
    return Object.fromEntries(sortedEmployeeEntries.value)
})


const totals = computed(() => {
    return payrollData.value.reduce((acc, record) => {
        record.forEach(item => {
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
            acc.payrollHours += parseFloat(item.payroll_hours ?? 0)
        })
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
        payrollHours: 0
    })
})

const summaryStats = computed(() => {
    return {
        totalEmployees: Object.keys(groupByEmployee.value).length,
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
const handleSort = (field) => {
    
    if (sortField.value === field) {
        // Toggle direction if same field
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
    } else {
        // Set new field and default to ascending
        sortField.value = field
        sortDirection.value = 'asc'
    }
    
}

const getSortDirection = (field) => {
    if (sortField.value !== field) return null
    return sortDirection.value
}

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
        const response = await useRequest('post', '/reports/payroll/weekly', {
            start_date: filters.value.selectedPeriod.split('to')[0],
            end_date: filters.value.selectedPeriod.split('to')[1],
        })
        payrollData.value = response.data
        isReviewed.value = response.reviewed
        minWageReview.value = response.min_wage_review
        reviewAvaible.value = response.reviewAvaible
    } catch (error) {
        message.error(error.response?.data?.message)
    } finally{
        loading.value = false
    }
}


const editingItem = ref(null)
const editCheckAmount = (item) => {
    if(isReviewed.value){
        message.error('You are not allowed to edit the check amount because the payroll has been reviewed.')
        return
    }
    editingItem.value = item
}
const changeCheckAmount = (item, event) => {
    const totalEarnings = parseFloat(item.total_earnings)
    const remainingPay = totalEarnings - parseFloat(item.payroll_methods);
    item.check_methods = event.target.value ? parseFloat(event.target.value) : null;
    if(item.check_methods > remainingPay){
        item.check_methods = remainingPay
        item.instant_methods = 0;
    }else{
        item.instant_methods = item.check_methods ? remainingPay - item.check_methods : remainingPay;
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
            exportData.push(['Weekly Payroll Report'])
            exportData.push(['Period:', currentPeriodLabel.value])
            exportData.push(['Generated:', new Date().toLocaleString()])
            exportData.push([]) // Empty row
            
            // Add column headers
            exportData.push([
                'Employee ID',
                'Employee Name',
                'Role',
                'EOW',
                'Regular Hours',
                'Overtime Hours',
                'Total Hours',
                'Pay Rate',
                'Net Payroll',
                'Tips',
                'MWA',
                'Tips Due',
                'Mileage Due',
                'Gross',
                'HR Pay',
                'MWA',
                'New Pay',
                'Payroll',
                ...(isDC.value ? [] : ['Check', 'Instant'])
            ])

            
            // Process grouped data
            Object.entries(groupByEmployee.value).forEach(([employeeId, employeeRoles]) => {
                // Add data for each role
                Object.entries(employeeRoles).forEach(([roleId, roleItems]) => {
                    // Add each week period row
                    roleItems.forEach((item, index) => {
                        exportData.push([
                            item.employee?.employee_id || '',
                            item.employee?.pos_name || '',
                            item.role?.name ? item.role?.code + ' - ' + item.role?.name : '',
                            item.week_period.split(' - ')[1] || '',
                            parseFloat(item.regular_hours || 0),
                            parseFloat(item.overtime_hours || 0),
                            parseFloat(item.total_hours || 0) +  ( item.payroll_hours > 0 && item.rate_type == 'Payroll Slab' ? ' ( PH: ' + parseFloat(item.payroll_hours || 0) + ')' : ''),
                            parseFloat(item.employee_rate?.rate || 0),
                            parseFloat(item.gross_pay || 0),
                            parseFloat(item.tips || 0),
                            parseFloat(item.mwa_amount || 0),
                            parseFloat(item.tips_due || 0),
                            parseFloat(item.mileage_due || 0),
                            parseFloat(item.total_earnings || 0),
                            parseFloat(item.hr_pay || 0),
                            parseFloat(item.min_wage_due || 0),
                            parseFloat(item.min_wage_due + item.payroll_methods || 0),
                            parseFloat(item.payroll_methods || 0),
                            ...(isDC.value ? [] : [parseFloat(item.check_methods || 0),
                            ...(isDC.value ? [] : [parseFloat(item.instant_methods || 0)])])
                        ])
                    })
                    
                    // Add role subtotal only if there are multiple roles
                    if (Object.keys(employeeRoles).length > 1) {
                        const roleTotal = roleItems.reduce((acc, item) => ({
                            regularHours: acc.regularHours + parseFloat(item.regular_hours ?? 0),
                            overtimeHours: acc.overtimeHours + parseFloat(item.overtime_hours ?? 0),
                            totalHours: acc.totalHours + parseFloat(item.total_hours ?? 0),
                            grossPay: acc.grossPay + parseFloat(item.gross_pay ?? 0),
                            tips: acc.tips + parseFloat(item.tips ?? 0),
                            mwaAmount: acc.mwaAmount + parseFloat(item.mwa_amount ?? 0),
                            tipsDue: acc.tipsDue + parseFloat(item.tips_due ?? 0),
                            mileageDue: acc.mileageDue + parseFloat(item.mileage_due ?? 0),
                            totalEarnings: acc.totalEarnings + parseFloat(item.total_earnings ?? 0),
                            hrPay: acc.hrPay + parseFloat(item.hr_pay ?? 0),
                            minWageDue: acc.minWageDue + parseFloat(item.min_wage_due ?? 0),
                            newPay: acc.newPay + parseFloat(item.min_wage_due + item.payroll_methods ?? 0),

                            payrollMethods: acc.payrollMethods + parseFloat(item.payroll_methods ?? 0),
                            checkMethods: acc.checkMethods + parseFloat(item.check_methods ?? 0),
                            instantMethods: acc.instantMethods + parseFloat(item.instant_methods ?? 0),
                            payrollHours: acc.payrollHours + parseFloat(item.payroll_hours ?? 0),
                        }), {
                            regularHours: 0, overtimeHours: 0, totalHours: 0, grossPay: 0,
                            tips: 0, tipsDue: 0, mileageDue: 0, totalEarnings: 0, hrPay: 0, mwaAmount: 0,
                            payrollMethods: 0, checkMethods: 0, instantMethods: 0, payrollHours: 0
                        })
                        
                        exportData.push([
                            '', '', '', 'Role Total:',
                            roleTotal.regularHours,
                            roleTotal.overtimeHours,
                            (roleTotal.totalHours).toFixed(2) + ( roleTotal.payrollHours > 0 ? ' ( PH: ' + roleTotal.payrollHours + ')' : ''),
                            '-',
                            roleTotal.grossPay,
                            roleTotal.tips,
                            roleTotal.mwaAmount,
                            roleTotal.tipsDue,
                            roleTotal.mileageDue,
                            roleTotal.totalEarnings,
                            '-',
                            '-',
                            '-',
                            roleTotal.payrollMethods,
                            ...(isDC.value ? [] : [roleTotal.checkMethods]),
                            ...(isDC.value ? [] : [roleTotal.instantMethods])
                        ])
                    }
                })
                
                // Add employee total
                const allRoleItems = Object.values(employeeRoles).flat()
                const employeeTotal = allRoleItems.reduce((acc, item) => ({
                    regularHours: acc.regularHours + parseFloat(item.regular_hours ?? 0),
                    overtimeHours: acc.overtimeHours + parseFloat(item.overtime_hours ?? 0),
                    totalHours: acc.totalHours + parseFloat(item.total_hours ?? 0),
                    grossPay: acc.grossPay + parseFloat(item.gross_pay ?? 0),
                    tips: acc.tips + parseFloat(item.tips ?? 0),
                    mwaAmount: acc.mwaAmount + parseFloat(item.mwa_amount ?? 0),
                    tipsDue: acc.tipsDue + parseFloat(item.tips_due ?? 0),
                    mileageDue: acc.mileageDue + parseFloat(item.mileage_due ?? 0),
                    totalEarnings: acc.totalEarnings + parseFloat(item.total_earnings ?? 0),
                    payrollMethods: acc.payrollMethods + parseFloat(item.payroll_methods ?? 0),
                    checkMethods: acc.checkMethods + parseFloat(item.check_methods ?? 0),
                    instantMethods: acc.instantMethods + parseFloat(item.instant_methods ?? 0),
                    payrollHours: acc.payrollHours + parseFloat(item.payroll_hours ?? 0),
                }), {
                    regularHours: 0, overtimeHours: 0, totalHours: 0, grossPay: 0,
                    tips: 0, tipsDue: 0, mileageDue: 0, totalEarnings: 0, mwaAmount: 0,
                    payrollMethods: 0, checkMethods: 0, instantMethods: 0, payrollHours: 0
                })
                
                exportData.push([
                    '', '', '', 'Employee Total:',
                    employeeTotal.regularHours,
                    employeeTotal.overtimeHours,
                    (employeeTotal.totalHours).toFixed(2) + ( employeeTotal.payrollHours > 0 ? ' ( PH: ' + employeeTotal.payrollHours + ')' : ''),
                    '-',
                    employeeTotal.grossPay,
                    employeeTotal.tips,
                    employeeTotal.mwaAmount,
                    employeeTotal.tipsDue,
                    employeeTotal.mileageDue,
                    employeeTotal.totalEarnings,
                    '-',
                    '-',
                    '-',
                    employeeTotal.payrollMethods,
                    ...(isDC.value ? [] : [employeeTotal.checkMethods]),
                    ...(isDC.value ? [] : [employeeTotal.instantMethods])
                ])
                
                exportData.push([]) // Empty row between employees
            })
            
            // Add grand totals
            exportData.push([])
            exportData.push([
                '', '', '', 'GRAND TOTAL:',
                totals.value.regularHours,
                totals.value.overtimeHours,
                (totals.value.totalHours).toFixed(2) + ( totals.value.payrollHours > 0 ? ' ( PH: ' + totals.value.payrollHours + ')' : ''),
                '-',
                totals.value.grossPay,
                totals.value.tips,
                totals.value.mwaAmount,
                totals.value.tips_due,
                totals.value.mileage_due,
                totals.value.totalEarnings,
                '-',
                '-',
                '-',
                totals.value.payrollMethods,
                ...(isDC.value ? [] : [totals.value.checkMethods]),
                ...(isDC.value ? [] : [totals.value.instantMethods])
            ])
            
            // Create worksheet
            const ws = XLSX.utils.aoa_to_sheet(exportData)
            
            // Set column widths
            ws['!cols'] = [
                { wch: 12 },  // Employee ID
                { wch: 20 },  // Employee Name
                { wch: 15 },  // Role
                { wch: 20 },  // EOW
                { wch: 12 },  // Regular Hours
                { wch: 12 },  // Overtime Hours
                { wch: 12 },  // Total Hours
                { wch: 10 },  // Pay Rate
                { wch: 12 },  // Gross Pay
                { wch: 10 },  // Tips
                { wch: 10 },  // MWA Amount
                { wch: 10 },  // Tips Due
                { wch: 12 },  // Mileage Due
                { wch: 14 },  // Total Earnings
                { wch: 10 },  // HR Pay
                { wch: 14 },  // MWA
                { wch: 14 },  // New Pay
                { wch: 14 },  // Payroll
                ...(isDC.value ? [] : [{ wch: 14 }]),  // Check
                ...(isDC.value ? [] : [{ wch: 14 }])   // Instant
            ]
            
            // Create workbook
            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, ws, 'Payroll Report')
            
            // Generate filename
            const filename = `Weekly_Payroll_Report_${filters.value.selectedYear}_${new Date().getTime()}.xlsx`
            
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

    document.addEventListener('click', (e) => {
        if (editingItem.value) {
            const input = document.querySelector('input[type="number"]')
            const cell = input?.closest('td')
            
            if (cell && !cell.contains(e.target)) {
                editingItem.value = null
            }
        }
    })
})

const reviewPayroll = async () => {
    try {
        if(minWageReview.value){
          message.error('You are not allowed to review the payroll because the minimum wage is due.')
         return
        }

        const r = confirm("Are you sure you want to review the payroll?")
        if (r != true) {
            return
        }


        reviewLoading.value = true
        const check_data = []
        let zeroAmountEmployee = false;
        payrollData.value.forEach(item => {
            item.forEach(record => {

                if( record.total_earnings == 0 && record.total_hours > 0){
                    zeroAmountEmployee = record.employee?.pos_name;
                    return;
                }
                check_data.push({
                    employee_id: record.employee_id,
                    role_id: record.role_id,
                    eow: mmddyyyyToYmd(record.week_period.split(' - ')[1]),
                    amount: record.check_methods ?? 0,
                    payroll_amount: record.payroll_methods ?? 0,
                    instant_amount: record.instant_methods ?? 0,
                    is_1099: record.is_1099 ?? false,
                    company_id: record.company_id,
                })
            })
        })
        if(zeroAmountEmployee){
            message.error('Employee ' + zeroAmountEmployee + ' has no earnings but has hours worked.')
            return;
        }
        const response = await useRequest('post', '/reports/payroll/review', {check_data})
        isReviewed.value = response.saved
        message.success(response.message)
    } catch (error) {
        console.log(error)
        message.error(error.response?.data?.message)
    } finally {
        reviewLoading.value = false
    }
}
const navigateToEmployee = (item) => {
    if(item.employee && item.employee.employee_type == 'New'){
        window.open(`/onboarding/employee/new/${item.employee_id}`, '_blank')
    }else if(item.employee && item.employee.employee_type == 'Existing'){
        window.open(`/onboarding/employee/existing/${item.employee_id}`, '_blank')
    }else {
        window.open(`/employee/${item.employee_id}`, '_blank')
    }
}
</script>
