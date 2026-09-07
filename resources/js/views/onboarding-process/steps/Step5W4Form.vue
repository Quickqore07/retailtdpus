<template>
    <div class="w4-form-wrap max-h-screen overflow-y-auto">
        <form @submit.prevent="submit" class="w4-form-body">
            <div class="inner w4-page-1">
                <!-- W-4 Form Header (same as Blade) -->
                <table class="w4-header-table">
                    <tbody>
                    <tr>
                        <td class="w4-header-left">
                            <div class="w4-header-inline">
                                <span class="w4-form-label">Form</span>
                                <span class="w4-form-w4">W-4</span>
                                <div class="w4-dept">Department of the Treasury</div>
                                <div class="w4-dept">Internal Revenue Service</div>
                            </div>
                        </td>
                        <td class="w4-header-middle">
                            <div class="w4-middle-part">
                                <div class="w4-title-main">Employee's Withholding Certificate</div>
                                <div class="w4-title-sub"><b>Complete Form W-4 so that your employer can withhold the correct federal income tax from your pay.</b></div>
                                <div class="w4-title-sub"><b>Give Form W-4 to your employer.</b></div>
                                <div class="w4-title-sub"><b>Your withholding is subject to review by the IRS.</b></div>
                            </div>
                        </td>
                        <td class="w4-header-right">
                            <div class="w4-omb">OMB No. 1545-0074</div>
                                <div class="w4-year">2025</div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Step 1: Enter Personal Information -->
                <table class="w4-table">
                    <tbody>
                    <tr>
                        <td class="w4-step-cell" rowspan="4">Step 1:<br>Enter Personal Information</td>
                        <td class="w4-field-cell">
                            <div class="d-flex flex-column justify-between h-full">
                                <label class="w4-inline-label">(a) First name and middle initial</label>
                                <Input v-model="form.first_name"  class="w4-input-cell" />
                            </div>
                        </td>
                        <td class="w4-field-cell">
                            <label class="w4-inline-label">Last name</label>
                            <Input v-model="form.last_name"  class="w4-input-cell" />
                        </td>
                        <td class="w4-field-cell">
                            <label class="w4-inline-label">(b) Social security number</label>
                            <Input v-model="form.social_security_number"  class="w4-input-cell" :readonly="true" />
                        </td>
                    </tr>
                    <tr>
                        <td class="w4-field-cell" colspan="2">
                            <label class="w4-inline-label">Address</label>
                            <Input v-model="form.address"  class="w4-input-cell" />
                        </td>
                        <td class="w4-ssa-note" rowspan="2">
                            <b>Does your name match the name on your social security card?</b> If not, to ensure you get credit for your earnings, contact SSA at 800-772-1213 or go to www.ssa.gov.
                        </td>
                    </tr>
                    <tr>
                        <td class="w4-field-cell" colspan="2">
                            <label class="w4-inline-label">City or town, state, and ZIP code</label>
                            <Input v-model="form.city_state_zip"  class="w4-input-cell" placeholder="City, State, ZIP" />
                        </td>
                    </tr>
                    <tr>
                        <td class="w4-field-cell" colspan="3">
                            <div class="w4-marital-row">
                                <b>(c)</b>
                                <div>
                                    <label class="w4-check-label"><input type="radio" :checked="maritalStatus === 1" @change="maritalStatus = 1" /> <b>Single</b> or <b>Married filing separately</b></label>
                                    <label class="w4-check-label"><input type="radio" :checked="maritalStatus === 2" @change="maritalStatus = 2" /> <b>Married filing jointly</b> or <b>Qualifying surviving spouse</b></label>
                                    <label class="w4-check-label"><input type="radio" :checked="maritalStatus === 3" @change="maritalStatus = 3" /> <b>Head of household</b> (Check only if you're unmarried and pay more than half the costs of keeping up a home for yourself and a qualifying individual.)</label>
                                </div>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <div class="w4-tip-block">
                    <p class="w4-tip-p"><b>TIP:</b> Consider using the estimator at www.irs.gov/W4App to determine the most accurate withholding for the rest of the year if: you are completing this form after the beginning of the year; expect to work only part of the year; or have changes during the year in your marital status, number of jobs for you (and/or your spouse if married filing jointly), dependents, other income (not from jobs), deductions, or credits. Have your most recent pay stub(s) from this year available when using the estimator. At the beginning of next year, use the estimator again to recheck your withholding.</p>
                    <p class="w4-tip-p"><b>Complete Steps 2–4 ONLY if they apply to you; otherwise, skip to Step 5.</b> See page 2 for more information on each step, who can claim exemption from withholding, and when to use the estimator at www.irs.gov/W4App.</p>
                </div>

                <!-- Step 2: Multiple Jobs or Spouse Works -->
                <table class="w4-table">
                    <tbody>
                    <tr style="border-bottom: none;">
                        <td class="w4-step-cell w4-no-bottom">Step 2: Multiple Jobs or Spouse Works</td>
                        <td class="w4-step2-cell">
                            <p class="w4-p">Complete this step if you (1) hold more than one job at a time, or (2) are married filing jointly and your spouse also works. The correct amount of withholding depends on income earned from all of these jobs.</p>
                            <div class="w4-p">Do <b>only one</b> of the following.</div>
                            <div class="w4-radio-row"><input type="radio" v-model="form.multiple_jobs_and_spouse_works" :value="1" />&nbsp;<b>(a)</b> Use the estimator at www.irs.gov/W4App for most accurate withholding for this step (and Steps 3–4). If you or your spouse have self-employment income,</div>
                            <div class="w4-radio-row"><input type="radio" v-model="form.multiple_jobs_and_spouse_works" :value="2" />&nbsp;<b>(b)</b> Use the Multiple Jobs Worksheet on page 3 and enter the result in Step 4(c) below</div>
                            <div class="w4-radio-row"><input type="radio" v-model="form.multiple_jobs_and_spouse_works" :value="3" />&nbsp;<b>(c)</b> If there are only two jobs total, you may check this box. Do the same on Form W-4 for the other job. . . . &nbsp;<input type="checkbox" :checked="form.multiple_jobs_and_spouse_works === 3" @change="form.multiple_jobs_and_spouse_works = $event.target.checked ? 3 : null" /></div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="w4-note-cell"><b>Complete Steps 3–4(b) on Form W-4 for only ONE of these jobs.</b> Leave those steps blank for the other jobs. (Your withholding will be most accurate if you complete Steps 3–4(b) on the Form W-4 for the highest paying job.)</td>
                    </tr>
                    </tbody>
                </table>

                <!-- Step 3: Claim Dependent and Other Credits -->
                <table class="w4-table">
                    <tbody>
                    <tr>
                        <td class="w4-step-cell w4-step3-label">Step 3: Claim Dependent and Other Credits</td>
                        <td class="w4-step3-desc">
                            <p class="w4-p">If your total income will be $200,000 or less ($400,000 or less if married filing jointly):</p>
                            <p class="w4-p indent">Multiply the number of qualifying children under age 17 by $2,000&nbsp;<b>$</b>&nbsp;<Input v-model="form.qualifying_children"  class="w4-amount-inline" /></p>
                            <p class="w4-p indent">Multiply the number of other dependents by $500&nbsp;. . . . . . . . . . . . . . <b>$</b>&nbsp;<Input v-model="form.dependents"  class="w4-amount-inline" /></p>
                            <p class="w4-p indent margin-bottom-0">Add the amounts above for qualifying children and other dependents. You may add to this the amount of any other credits. Enter the total here</p>
                        </td>
                        <td class="w4-step-num">3</td>
                        <td class="w4-step-input-cell"> <b class="w4-dollar">$</b>&nbsp;<Input v-model="form.total_amount"  class="w4-input-cell" /></td>
                    </tr>
                    </tbody>
                </table>

                <!-- Step 4: Other Adjustments -->
                <table class="w4-table">
                    <tbody>
                    <tr>
                        <td class="w4-step-cell" rowspan="3">Step 4: (optional): Other Adjustments</td>
                        <td class="w4-step4-desc"><b>(a) Other income (not from jobs).</b> If you want tax withheld for other income you expect this year that won't have withholding, enter the amount of other income here. This may include interest, dividends, and retirement income</td>
                        <td class="w4-step4-num">4(a)</td>
                        <td class="w4-step-input-cell"><b class="w4-dollar">$</b>&nbsp;<Input v-model="form.other_income"  class="w4-input-cell" /></td>
                    </tr>
                    <tr>
                        <td class="w4-step4-desc"><b>(b) Deductions.</b> If you expect to claim deductions other than the standard deduction and want to reduce your withholding, use the Deductions Worksheet on page 3 and enter the result here</td>
                        <td class="w4-step4-num">4(b)</td>
                        <td class="w4-step-input-cell"><b class="w4-dollar">$</b>&nbsp;<Input v-model="form.deductions"  class="w4-input-cell" /></td>
                    </tr>
                    <tr>
                        <td class="w4-step4-desc"><b>(c) Extra withholding.</b> Enter any additional tax you want withheld each pay period</td>
                        <td class="w4-step4-num">4(c)</td>
                        <td class="w4-step-input-cell"><b class="w4-dollar">$</b>&nbsp;<Input v-model="form.extra_withholding"  class="w4-input-cell" /></td>
                    </tr>
                    </tbody>
                </table>

                <!-- Step 5: Sign Here -->
                <table class="w4-table">
                    <tbody>
                        <tr>
                        <td class="w4-step-cell w4-sign-step" rowspan="2">Step 5:<br>Sign Here</td>
                        <td colspan="2" class="w4-sign-declare">Under penalties of perjury, I declare that this certificate, to the best of my knowledge and belief, is true, correct, and complete.</td>
                    </tr>
                    <tr>
                        <td class="w4-sign-cell">
                            <Input v-model="form.employee_sign"  class="w4-input-cell" placeholder="Employee's signature" />
                            <b>Employee's signature</b> (This form is not valid unless you sign it.)
                        </td>
                        <td class="w4-sign-cell">
                            <Input v-model="form.employee_date" type="date"  class="w4-input-cell" />
                            <b>Date</b>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <!-- Employers Only -->
                <table class="w4-table">
                    <tbody>
                        <tr>
                        <td class="w4-step-cell w4-employer-label">Employers<br>Only</td>
                        <td class="w4-employer-cell">Employer's name and address <Input v-model="form.employer_name"  class="w4-input-cell" :readonly="true" /></td>
                        <td class="w4-employer-cell">First date of employment <Input v-model="form.date_of_employment"  class="w4-input-cell" :readonly="true" /></td>
                        <td class="w4-employer-cell">Employer identification number (EIN) <Input :model-value="company?.employer_identification_number || ''"  class="w4-input-cell" :readonly="true" /></td>
                    </tr>
                    </tbody>
                </table>

                <div class="w4-page-footer">
                    <label><b>For Privacy Act and Paperwork Reduction Act Notice, see page 3.</b></label>
                    <label>Cat. No. 10220Q</label>
                    <label>Form <b>W-4</b> (2025)</label>
                </div>
                <hr class="w4-hr" />
            </div>

            <!-- Page 2: General and Specific Instructions -->
            <div class="inner w4-page-2">
                <div class="w4-page-head">
                    <label>Form W-4 (2025)</label>
                    <label>Page <b>2</b></label>
                </div>
                <hr class="w4-hr-thin" />
                <table class="w4-instructions-table">
                    <tbody>
                        <tr>
                        <td class="w4-inst-col">
                            <div class="w4-inst-title">General Instructions</div>
                            <p>Section references are to the Internal Revenue Code unless otherwise noted.</p>
                            <div class="w4-inst-subtitle">Future Developments</div>
                            <p>For the latest information about developments related to Form W-4, go to <a href="https://www.irs.gov/FormW4" target="_blank" rel="noopener">www.irs.gov/FormW4.</a></p>
                            <div class="w4-inst-subtitle">Purpose of Form</div>
                            <p>Complete Form W-4 so that your employer can withhold the correct federal income tax from your pay. If too little is withheld, you will generally owe tax when you file your tax return and may owe a penalty. If too much is withheld, you will generally be due a refund. Complete a new Form W-4 when changes to your personal or financial situation would change the entries on the form. For more information see Pub. 505, Tax Withholding and Estimated Tax.</p>
                            <p><b>Exemption from withholding.</b> You may claim exemption from withholding for 2025 if you meet both conditions: you had no federal income tax liability in 2024 and you expect to have no federal income tax liability in 2025. If you claim exemption, you will have no income tax withheld and may owe taxes and penalties when you file your 2025 tax return. To claim exemption, write "Exempt" on Form W-4 in the space below Step 4(c). Then complete Steps 1(a), 1(b), and 5. Do not complete any other steps. You will need to submit a new Form W-4 by February 17, 2026.</p>
                            <p><b>Your privacy.</b> Steps 2(c) and 4(a) ask for information regarding income from sources other than this job. You may choose Step 2(b) or enter an additional amount in Step 4(c) as alternatives.</p>
                            <p><b>When to use the estimator.</b> Consider using the estimator at <a href="https://www.irs.gov/W4App" target="_blank" rel="noopener">www.irs.gov/W4App</a> if you: (1) Are submitting this form after the beginning of the year; (2) Expect to work only part of the year; (3) Have changes in marital status, number of jobs, dependents, deductions, or credits; (4) Receive dividends, capital gains, social security, bonuses, or business income; (5) Prefer the most accurate withholding for multiple job situations.</p>
                            <p><b>Self-employment.</b> If you want to pay taxes on self-employment income through withholding from your wages, use the estimator at www.irs.gov/W4App.</p>
                        </td>
                        <td class="w4-inst-col">
                            <p>Nonresident alien. If you're a nonresident alien, see Notice 1392 before completing this form.</p>
                            <div class="w4-inst-title">Specific Instructions</div>
                            <p><b>Step 1(c).</b> Check your anticipated filing status. This will determine the standard deduction and tax rates used to compute your withholding.</p>
                            <p><b>Step 2.</b> Use this step if you (1) have more than one job at the same time, or (2) are married filing jointly and you and your spouse both work. Submit a separate Form W-4 for each job. Option (a) most accurately calculates the additional tax; option (b) does so with a little less accuracy. If you have only two jobs total, you may check the box in option (c); the box must also be checked on the Form W-4 for the other job. Complete Steps 3–4(b) on only one Form W-4—preferably for the highest paying job.</p>
                            <p><b>Step 3.</b> This step provides instructions for the child tax credit and the credit for other dependents. You can also include other tax credits (e.g., foreign tax credit, education credits). See Pub. 501 for eligibility.</p>
                            <p><b>Step 4 (optional).</b> <b>Step 4(a)</b>—Enter other estimated income for the year (interest, dividends, retirement income). <b>Step 4(b)</b>—Enter amount from Deductions Worksheet line 5 if you expect to claim deductions other than the standard deduction. <b>Step 4(c)</b>—Enter any additional tax you want withheld each pay period.</p>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <hr class="w4-hr" />
            </div>

            <!-- Page 3: Multiple Jobs Worksheet, Deductions Worksheet, Privacy Act -->
            <div class="inner w4-page-3">
                <div class="w4-page-head">
                    <label>Form W-4 (2025)</label>
                    <label>Page <b>3</b></label>
                </div>
                <hr class="w4-hr-thin" />
                <div class="w4-worksheet-title"><b>Step 2(b)—Multiple Jobs Worksheet</b> <i>(Keep for your records.)</i></div>
                <p class="w4-p">If you choose the option in Step 2(b) on Form W-4, complete this worksheet on <b>only ONE</b> Form W-4. Withholding will be most accurate if you complete it for the highest paying job. <b>Note:</b> If more than one job has annual wages over $120,000 or there are more than three jobs, see Pub. 505 or use the estimator at www.irs.gov/W4App.</p>
                <table class="w4-worksheet-table">
                    <tbody>
                        <tr>
                        <td class="w4-ws-desc"><b>1. Two jobs.</b> If you have two jobs or you're married filing jointly and you and your spouse each have one job, find the amount from the appropriate table on page 4. Enter that value on line 1. Then <b>skip</b> to line 3.</td>
                        <td class="w4-ws-input"> <div class="flex gap-2 items-center"><b>1 $</b><Input v-model="worksheet.line1"/></div></td>
                    </tr>
                    <tr>
                        <td class="w4-ws-desc"><b>2. Three jobs.</b> Complete lines 2a, 2b, 2c below. Otherwise, skip to line 3.</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td class="w4-ws-desc indent"><b>a</b> Find the amount from the table on page 4 (highest + next highest job). Enter on line 2a.</td>
                        <td class="w4-ws-input"> <div class="flex gap-2 items-center"><b>2a $</b><Input v-model="worksheet.line2a"/></div></td>
                    </tr>
                    <tr>
                        <td class="w4-ws-desc indent"><b>b</b> Add the two highest jobs; use table with that total and third job. Enter on line 2b.</td>
                        <td class="w4-ws-input"><div class="flex gap-2 items-center"><b>2b $</b><Input v-model="worksheet.line2b"/></div></td>
                    </tr>
                    <tr>
                        <td class="w4-ws-desc indent"><b>c</b> Add lines 2a and 2b. Enter on line 2c.</td>
                        <td class="w4-ws-input"><div class="flex gap-2 items-center"><b>2c $</b><Input v-model="worksheet.line2c"/></div></td>
                    </tr>
                    <tr>
                        <td class="w4-ws-desc"><b>3</b> Enter the number of pay periods per year for the highest paying job (e.g., 52 for weekly, 26 for biweekly, 12 for monthly).</td>
                        <td class="w4-ws-input"><div class="flex gap-2 items-center"><b>3 $</b> <Input v-model="worksheet.line3"/></div></td>
                    </tr>
                    <tr>
                        <td class="w4-ws-desc"><b>4 Divide</b> the amount on line 1 or 2c by the number on line 3. Enter here and in <b>Step 4(c)</b> of Form W-4.</td>
                        <td class="w4-ws-input"><div class="flex gap-2 items-center"><b>4 $</b><Input v-model="worksheet.line4"/></div></td>
                    </tr>
                    </tbody>
                </table>
                <hr class="w4-hr-thin" />
                <div class="w4-worksheet-title"><b>Step 4(b)—Deductions Worksheet</b> <i>(Keep for your records.)</i></div>
                <table class="w4-worksheet-table">
                    <tbody>
                        <tr>
                        <td class="w4-ws-desc"><b>1</b> Enter an estimate of your 2025 itemized deductions (from Schedule A).</td>
                        <td class="w4-ws-input"><div class="flex gap-2 items-center"><b>1 $</b><Input v-model="worksheet.ded1"/></div></td>
                    </tr>
                    <tr>
                        <td class="w4-ws-desc"><b>2</b> Enter $30,000 (married filing jointly), $22,500 (head of household), or $15,000 (single or married filing separately).</td>
                        <td class="w4-ws-input"><div class="flex gap-2 items-center"><b>2 $</b><Input v-model="worksheet.ded2"/></div></td>
                    </tr>
                    <tr>
                        <td class="w4-ws-desc"><b>3</b> If line 1 &gt; line 2, subtract line 2 from line 1. Otherwise enter "-0-".</td>
                        <td class="w4-ws-input"><div class="flex gap-2 items-center"><b>3 $</b><Input v-model="worksheet.ded3"/></div></td>
                    </tr>
                    <tr>
                        <td class="w4-ws-desc"><b>4</b> Enter estimate of student loan interest, deductible IRA, etc. (Schedule 1 Part II).</td>
                        <td class="w4-ws-input"><div class="flex gap-2 items-center"><b>4 $</b><Input v-model="worksheet.ded4"/></div></td>
                    </tr>
                    <tr>
                        <td class="w4-ws-desc"><b>5 Add</b> lines 3 and 4. Enter here and in <b>Step 4(b)</b> of Form W-4.</td>
                        <td class="w4-ws-input"><div class="flex gap-2 items-center"><b>5 $</b><Input v-model="worksheet.ded5"/></div></td>
                    </tr>
                    </tbody>
                </table>
                <hr class="w4-hr-thin" />
                <table class="w4-privacy-table">
                    <tbody>
                        <tr>
                        <td class="w4-privacy-col"><b>Privacy Act and Paperwork Reduction Act Notice.</b> We ask for the information on this form to carry out the Internal Revenue laws of the United States. Internal Revenue Code sections 3402(f)(2) and 6109 and their regulations require you to provide this information; your employer uses it to determine your federal income tax withholding. Failure to provide a properly completed form will result in your being treated as a single person with no other entries; providing fraudulent information may subject you to penalties. Routine uses include giving it to the Department of Justice; to cities, states, and territories for tax administration; and to the Department of Health and Human Services for the National Directory of New Hires. We may also disclose this information under tax treaties, to enforce federal nontax criminal laws, or to federal law enforcement and intelligence agencies to combat terrorism.</td>
                        <td class="w4-privacy-col">You are not required to provide the information requested on a form subject to the Paperwork Reduction Act unless the form displays a valid OMB control number. Books or records relating to a form must be retained as long as their contents may become material in the administration of any Internal Revenue law. Generally, tax returns and return information are confidential (Code section 6103). The average time and expenses to complete and file this form will vary. For estimated averages, see the instructions for your income tax return.</td>
                    </tr>
                    </tbody>
                </table>
                <hr class="w4-hr" />
            </div>

            <!-- Page 4: Wage tables (Married Filing Jointly, Single/MFS, Head of Household) - full as Blade -->
            <div class="inner w4-page-4">
                <div class="w4-page-head">
                    <label>Form W-4 (2025)</label>
                    <label>Page <b>4</b></label>
                </div>
                <hr class="w4-hr-thin" />
                <div class="w4-table-title">Married Filing Jointly or Qualifying Surviving Spouse</div>
                <table class="w4-wage-table w4-wage-full">
                    <tbody>
                        <tr>
                            <td class="w4-wage-first-col" rowspan="2"><b>Higher Paying Job Annual Taxable Wage &amp; Salary</b></td>
                            <td class="w4-wage-header-col" colspan="12"><b>Lower Paying Job Annual Taxable Wage &amp; Salary</b></td>
                        </tr>
                        <tr>
                            <td class="w4-wage-th">$0 - 9,999</td>
                            <td class="w4-wage-th">$10,000 - 19,999</td>
                            <td class="w4-wage-th">$20,000 - 29,999</td>
                            <td class="w4-wage-th">$30,000 - 39,999</td>
                            <td class="w4-wage-th">$40,000 - 49,999</td>
                            <td class="w4-wage-th">$50,000 - 59,999</td>
                            <td class="w4-wage-th">$60,000 - 69,999</td>
                            <td class="w4-wage-th">$70,000 - 79,999</td>
                            <td class="w4-wage-th">$80,000 - 89,999</td>
                            <td class="w4-wage-th">$90,000 - 99,999</td>
                            <td class="w4-wage-th">$100,000- 109,999</td>
                            <td class="w4-wage-th">$110,000- 120,000</td>
                        </tr>
                        <tr>
                            <td class="w4-wage-td"><div>$0 - 9,999</div><div>$10,000 - 19,999</div><div>$20,000 - 29,999</div></td>
                            <td class="w4-wage-td"><div>$0</div><div>0</div><div>700</div></td>
                            <td class="w4-wage-td"><div>$0</div><div>700</div><div>1,700</div></td>
                            <td class="w4-wage-td"><div>$700</div><div>1,700</div><div>2,760</div></td>
                            <td class="w4-wage-td"><div>$850</div><div>1,910</div><div>3,110</div></td>
                            <td class="w4-wage-td"><div>$910</div><div>2,110</div><div>3,310</div></td>
                            <td class="w4-wage-td"><div>$1,020</div><div>2,220</div><div>3,420</div></td>
                            <td class="w4-wage-td"><div>$1,020</div><div>2,220</div><div>3,420</div></td>
                            <td class="w4-wage-td"><div>$1,020</div><div>2,220</div><div>3,420</div></td>
                            <td class="w4-wage-td"><div>$1,020</div><div>2,220</div><div>3,420</div></td>
                            <td class="w4-wage-td"><div>$1,020</div><div>2,220</div><div>3,420</div></td>
                            <td class="w4-wage-td"><div>$1,020</div><div>2,220</div><div>4,420</div></td>
                            <td class="w4-wage-td"><div>$1,020</div><div>3,220</div><div>5,420</div></td>
                        </tr>
                        <tr>
                            <td class="w4-wage-td"><div>$30,000 - 39,999</div><div>$40,000 - 49,999</div><div>$50,000 - 59,999</div></td>
                            <td class="w4-wage-td"><div>850</div><div>910</div><div>1,020</div></td>
                            <td class="w4-wage-td"><div>1,910</div><div>2,110</div><div>2,220</div></td>
                            <td class="w4-wage-td"><div>3,110</div><div>3,310</div><div>3,420</div></td>
                            <td class="w4-wage-td"><div>3,460</div><div>3,660</div><div>3,770</div></td>
                            <td class="w4-wage-td"><div>3,660</div><div>3,860</div><div>3,970</div></td>
                            <td class="w4-wage-td"><div>3,770</div><div>3,970</div><div>4,080</div></td>
                            <td class="w4-wage-td"><div>3,770</div><div>3,970</div><div>4,080</div></td>
                            <td class="w4-wage-td"><div>3,770</div><div>3,970</div><div>4,080</div></td>
                            <td class="w4-wage-td"><div>3,770</div><div>4,970</div><div>5,080</div></td>
                            <td class="w4-wage-td"><div>4,770</div><div>5,970</div><div>6,080</div></td>
                            <td class="w4-wage-td"><div>5,770</div><div>6,970</div><div>7,080</div></td>
                            <td class="w4-wage-td"><div>6,770</div><div>7,970</div><div>9,080</div></td>
                        </tr>
                        <tr>
                            <td class="w4-wage-td"><div>$60,000 - 69,999</div><div>$70,000 - 79,999</div><div>$80,000 - 99,999</div></td>
                            <td class="w4-wage-td"><div>1,020</div><div>1,020</div><div>1,020</div></td>
                            <td class="w4-wage-td"><div>2,220</div><div>2,220</div><div>2,220</div></td>
                            <td class="w4-wage-td"><div>3,420</div><div>3,420</div><div>4,620</div></td>
                            <td class="w4-wage-td"><div>3,770</div><div>3,770</div><div>5,820</div></td>
                            <td class="w4-wage-td"><div>3,970</div><div>3,970</div><div>6,330</div></td>
                            <td class="w4-wage-td"><div>4,080</div><div>5,080</div><div>7,930</div></td>
                            <td class="w4-wage-td"><div>5,080</div><div>6,080</div><div>8,830</div></td>
                            <td class="w4-wage-td"><div>6,080</div><div>7,080</div><div>9,930</div></td>
                            <td class="w4-wage-td"><div>7,080</div><div>8,080</div><div>10,930</div></td>
                            <td class="w4-wage-td"><div>8,080</div><div>9,080</div><div>11,930</div></td>
                            <td class="w4-wage-td"><div>9,080</div><div>10,080</div><div>11,930</div></td>
                            <td class="w4-wage-td"><div>10,080</div><div>11,080</div><div>12,930</div></td>
                        </tr>
                        <tr>
                            <td class="w4-wage-td"><div>$100,000 - 149,999</div><div>$150,000 - 239,999</div><div>$240,000 - 259,999</div></td>
                            <td class="w4-wage-td"><div>1,870</div><div>1,870</div><div>2,040</div></td>
                            <td class="w4-wage-td"><div>4,070</div><div>4,240</div><div>4,440</div></td>
                            <td class="w4-wage-td"><div>6,270</div><div>6,840</div><div>6,840</div></td>
                            <td class="w4-wage-td"><div>7,620</div><div>8,190</div><div>8,390</div></td>
                            <td class="w4-wage-td"><div>8,820</div><div>9,590</div><div>9,790</div></td>
                            <td class="w4-wage-td"><div>9,930</div><div>10,890</div><div>11,100</div></td>
                            <td class="w4-wage-td"><div>10,930</div><div>12,090</div><div>12,300</div></td>
                            <td class="w4-wage-td"><div>11,930</div><div>13,290</div><div>13,500</div></td>
                            <td class="w4-wage-td"><div>13,290</div><div>14,490</div><div>14,700</div></td>
                            <td class="w4-wage-td"><div>14,010</div><div>15,690</div><div>15,900</div></td>
                            <td class="w4-wage-td"><div>15,210</div><div>16,890</div><div>17,100</div></td>
                            <td class="w4-wage-td"><div>16,410</div><div>18,090</div><div>18,300</div></td>
                        </tr>
                        <tr>
                            <td class="w4-wage-td"><div>$260,000 - 279,999</div><div>$280,000 - 299,999</div><div>$300,000 - 319,999</div></td>
                            <td class="w4-wage-td"><div>2,040</div><div>2,040</div><div>2,040</div></td>
                            <td class="w4-wage-td"><div>4,440</div><div>4,440</div><div>4,440</div></td>
                            <td class="w4-wage-td"><div>6,840</div><div>6,840</div><div>6,840</div></td>
                            <td class="w4-wage-td"><div>8,390</div><div>8,390</div><div>8,390</div></td>
                            <td class="w4-wage-td"><div>9,790</div><div>9,790</div><div>9,790</div></td>
                            <td class="w4-wage-td"><div>11,100</div><div>11,100</div><div>11,100</div></td>
                            <td class="w4-wage-td"><div>12,300</div><div>12,300</div><div>12,300</div></td>
                            <td class="w4-wage-td"><div>13,500</div><div>13,500</div><div>13,500</div></td>
                            <td class="w4-wage-td"><div>14,700</div><div>14,700</div><div>14,700</div></td>
                            <td class="w4-wage-td"><div>15,900</div><div>15,900</div><div>15,900</div></td>
                            <td class="w4-wage-td"><div>17,100</div><div>17,100</div><div>17,170</div></td>
                            <td class="w4-wage-td"><div>18,300</div><div>18,300</div><div>19,170</div></td>
                        </tr>
                        <tr>
                            <td class="w4-wage-td"><div>$320,000 - 364,999</div><div>$365,000 - 524,999</div><div>$525,000 and over</div></td>
                            <td class="w4-wage-td"><div>2,040</div><div>2,790</div><div>3,140</div></td>
                            <td class="w4-wage-td"><div>4,440</div><div>6,290</div><div>6,840</div></td>
                            <td class="w4-wage-td"><div>6,840</div><div>9,270</div><div>10,540</div></td>
                            <td class="w4-wage-td"><div>8,390</div><div>12,440</div><div>13,390</div></td>
                            <td class="w4-wage-td"><div>9,790</div><div>14,940</div><div>16,090</div></td>
                            <td class="w4-wage-td"><div>11,100</div><div>17,350</div><div>18,700</div></td>
                            <td class="w4-wage-td"><div>12,470</div><div>19,650</div><div>21,200</div></td>
                            <td class="w4-wage-td"><div>14,470</div><div>21,950</div><div>23,700</div></td>
                            <td class="w4-wage-td"><div>16,470</div><div>24,250</div><div>26,200</div></td>
                            <td class="w4-wage-td"><div>18,470</div><div>26,550</div><div>28,700</div></td>
                            <td class="w4-wage-td"><div>20,470</div><div>28,850</div><div>31,200</div></td>
                            <td class="w4-wage-td"><div>22,470</div><div>31,150</div><div>33,700</div></td>
                        </tr>
                    </tbody>
                </table>

                <div class="w4-table-title w4-table-title-mt">Single or Married Filing Separately</div>
                <table class="w4-wage-table w4-wage-full">
                    <tbody>
                        <tr>
                            <td class="w4-wage-first-col" rowspan="2"><b>Single or Married Filing Separately</b></td>
                            <td class="w4-wage-header-col" colspan="12"><b>Lower Paying Job Annual Taxable Wage &amp; Salary</b></td>
                        </tr>
                        <tr>
                            <td class="w4-wage-th">$0 - 9,999</td>
                            <td class="w4-wage-th">$10,000 - 19,999</td>
                            <td class="w4-wage-th">$20,000 - 29,999</td>
                            <td class="w4-wage-th">$30,000 - 39,999</td>
                            <td class="w4-wage-th">$40,000 - 49,999</td>
                            <td class="w4-wage-th">$50,000 - 59,999</td>
                            <td class="w4-wage-th">$60,000 - 69,999</td>
                            <td class="w4-wage-th">$70,000 - 79,999</td>
                            <td class="w4-wage-th">$80,000 - 89,999</td>
                            <td class="w4-wage-th">$90,000 - 99,999</td>
                            <td class="w4-wage-th">$100,000- 109,999</td>
                            <td class="w4-wage-th">$110,000- 120,000</td>
                        </tr>
                        <tr>
                            <td class="w4-wage-td"><div>$0 - 9,999</div><div>$10,000 - 19,999</div><div>$20,000 - 29,999</div></td>
                            <td class="w4-wage-td"><div>200</div><div>850</div><div>1,020</div></td>
                            <td class="w4-wage-td"><div>850</div><div>1,700</div><div>1,870</div></td>
                            <td class="w4-wage-td"><div>1,020</div><div>1,870</div><div>2,040</div></td>
                            <td class="w4-wage-td"><div>1,020</div><div>1,870</div><div>2,390</div></td>
                            <td class="w4-wage-td"><div>1,020</div><div>2,220</div><div>3,390</div></td>
                            <td class="w4-wage-td"><div>1,020</div><div>3,220</div><div>4,390</div></td>
                            <td class="w4-wage-td"><div>1,370</div><div>3,720</div><div>4,890</div></td>
                            <td class="w4-wage-td"><div>1,870</div><div>3,720</div><div>4,890</div></td>
                            <td class="w4-wage-td"><div>1,870</div><div>3,720</div><div>5,060</div></td>
                            <td class="w4-wage-td"><div>1,870</div><div>3,720</div><div>5,260</div></td>
                            <td class="w4-wage-td"><div>1,870</div><div>3,890</div><div>5,460</div></td>
                            <td class="w4-wage-td"><div>2,040</div><div>4,090</div><div>5,460</div></td>
                        </tr>
                        <tr>
                            <td class="w4-wage-td"><div>$30,000 - 39,999</div><div>$40,000 - 59,999</div><div>$60,000 - 79,999</div></td>
                            <td class="w4-wage-td"><div>1,020</div><div>1,220</div><div>1,870</div></td>
                            <td class="w4-wage-td"><div>1,870</div><div>3,070</div><div>3,720</div></td>
                            <td class="w4-wage-td"><div>2,390</div><div>4,240</div><div>4,890</div></td>
                            <td class="w4-wage-td"><div>3,390</div><div>5,240</div><div>5,890</div></td>
                            <td class="w4-wage-td"><div>4,390</div><div>6,240</div><div>7,030</div></td>
                            <td class="w4-wage-td"><div>5,390</div><div>7,240</div><div>8,030</div></td>
                            <td class="w4-wage-td"><div>5,890</div><div>7,880</div><div>8,930</div></td>
                            <td class="w4-wage-td"><div>5,890</div><div>8,080</div><div>9,330</div></td>
                            <td class="w4-wage-td"><div>6,260</div><div>8,280</div><div>9,530</div></td>
                            <td class="w4-wage-td"><div>6,460</div><div>8,480</div><div>9,730</div></td>
                            <td class="w4-wage-td"><div>6,660</div><div>8,680</div><div>9,930</div></td>
                            <td class="w4-wage-td"><div>6,660</div><div>8,880</div><div>9,930</div></td>
                        </tr>
                        <tr>
                            <td class="w4-wage-td"><div>$80,000 - 99,999</div><div>$100,000 - 124,999</div><div>$125,000 - 149,999</div></td>
                            <td class="w4-wage-td"><div>1,870</div><div>2,040</div><div>2,040</div></td>
                            <td class="w4-wage-td"><div>3,720</div><div>4,090</div><div>4,090</div></td>
                            <td class="w4-wage-td"><div>5,030</div><div>5,460</div><div>5,460</div></td>
                            <td class="w4-wage-td"><div>6,230</div><div>6,660</div><div>6,660</div></td>
                            <td class="w4-wage-td"><div>7,430</div><div>7,860</div><div>7,860</div></td>
                            <td class="w4-wage-td"><div>8,630</div><div>9,060</div><div>9,060</div></td>
                            <td class="w4-wage-td"><div>9,330</div><div>9,760</div><div>9,950</div></td>
                            <td class="w4-wage-td"><div>9,530</div><div>9,960</div><div>10,950</div></td>
                            <td class="w4-wage-td"><div>9,930</div><div>10,160</div><div>11,950</div></td>
                            <td class="w4-wage-td"><div>10,130</div><div>10,390</div><div>12,950</div></td>
                            <td class="w4-wage-td"><div>10,580</div><div>10,950</div><div>13,950</div></td>
                            <td class="w4-wage-td"><div>10,580</div><div>12,950</div><div>14,950</div></td>
                        </tr>
                        <tr>
                            <td class="w4-wage-td"><div>$150,000 - 174,999</div><div>$175,000 - 199,999</div><div>$200,000 - 249,999</div></td>
                            <td class="w4-wage-td"><div>2,040</div><div>2,040</div><div>2,720</div></td>
                            <td class="w4-wage-td"><div>4,090</div><div>4,290</div><div>5,570</div></td>
                            <td class="w4-wage-td"><div>5,460</div><div>6,450</div><div>7,900</div></td>
                            <td class="w4-wage-td"><div>6,660</div><div>8,450</div><div>10,200</div></td>
                            <td class="w4-wage-td"><div>8,450</div><div>10,450</div><div>12,500</div></td>
                            <td class="w4-wage-td"><div>10,450</div><div>12,450</div><div>14,800</div></td>
                            <td class="w4-wage-td"><div>11,950</div><div>13,950</div><div>16,600</div></td>
                            <td class="w4-wage-td"><div>12,950</div><div>15,230</div><div>17,900</div></td>
                            <td class="w4-wage-td"><div>13,950</div><div>16,530</div><div>19,200</div></td>
                            <td class="w4-wage-td"><div>15,080</div><div>17,830</div><div>20,500</div></td>
                            <td class="w4-wage-td"><div>16,380</div><div>19,130</div><div>21,800</div></td>
                            <td class="w4-wage-td"><div>17,680</div><div>20,430</div><div>23,100</div></td>
                        </tr>
                        <tr>
                            <td class="w4-wage-td"><div>$250,000 - 399,999</div><div>$400,000 - 449,999</div><div>$450,000 and over</div></td>
                            <td class="w4-wage-td"><div>2,970</div><div>2,970</div><div>3,140</div></td>
                            <td class="w4-wage-td"><div>6,120</div><div>6,120</div><div>6,490</div></td>
                            <td class="w4-wage-td"><div>8,590</div><div>8,590</div><div>9,160</div></td>
                            <td class="w4-wage-td"><div>10,890</div><div>10,890</div><div>11,660</div></td>
                            <td class="w4-wage-td"><div>13,190</div><div>13,190</div><div>14,160</div></td>
                            <td class="w4-wage-td"><div>15,490</div><div>15,490</div><div>16,660</div></td>
                            <td class="w4-wage-td"><div>17,290</div><div>17,290</div><div>18,660</div></td>
                            <td class="w4-wage-td"><div>18,590</div><div>18,590</div><div>20,160</div></td>
                            <td class="w4-wage-td"><div>19,890</div><div>19,890</div><div>21,160</div></td>
                            <td class="w4-wage-td"><div>21,190</div><div>21,190</div><div>23,160</div></td>
                            <td class="w4-wage-td"><div>22,490</div><div>22,490</div><div>24,660</div></td>
                            <td class="w4-wage-td"><div>23,790</div><div>23,790</div><div>26,160</div></td>
                        </tr>
                    </tbody>
                </table>

                <div class="w4-table-title w4-table-title-mt">Head of Household</div>
                <table class="w4-wage-table w4-wage-full">
                    <tbody>
                        <tr>
                            <td class="w4-wage-first-col" rowspan="2"><b>Head of Household</b></td>
                            <td class="w4-wage-header-col" colspan="12"><b>Lower Paying Job Annual Taxable Wage &amp; Salary</b></td>
                        </tr>
                        <tr>
                            <td class="w4-wage-th">$0 - 9,999</td>
                            <td class="w4-wage-th">$10,000 - 19,999</td>
                            <td class="w4-wage-th">$20,000 - 29,999</td>
                            <td class="w4-wage-th">$30,000 - 39,999</td>
                            <td class="w4-wage-th">$40,000 - 49,999</td>
                            <td class="w4-wage-th">$50,000 - 59,999</td>
                            <td class="w4-wage-th">$60,000 - 69,999</td>
                            <td class="w4-wage-th">$70,000 - 79,999</td>
                            <td class="w4-wage-th">$80,000 - 89,999</td>
                            <td class="w4-wage-th">$90,000 - 99,999</td>
                            <td class="w4-wage-th">$100,000- 109,999</td>
                            <td class="w4-wage-th">$110,000- 120,000</td>
                        </tr>
                        <tr>
                            <td class="w4-wage-td"><div>$0 - 9,999</div><div>$10,000 - 19,999</div><div>$20,000 - 29,999</div></td>
                            <td class="w4-wage-td"><div>$0</div><div>450</div><div>850</div></td>
                            <td class="w4-wage-td"><div>$450</div><div>1,450</div><div>2,000</div></td>
                            <td class="w4-wage-td"><div>$850</div><div>2,000</div><div>2,600</div></td>
                            <td class="w4-wage-td"><div>$1,000</div><div>2,200</div><div>2,800</div></td>
                            <td class="w4-wage-td"><div>$1,020</div><div>2,220</div><div>2,820</div></td>
                            <td class="w4-wage-td"><div>$1,020</div><div>2,220</div><div>2,820</div></td>
                            <td class="w4-wage-td"><div>$1,020</div><div>2,220</div><div>3,780</div></td>
                            <td class="w4-wage-td"><div>$1,020</div><div>3,180</div><div>4,780</div></td>
                            <td class="w4-wage-td"><div>$1,870</div><div>4,070</div><div>5,670</div></td>
                            <td class="w4-wage-td"><div>$1,870</div><div>4,070</div><div>5,690</div></td>
                            <td class="w4-wage-td"><div>$1,870</div><div>4,090</div><div>5,890</div></td>
                            <td class="w4-wage-td"><div>$1,890</div><div>4,290</div><div>6,090</div></td>
                        </tr>
                        <tr>
                            <td class="w4-wage-td"><div>$30,000 - 39,999</div><div>$40,000 - 59,999</div><div>$60,000 - 79,999</div></td>
                            <td class="w4-wage-td"><div>1,000</div><div>1,020</div><div>1,020</div></td>
                            <td class="w4-wage-td"><div>2,200</div><div>2,220</div><div>3,030</div></td>
                            <td class="w4-wage-td"><div>2,800</div><div>2,820</div><div>4,630</div></td>
                            <td class="w4-wage-td"><div>3,000</div><div>3,830</div><div>5,830</div></td>
                            <td class="w4-wage-td"><div>3,020</div><div>4,850</div><div>6,850</div></td>
                            <td class="w4-wage-td"><div>3,980</div><div>5,850</div><div>8,050</div></td>
                            <td class="w4-wage-td"><div>4,980</div><div>6,850</div><div>9,250</div></td>
                            <td class="w4-wage-td"><div>5,980</div><div>8,050</div><div>10,450</div></td>
                            <td class="w4-wage-td"><div>6,890</div><div>9,130</div><div>11,530</div></td>
                            <td class="w4-wage-td"><div>7,090</div><div>9,330</div><div>11,730</div></td>
                            <td class="w4-wage-td"><div>7,290</div><div>9,530</div><div>11,930</div></td>
                            <td class="w4-wage-td"><div>7,490</div><div>9,730</div><div>12,130</div></td>
                        </tr>
                        <tr>
                            <td class="w4-wage-td"><div>$80,000 - 99,999</div><div>$100,000 - 124,999</div><div>$125,000 - 149,999</div></td>
                            <td class="w4-wage-td"><div>1,870</div><div>1,950</div><div>2,040</div></td>
                            <td class="w4-wage-td"><div>4,070</div><div>4,350</div><div>4,440</div></td>
                            <td class="w4-wage-td"><div>5,670</div><div>6,150</div><div>6,240</div></td>
                            <td class="w4-wage-td"><div>7,060</div><div>7,550</div><div>7,640</div></td>
                            <td class="w4-wage-td"><div>8,280</div><div>8,770</div><div>8,860</div></td>
                            <td class="w4-wage-td"><div>9,480</div><div>9,970</div><div>10,060</div></td>
                            <td class="w4-wage-td"><div>10,680</div><div>11,170</div><div>11,260</div></td>
                            <td class="w4-wage-td"><div>11,880</div><div>12,370</div><div>12,860</div></td>
                            <td class="w4-wage-td"><div>12,970</div><div>13,450</div><div>14,740</div></td>
                            <td class="w4-wage-td"><div>13,170</div><div>13,650</div><div>15,740</div></td>
                            <td class="w4-wage-td"><div>13,370</div><div>14,650</div><div>16,740</div></td>
                            <td class="w4-wage-td"><div>13,570</div><div>15,650</div><div>17,740</div></td>
                        </tr>
                        <tr>
                            <td class="w4-wage-td"><div>$150,000 - 174,999</div><div>$175,000 - 199,999</div><div>$200,000 - 249,999</div></td>
                            <td class="w4-wage-td"><div>2,040</div><div>2,040</div><div>2,720</div></td>
                            <td class="w4-wage-td"><div>4,440</div><div>4,440</div><div>5,920</div></td>
                            <td class="w4-wage-td"><div>6,240</div><div>6,640</div><div>8,520</div></td>
                            <td class="w4-wage-td"><div>7,640</div><div>8,840</div><div>10,960</div></td>
                            <td class="w4-wage-td"><div>8,860</div><div>10,860</div><div>13,280</div></td>
                            <td class="w4-wage-td"><div>10,860</div><div>12,860</div><div>15,580</div></td>
                            <td class="w4-wage-td"><div>12,860</div><div>14,860</div><div>17,880</div></td>
                            <td class="w4-wage-td"><div>14,860</div><div>16,910</div><div>20,180</div></td>
                            <td class="w4-wage-td"><div>16,740</div><div>19,090</div><div>22,360</div></td>
                            <td class="w4-wage-td"><div>17,740</div><div>20,390</div><div>23,660</div></td>
                            <td class="w4-wage-td"><div>18,940</div><div>21,690</div><div>24,960</div></td>
                            <td class="w4-wage-td"><div>20,240</div><div>22,990</div><div>26,260</div></td>
                        </tr>
                        <tr>
                            <td class="w4-wage-td"><div>$250,000 - 449,999</div><div>$450,000 and over</div></td>
                            <td class="w4-wage-td"><div>2,970</div><div>3,140</div></td>
                            <td class="w4-wage-td"><div>6,470</div><div>6,840</div></td>
                            <td class="w4-wage-td"><div>9,370</div><div>9,940</div></td>
                            <td class="w4-wage-td"><div>11,870</div><div>12,640</div></td>
                            <td class="w4-wage-td"><div>14,190</div><div>15,160</div></td>
                            <td class="w4-wage-td"><div>16,490</div><div>17,660</div></td>
                            <td class="w4-wage-td"><div>18,790</div><div>20,160</div></td>
                            <td class="w4-wage-td"><div>21,090</div><div>22,660</div></td>
                            <td class="w4-wage-td"><div>23,280</div><div>25,050</div></td>
                            <td class="w4-wage-td"><div>24,580</div><div>26,550</div></td>
                            <td class="w4-wage-td"><div>25,880</div><div>28,050</div></td>
                            <td class="w4-wage-td"><div>27,180</div><div>29,550</div></td>
                        </tr>
                    </tbody>
                </table>
                <hr class="w4-hr" />
            </div>

            <p v-if="submitError" class="i9-error">{{ submitError }}</p>
            <div class="i9-form-actions">
                <button type="button" class="i9-btn i9-btn-back" @click="goToBackStep">Back</button>
                <button type="submit" class="i9-btn i9-btn-primary" :disabled="submitting">{{ submitting ? 'Saving...' : 'Save & Next' }}</button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import axios from 'axios'
import { isSSNValid, formatSSN } from '@/utils/ssn'
import Input from '@/components/ui/input.vue'

const props = defineProps({
    onboardingId: { type: String, default: '' },
    goToNextStep: { type: Function, default: () => {} },
    goToBackStep: { type: Function, default: () => {} },
})

const submitting = ref(false)
const submitError = ref('')
const company = ref(null)
/** 1 = Single/MFS, 2 = Married joint, 3 = Head of household */
const maritalStatus = ref(null)

const form = reactive({
    first_name: '',
    last_name: '',
    middle_initial: '',
    social_security_number: '',
    address: '',
    city: '',
    state: '',
    zip_code: '',
    city_state_zip: '',
    multiple_jobs_and_spouse_works: null,
    qualifying_children: '',
    dependents: '',
    total_amount: '',
    other_income: '',
    deductions: '',
    extra_withholding: '',
    employee_sign: '',
    employee_date: '',
    employer_name: '',
    date_of_employment: '',
})

/** Page 3 worksheets (saved to w4_forms) */
const worksheet = reactive({
    line1: '',
    line2a: '',
    line2b: '',
    line2c: '',
    line3: '',
    line4: '',
    ded1: '',
    ded2: '',
    ded3: '',
    ded4: '',
    ded5: '',
})

// Keep city_state_zip in sync with city, state, zip_code for display when loading or when city/state/zip change
function updateCityStateZip() {
    const parts = [form.city, form.state, form.zip_code].filter(Boolean)
    form.city_state_zip = parts.join(', ')
}
watch(() => [form.city, form.state, form.zip_code], updateCityStateZip, { deep: true })

function getCsrfHeaders() {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    return token ? { 'X-CSRF-TOKEN': token } : {}
}

onMounted(() => { fetchW4Data() })

async function fetchW4Data() {
    if (!props.onboardingId) return
    try {
        const { data: res } = await axios.get('/onboarding-process/w4-data', {
            params: { onboardingId: props.onboardingId },
            headers: getCsrfHeaders(),
            withCredentials: true,
        })
        if (res.success && res.form) {
            const f = res.form
            form.first_name = f.first_name ?? ''
            form.last_name = f.last_name ?? ''
            form.middle_initial = f.middle_initial ?? ''
            form.social_security_number = f.social_security_number ?? ''
            form.address = f.address ?? ''
            form.city = f.city ?? ''
            form.state = f.state ?? ''
            form.zip_code = f.zip_code ?? ''
            form.city_state_zip = f.city_state_zip ?? [f.city, f.state, f.zip_code].filter(Boolean).join(', ')
            if (f.single_or_married === 1 || f.single_or_married === '1') maritalStatus.value = 1
            else if (f.married_filing === 1 || f.married_filing === '1') maritalStatus.value = 2
            else if (f.head_of_household === 1 || f.head_of_household === '1') maritalStatus.value = 3
            else maritalStatus.value = null
            form.multiple_jobs_and_spouse_works = f.multiple_jobs_and_spouse_works ?? null
            form.qualifying_children = f.qualifying_children ?? ''
            form.dependents = f.dependents ?? ''
            form.total_amount = f.total_amount ?? ''
            form.other_income = f.other_income ?? ''
            form.deductions = f.deductions ?? ''
            form.extra_withholding = f.extra_withholding ?? ''
            form.employee_sign = f.employee_sign ?? ''
            form.employee_date = f.employee_date ?? ''
            form.employer_name = f.employer_name ?? ''
            form.date_of_employment = f.date_of_employment ?? ''
            worksheet.line1 = f.worksheet_line1 ?? ''
            worksheet.line2a = f.worksheet_line2a ?? ''
            worksheet.line2b = f.worksheet_line2b ?? ''
            worksheet.line2c = f.worksheet_line2c ?? ''
            worksheet.line3 = f.worksheet_line3 ?? ''
            worksheet.line4 = f.worksheet_line4 ?? ''
            worksheet.ded1 = f.worksheet_ded1 ?? ''
            worksheet.ded2 = f.worksheet_ded2 ?? ''
            worksheet.ded3 = f.worksheet_ded3 ?? ''
            worksheet.ded4 = f.worksheet_ded4 ?? ''
            worksheet.ded5 = f.worksheet_ded5 ?? ''
        }
        if (res.company) company.value = res.company
    } catch (err) {
        console.error('Error fetching W-4 data:', err)
    }
}

function parseCityStateZip(str) {
    if (!str || !str.trim()) return { city: null, state: null, zip_code: null }
    const parts = str.split(',').map((p) => p.trim())
    if (parts.length >= 3) return { city: parts[0] || null, state: parts[1] || null, zip_code: parts[2] || null }
    if (parts.length === 2) return { city: parts[0] || null, state: parts[1] || null, zip_code: null }
    return { city: parts[0] || null, state: null, zip_code: null }
}

async function submit() {
    submitError.value = ''
    if (form.social_security_number && !isSSNValid(form.social_security_number)) {
        submitError.value = 'Please ensure a valid 9-digit Social Security Number is on file (e.g. XXX-XX-XXXX).'
        return
    }
    if (!form.first_name?.trim() || !form.last_name?.trim() || !form.employee_sign?.trim()) {
        submitError.value = 'First name, last name, and signature are required.'
        return
    }
    submitting.value = true
    try {
        const parsed = parseCityStateZip(form.city_state_zip)
        const city = form.city || parsed.city
        const state = form.state || parsed.state
        const zip_code = form.zip_code || parsed.zip_code
        const payload = {
            onboardingId: props.onboardingId,
            first_name: form.first_name.trim(),
            last_name: form.last_name.trim(),
            middle_initial: form.middle_initial || null,
            social_security_number: form.social_security_number ? (formatSSN(form.social_security_number) || form.social_security_number) : null,
            address: form.address || null,
            city: city || null,
            state: state || null,
            zip_code: zip_code || null,
            single_or_married: maritalStatus.value === 1 ? 1 : null,
            married_filing: maritalStatus.value === 2 ? 1 : null,
            head_of_household: maritalStatus.value === 3 ? 1 : null,
            multiple_jobs_and_spouse_works: form.multiple_jobs_and_spouse_works ?? null,
            qualifying_children: form.qualifying_children || null,
            dependents: form.dependents || null,
            total_amount: form.total_amount || null,
            other_income: form.other_income || null,
            deductions: form.deductions || null,
            extra_withholding: form.extra_withholding || null,
            employee_sign: form.employee_sign.trim(),
            employee_date: form.employee_date || new Date().toISOString().slice(0, 10),
            employer_name: form.employer_name || null,
            date_of_employment: form.date_of_employment || null,
            worksheet_line1: worksheet.line1 || null,
            worksheet_line2a: worksheet.line2a || null,
            worksheet_line2b: worksheet.line2b || null,
            worksheet_line2c: worksheet.line2c || null,
            worksheet_line3: worksheet.line3 || null,
            worksheet_line4: worksheet.line4 || null,
            worksheet_ded1: worksheet.ded1 || null,
            worksheet_ded2: worksheet.ded2 || null,
            worksheet_ded3: worksheet.ded3 || null,
            worksheet_ded4: worksheet.ded4 || null,
            worksheet_ded5: worksheet.ded5 || null,
        }
        await axios.post('/onboarding-process/save-w4', payload, {
            headers: { ...getCsrfHeaders(), 'Content-Type': 'application/json' },
            withCredentials: true,
        })
        props.goToNextStep()
    } catch (err) {
        submitError.value = err.response?.data?.message || err.response?.data?.error || 'Failed to save Form W-4. Please try again.'
    } finally {
        submitting.value = false
    }
}
</script>

<style scoped>
.w4-form-wrap {
    margin: 0;
    padding: 1rem 1.25rem 1.5rem;
    font-size: 11px;
    line-height: 14px;
    background: #fff;
    border-radius: 6px;
}
.w4-form-body {
    max-width: 100%;
}
.w4-form-body .inner {
    margin-bottom: 1.5rem;
    padding: 0 0.25rem;
}
.w4-form-wrap :deep(table) {
    border-collapse: collapse;
    width: 100%;
}
.w4-form-wrap :deep(.w4-table td) {
    border: 1px solid #000;
    padding: 4px 8px;
    vertical-align: top;
    overflow: visible;
    box-sizing: border-box;
}
/* Inputs fill cell but content is not clipped */
.w4-form-wrap :deep(.w4-input-cell),
.w4-form-wrap :deep(.w4-amount-inline) {
    width: 100%;
    box-sizing: border-box;
}
.w4-form-wrap :deep(.w4-input-cell input),
.w4-form-wrap :deep(.w4-amount-inline input),
.w4-form-wrap :deep(.w4-input-cell .relative > div) {
    width: 100%;
    box-sizing: border-box;
    min-width: 0;
}
.w4-form-wrap :deep(.w4-input-cell > div),
.w4-form-wrap :deep(.w4-amount-inline > div),
.w4-form-wrap :deep(.w4-input-cell .relative) {
    width: 100%;
    min-width: 0;
}
.w4-header-table {
    width: 100%;
    border-bottom: 1px solid #000;
    font-size: 11px;
    line-height: 14px;
}
.w4-header-table td {
    border: none !important;
    padding: 8px !important;
    vertical-align: top;
}
.w4-header-left { width: 20%; border-right: 1px solid #000 !important; }
.w4-header-middle { width: 60%; text-align: center; }
.w4-header-right { width: 20%; border-left: 1px solid #000 !important; text-align: right; }
.w4-header-inline { display: inline-block; }
.w4-form-label { font-size: 14px; font-weight: bold; display: inline-block; }
.w4-form-w4 { font-size: 26px; font-weight: bold; display: inline-block; margin-left: 10px; }
.w4-dept { font-size: 12px; line-height: 14px; margin-top: 8px; }
.w4-middle-part { margin-top: 0; margin-bottom: 5px; }
.w4-title-main { font-size: 18px; font-weight: 600; text-align: center; }
.w4-title-sub { font-size: 12px; line-height: 14px; text-align: center; margin-top: 5px; }
.w4-omb { font-size: 12px; border-bottom: 2px solid #000; margin-bottom: 15px; }
.w4-year { font-size: 36px; font-weight: bold; }
.w4-step-cell {
    font-weight: bold;
    width: 10%;
    font-size: 14px;
    line-height: 18px;
    vertical-align: baseline;
}
.w4-field-cell { width: auto; }
.w4-inline-label { display: block; margin-bottom: 2px; }
.w4-input-cell { margin-top: 5px;}
.w4-ssa-note { width: 20%; font-size: 11px; line-height: 14px; vertical-align: baseline; }
.w4-marital-row { display: flex; gap: 10px; }
.w4-check-label { display: block; margin-bottom: 4px; cursor: pointer; }
.w4-tip-block { margin-top: 5px; margin-bottom: 8px; }
.w4-tip-p { font-size: 11px; line-height: 14px; margin: 0 0 5px; }
.w4-no-bottom { border-bottom: none !important; }
.w4-step2-cell { padding: 10px !important; border-left: none !important; }
.w4-p { font-size: 11px; line-height: 14px; margin: 0 0 8px; }
.w4-radio-row { margin-bottom: 8px; }
.w4-note-cell { padding: 8px !important; border-top: none !important; font-size: 11px; }
.w4-step3-label { font-size: 14px; line-height: 18px; }
.w4-step3-desc { width: 70%; padding: 10px !important; }
.w4-step-num { width: 28px; vertical-align: bottom; font-weight: bold; }
.w4-step-input-cell { vertical-align: bottom; min-width: 90px; }
.w4-dollar { font-size: 11px; line-height: 14px; }
.w4-amount-inline { display: inline-block; width: 90px; vertical-align: middle; }
.indent { padding-left: 25px; }
.margin-bottom-0 { margin-bottom: 0 !important; }
.w4-step4-desc { padding: 5px 8px !important; font-size: 11px; line-height: 14px; }
.w4-step4-num { width: 28px; vertical-align: bottom; font-size: 11px; }
.w4-sign-step { font-size: 14px; line-height: 20px; border-right: 1px solid #000; }
.w4-sign-declare { padding: 5px 8px !important; border-bottom: none !important; border-top: none !important; }
.w4-sign-cell { padding: 5px 10px !important; }
.w4-employer-label { font-size: 14px; line-height: 20px; border-right: 1px solid #000; }
.w4-employer-cell { padding: 5px 8px !important; }
.w4-page-footer { display: flex; justify-content: space-between; font-size: 11px; margin-top: 8px; }
.w4-hr { border-color: #BF162F; border-width: 3px; opacity: 1; margin: 1rem 0; }
/* Page 2: Instructions */
.w4-page-head { display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 2px; }
.w4-hr-thin { border-color: #000; border-width: 2px; opacity: 1; margin: 2px 0 8px; }
.w4-instructions-table { width: 100%; font-size: 11px; line-height: 14px; border: none; }
.w4-instructions-table td { border: none !important; padding: 8px 20px 8px 0; vertical-align: top; }
.w4-inst-col { width: 50%; }
.w4-inst-col + .w4-inst-col { padding-left: 20px !important; padding-right: 0 !important; }
.w4-inst-title { font-size: 18px; font-weight: 600; margin: 0 0 5px; }
.w4-inst-subtitle { font-size: 14px; font-weight: 600; margin: 10px 0 5px; }
.w4-instructions-table p { margin: 0 0 8px; }
.w4-instructions-table a { color: #0d6efd; }
/* Page 3: Worksheets */
.w4-worksheet-title { font-size: 14px; line-height: 16px; text-align: center; border-bottom: 2px solid #000; padding-bottom: 3px; margin: 0 0 5px; }
.w4-worksheet-table { width: 100%; font-size: 11px; line-height: 14px; border-collapse: collapse; margin-bottom: 8px; }
.w4-worksheet-table td { padding: 5px 8px; vertical-align: bottom; border: none; }
.w4-ws-desc { width: 80%; }
.w4-ws-desc.indent { padding-left: 30px; }
.w4-ws-input { text-align: right; white-space: nowrap; width: 20%; min-width: 100px; }
.w4-ws-inp { display: inline-block; width: 90px; }
.w4-form-wrap :deep(.w4-ws-inp input) { height: 20px; border: none; border-bottom: 1px solid #000; background: #EDF1FA; line-height: 26px; width: 100%; box-sizing: border-box; }
.w4-privacy-table { width: 100%; font-size: 11px; line-height: 14px; border-collapse: collapse; margin-top: 8px; }
.w4-privacy-table td { padding: 5px 8px; vertical-align: top; border: none; }
.w4-privacy-col { width: 50%; }
/* Page 4: Wage tables */
.w4-table-title { font-size: 14px; line-height: 16px; text-align: center; border-bottom: 2px solid #000; padding-bottom: 3px; margin: 10px 0 5px; }
.w4-table-title-mt { margin-top: 10px; }
.w4-table-note { font-size: 11px; line-height: 14px; margin: 0 0 8px; }
.w4-wage-table { width: 100%; font-size: 11px; line-height: 14px; border-collapse: collapse; text-align: center; margin-bottom: 12px; }
.w4-wage-table.w4-wage-full { border: 1px solid #000; border-top: none; }
.w4-wage-table td, .w4-wage-table th { border: 1px solid #000; padding: 3px 8px; }
.w4-wage-first-col { width: 15%; padding: 5px 8px !important; vertical-align: middle; }
.w4-wage-header-col { text-align: center; padding: 5px 8px !important; border-left: 1px solid #000 !important; }
.w4-wage-th { font-weight: bold; }
.w4-wage-td { font-size: 10px; }
.w4-wage-td div { margin: 0; line-height: 1.2; }
/* Reuse I9 button styles (same as Step4I9Form) */
.i9-error { color: #b1151d; font-size: 13px; margin: 1rem 0; padding: 0.75rem 1rem; background: #fef2f2; border-radius: 6px; border-left: 4px solid #b1151d; }
.i9-form-actions { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-top: 2rem; padding: 1.25rem 0 0.5rem; border-top: 1px solid #e5e5e5; }
.i9-btn { padding: 0.6rem 1.25rem; font-size: 14px; border-radius: 6px; cursor: pointer; border: 1px solid #ccc; background: #f5f5f5; transition: background 0.15s, border-color 0.15s; }
.i9-btn:hover:not(:disabled) { background: #ebebeb; border-color: #aaa; }
.i9-btn:disabled { opacity: 0.7; cursor: not-allowed; }
.i9-btn-primary { background: #0d6efd; color: #fff; border-color: #0d6efd; }
.i9-btn-primary:hover:not(:disabled) { background: #0b5ed7; border-color: #0b5ed7; }
.i9-btn-back { background: #fff; color: #333; }
input[type="radio"]:focus { outline: none !important; box-shadow: none !important; border: none !important; background: transparent !important; }
</style>
