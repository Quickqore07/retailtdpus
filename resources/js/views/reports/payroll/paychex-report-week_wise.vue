<template>
    <div class="weekly-payroll-report p-2 bg-gray-50 dark:bg-gray-900">
        <!-- Header Section -->
        
        <!-- Filter Panel -->
        <Panel class="mb-6">
            <div class="mb-6">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Paychex Reports
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    View and analyze Paychex data for bi-weekly periods (Monday to Sunday)
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
                        v-if="canUpdate"
                        icon-left="check"
                        icon-size="sm"
                        variant="success"
                        size="sm"
                        @click="reviewPaychexCheck"
                        :loading="reviewCheckLoading"
                        :disabled="!paginatedCompanyEntries || paginatedCompanyEntries.length === 0"
                    >
                        Review Check
                    </Button>
                    <Button
                        v-if="canUpdate"
                        icon-left="print"
                        icon-size="sm"
                        variant="secondary"
                        size="sm"
                        @click="printCheck"
                        :loading="printingLoading"
                        :disabled="!paginatedCompanyEntries || paginatedCompanyEntries.length === 0"

                    >
                        Print Check
                    </Button>
                </div>
                
            </div>
        </Panel>

        <!-- Summary Cards -->
        <div class="mb-6">
            <MultiStatCard
                label="Summary"
                :items="DataSummaryStats"
                icon="chart"
                icon-color="green"
                format-type="number"
            />
        </div>

        <!-- Data Table -->
        <Panel>
            <!-- Controls -->
            <div class="flex flex-wrap items-center gap-3 mb-3 px-4 pt-4">
                <!-- Search Company -->
                <div class="flex-1 max-w-md">
                    <input
                        v-model="companySearchQuery"
                        type="text"
                        placeholder="Search companies..."
                        class="form-input w-full"
                    />
                </div>
                
                <!-- Search Employee -->
                <div class="flex-1 max-w-md">
                    <input
                        v-model="employeeSearchQuery"
                        type="text"
                        placeholder="Search employees..."
                        class="form-input w-full"
                    />
                </div>
                
                <!-- Expand/Collapse All Employees Button -->
                <Button
                    :icon-left="expandAllEmployees ? 'chevron-up' : 'chevron-down'"
                    icon-size="sm"
                    variant="secondary"
                    size="sm"
                    @click="toggleAllEmployees"
                >
                    {{ expandAllEmployees ? 'Collapse All Employees' : 'Expand All Employees' }}
                </Button>
            </div>
            
            <div 
                ref="scrollContainer"
                @scroll="handleScroll"
                class="overflow-x-auto max-h-[calc(100vh-100px)] relative"
            >
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900 sticky top-0 !z-[101]">
                        <tr>
                            <Th class="sticky left-0 bg-white dark:bg-gray-900 !z-[101]">
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
                            <Th class="sticky left-23 bg-white dark:bg-gray-900 !z-[101] w-[200px]">
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
                            <Th class="sticky left-80 bg-white dark:bg-gray-900 !z-[101]">Role</Th>
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
                            <Th>From Store</Th>
                            <Th>Check #</Th>
                            <Th>Date</Th>
                            <Th>Actions</Th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 !z-[100]" v-if="paginatedCompanyEntries.length > 0">
                        <!-- Loop through companies (paginated) -->
                        <template v-for="([companyName, companyEmployees], companyIndex) in paginatedCompanyEntries" :key="`company-${companyIndex}`">
                            <!-- Company Header Row -->
                            <tr class="bg-green-50 dark:bg-green-900/30 font-bold border-t border-green-500">
                                <Td colspan="3" weight="bold" color="primary" class="sticky left-0 bg-green-50 dark:bg-green-900/30 !z-[100] text-lg">
                                    {{ companyName }}
                                </Td>
                                <Td colspan="16" weight="bold" color="primary" class="text-lg"></Td>
                            </tr>
                            
                            <!-- Loop through employees in this company -->
                            <template v-for="([employeeName, employeeRoles], empIndex) in Object.entries(companyEmployees)" :key="`${companyName}-${employeeName}-${empIndex}`">
                                <!-- Employee Details (only if expanded) -->
                                <template v-if="isEmployeeExpanded(companyName, employeeName)">
                                    <!-- Loop through roles for each employee -->
                                    <template v-for="(roleItems, roleId) in employeeRoles" :key="`${companyName}-${employeeName}-${roleId}`">
                                        <!-- Loop through week periods for each role -->
                                        <tr 
                                            v-for="(item, itemIndex) in roleItems" 
                                            :key="`${companyName}-${employeeName}-${roleId}-${itemIndex}`"
                                            class="border-t-2 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 !z-[100]"
                                        >
                                        <Td  
                                            color="secondary"
                                            class="border-r-2 border-gray-300 dark:border-gray-600 sticky left-0 bg-white dark:bg-gray-900 !z-[100]"
                                        >
                                            {{ item.employee?.employee_id }}
                                        </Td>
                                        <Td  
                                            weight="medium"
                                            color="primary"
                                            class="border-r-2 border-gray-300 w-[200px] max-w-[200px] dark:border-gray-600 max-w-96 truncate sticky left-23 bg-white dark:bg-gray-900 !z-[100]"
                                            :title="item.employee?.pos_name"
                                            @dblclick="navigateToEmployee(item)"
                                        >
                                            {{ item.employee?.pos_name }}
                                        </Td>
                                        
                                        <Td 
                                            color="secondary"
                                            class="border-r border-gray-300 dark:border-gray-600 sticky left-80 bg-white dark:bg-gray-900 !z-[100]"
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
                                        <Td color="secondary">${{ formatNumber(item.tips_due) }}</Td>
                                        <Td color="secondary">${{ formatNumber(item.mileage_due) }}</Td>
                                        <Td weight="medium" color="primary">${{ formatNumber(item.total_earnings) }}</Td>
                                        <Td weight="medium" color="primary">${{ formatNumber(item.hr_pay) }}</Td>
                                        <Td weight="medium" color="primary" :customClass="item.min_wage_due > 0 ? '!text-red-500' : ''"
                                            :title="renderMinWageTitle(item)"
                                        >${{ formatNumber(item.payroll_methods) }}</Td>
                                        
                                        <Td weight="medium" color="primary">-</Td>
                                        <Td weight="medium" color="primary">-</Td>
                                        <Td weight="medium" color="primary">-</Td>
                                        <Td>-</Td>
                                    </tr>
                                        
                                        <!-- Role Subtotal Row -->
                                        <tr class="bg-gray-100 dark:bg-gray-700 font-semibold" v-if="Object.keys(employeeRoles).length > 1">
                                        <Td class="sticky left-0 bg-gray-100 dark:bg-gray-700 !z-[100]"></Td>
                                        <Td class="sticky left-23 bg-gray-100 dark:bg-gray-700 !z-[100] w-[200px]"></Td>
                                        <Td  weight="semibold" color="secondary" class="text-right sticky left-80 bg-gray-100 dark:bg-gray-700 !z-[100]">
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
                                            {{ formatNumber(roleItems.reduce((sum, i) => sum + parseFloat(i.total_hours), 0)) }} hrs
                                        </Td>
                                        <Td>-</Td>
                                        <Td weight="semibold" color="primary">
                                            ${{ formatNumber(roleItems.reduce((sum, i) => sum + parseFloat(i.gross_pay), 0)) }}
                                        </Td>
                                        <Td weight="semibold" color="secondary">
                                            ${{ formatNumber(roleItems.reduce((sum, i) => sum + parseFloat(i.tips), 0)) }}
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
                                        <Td  weight="semibold" color="primary">-</Td>
                                        <Td weight="semibold" color="primary">
                                            ${{ formatNumber(roleItems.reduce((sum, i) => sum + parseFloat(i.payroll_methods), 0)) }}
                                        </Td>
                                        <Td  weight="semibold" color="primary">-</Td>
                                        <Td  weight="semibold" color="primary">-</Td>
                                        <Td  weight="semibold" color="primary">-</Td>
                                        <Td>-</Td>
                                        </tr>
                                    </template>
                                </template>
                                
                                <!-- Employee Total Row (Clickable to expand/collapse) -->
                                <tr 
                                    class="font-bold cursor-pointer transition-colors"
                                    :class="isEmployeeExpanded(companyName, employeeName) ? 'bg-blue-50 dark:bg-blue-900/20 border-b-2 border-blue-300 dark:border-blue-700 hover:bg-blue-100 dark:hover:bg-blue-900/30' : 'bg-white dark:bg-gray-900 border-b-2 border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'"
                                   
                                >
                                    <Td class="sticky left-0 !z-[100]" :class="isEmployeeExpanded(companyName, employeeName) ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-white dark:bg-gray-900'">
                                        <div class="flex items-center gap-2">
                                            <span class="flex items-center gap-2 text-green-600" @click.stop>
                                                <SvgIcon
                                                    v-if="getEmployeeRowCheckData(employeeRoles)?.reviewed"
                                                    name="check-circle"
                                                    size="sm"
                                                    class="text-green-600 dark:text-green-400"
                                                />
                                                <input
                                                    v-else
                                                    type="checkbox"
                                                    :checked="!!getEmployeeRowCheckData(employeeRoles)?.checked"
                                                    @change="handlePaychexReviewChange(employeeRoles, $event)"
                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                                />
                                            </span>
                                            <span @click="toggleEmployee(companyName, employeeName)" class="cursor-pointer">
                                                <SvgIcon 
                                                    :name="isEmployeeExpanded(companyName, employeeName) ? 'chevron-down' : 'chevron-up'" 
                                                    size="sm" 
                                                    class="text-blue-600 dark:text-blue-400"
                                                />
                                            </span>
                                        </div>
                                    </Td>
                                    <Td :weight="isEmployeeExpanded(companyName, employeeName) ? 'bold' : 'medium'" color="primary" class="sticky left-23 !z-[100] w-[200px]" :title="employeeName" :class="isEmployeeExpanded(companyName, employeeName) ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-white dark:bg-gray-900'"
                                        @dblclick="navigateToEmployee(getEmployeeRowCheckData(employeeRoles))"
                                    >
                                        {{ employeeName }}
                                    </Td>
                                    <Td :weight="isEmployeeExpanded(companyName, employeeName) ? 'bold' : 'medium'" color="primary" class="text-right sticky left-80 !z-[100]" :class="isEmployeeExpanded(companyName, employeeName) ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-white dark:bg-gray-900'">
                                        Total:
                                    </Td>
                                    <Td :weight="isEmployeeExpanded(companyName, employeeName) ? 'bold' : 'medium'" color="secondary" ></Td>
                                    <Td :weight="isEmployeeExpanded(companyName, employeeName) ? 'bold' : 'medium'" color="secondary">
                                        {{ formatNumber(getEmployeeTotals(employeeRoles)?.regular_hours ?? 0) }} hrs
                                    </Td>
                                    <Td :weight="isEmployeeExpanded(companyName, employeeName) ? 'bold' : 'medium'" color="secondary">
                                        {{ formatNumber(getEmployeeTotals(employeeRoles)?.overtime_hours ?? 0) }} hrs
                                    </Td>
                                    <Td :weight="isEmployeeExpanded(companyName, employeeName) ? 'bold' : 'medium'" color="primary">
                                        {{ formatNumber(getEmployeeTotals(employeeRoles)?.total_hours ?? 0) }} hrs
                                    </Td>
                                    <Td>-</Td>
                                    <Td :weight="isEmployeeExpanded(companyName, employeeName) ? 'bold' : 'medium'" color="primary">
                                        ${{ formatNumber(getEmployeeTotals(employeeRoles)?.gross_pay ?? 0) }}
                                    </Td>
                                    <Td :weight="isEmployeeExpanded(companyName, employeeName) ? 'bold' : 'medium'" color="secondary">
                                        ${{ formatNumber(getEmployeeTotals(employeeRoles)?.tips ?? 0) }}
                                    </Td>
                                    <Td :weight="isEmployeeExpanded(companyName, employeeName) ? 'bold' : 'medium'" color="secondary">
                                        ${{ formatNumber(getEmployeeTotals(employeeRoles)?.tips_due ?? 0) }}
                                    </Td>
                                    <Td :weight="isEmployeeExpanded(companyName, employeeName) ? 'bold' : 'medium'" color="secondary">
                                        ${{ formatNumber(getEmployeeTotals(employeeRoles)?.mileage_due ?? 0) }}
                                    </Td>
                                    <Td :weight="isEmployeeExpanded(companyName, employeeName) ? 'bold' : 'medium'" color="primary">
                                        ${{ formatNumber(getEmployeeTotals(employeeRoles)?.total_earnings ?? 0) }}
                                    </Td>
                                    <Td :weight="isEmployeeExpanded(companyName, employeeName) ? 'bold' : 'medium'" color="primary">-</Td>
                                    <Td :weight="isEmployeeExpanded(companyName, employeeName) ? 'bold' : 'medium'" color="primary">
                                        ${{ formatNumber(getEmployeeTotals(employeeRoles)?.payroll_methods ?? 0) }}
                                    </Td>
                                    <Td :weight="isEmployeeExpanded(companyName, employeeName) ? 'bold' : 'medium'" color="primary" @click.stop >
                                        <div v-if="getEmployeeRowCheckData(employeeRoles)" class="flex items-center gap-2">
                                            <DynamicDropdown
                                                v-model="getEmployeeRowCheckData(employeeRoles).fromCompany"
                                                resource="companies"
                                                display-name="name"
                                                placeholder="Select From Store"
                                                :required="false"
                                                :removeNullOption="true"
                                                customClass="min-w-[200px]"
                                                :disabled="makePaychexDisabled(employeeRoles)"
                                                @change="handlePaychexFromCompanyChange(getEmployeeRowCheckData(employeeRoles))"
                                            />
                                            <DynamicDropdown
                                                v-model="getEmployeeRowCheckData(employeeRoles).ledger"
                                                resource="banks"
                                                display-name="name"
                                                placeholder="Select Bank"
                                                :required="false"
                                                :removeNullOption="true"
                                                customClass="min-w-[150px]"
                                                :disabled="makePaychexDisabled(employeeRoles)"
                                                @change="handlePaychexLedgerChange(getEmployeeRowCheckData(employeeRoles))"
                                            />
                                        </div>
                                        <span v-else>-</span>
                                    </Td>
                                    <Td :weight="isEmployeeExpanded(companyName, employeeName) ? 'bold' : 'medium'" color="primary" @click.stop customClass="min-w-[150px]">
                                        <Input
                                            v-if="getEmployeeRowCheckData(employeeRoles)"
                                            v-model="getEmployeeRowCheckData(employeeRoles).check_number"
                                            :required="false"
                                            type="number"
                                            :disabled="makePaychexDisabled(employeeRoles)"
                                        />
                                        <span v-else>-</span>
                                    </Td>
                                    <Td weight="bold" color="primary">
                                        {{ getEmployeeRowCheckData(employeeRoles)?.check_date ? formatDate(getEmployeeRowCheckData(employeeRoles).check_date) : '-' }}
                                    </Td>
                                    <Td weight="bold" color="primary" @click.stop>
                                        <Button
                                            v-if="canUpdate"
                                            icon-left="edit"
                                            icon-size="sm"
                                            variant="primary"
                                            size="sm"
                                            :disabled="!(getEmployeeRowCheckData(employeeRoles)?.reviewed )"
                                            @click="handlePaychexEdit(employeeRoles)"
                                        >
                                            Edit
                                        </Button>
                                    </Td>
                                </tr>
                            </template>
                            
                            <!-- Company Total Row -->
                            <tr class="bg-green-50 dark:bg-green-900/30 font-bold border-b border-green-500">
                                <Td class="sticky left-0 bg-green-50 dark:bg-green-900/30 !z-[100]"></Td>
                                <Td class="sticky left-23 bg-green-50 dark:bg-green-900/30 !z-[100] w-[200px]"></Td>
                                <Td weight="bold" color="primary" class="text-right sticky left-80 bg-green-50 dark:bg-green-900/30 !z-[100]">
                                    {{ companyName }} Total:
                                </Td>
                                <Td weight="bold" color="secondary"></Td>
                                <Td weight="bold" color="secondary">
                                    {{ formatNumber(calculateCompanyTotal(companyEmployees, 'regular_hours')) }} hrs
                                </Td>
                                <Td weight="bold" color="secondary">
                                    {{ formatNumber(calculateCompanyTotal(companyEmployees, 'overtime_hours')) }} hrs
                                </Td>
                                <Td weight="bold" color="primary">
                                    {{ formatNumber(calculateCompanyTotal(companyEmployees, 'total_hours')) }} hrs
                                </Td>
                                <Td>-</Td>
                                <Td weight="bold" color="primary">
                                    ${{ formatNumber(calculateCompanyTotal(companyEmployees, 'gross_pay')) }}
                                </Td>
                                <Td weight="bold" color="secondary">
                                    ${{ formatNumber(calculateCompanyTotal(companyEmployees, 'tips')) }}
                                </Td>
                                <Td weight="bold" color="secondary">
                                    ${{ formatNumber(calculateCompanyTotal(companyEmployees, 'tips_due')) }}
                                </Td>
                                <Td weight="bold" color="secondary">
                                    ${{ formatNumber(calculateCompanyTotal(companyEmployees, 'mileage_due')) }}
                                </Td>
                                <Td weight="bold" color="primary">
                                    ${{ formatNumber(calculateCompanyTotal(companyEmployees, 'total_earnings')) }}
                                </Td>
                                <Td weight="bold" color="primary">-</Td>
                                <Td weight="bold" color="primary">
                                    ${{ formatNumber(calculateCompanyTotal(companyEmployees, 'payroll_methods')) }}
                                </Td>
                                <Td weight="bold" color="primary">-</Td>
                                <Td weight="bold" color="primary">-</Td>
                                <Td weight="bold" color="primary">-</Td>
                                <Td>-</Td>
                            </tr>
                            
                            
                            
                            <!-- Spacer Row -->
                            <tr>
                                <Td colspan="19" weight="bold" color="primary" class="h-7"></Td>
                            </tr>
                        </template>
                    </tbody>
                    <tbody class="bg-white dark:bg-gray-800" v-else>
                        <tr>
                            <td colspan="19">
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
                            <Td class="sticky left-0 bg-gray-50 dark:bg-gray-900 !z-[101]"></Td>
                            <Td class="sticky left-23 bg-gray-50 dark:bg-gray-900 !z-[101]"></Td>
                            <Td  weight="bold" color="primary" class="text-right sticky left-80 bg-gray-50 dark:bg-gray-900 !z-[101]">TOTALS</Td>
                            <Td class=""></Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.regularHours) }} hrs</Td>
                            <Td weight="bold" color="secondary">{{ formatNumber(totals.overtimeHours) }} hrs</Td>
                            <Td weight="bold" color="primary">{{ formatNumber(totals.totalHours) }} hrs</Td>
                            <Td>-</Td>
                            <Td weight="bold" color="primary">${{ formatNumber(totals.grossPay) }}</Td>
                            <Td weight="bold" color="secondary">${{ formatNumber(totals.tips) }}</Td>
                            <Td weight="bold" color="secondary">${{ formatNumber(totals.tips_due) }}</Td>
                            <Td weight="bold" color="secondary">${{ formatNumber(totals.mileage_due) }}</Td>
                            <Td weight="bold" color="primary">${{ formatNumber(totals.totalEarnings) }}</Td>
                            <Td>-</Td>
                            <Td weight="bold" color="primary">${{ formatNumber(totals.payrollMethods) }}</Td>
                            <Td>-</Td>
                            <Td>-</Td>
                            <Td>-</Td>
                            <Td>-</Td>
                        </tr>
                    </tfoot>
                </table>  
            </div> 

            <!-- Loading Indicator -->
            <div v-if="loadingMore" class="flex justify-center px-4 py-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Loading more companies...</span>
                </div>
            </div>
            
            <!-- Pagination Info -->
            <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Showing <span class="font-medium">{{ paginatedCompanyEntries.length }}</span> of <span class="font-medium">{{ filteredCompanyEntries.length }}</span> companies
                    <span v-if="companySearchDebounced || employeeSearchDebounced" class="text-gray-500">(filtered from {{ sortedCompanyEntries.length }} total)</span>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Period: {{ currentPeriodLabel }}
                </div>
            </div>
        </Panel>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import MultiStatCard from '@/components/ui/multi-stat-card.vue'
import Td from '@/components/ui/td.vue'
import Th from '@/components/ui/th.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import NoData from '@/components/ui/no-data.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import { mmddyyyyToYmd, formatDate } from '@/utils/date'
import { formatNumber } from '@/utils/number'
import { applyPayPeriodDefaults, savePayPeriodSelection } from '@/utils/payPeriodSelection'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import Input from '@/components/ui/input.vue'
import { usePermission } from '@/composables/usePermission'
const message = useMessage()
const filters = ref({
    selectedYear: 2026,
    selectedPeriod: '',
    company: '',
    status: ''
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
const employeeCheckData = ref([])
const editingItem = ref(null)
const loading = ref(false)
const reviewLoading = ref(false)
const reviewCheckLoading = ref(false)
const exportLoading = ref(false)
const minWageReview = ref(false)
const { can } = usePermission()
const printingLoading = ref(false)


// Company search (raw input for immediate UI feedback)
const companySearchQuery = ref('')

// Employee search (raw input for immediate UI feedback)
const employeeSearchQuery = ref('')

// Debounced search values (used for filtering - 300ms delay)
const companySearchDebounced = ref('')
const employeeSearchDebounced = ref('')

// Debounce timers for search
let companySearchDebounceTimer = null
let employeeSearchDebounceTimer = null

// Debounce search inputs
watch(companySearchQuery, (val) => {
    clearTimeout(companySearchDebounceTimer)
    companySearchDebounceTimer = setTimeout(() => {
        companySearchDebounced.value = val
    }, 300)
}, { immediate: true })

watch(employeeSearchQuery, (val) => {
    clearTimeout(employeeSearchDebounceTimer)
    employeeSearchDebounceTimer = setTimeout(() => {
        employeeSearchDebounced.value = val
    }, 300)
}, { immediate: true })

// Employee expand/collapse state
const expandedEmployees = ref(new Set())
const expandAllEmployees = ref(false)

// Toggle employee expansion
const toggleEmployee = (companyName, employeeName) => {
    const key = `${companyName}-${employeeName}`
    if (expandedEmployees.value.has(key)) {
        expandedEmployees.value.delete(key)
    } else {
        expandedEmployees.value.add(key)
    }
}

// Toggle all employees
const toggleAllEmployees = () => {
    expandAllEmployees.value = !expandAllEmployees.value
    if (!expandAllEmployees.value) {
        // Collapse all employees
        expandedEmployees.value.clear()
    }
}

// Check if employee is expanded
const isEmployeeExpanded = (companyName, employeeName) => {
    if (expandAllEmployees.value) return true
    const key = `${companyName}-${employeeName}`
    return expandedEmployees.value.has(key)
}

// Infinite scrolling state
const companiesPerPage = ref(5)
const currentPage = ref(1)
const loadingMore = ref(false)
const scrollContainer = ref(null)

// Filter companies and employees by search query (uses debounced values)
const filteredCompanyEntries = computed(() => {
    const companyQuery = companySearchDebounced.value.trim().toLowerCase()
    const employeeQuery = employeeSearchDebounced.value.trim().toLowerCase()
    
    if (!companyQuery && !employeeQuery) {
        return sortedCompanyEntries.value
    }
    
    return sortedCompanyEntries.value
        .map(([companyName, companyEmployees]) => {
            // Get company from first item for store_number search
            const firstItem = Object.values(companyEmployees).flat()[0]
            const company = firstItem?.company
            
            // Filter by company name or store number
            const companyMatches = !companyQuery || (
                companyName.toLowerCase().includes(companyQuery) ||
                (company?.store_number && String(company.store_number).toLowerCase().includes(companyQuery))
            )
            
            // Filter employees by name
            let filteredEmployees = companyEmployees
            if (employeeQuery) {
                filteredEmployees = Object.fromEntries(
                    Object.entries(companyEmployees).filter(([employeeName]) => {
                        return employeeName.toLowerCase().includes(employeeQuery)
                    })
                )
            }
            
            // Include company only if: (company matches OR no company filter) AND (has matching employees OR no employee filter)
            const hasMatchingEmployees = !employeeQuery || Object.keys(filteredEmployees).length > 0
            if (companyMatches && hasMatchingEmployees) {
                return [companyName, filteredEmployees]
            }
            return null
        })
        .filter(entry => entry !== null && Object.keys(entry[1]).length > 0)
})

// Get paginated companies (for infinite scroll)
const paginatedCompanyEntries = computed(() => {
    const end = currentPage.value * companiesPerPage.value
    return filteredCompanyEntries.value.slice(0, end)
})

const totalPages = computed(() => {
    return Math.ceil(filteredCompanyEntries.value.length / companiesPerPage.value)
})

const hasMoreCompanies = computed(() => {
    return paginatedCompanyEntries.value.length < filteredCompanyEntries.value.length
})

// Throttle timer for scroll handler
let scrollThrottle = null

// Infinite scroll handler (throttled)
const handleScroll = (event) => {
    if (scrollThrottle) return
    
    scrollThrottle = setTimeout(() => {
        scrollThrottle = null
        
        if (loadingMore.value || !hasMoreCompanies.value) return
        
        const container = event.target
        const scrollPosition = container.scrollTop + container.clientHeight
        const scrollHeight = container.scrollHeight
        
        // Load more when user is within 200px of the bottom
        if (scrollHeight - scrollPosition < 200) {
            loadMoreCompanies()
        }
    }, 100) // Throttle to every 100ms
}

const loadMoreCompanies = () => {
    if (!hasMoreCompanies.value || loadingMore.value) return
    
    loadingMore.value = true
    
    // Simulate async loading for smooth UX
    setTimeout(() => {
        currentPage.value++
        loadingMore.value = false
    }, 300)
}

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
    const daysToMonday = dayOfWeek === 0 ? -6 : 1 - dayOfWeek - 7 // If Sunday, go back 6 days; otherwise go to previous/current Monday
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
            value: totals.value.grossPay
        },
        {
            label: 'Total tips',
            value: totals.value.tips
        },
        {
            label: 'Total tips due',
            value: totals.value.tips_due
        },

        {
            label: 'Total mileage due',
            value: totals.value.mileage_due
        },
       
        {
            label: 'Total Gross',
            value: totals.value.totalEarnings
        },
        {
            label: 'Total Payroll Methods',
            value: totals.value.payrollMethods
        }
    ]
})
// Current period label for display
const currentPeriodLabel = computed(() => {
    if (!filters.value.selectedPeriod) return 'No period selected'
    const period = availablePeriods.value.find(p => p.value === filters.value.selectedPeriod)
    return period ? period.label : 'No period selected'
})

// Create a single computed object that stores all employee totals and check data
// Key format: "companyId-employeeId"

const employeeTotalsMap = ref({})
const calculateEmployeeTotals = () => {
    employeeTotalsMap.value = {}
    
    // Iterate through all companies and employees
    Object.entries(groupedByCompany.value).forEach(([companyName, companyEmployees]) => {
        Object.entries(companyEmployees).forEach(([employeeName, employeeRoles]) => {
            const firstItem = Object.values(employeeRoles).flat()[0]
            if (!firstItem?.company?.id || !firstItem?.employee?.id) return
            
            const key = `${firstItem.company.id}-${firstItem.employee.id}`
            const allItems = Object.values(employeeRoles).flat()
            
            // Calculate all totals once
            const totals = {
                regular_hours: 0,
                overtime_hours: 0,
                total_hours: 0,
                gross_pay: 0,
                tips: 0,
                tips_due: 0,
                mileage_due: 0,
                total_earnings: 0,
                payroll_methods: 0,
                hr_pay: 0
            }
            
            allItems.forEach(item => {
                totals.regular_hours += parseFloat(item.regular_hours ?? 0)
                totals.overtime_hours += parseFloat(item.overtime_hours ?? 0)
                totals.total_hours += parseFloat(item.total_hours ?? 0)
                totals.gross_pay += parseFloat(item.gross_pay ?? 0)
                totals.tips += parseFloat(item.tips ?? 0)
                totals.tips_due += parseFloat(item.tips_due ?? 0)
                totals.mileage_due += parseFloat(item.mileage_due ?? 0)
                totals.total_earnings += parseFloat(item.total_earnings ?? 0)
                totals.payroll_methods += parseFloat(item.payroll_methods ?? 0)
                totals.hr_pay += parseFloat(item.hr_pay ?? 0)
            })
            
            // Get or create check data
            const checkData = getOrCreateEmployeeCheckData(
                firstItem.company.id, 
                firstItem.employee.id, 
                firstItem.company
            )
            
            employeeTotalsMap.value[key] = {
                reviewKey: key,
                companyId: firstItem.company.id,
                employeeId: firstItem.employee.id,
                employeeInternalId: firstItem.employee.employee_id,
                totals,
                checkData,
                firstItem
            }
        })
    })
    
    return employeeTotalsMap.value
}

// Get review key for employee (company_id-employee_id internal)
const getEmployeeReviewKey = (employeeRoles) => {
    const firstItem = Object.values(employeeRoles || {}).flat()[0]
    if (!firstItem?.company?.id || !firstItem?.employee?.id) return null
    const key = `${firstItem.company.id}-${firstItem.employee.id}`
    return employeeTotalsMap.value[key]?.reviewKey || null
}

// Get check data for Employee Total row (used in template to avoid repeated calls)
const getEmployeeRowCheckData = (employeeRoles) => {
    const firstItem = Object.values(employeeRoles || {}).flat()[0]
    if (!firstItem?.company?.id || !firstItem?.employee?.id) return null
    const key = `${firstItem.company.id}-${firstItem.employee.id}`
    return employeeTotalsMap.value[key]?.checkData || null
}

// Get employee totals from the map
const getEmployeeTotals = (employeeRoles) => {
    const firstItem = Object.values(employeeRoles || {}).flat()[0]
    if (!firstItem?.company?.id || !firstItem?.employee?.id) return null
    const key = `${firstItem.company.id}-${firstItem.employee.id}`
    return employeeTotalsMap.value[key]?.totals || null
}

// Disable From Store / Ledger / Check # when row is reviewed (saved) unless user clicked Edit for this row
const makePaychexDisabled = (employeeRoles) => {
    const key = getEmployeeReviewKey(employeeRoles)
    if (editingItem.value && editingItem.value === key) return false
    const checkData = getEmployeeRowCheckData(employeeRoles)
    return (checkData?.reviewed ) && canUpdate.value
}

const handlePaychexEdit = (employeeRoles) => {
    editingItem.value = getEmployeeReviewKey(employeeRoles)
}

// Get or create employee check data for Employee Total row
const getOrCreateEmployeeCheckData = (companyId, employeeId, defaultCompany) => {
    const existing = employeeCheckData.value.find(
        c => String(c.company_id) === String(companyId) && String(c.employee_id) === String(employeeId)
    )
    if (existing) return existing
    const newCheck = {
        company_id: companyId,
        employee_id: employeeId,
        fromCompany: defaultCompany || null,
        ledger: null,
        check_number: 0,
        check_date: new Date().toISOString().split('T')[0],
        company: defaultCompany || null,
        reviewed: false
    }
    employeeCheckData.value.push(newCheck)
    return newCheck
}

const handlePaychexFromCompanyChange = async (checkData) => {
    try {
        const response = await useRequest('get', `/get-default-bank/${checkData.fromCompany?.id}`)
        checkData.ledger = response.bank ? response.bank : null
        if (checkData.ledger) {
            await handlePaychexLedgerChange(checkData)
        }
    } catch (error) {
        message.error(error.response?.data?.message)
    }
}

const handlePaychexReviewChange = (employeeRoles, event) => {
    const checkData = getEmployeeRowCheckData(employeeRoles)
    const key = getEmployeeReviewKey(employeeRoles)
    if (!checkData || !key) return

    const totals = getEmployeeTotals(employeeRoles)
    const amount = totals?.payroll_methods ?? 0
    const isValid = amount > 0 && checkData.fromCompany && checkData.ledger && checkData.check_number > 0

    if (event.target.checked) {
        if (!isValid) {
            event.target.checked = false
            checkData.checked = false
            message.error('Please fill From Store, Ledger, and Check # before marking for review.')
        } else {
            checkData.checked = true
        }
    } 
}

const handlePaychexLedgerChange = async (checkData) => {
    try {
        const response = await useRequest('get', `/get-latest-check-number/${checkData.ledger?.id}/${checkData.company?.id}`)
        checkData.check_number = response.check_number ?? 0
    } catch (error) {
        message.error(error.response?.data?.message)
    }
}

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

// Helper function to calculate company totals
const calculateCompanyTotal = (companyEmployees, field) => {
    let total = 0
    Object.values(companyEmployees).forEach(employeeRoles => {
        const allItems = Object.values(employeeRoles).flat()
        allItems.forEach(item => {
            total += parseFloat(item[field] ?? 0)
        })
    })
    return total
}

// Group by company first, then by employee, then by role
const groupedByCompany = computed(() => {
    return payrollData.value.reduce((acc, record) => {
        record.forEach(item => {
            const companyName = item.company?.name || 'Unknown Company'
            const employeeName = item.employee?.pos_name
            const roleKey = item.role_id + '-' + item.employee?.employee_id
            
            // Create company group if it doesn't exist
            if (!acc[companyName]) {
                acc[companyName] = {}
            }
            
            // Create employee group within company if it doesn't exist
            if (!acc[companyName][employeeName]) {
                acc[companyName][employeeName] = {}
            }
            
            // Create role group within employee if it doesn't exist
            if (!acc[companyName][employeeName][roleKey]) {
                acc[companyName][employeeName][roleKey] = []
            }
            
            acc[companyName][employeeName][roleKey].push(item)
        })
        return acc
    }, {})
})

// Return unsorted grouped data (kept for backward compatibility)
const groupedByEmployee = computed(() => {
    return payrollData.value.reduce((acc, record) => {
        record.forEach(item => {
            if (!acc[item.employee?.pos_name]) {
                acc[item.employee?.pos_name] = {}
            }
            if (!acc[item.employee?.pos_name][item.role_id + '-' + item.employee?.employee_id]) {
                acc[item.employee?.pos_name][item.role_id + '-' + item.employee?.employee_id] = []
            }
            acc[item.employee?.pos_name][item.role_id + '-' + item.employee?.employee_id].push(item)
        })
        return acc
    }, {})
})

// Return sorted array of companies with their employees
const sortedCompanyEntries = computed(() => {
    const currentSortField = sortField.value
    const currentSortDirection = sortDirection.value
    
    const grouped = groupedByCompany.value
    const companyEntries = Object.entries(grouped)
    
    // Sort companies alphabetically
    companyEntries.sort((a, b) => {
        const [companyA] = a
        const [companyB] = b
        return companyA.localeCompare(companyB)
    })
    
    // For each company, sort employees
    const sortedCompanies = companyEntries.map(([companyName, employees]) => {
        const employeeEntries = Object.entries(employees)
        
        if (currentSortField) {
            employeeEntries.sort((a, b) => {
                const [employeeNameA, employeeRolesA] = a
                const [employeeNameB, employeeRolesB] = b
                
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
                
                // Numeric sorting based on Employee Total
                const totalA = calculateEmployeeTotal(employeeRolesA, currentSortField)
                const totalB = calculateEmployeeTotal(employeeRolesB, currentSortField)
                
                const diff = totalA - totalB
                return currentSortDirection === 'asc' ? diff : -diff
            })
        }
        
        return [companyName, Object.fromEntries(employeeEntries)]
    })
    
    return sortedCompanies
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
            acc.mileage += parseFloat(item.mileage_excess ?? 0)
            acc.tips_due += parseFloat(item.tips_due ?? 0)
            acc.mileage_due += parseFloat(item.mileage_due ?? 0)
            acc.totalEarnings += parseFloat(item.total_earnings ?? 0)
            acc.payrollMethods += parseFloat(item.payroll_methods ?? 0)
        })
        return acc
    }, {
        totalHours: 0,
        regularHours: 0,
        overtimeHours: 0,
        grossPay: 0,
        tips: 0,
        mileage: 0,
        tips_due: 0,
        mileage_due: 0,
        totalEarnings: 0,
        payrollMethods: 0,
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
}

const applyFilters = async () => {
    try {
        loading.value = true;

        const [response] = await Promise.all([
                useRequest('post', '/reports/payroll/paychex', {
                    start_date: filters.value.selectedPeriod.split('to')[0],
                    end_date: filters.value.selectedPeriod.split('to')[1],
                })
        ])
        payrollData.value = response.data;
        minWageReview.value = response.min_wage_review;
        employeeCheckData.value = response.companyEmployees || [];

        calculateEmployeeTotals();
    } catch (error) {
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
                'Tips Due',
                'Mileage Due',
                'Gross',
                'HR Pay',
                'Payroll',
            ])
            
            // Process grouped data by company
            sortedCompanyEntries.value.forEach(([companyName, companyEmployees]) => {
                // Add company header
                exportData.push([companyName])
                exportData.push([]) // Empty row
                
                // Loop through employees in this company
                Object.entries(companyEmployees).forEach(([employeeName, employeeRoles]) => {
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
                                parseFloat(item.tips_due || 0),
                                parseFloat(item.mileage_due || 0),
                                parseFloat(item.total_earnings || 0),
                                parseFloat(item.hr_pay || 0),
                                parseFloat(item.payroll_methods || 0),
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
                                tipsDue: acc.tipsDue + parseFloat(item.tips_due ?? 0),
                                mileageDue: acc.mileageDue + parseFloat(item.mileage_due ?? 0),
                                totalEarnings: acc.totalEarnings + parseFloat(item.total_earnings ?? 0),
                                hrPay: acc.hrPay + parseFloat(item.hr_pay ?? 0),
                                payrollMethods: acc.payrollMethods + parseFloat(item.payroll_methods ?? 0),
                            }), {
                                regularHours: 0, overtimeHours: 0, totalHours: 0, grossPay: 0,
                                tips: 0, tipsDue: 0, mileageDue: 0, totalEarnings: 0, hrPay: 0,
                                payrollMethods: 0
                            })
                            
                            exportData.push([
                                '', '', '', 'Role Total:',
                                roleTotal.regularHours,
                                roleTotal.overtimeHours,
                                roleTotal.totalHours,
                                '-',
                                roleTotal.grossPay,
                                roleTotal.tips,
                                roleTotal.tipsDue,
                                roleTotal.mileageDue,
                                roleTotal.totalEarnings,
                                '-',
                                roleTotal.payrollMethods,
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
                        tipsDue: acc.tipsDue + parseFloat(item.tips_due ?? 0),
                        mileageDue: acc.mileageDue + parseFloat(item.mileage_due ?? 0),
                        totalEarnings: acc.totalEarnings + parseFloat(item.total_earnings ?? 0),
                        payrollMethods: acc.payrollMethods + parseFloat(item.payroll_methods ?? 0),
                    }), {
                        regularHours: 0, overtimeHours: 0, totalHours: 0, grossPay: 0,
                        tips: 0, tipsDue: 0, mileageDue: 0, totalEarnings: 0,
                        payrollMethods: 0
                    })
                    
                    exportData.push([
                        '', '', '', 'Employee Total:',
                        employeeTotal.regularHours,
                        employeeTotal.overtimeHours,
                        employeeTotal.totalHours,
                        '-',
                        employeeTotal.grossPay,
                        employeeTotal.tips,
                        employeeTotal.tipsDue,
                        employeeTotal.mileageDue,
                        employeeTotal.totalEarnings,
                        '-',
                        employeeTotal.payrollMethods,
                    ])
                })
                
                // Add company total
                exportData.push([
                    '', '', '', companyName + ' Total:',
                    calculateCompanyTotal(companyEmployees, 'regular_hours'),
                    calculateCompanyTotal(companyEmployees, 'overtime_hours'),
                    calculateCompanyTotal(companyEmployees, 'total_hours'),
                    '-',
                    calculateCompanyTotal(companyEmployees, 'gross_pay'),
                    calculateCompanyTotal(companyEmployees, 'tips'),
                    calculateCompanyTotal(companyEmployees, 'tips_due'),
                    calculateCompanyTotal(companyEmployees, 'mileage_due'),
                    calculateCompanyTotal(companyEmployees, 'total_earnings'),
                    '-',
                    calculateCompanyTotal(companyEmployees, 'payroll_methods'),
                ])
                
                exportData.push([]) // Empty row between companies
                exportData.push([]) // Extra empty row
            })
            
            // Add grand totals
            exportData.push([])
            exportData.push([
                '', '', '', 'GRAND TOTAL:',
                totals.value.regularHours,
                totals.value.overtimeHours,
                totals.value.totalHours,
                '-',
                totals.value.grossPay,
                totals.value.tips,
                totals.value.tips_due,
                totals.value.mileage_due,
                totals.value.totalEarnings,
                '-',
                totals.value.payrollMethods,
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
                { wch: 10 },  // Tips Due
                { wch: 12 },  // Mileage Due
                { wch: 14 },  // Total Earnings
                { wch: 10 },  // HR Pay
                { wch: 14 },  // Payroll
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
// Watch for search query changes and reset pagination
watch([companySearchDebounced, employeeSearchDebounced], () => {
    currentPage.value = 1
    if (scrollContainer.value) {
        scrollContainer.value.scrollTop = 0
    }
})

onMounted(() => {
    setDefaultPeriod()

    document.addEventListener('click', (e) => {

    })
})

const reviewPaychexCheck = async () => {
    try {
        // if (minWageReview.value) {
        //     message.error('You are not allowed to review the check because the minimum wage is due.')
        //     return
        // }

        const r = confirm("Are you sure you want to review the check?")
        if (r != true) {
            return
        }
        reviewCheckLoading.value = true
        const check_data = []
        const payrollEow = filters.value.selectedPeriod.split('to')[1]?.trim()?.split('T')[0]
        const existingData = [] 

        Object.values(employeeTotalsMap.value).forEach(item => {
            if (item.checkData && (item.checkData.reviewed || item.checkData.checked)) {
                check_data.push({
                    employee_id: item.employeeId,
                    company_id: item.companyId,
                    from_company_id: item.checkData.fromCompany?.id,
                    ledger_id: item.checkData.ledger?.id,
                    payroll_eow: payrollEow,
                    check_amount: item.totals?.payroll_methods ?? 0,
                    check_number: item.checkData.check_number,
                    check_date: item.checkData.check_date,
                    check_type: 'payroll',
                })
            }
        })

        if (check_data.length === 0) {
            message.error('No checks marked for review. Please select at least one employee and ensure From Store, Ledger, and Check # are filled.')
            return
        }

        const response = await useRequest('post', '/payroll/network-check-review', { data: check_data,existing_data: existingData })

        // Clear reviewed checks and refresh data
        editingItem.value = null
        await applyFilters()
        message.success(response.message)
    } catch (error) {
        console.log(error)
        message.error(error.response?.data?.message)
    } finally {
        reviewCheckLoading.value = false
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

const printCheck = async ()=>{
    try {
        const reviewedChecks = employeeCheckData.value.filter(item=> item.checked  )
        if(!reviewedChecks.length){
            message.warning('There is no any reviewd check')
            return
        }
        const response = await useRequest('post', `/print-check`,{check_type:'payroll',payroll_eow:filters.value.selectedPeriod.split(' to ')[1].split('T')[0]})
        const pdfUrls = response.pdfpaths || (response.pdfpath ? [response.pdfpath] : [])
        pdfUrls.forEach((url, i) => setTimeout(() => window.open(url, '_blank'), i * 200))
    } catch (error) {
        console.log(error)
        message.error(error.response?.data?.message)
    }
}
</script>

<style scoped>
/* Performance optimizations for large tables */
.overflow-x-auto {
    contain: layout style paint;
    will-change: scroll-position;
    scroll-behavior: smooth;
}

/* Optimize sticky positioning */
.sticky {
    contain: layout style paint;
}

/* Reduce paint on hover */
tr {
    contain: layout style;
}

/* Hardware acceleration for smooth scrolling */
tbody {
    transform: translateZ(0);
    backface-visibility: hidden;
}

/* Smooth loading animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

tbody > template {
    animation: fadeIn 0.3s ease-in-out;
}
</style>
