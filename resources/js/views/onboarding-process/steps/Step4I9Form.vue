<template>
    <div class="i9-form-wrap max-h-screen overflow-y-auto">
        <form @submit.prevent="submit" class="i9-form-body">
            <!-- Page 1 -->
            <div class="inner i9-page-1">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <td width="20%">
                                <div><img :src="i9LogoUrl" alt="USCIS" style="max-width: 80px;" /></div>
                            </td>
                            <td width="60%" style="text-align: center;">
                                <h2 style="margin-top:0;margin-bottom: 5px;font-size: 18px;font-weight: 600;">Employment Eligibility Verification</h2>
                                <h4 style="margin:0;margin-bottom: 5px;font-size: 14px;font-weight: 600;">Department of Homeland Security</h4>
                                <p style="margin:0;font-size: 12px;">U.S. Citizenship and Immigration Services</p>
                            </td>
                            <td width="20%" style="text-align: center;">
                                <h5 style="margin:0;font-size: 14px;font-weight: 600;">USCIS</h5>
                                <h5 style="margin:0;font-size: 14px;font-weight: 600;">Form I-9</h5>
                                <p style="margin:0;font-size: 12px;">OMB No.1615-0047</p>
                                <p style="margin:0;font-size: 12px;">Expires 07/31/2026</p>
                            </td>
                        </tr>
                    </thead>

                </table>

                <hr style="border-color: #000;border-width: 2px;opacity: 1;" />

                <div>
                    <p style="font-size: 11px;line-height: 13px;">
                        <b>START HERE: Employers must ensure the form instructions are available to employees when completing this form. Employers are liable for failing to comply with the requirements for completing this form. See below and the <a href="https://www.uscis.gov/i-9" target="_blank" style="color: #b1151d;">Instructions.</a></b>
                    </p>
                    <p style="font-size: 11px;line-height: 13px;">
                        <b>ANTI-DISCRIMINATION NOTICE:</b> All employees can choose which acceptable documentation to present for Form I-9. Employers cannot ask employees for documentation to verify information in <b>Section 1</b>, or specify which acceptable documentation employees must present for <b>Section 2</b> or Supplement B, Reverification and Rehire. Treating employees differently based on their citizenship, immigration status, or national origin may be illegal.
                    </p>
                </div>

                <table style="width: 100%;border-collapse: collapse;">
                    <tr>
                        <td style="border: 1px solid #000;padding: 1px 8px;background-color: #d3d3d3;font-size: 11px;line-height: 13px;" colspan="5">
                            <b>Section 1. Employee Information and Attestation:</b> Employees must complete and sign Section 1 of Form I-9 no later than the <b>first day of employment,</b> but not before accepting a job offer.
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000;padding: 1px 8px;">
                            <Input v-model="section1.last_name" label="Last Name (Family Name)" size="sm" />
                        </td>
                        <td style="border: 1px solid #000;padding: 1px 8px;">
                            <Input v-model="section1.first_name" label="First Name (Given Name)" size="sm" />
                        </td>
                        <td style="border: 1px solid #000;padding: 1px 8px;">
                            <Input v-model="section1.middle_initial" label="Middle Initial (if any)" size="sm" />
                        </td>
                        <td style="border: 1px solid #000;padding: 1px 8px;" colspan="2">
                            <Input v-model="form.other_last_names" label="Other Last Names Used (if any)" size="sm" />
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000;padding: 1px 8px;" width="28%">
                            <Input v-model="section1.address" label="Address (Street Number and Name)" size="sm" />
                        </td>
                        <td style="border: 1px solid #000;padding: 1px 8px;">
                            <Input v-model="section1.apt_number" label="Apt. Number (if any)" size="sm" />
                        </td>
                        <td style="border: 1px solid #000;padding: 1px 8px;">
                            <Input v-model="section1.city" label="City or Town" size="sm" />
                        </td>
                        <td style="border: 1px solid #000;padding: 1px 8px;">
                            <Input v-model="section1.state" label="State" size="sm" />
                        </td>
                        <td style="border: 1px solid #000;padding: 1px 8px;">
                            <Input v-model="section1.zipcode" label="ZIP Code" size="sm" />
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000;padding: 1px 8px;">
                            <Input v-model="section1.date_of_birth" type="date" label="Date of Birth (dd/mm/yyyy)" size="sm" />
                        </td>
                        <td style="border: 1px solid #000;padding: 1px 8px;" width="23%">
                            <Input v-model="section1.social_security_number" label="U.S. Social Security Number" placeholder="XXX-XX-XXXX" size="sm" />
                        </td>
                        <td style="border: 1px solid #000;padding: 1px 8px;">
                            <Input v-model="section1.employee_email" label="Employee's Email" size="sm" :readonly="true" />
                        </td>
                        <td style="border: 1px solid #000;padding: 1px 8px;" colspan="2">
                            <Input v-model="section1.employee_telephone" label="Employee's Telephone Number" size="sm" />
                        </td>
                    </tr>
                </table>

                <table style="width: 100%;border-collapse: collapse;">
                    <tr>
                        <td style="border: 1px solid #000;padding: 1px 8px;width: 25%;font-size: 11px;line-height: 13px;">
                            <b>I am aware that federal law provides for imprisonment and/or fines for false statements, or the use of false documents, in connection with the completion of this form. I attest, under penalty of perjury, that this information, including my selection of the box attesting to my citizenship or immigration status, is true and correct.</b>
                        </td>
                        <td style="border: 1px solid #000;padding: 1px 8px;font-size: 11px;line-height: 13px;">
                            <label style="margin-bottom: 10px;display: inline-block;font-size: 11px;"><strong>Check one of the following boxes to attest to your citizenship or immigration status (See page 2 and 3 of the instructions.):</strong></label>
                            <div class="mb-1">&nbsp;&nbsp;
                                <input type="radio" :value="1" v-model="form.citizenship_status" /><span style="margin-left: 5px;vertical-align: top;">1. A citizen of the United States</span>
                            </div>
                            <div class="mb-1">&nbsp;&nbsp;
                                <input type="radio" :value="2" v-model="form.citizenship_status" /><span style="margin-left: 5px;vertical-align: top;">2. A noncitizen national of the United States</span>
                            </div>
                            <div class="mb-1">&nbsp;&nbsp;
                                <input type="radio" :value="3" v-model="form.citizenship_status" /><span style="margin-left: 5px;vertical-align: top;">3. A lawful permanent resident (Enter USCIS or A-Number.)</span>&nbsp;
                                <Input v-model="form.uscis_or_a_number" size="sm" />
                            </div>
                            <div class="mb-1">&nbsp;&nbsp;
                                <input type="radio" :value="4" v-model="form.citizenship_status" /><span style="margin-left: 5px;vertical-align: top;">4. An alien authorized to work until (exp. date, if any)</span>
                                <Input v-model="form.alien_authorized_exp_date" size="sm" type="date" />
                            </div>
                            <label style="display: inline-block;margin-bottom: 5px;font-size: 11px;">If you check Item <strong>Number 4</strong>, enter one of these:</label>
                            <div style="display: table; width: 100%;margin-top: 5px;">
                                <div style="display: flex;">
                                    <div style="padding: 5px; height: 100%;">
                                        <Input v-model="form.uscis_a_number" label="USCIS A-Number" size="sm" />
                                    </div>
                                    <div style="padding:5px;font-weight: bold;font-size: 12px;text-align: center;">OR</div>
                                    <div style="padding: 5px; height: 100%;">
                                        <Input v-model="form.form_i94_admission_number" label="Form I-94 Admission Number" size="sm" />
                                    </div>
                                    <div style="padding:5px;font-weight: bold;font-size: 12px;text-align: center;">OR</div>
                                    <div style="padding: 5px; height: 100%;">
                                        <Input v-model="form.foreign_passport_number" label="Foreign Passport Number and Country of Issuance" size="sm" />
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000;padding: 1px 8px;">
                            <Input v-model="form.employee_signature" label="Signature of Employee (required)" size="sm" />
                        </td>
                        <td style="border: 1px solid #000;padding: 1px 8px;">
                            <Input v-model="form.section1_today_date" label="Today's Date (dd/mm/yyyy)" type="date" size="sm" placeholder="mm/dd/yyyy" />
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000;padding: 1px 5px;font-size: 10px;font-weight: 500;" colspan="2">If a preparer and/or translator assisted you in completing Section 1, that person MUST complete the <a href="https://www.uscis.gov/i-9" target="_blank" style="color: #b1151d;">Preparer and/or Translator Certification</a> on Page 3.</td>
                    </tr>
                </table>

                <table style="width: 100%;border-collapse: collapse;font-size: 11px;">
                    <tr>
                        <td style="border: 1px solid #000;padding: 1px 8px;background-color: #d3d3d3;">
                            <b>Section 2. Employer Review and Verification:</b> Employers or their authorized representative must complete and sign Section 2 within three business days after the employee's first day of employment, and must physically examine, or examine consistent with an alternative procedure authorized by the Secretary of DHS, documentation from List A OR a combination of documentation from List B and List C. Enter any additional documentation in the Additional Information box; see Instructions.
                        </td>
                    </tr>
                </table>

                <table style="width: 100%;border-collapse: collapse;font-size: 11px;">
                    <tr>
                        <td></td>
                        <td style="text-align:center;"><b>List A</b></td>
                        <td style="background-color:#d3d3d3;width: 2%;font-size: 12px;"><b>OR</b></td>
                        <td style="text-align:center;"><b>List B</b></td>
                        <td style="text-align:center;"><b>List C</b></td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000;padding: 1px 8px;background-color: #d3d3d3;width: 18%;"><b>Document Title 1</b></td>
                        <td style="border: 1px solid #000;padding: 1px 8px;width: 20%;">
                            <select v-model="form.list_a_doc_title_1" class="i9-section2-select">
                                <option value="">Select document type</option>
                                <optgroup label="Primary Documents">
                                    <option :value="docs.list_a_doc_title_1[1].key">{{ docs.list_a_doc_title_1[1].value }}</option>
                                    <option :value="docs.list_a_doc_title_1[2].key">{{ docs.list_a_doc_title_1[2].value }}</option>
                                    <option :value="docs.list_a_doc_title_1[3].key">{{ docs.list_a_doc_title_1[3].value }}</option>
                                    <option :value="docs.list_a_doc_title_1[4].key">{{ docs.list_a_doc_title_1[4].value }}</option>
                                    <option :value="docs.list_a_doc_title_1[5].key">{{ docs.list_a_doc_title_1[5].value }}</option>
                                </optgroup>
                                <optgroup label="Work Authorization">
                                    <option :value="docs.list_a_doc_title_1['5a'].key">{{ docs.list_a_doc_title_1['5a'].value }}</option>
                                    <option :value="docs.list_a_doc_title_1['5b'].key">{{ docs.list_a_doc_title_1['5b'].value }}</option>
                                    <option :value="docs.list_a_doc_title_1['5(1)'].key">{{ docs.list_a_doc_title_1['5(1)'].value }}</option>
                                    <option :value="docs.list_a_doc_title_1['5(2)'].key">{{ docs.list_a_doc_title_1['5(2)'].value }}</option>
                                </optgroup>
                                <optgroup label="Compact of Free Association">
                                    <option :value="docs.list_a_doc_title_1[6].key">{{ docs.list_a_doc_title_1[6].value }}</option>
                                </optgroup>
                            </select>
                        </td>
                        <td style="background-color:#d3d3d3;"></td>
                        <td style="border: 1px solid #000;padding: 1px 8px;width: 25%;">
                            <select v-model="form.list_b_doc_title" class="i9-section2-select">
                                <option value="">Select document type</option>
                                <option :value="docs.list_b_doc_title[1].key">{{ docs.list_b_doc_title[1].value }}</option>
                                <option :value="docs.list_b_doc_title[2].key">{{ docs.list_b_doc_title[2].value }}</option>
                                <option :value="docs.list_b_doc_title[3].key">{{ docs.list_b_doc_title[3].value }}</option>
                                <option :value="docs.list_b_doc_title[4].key">{{ docs.list_b_doc_title[4].value }}</option>
                                <option :value="docs.list_b_doc_title[5].key">{{ docs.list_b_doc_title[5].value }}</option>
                                <option :value="docs.list_b_doc_title[6].key">{{ docs.list_b_doc_title[6].value }}</option>
                                <option :value="docs.list_b_doc_title[7].key">{{ docs.list_b_doc_title[7].value }}</option>
                                <option :value="docs.list_b_doc_title[8].key">{{ docs.list_b_doc_title[8].value }}</option>
                                <option :value="docs.list_b_doc_title[9].key">{{ docs.list_b_doc_title[9].value }}</option>
                                <optgroup label="For persons under age 18 who are unable to present a document listed above:">
                                    <option :value="docs.list_b_doc_title[10].key">{{ docs.list_b_doc_title[10].value }}</option>
                                    <option :value="docs.list_b_doc_title[11].key">{{ docs.list_b_doc_title[11].value }}</option>
                                    <option :value="docs.list_b_doc_title[12].key">{{ docs.list_b_doc_title[12].value }}</option>
                                </optgroup>
                            </select>
                        </td>
                        <td style="border: 1px solid #000;padding: 1px 8px;width: 25%;">
                            <select v-model="form.list_c_doc_title" class="i9-section2-select">
                                <option value="">Select document type</option>
                                <option :value="docs.list_c_doc_title[1].key">{{ docs.list_c_doc_title[1].value }}</option>
                                <option :value="docs.list_c_doc_title['1a'].key">{{ docs.list_c_doc_title['1a'].value }}</option>
                                <option :value="docs.list_c_doc_title['1b'].key">{{ docs.list_c_doc_title['1b'].value }}</option>
                                <option :value="docs.list_c_doc_title['1c'].key">{{ docs.list_c_doc_title['1c'].value }}</option>
                                <option :value="docs.list_c_doc_title[2].key">{{ docs.list_c_doc_title[2].value }}</option>
                                <option :value="docs.list_c_doc_title[3].key">{{ docs.list_c_doc_title[3].value }}</option>
                                <option :value="docs.list_c_doc_title[4].key">{{ docs.list_c_doc_title[4].value }}</option>   
                                <option :value="docs.list_c_doc_title[5].key">{{ docs.list_c_doc_title[5].value }}</option>
                                <option :value="docs.list_c_doc_title[6].key">{{ docs.list_c_doc_title[6].value }}</option>
                                <option :value="docs.list_c_doc_title[7].key">{{ docs.list_c_doc_title[7].value }}</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000;padding: 1px 8px;background-color: #d3d3d3;">Issuing Authority</td>
                        <td style="border: 1px solid #000;padding: 1px 8px;"><Input v-model="form.list_a_issuing_authority_1" size="sm" /></td>
                        <td style="background-color:#d3d3d3;"></td>
                        <td style="border: 1px solid #000;padding: 1px 8px;"><Input v-model="form.list_b_issuing_authority" size="sm" /></td>
                        <td style="border: 1px solid #000;padding: 1px 8px;"><Input v-model="form.list_c_issuing_authority" size="sm" /></td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000;padding: 1px 8px;background-color: #d3d3d3;">Document Number (if any)</td>
                        <td style="border: 1px solid #000;padding: 1px 8px;"><Input v-model="form.list_a_document_number_1" size="sm" /></td>
                        <td style="background-color:#d3d3d3;"></td>
                        <td style="border: 1px solid #000;padding: 1px 8px;"><Input v-model="form.list_b_document_number" size="sm" /></td>
                        <td style="border: 1px solid #000;padding: 1px 8px;"><Input v-model="form.list_c_document_number" size="sm" /></td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000;padding: 1px 8px;background-color: #d3d3d3;">Expiration Date (if any)</td>
                        <td style="border: 1px solid #000;padding: 1px 8px;"><Input v-model="form.list_a_expiration_date_1" type="date" size="sm" /></td>
                        <td style="background-color:#d3d3d3;"></td>
                        <td style="border: 1px solid #000;padding: 1px 8px;"><Input v-model="form.list_b_expiration_date" type="date" size="sm"  /></td>
                        <td style="border: 1px solid #000;padding: 1px 8px;"><Input v-model="form.list_c_expiration_date" type="date" size="sm"  /></td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000;padding: 1px 8px;background-color: #d3d3d3;"><b>Document Title 2 (if any)</b></td>
                        <td style="border: 1px solid #000;padding: 1px 8px;"><Input v-model="form.list_a_doc_title_2" size="sm" /></td>
                        <td style="background-color:#d3d3d3;padding-left: 8px;" colspan="3"><b>Additional Information</b></td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000;padding: 1px 8px;background-color: #d3d3d3;">Issuing Authority</td>
                        <td style="border: 1px solid #000;padding: 1px 8px;"><Input v-model="form.list_a_issuing_authority_2" size="sm" /></td>
                        <td style="border: 1px solid #000;padding: 1px 8px;" colspan="3" rowspan="7">
                            <Textarea v-model="form.additional_information" :rows="6" size="sm" />
                            <label style="display: block;margin-top: 10px;"><input type="checkbox" v-model="form.alternative_procedure" /><span style="margin: 5px;vertical-align: top;font-size: 11px;">Check here if you used an alternative procedure authorized by DHS to examine documents.</span></label>
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000;padding: 1px 8px;background-color: #d3d3d3;">Document Number (if any)</td>
                        <td style="border: 1px solid #000;padding: 1px 8px;"><Input v-model="form.list_a_document_number_2" size="sm" /></td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000;padding: 1px 8px;background-color: #d3d3d3;">Expiration Date (if any)</td>
                        <td style="border: 1px solid #000;padding: 1px 8px;"><Input v-model="form.list_a_expiration_date_2" type="date" size="sm" /></td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000;padding: 1px 8px;background-color: #d3d3d3;"><b>Document Title 3 (if any)</b></td>
                        <td style="border: 1px solid #000;padding: 1px 8px;"><Input v-model="form.list_a_doc_title_3" size="sm" /></td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000;padding: 1px 8px;background-color: #d3d3d3;">Issuing Authority</td>
                        <td style="border: 1px solid #000;padding: 1px 8px;"><Input v-model="form.list_a_issuing_authority_3" size="sm" /></td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000;padding: 1px 8px;background-color: #d3d3d3;">Document Number (if any)</td>
                        <td style="border: 1px solid #000;padding: 1px 8px;"><Input v-model="form.list_a_document_number_3" size="sm" /></td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000;padding: 1px 8px;background-color: #d3d3d3;">Expiration Date (if any)</td>
                        <td style="border: 1px solid #000;padding: 1px 8px;"><Input v-model="form.list_a_expiration_date_3" type="date" size="sm" /></td>
                    </tr>
                </table>

                <table style="width: 100%;border-collapse: collapse;font-size: 11px;">
                    <tr>
                        <td style="border: 1px solid #000;padding: 1px 8px;width: 50%;" colspan="2">
                            <b>Certification: I attest, under penalty of perjury, that (1) I have examined the documentation presented by the above-named employee, (2) the above-listed documentation appears to be genuine and to relate to the employee named, and (3) to the best of my knowledge, the employee is authorized to work in the United States.</b>
                        </td>
                        <td style="border: 1px solid #000;padding: 1px 8px;">
                            <Input v-model="form.first_day_employment" label="First Day of Employment (dd/mm/yyyy):" type="date" size="sm" placeholder="mm/dd/yyyy" />
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000;padding: 1px 8px;">
                            <Input v-model="form.employer_name" label="Last Name, First Name and Title of Employer or Authorized Representative" size="sm" />
                        </td>
                        <td style="border: 1px solid #000;padding: 1px 8px;">
                            <Input v-model="form.employer_signature" label="Signature of Employer or Authorized Representative" size="sm" />
                        </td>
                        <td style="border: 1px solid #000;padding: 1px 8px;">
                            <Input v-model="form.employer_today_date" label="Today's Date (dd/mm/yyyy)" type="date" size="sm" placeholder="mm/dd/yyyy" />
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000;padding: 1px 8px;">
                            <Input v-model="form.employer_business_name" label="Employer's Business or Organization Name" size="sm" />
                        </td>
                        <td style="border: 1px solid #000;padding: 1px 8px;" colspan="2">
                            <Input v-model="form.employer_business_address" label="Employer's Business or Organization Address, City or Town, State, ZIP Code" size="sm" />
                        </td>
                    </tr>
                </table>
                <label style="width: 100%;font-size: 11px;text-align: center;display: inline-block;border-bottom: 1px solid;">For reverification or rehire, complete <a href="http://www.uscis.gov/I-9" target="_blank"><b>Supplement B, Reverification and Rehire</b></a> on Page 4.</label>
                <div style="font-size: 11px;">
                    <label style="float: left;">Form I-9 Edition 01/20/25</label>
                    <label style="float: right;">Page 1 of 4</label>
                </div>
            </div>

            <!-- Page 2 - Lists of Acceptable Documents (static) -->
            <div class="inner i9-page-2 border-t-5 border-red-700">
                <div class="header i9-lists-header">
                    <h2 class="i9-lists-title">LISTS OF ACCEPTABLE DOCUMENTS</h2>
                    <p class="i9-lists-note">All documents containing an expiration date must be unexpired.</p>
                    <p class="i9-lists-note">* Documents extended by the issuing authority are considered unexpired.</p>
                    <p class="i9-lists-note">Employees may present one selection from List A or a combination of one selection from List B and one selection from List C.</p>
                    <h4 class="i9-lists-subtitle">Examples of many of these documents appear in the Handbook for Employers (M-274).</h4>
                </div>
                <table class="i9-lists-table">
                    <thead>
                        <tr>
                            <th class="i9-list-a-col">
                                <strong>LIST A</strong><br />
                                <span class="list-subtitle">Documents that Establish Both Identity<br />and Employment Authorization</span><br />
                                <span class="list-hint">(An employee must present only one document from this list.)</span>
                            </th>
                            <th class="i9-or-col">OR</th>
                            <th class="i9-list-b-col">
                                <strong>LIST B</strong><br />
                                <span class="list-subtitle">Documents that Establish Identity</span><br />
                                <span class="list-hint">(An employee must present one document from this list if not providing a List A document.)</span>
                            </th>
                            <th class="i9-and-col">AND</th>
                            <th class="i9-list-c-col">
                                <strong>LIST C</strong><br />
                                <span class="list-subtitle">Documents that Establish Employment Authorization</span><br />
                                <span class="list-hint">(An employee must present one document from this list if not providing a List A document.)</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="i9-list-td">
                                <ol class="i9-list">
                                    <li>U.S. Passport or U.S. Passport Card</li>
                                    <li>Permanent Resident Card or Alien Registration Receipt Card (Form I-551)</li>
                                    <li>Foreign passport that contains a temporary I-551 stamp or temporary I-551 printed notation on a machine-readable immigrant visa</li>
                                    <li>Employment Authorization Document that contains a photograph (Form I-766)</li>
                                    <li>For an individual temporarily authorized to work for a specific employer:
                                        <ol type="a" class="i9-sublist">
                                            <li>Foreign passport; <strong>and</strong></li>
                                            <li class="!border-none">Form I-94 or Form I-94A that has:
                                                <ol type="1" class="i9-sublist">
                                                    <li>The same name as the passport; <strong>and</strong></li>
                                                    <li class="!border-none">An endorsement of the individual's status or parole as long as it has not expired and the proposed employment is not in conflict with any restrictions or limitations.</li>
                                                </ol>
                                            </li>
                                        </ol>
                                    </li>
                                    <li class="!border-none">Passport from the Federated States of Micronesia (FSM) or the Republic of the Marshall Islands (RMI) with Form I-94/I-94A indicating nonimmigrant admission under the Compact of Free Association.</li>
                                </ol>
                            </td>
                            <td class="i9-or-td"></td>
                            <td class="i9-list-td">
                                <ol class="i9-list">
                                    <li>Driver's license or ID card issued by a State or outlying possession of the U.S. with a photograph or identifying information</li>
                                    <li>ID card issued by federal, state, or local agencies with photo or identifying information</li>
                                    <li>School ID card with a photograph</li>
                                    <li>Voter's registration card</li>
                                    <li>U.S. Military card or draft record</li>
                                    <li>Military dependent's ID card</li>
                                    <li>U.S. Coast Guard Merchant Mariner Card</li>
                                    <li>Native American tribal document</li>
                                    <li>Driver's license issued by a Canadian government authority</li>
                                    <li class="i9-subheader">For persons under age 18 who are unable to present a document listed above:</li>
                                    <li>School record or report card</li>
                                    <li>Clinic, doctor, or hospital record</li>
                                    <li class="!border-none">Day-care or nursery school record</li>
                                </ol>
                            </td>
                            <td class="i9-and-td"></td>
                            <td class="i9-list-td">
                                <ol class="i9-list">
                                    <li class="!border-none">Social Security Account Number card, unless it states:
                                        <ol type="1" class="i9-sublist">
                                            <li>NOT VALID FOR EMPLOYMENT</li>
                                            <li>VALID FOR WORK ONLY WITH INS AUTHORIZATION</li>
                                            <li>VALID FOR WORK ONLY WITH DHS AUTHORIZATION</li>
                                        </ol>
                                    </li>
                                    <li>Certification of report of birth issued by the Department of State (Forms DS-1350, FS-545, FS-240)</li>
                                    <li>Original or certified copy of birth certificate issued by a U.S. entity bearing an official seal</li>
                                    <li>Native American tribal document</li>
                                    <li>U.S. Citizen ID Card (Form I-197)</li>
                                    <li>Identification Card for Use of Resident Citizen in the U.S. (Form I-179)</li>
                                    <li class="!border-none">Employment authorization document issued by the Department of Homeland Security.
                                        <p class="i9-list-note">For examples, see <a href="https://www.uscis.gov/i-9-central/handbook-for-employers-m-274/60-evidence-of-status-for-certain-categories" target="_blank">Section 7</a> and <a href="https://www.uscis.gov/i-9-central/form-i-9-resources/handbook-for-employers-m-274/120-acceptable-documents-for-verifying-employment-authorization-and-identity/123-list-c-documents-that-establish-employment-authorization" target="_blank">Section 13</a> of the M-274 on <a href="https://www.uscis.gov/i-9-central" target="_blank">uscis.gov/i-9-central</a>.</p>
                                        <p class="i9-list-note">The Form I-766, Employment Authorization Document, is a List A, not List C document.</p>
                                    </li>
                                </ol>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="i9-page-footer"><label>Form I-9 Edition 01/20/25</label><label>Page 2 of 4</label></div>
            </div>

            <!-- Page 3 - Supplement A -->
            <div class="inner i9-page-3 border-t-5 border-red-700">
                <table style="width: 100%;" class="mt-5">
                    <tr>
                        <td width="15%"><div><img :src="i9LogoUrl" alt="USCIS" style="max-width: 60px;" /></div></td>
                        <td width="65%" style="text-align: center;">
                            <h2 style="margin-top: 0; margin-bottom: 5px; font-size: 15px; font-weight: 600;">Supplement A,</h2>
                            <h2 style="margin-top: 0; margin-bottom: 5px; font-size: 15px; font-weight: 600;">Preparer and/or Translator Certification for Section 1</h2>
                            <h4 style="margin: 0; margin-bottom: 5px; font-size: 12px; font-weight: 600;">Department of Homeland Security</h4>
                            <p style="margin: 0;font-size: 12px;">U.S. Citizenship and Immigration Services</p>
                        </td>
                        <td width="20%" style="text-align: center;">
                            <h5 style="margin: 0; font-size: 14px; font-weight: 600;">USCIS</h5>
                            <h5 style="margin: 0; font-size: 14px; font-weight: 600;">Form I-9</h5>
                            <h5 style="margin: 0; font-size: 14px; font-weight: 600;">Supplement A</h5>
                            <p style="margin: 0; font-size: 12px;">OMB No.1615-0047</p>
                            <p style="margin: 0; font-size: 12px;">Expires 05/31/2027</p>
                        </td>
                    </tr>
                </table>
                <hr style="border-color: #000; border-width: 3px; opacity: 1;" />
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="border: 1px solid #000; padding: 4px 8px;"><Input v-model="section1.last_name" label="Last Name (Family Name) from Section 1." readonly size="sm" /></td>
                        <td style="border: 1px solid #000; padding: 4px 8px;"><Input v-model="section1.first_name" label="First Name (Given Name) from Section 1." readonly size="sm" /></td>
                        <td style="border: 1px solid #000; padding: 4px 8px;"><Input v-model="section1.middle_initial" label="Middle initial (if any) from Section 1." readonly size="sm" /></td>
                    </tr>
                </table>
                <div style="font-size: 13px; line-height: 16px; margin: 15px 0;"><b>Instructions:</b> This supplement must be completed by any preparer and/or translator who assists an employee in completing Section 1 of Form I-9. The preparer and/or translator must enter the employee's name in the spaces provided above. Each preparer or translator must complete, sign, and date a separate certification area. Employers must retain completed supplement sheets with the employee's completed Form I-9.</div>
                <div style="font-size: 10px; line-height: 10px; margin-bottom: 5px;"><b>I attest, under penalty of perjury, that I have assisted in the completion of Section 1 of this form and that to the best of my knowledge the information is true and correct.</b></div>
                <table v-for="(row, idx) in form.preparer_translators" :key="'prep-' + idx" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                    <tr>
                        <td style="border: 1px solid #000; padding: 4px 8px;" colspan="3"><Input v-model="row.signature" label="Signature of Preparer or Translator" size="sm" /></td>
                        <td style="border: 1px solid #000; padding: 4px 8px;"><Input v-model="row.signature_date" label="Date (dd/mm/yyyy)" type="date" size="sm" placeholder="mm/dd/yyyy" /></td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000; padding: 4px 8px;"><Input v-model="row.last_name" label="Last Name (Family Name)" size="sm" /></td>
                        <td style="border: 1px solid #000; padding: 4px 8px;" colspan="2"><Input v-model="row.first_name" label="First Name (Given Name)" size="sm" /></td>
                        <td style="border: 1px solid #000; padding: 4px 8px;"><Input v-model="row.middle_initial" label="Middle Initial (if any)" size="sm" /></td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000; padding: 4px 8px;"><Input v-model="row.address" label="Address (Street Number and Name)" size="sm" /></td>
                        <td style="border: 1px solid #000; padding: 4px 8px;"><Input v-model="row.city" label="City or Town" size="sm" /></td>
                        <td style="border: 1px solid #000; padding: 4px 8px;"><Input v-model="row.state" label="State" size="sm" /></td>
                        <td style="border: 1px solid #000; padding: 4px 8px;"><Input v-model="row.zip_code" label="ZIP Code" size="sm" /></td>
                    </tr>
                </table>
                <!-- <button type="button" class="i9-btn-add" @click="addPreparer">+ Add preparer/translator</button> -->
                <div style="font-size: 11px;display: inline-block;width: 100%;border-top: 1px solid;padding-top: 5px;margin-top: 30px;"><label style="float: left;">Form I-9 Edition 01/20/25</label><label style="float: right;">Page 3 of 4</label></div>
            </div>

            <!-- Page 4 - Supplement B -->
            <div class="inner i9-page-4 border-t-5 border-red-700">
                <table style="width: 100%;" class="mt-5">
                    <tr>
                        <td width="15%"><div><img :src="i9LogoUrl" alt="USCIS" style="max-width: 60px;" /></div></td>
                        <td width="65%" style="text-align: center;">
                            <h2 style="margin-top: 0; margin-bottom: 5px; font-size: 15px; font-weight: 600;">Supplement B,</h2>
                            <h2 style="margin-top: 0; margin-bottom: 5px; font-size: 15px; font-weight: 600;">Reverification and Rehire (formerly Section 3)</h2>
                            <h4 style="margin: 0; margin-bottom: 5px; font-size: 12px; font-weight: 600;">Department of Homeland Security</h4>
                            <p style="margin: 0;font-size: 12px;">U.S. Citizenship and Immigration Services</p>
                        </td>
                        <td width="20%" style="text-align: center;">
                            <h5 style="margin: 0; font-size: 14px; font-weight: 600;">USCIS</h5>
                            <h5 style="margin: 0; font-size: 14px; font-weight: 600;">Form I-9</h5>
                            <h5 style="margin: 0; font-size: 14px; font-weight: 600;">Supplement B</h5>
                            <p style="margin: 0; font-size: 12px;">OMB No. 1615-0047</p>
                            <p style="margin: 0; font-size: 12px;">Expires 05/31/2027</p>
                        </td>
                    </tr>
                </table>
                <hr style="border-color: #000; border-width: 3px; opacity: 1;" />
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="border: 1px solid #000; padding: 4px 8px;"><Input v-model="section1.last_name" label="Last Name (Family Name) from Section 1." readonly size="sm" /></td>
                        <td style="border: 1px solid #000; padding: 4px 8px;"><Input v-model="section1.first_name" label="First Name (Given Name) from Section 1." readonly size="sm" /></td>
                        <td style="border: 1px solid #000; padding: 4px 8px;"><Input v-model="section1.middle_initial" label="Middle initial (if any) from Section 1." readonly size="sm" /></td>
                    </tr>
                </table>
                <div style="font-size: 11px; line-height: 13px; margin: 5px 0;">Instructions: This supplement replaces Section 3. Only use for reverification, rehire within three years, or legal name change.</div>
                <template v-for="(row, idx) in form.reverifications" :key="'rev-' + idx">
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
                        <tr>
                            <td style="border: 1px solid #000; padding: 0 8px;background-color: #d3d3d3;"><label style="font-size: 11px;">Date of Rehire (if applicable)</label></td>
                            <td style="border: 1px solid #000; padding: 0 8px;background-color: #d3d3d3;" colspan="3"><label style="font-size: 11px;">New Name (if applicable)</label></td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #000; padding: 4px 8px;"><Input v-model="row.rehire_date" label="Date (dd/mm/yyyy)" type="date" size="sm" placeholder="mm/dd/yyyy" /></td>
                            <td style="border: 1px solid #000; padding: 4px 8px;"><Input v-model="row.new_last_name" label="Last Name (Family Name)" size="sm" /></td>
                            <td style="border: 1px solid #000; padding: 4px 8px;"><Input v-model="row.new_first_name" label="First Name (Given Name)" size="sm" /></td>
                            <td style="border: 1px solid #000; padding: 4px 8px;"><Input v-model="row.new_middle_initial" label="Middle Initial" size="sm" /></td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #000; padding: 4px 8px;background-color: #d3d3d3;" colspan="4"><label style="font-size: 11px;">Reverification: Enter document information below.</label></td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #000; padding: 4px 8px;" colspan="2"><Input v-model="row.document_title" label="Document Title" size="sm" /></td>
                            <td style="border: 1px solid #000; padding: 4px 8px;"><Input v-model="row.document_number" label="Document Number (if any)" size="sm" /></td>
                            <td style="border: 1px solid #000; padding: 4px 8px;"><Input v-model="row.expiration_date" label="Expiration Date (if any) (dd/mm/yyyy)" type="date" size="sm" placeholder="mm/dd/yyyy" /></td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #000; padding: 4px 8px;" colspan="2"><Input v-model="row.employer_representative_name" label="Name of Employer or Authorized Representative" size="sm" /></td>
                            <td style="border: 1px solid #000; padding: 4px 8px;"><Input v-model="row.employer_signature" label="Signature" size="sm" /></td>
                            <td style="border: 1px solid #000; padding: 4px 8px;"><Input v-model="row.today_date" label="Today's Date (dd/mm/yyyy)" type="date" size="sm" placeholder="mm/dd/yyyy" /></td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #000; padding: 4px 8px;" colspan="3"><Input v-model="row.additional_information" label="Additional Information" size="sm" /></td>
                            <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 11px;"><input type="checkbox" v-model="row.alternative_procedure_dhs" /> Alternative procedure authorized by DHS</label></td>
                        </tr>
                    </table>
                </template>
                <!-- <button type="button" class="i9-btn-add" @click="addReverification">+ Add reverification/rehire section</button> -->
                <div style="font-size: 11px;display: inline-block;width: 100%;border-top: 1px solid;padding-top: 5px;margin-top: 5px;"><label style="float: left;">Form I-9 Edition 01/20/25</label><label style="float: right;">Page 4 of 4</label></div>
            </div>

            <!-- Document Upload (List A/B/C + additional/last document) -->
            <div class="inner i9-doc-upload border-t-5 border-red-700">
                <h6 class="i9-doc-upload-title !mt-3 ">Document Upload</h6>
                <table class="i9-doc-upload-table">
                    <thead>
                        <tr>
                            <th class="i9-doc-col-a">List A<br /><span class="i9-doc-sub">Identity &amp; Employment Authorization</span></th>
                            <th class="i9-doc-col-or">OR</th>
                            <th class="i9-doc-col-bc">List B (Identity) + List C (Employment Authorization)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <label class="i9-doc-label">Select document type</label>
                                <select v-model="form.list_a_doc_title_1" class="i9-select">
                                    <option value="">Select document type</option>
                                    <optgroup label="Primary Documents">
                                        <option :value="docs.list_a_doc_title_1[1].key">{{ docs.list_a_doc_title_1[1].value }}</option>
                                        <option :value="docs.list_a_doc_title_1[2].key">{{ docs.list_a_doc_title_1[2].value }}</option>
                                        <option :value="docs.list_a_doc_title_1[3].key">{{ docs.list_a_doc_title_1[3].value }}</option>
                                        <option :value="docs.list_a_doc_title_1[4].key">{{ docs.list_a_doc_title_1[4].value }}</option>
                                    </optgroup>
                                    <optgroup label="Work Authorization">
                                        <option :value="docs.list_a_doc_title_1[5].key">{{ docs.list_a_doc_title_1[5].value }}</option>
                                        <option :value="docs.list_a_doc_title_1['5a'].key">{{ docs.list_a_doc_title_1['5a'].value }}</option>
                                        <option :value="docs.list_a_doc_title_1['5b'].key">{{ docs.list_a_doc_title_1['5b'].value }}</option>
                                        <option :value="docs.list_a_doc_title_1['5(1)'].key">{{ docs.list_a_doc_title_1['5(1)'].value }}</option>
                                        <option :value="docs.list_a_doc_title_1['5(2)'].key">{{ docs.list_a_doc_title_1['5(2)'].value }}</option>
                                    </optgroup>
                                    <optgroup label="Compact of Free Association">
                                        <option :value="docs.list_a_doc_title_1[6].key">{{ docs.list_a_doc_title_1[6].value }}</option>
                                    </optgroup>
                                </select>
                                <label class="i9-doc-label">Upload document</label>
                                <input
                                    ref="listAFileRef"
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    class="i9-file-input"
                                    @change="onFileChange($event, 'list_a')"
                                />
                                <a
                                    v-if="form.list_a_file_path"
                                    :href="form.list_a_file_path"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-sm text-blue-600 hover:underline"
                                >
                                    Uploaded List A Doc Link
                                </a>
                                <!-- <p v-if="form.list_a_file_path" class="i9-current-file">Current: {{ fileBasename(form.list_a_file_path) }}</p> -->
                            </td>
                            <td class="i9-doc-or-cell"></td>
                            <td>
                                <div class="i9-doc-bc-row">
                                    <div class="i9-doc-half">
                                        <label class="i9-doc-label">List B – Select document type</label>
                                        <select v-model="form.list_b_doc_title" class="i9-select">
                                            <option value="">Select document type</option>  
                                            <option :value="docs.list_b_doc_title[1].key">{{ docs.list_b_doc_title[1].value }}</option>
                                            <option :value="docs.list_b_doc_title[2].key">{{ docs.list_b_doc_title[2].value }}</option>
                                            <option :value="docs.list_b_doc_title[3].key">{{ docs.list_b_doc_title[3].value }}</option>
                                            <option :value="docs.list_b_doc_title[4].key">{{ docs.list_b_doc_title[4].value }}</option>
                                            <option :value="docs.list_b_doc_title[5].key">{{ docs.list_b_doc_title[5].value }}</option>
                                            <option :value="docs.list_b_doc_title[6].key">{{ docs.list_b_doc_title[6].value }}</option>
                                            <option :value="docs.list_b_doc_title[7].key">{{ docs.list_b_doc_title[7].value }}</option>
                                            <option :value="docs.list_b_doc_title[8].key">{{ docs.list_b_doc_title[8].value }}</option>
                                            <option :value="docs.list_b_doc_title[9].key">{{ docs.list_b_doc_title[9].value }}</option>
                                            <optgroup label="For persons under 18:">
                                                <option :value="docs.list_b_doc_title[10].key">{{ docs.list_b_doc_title[10].value }}</option>
                                                <option :value="docs.list_b_doc_title[11].key">{{ docs.list_b_doc_title[11].value }}</option>
                                                <option :value="docs.list_b_doc_title[12].key">{{ docs.list_b_doc_title[12].value }}</option>
                                            </optgroup>
                                        </select>
                                        <label class="i9-doc-label">Upload document</label>
                                        <input
                                            ref="listBFileRef"
                                            type="file"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                            class="i9-file-input"
                                            @change="onFileChange($event, 'list_b')"
                                        />
                                        <p v-if="form.list_b_file_path" class="i9-current-file">Current: {{ fileBasename(form.list_b_file_path) }}</p>
                                    </div>
                                    <div class="i9-doc-half">
                                        <label class="i9-doc-label">List C – Select document type</label>
                                        <select v-model="form.list_c_doc_title" class="i9-select">
                                            <option value="">Select document type</option>
                                            <option :value="docs.list_c_doc_title[1].key">{{ docs.list_c_doc_title[1].value  }}</option>
                                            <option :value="docs.list_c_doc_title['1a'].key">{{ docs.list_c_doc_title['1a'].value }}</option>
                                            <option :value="docs.list_c_doc_title['1b'].key">{{ docs.list_c_doc_title['1b'].value }}</option>
                                            <option :value="docs.list_c_doc_title['1c'].key">{{ docs.list_c_doc_title['1c'].value }}</option>
                                            <option :value="docs.list_c_doc_title[2].key">{{ docs.list_c_doc_title[2].value }}</option>
                                            <option :value="docs.list_c_doc_title[3].key">{{ docs.list_c_doc_title[3].value }}</option>
                                            <option :value="docs.list_c_doc_title[4].key">{{ docs.list_c_doc_title[4].value }}</option>
                                            <option :value="docs.list_c_doc_title[5].key">{{ docs.list_c_doc_title[5].value }}</option>
                                            <option :value="docs.list_c_doc_title[6].key">{{ docs.list_c_doc_title[6].value }}</option>
                                            <option :value="docs.list_c_doc_title[7].key">{{ docs.list_c_doc_title[7].value }}</option>
                                        </select>
                                        <label class="i9-doc-label">Upload document</label>
                                        <input
                                            ref="listCFileRef"
                                            type="file"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                            class="i9-file-input"
                                            @change="onFileChange($event, 'list_c')"
                                        />
                                        <p v-if="form.list_c_file_path" class="i9-current-file">Current: {{ fileBasename(form.list_c_file_path) }}</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="i9-additional-doc">
                    <h6 class="i9-doc-upload-title">Additional Document (optional)</h6>
                    <div class="i9-additional-doc-row">
                        <div class="i9-additional-doc-input">
                            <label class="i9-doc-label">Description / label (optional)</label>
                            <input
                                v-model="form.additional_document_label"
                                type="text"
                                class="i9-text-input"
                                placeholder="e.g. Work permit, Other supporting document"
                            />
                        </div>
                        <div class="i9-additional-doc-file">
                            <label class="i9-doc-label">File</label>
                            <input
                                ref="additionalDocFileRef"
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png"
                                class="i9-file-input"
                                @change="onFileChange($event, 'additional')"
                            />
                            <p v-if="form.additional_document_path" class="i9-current-file">Current: {{ fileBasename(form.additional_document_path) }}</p>
                        </div>
                    </div>
                </div>
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
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import { isSSNValid, formatSSN } from '@/utils/ssn'
import { rejectOversizedFile } from '@/utils/documentUpload'
import Input from '@/components/ui/input.vue'
import Textarea from '@/components/ui/textarea.vue'
import docs from '@/views/onboarding-process/docs.json'

const listAFileRef = ref(null)
const listBFileRef = ref(null)
const listCFileRef = ref(null)
const additionalDocFileRef = ref(null)

const props = defineProps({
    onboardingId: { type: String, default: '' },
    goToNextStep: { type: Function, default: () => {} },
    goToBackStep: { type: Function, default: () => {} },
})

// Public folder asset – use variable so Vite doesn’t try to resolve it as an import
const i9LogoUrl = '/images/i9-logo.png'

const submitting = ref(false)
const submitError = ref('')

const section1 = reactive({
    last_name: '',
    first_name: '',
    middle_initial: '',
    address: '',
    apt_number: '',
    city: '',
    state: '',
    zipcode: '',
    date_of_birth: '',
    social_security_number: '',
    employee_email: '',
    employee_telephone: '',
})

function getTodayDate() {
    const d = new Date()
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0')
}

const defaultForm = () => ({
    other_last_names: '',
    citizenship_status: 1,
    uscis_or_a_number: '',
    alien_authorized_exp_date: '',
    uscis_a_number: '',
    form_i94_admission_number: '',
    foreign_passport_number: '',
    employee_signature: '',
    section1_today_date: getTodayDate(),
    list_a_doc_title_1: '',
    list_a_issuing_authority_1: '',
    list_a_document_number_1: '',
    list_a_expiration_date_1: '',
    list_a_doc_title_2: '',
    list_a_issuing_authority_2: '',
    list_a_document_number_2: '',
    list_a_expiration_date_2: '',
    list_a_doc_title_3: '',
    list_a_issuing_authority_3: '',
    list_a_document_number_3: '',
    list_a_expiration_date_3: '',
    list_b_doc_title: '',
    list_b_issuing_authority: '',
    list_b_document_number: '',
    list_b_expiration_date: '',
    list_c_doc_title: '',
    list_c_issuing_authority: '',
    list_c_document_number: '',
    list_c_expiration_date: '',
    additional_information: '',
    alternative_procedure: false,
    first_day_employment: '',
    employer_name: '',
    employer_signature: '',
    employer_today_date: '',
    employer_business_name: '',
    employer_business_address: '',
    list_a_file_path: '',
    list_b_file_path: '',
    list_c_file_path: '',
    additional_document_path: '',
    additional_document_label: '',
    preparer_translators: [{ signature: '', signature_date: '', last_name: '', first_name: '', middle_initial: '', address: '', city: '', state: '', zip_code: '' },{ signature: '', signature_date: '', last_name: '', first_name: '', middle_initial: '', address: '', city: '', state: '', zip_code: '' },{ signature: '', signature_date: '', last_name: '', first_name: '', middle_initial: '', address: '', city: '', state: '', zip_code: '' },{ signature: '', signature_date: '', last_name: '', first_name: '', middle_initial: '', address: '', city: '', state: '', zip_code: '' }],
    reverifications: [{ rehire_date: '', new_last_name: '', new_first_name: '', new_middle_initial: '', document_title: '', document_number: '', expiration_date: '', employer_representative_name: '', employer_signature: '', today_date: '', additional_information: '', alternative_procedure_dhs: false },{ rehire_date: '', new_last_name: '', new_first_name: '', new_middle_initial: '', document_title: '', document_number: '', expiration_date: '', employer_representative_name: '', employer_signature: '', today_date: '', additional_information: '', alternative_procedure_dhs: false },{ rehire_date: '', new_last_name: '', new_first_name: '', new_middle_initial: '', document_title: '', document_number: '', expiration_date: '', employer_representative_name: '', employer_signature: '', today_date: '', additional_information: '', alternative_procedure_dhs: false },{ rehire_date: '', new_last_name: '', new_first_name: '', new_middle_initial: '', document_title: '', document_number: '', expiration_date: '', employer_representative_name: '', employer_signature: '', today_date: '', additional_information: '', alternative_procedure_dhs: false }],
})

const form = reactive(defaultForm())

function getCsrfHeaders() {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    return token ? { 'X-CSRF-TOKEN': token } : {}
}

function addPreparer() {
    form.preparer_translators.push({ signature: '', signature_date: '', last_name: '', first_name: '', middle_initial: '', address: '', city: '', state: '', zip_code: '' })
}

function addReverification() {
    form.reverifications.push({ rehire_date: '', new_last_name: '', new_first_name: '', new_middle_initial: '', document_title: '', document_number: '', expiration_date: '', employer_representative_name: '', employer_signature: '', today_date: '', additional_information: '', alternative_procedure_dhs: false })
}

function fileBasename(path) {
    if (!path || typeof path !== 'string') return ''
    const parts = path.replace(/\\/g, '/').split('/')
    return parts[parts.length - 1] || path
}

function onFileChange(event, _which) {
    const input = event?.target
    const file = input?.files?.[0] || null
    if (rejectOversizedFile(file, (error) => {
        submitError.value = error
    })) {
        if (input) {
            input.value = ''
        }
    }
}

onMounted(() => { fetchI9Data() })

async function fetchI9Data() {
    if (!props.onboardingId) return
    try {
        const { data: res } = await axios.get('/onboarding-process/i9-data', {
            params: { onboardingId: props.onboardingId },
            headers: getCsrfHeaders(),
            withCredentials: true,
        })
        if (res.success && res.section1) Object.assign(section1, res.section1)
        if (res.success && res.form) {
            const f = res.form
            form.other_last_names = f.other_last_names ?? ''
            form.citizenship_status = f.citizenship_status ?? 1
            form.uscis_or_a_number = f.uscis_or_a_number ?? ''
            form.alien_authorized_exp_date = f.alien_authorized_exp_date ?? ''
            form.uscis_a_number = f.uscis_a_number ?? ''
            form.form_i94_admission_number = f.form_i94_admission_number ?? ''
            form.foreign_passport_number = f.foreign_passport_number ?? ''
            form.employee_signature = f.employee_signature ?? ''
            form.section1_today_date = f.section1_today_date ?? getTodayDate()
            form.list_a_doc_title_1 = f.list_a_doc_title_1 ?? ''
            form.list_a_issuing_authority_1 = f.list_a_issuing_authority_1 ?? ''
            form.list_a_document_number_1 = f.list_a_document_number_1 ?? ''
            form.list_a_expiration_date_1 = f.list_a_expiration_date_1 ?? ''
            form.list_a_doc_title_2 = f.list_a_doc_title_2 ?? ''
            form.list_a_issuing_authority_2 = f.list_a_issuing_authority_2 ?? ''
            form.list_a_document_number_2 = f.list_a_document_number_2 ?? ''
            form.list_a_expiration_date_2 = f.list_a_expiration_date_2 ?? ''
            form.list_a_doc_title_3 = f.list_a_doc_title_3 ?? ''
            form.list_a_issuing_authority_3 = f.list_a_issuing_authority_3 ?? ''
            form.list_a_document_number_3 = f.list_a_document_number_3 ?? ''
            form.list_a_expiration_date_3 = f.list_a_expiration_date_3 ?? ''
            form.list_b_doc_title = f.list_b_doc_title ?? ''
            form.list_b_issuing_authority = f.list_b_issuing_authority ?? ''
            form.list_b_document_number = f.list_b_document_number ?? ''
            form.list_b_expiration_date = f.list_b_expiration_date ?? ''
            form.list_c_doc_title = f.list_c_doc_title ?? ''
            form.list_c_issuing_authority = f.list_c_issuing_authority ?? ''
            form.list_c_document_number = f.list_c_document_number ?? ''
            form.list_c_expiration_date = f.list_c_expiration_date ?? ''
            form.additional_information = f.additional_information ?? ''
            form.alternative_procedure = !!f.alternative_procedure
            form.first_day_employment = f.first_day_employment ?? ''
            form.employer_name = f.employer_name ?? ''
            form.employer_signature = f.employer_signature ?? ''
            form.employer_today_date = f.employer_today_date ?? ''
            form.employer_business_name = f.employer_business_name ?? ''
            form.employer_business_address = f.employer_business_address ?? ''
            form.list_a_file_path = f.list_a_file_path ?? ''
            form.list_b_file_path = f.list_b_file_path ?? ''
            form.list_c_file_path = f.list_c_file_path ?? ''
            form.additional_document_path = f.additional_document_path ?? ''
            form.additional_document_label = f.additional_document_label ?? ''
            if (f.preparer_translators?.length) form.preparer_translators = f.preparer_translators.map((p) => ({ signature: p.signature ?? '', signature_date: p.signature_date ?? '', last_name: p.last_name ?? '', first_name: p.first_name ?? '', middle_initial: p.middle_initial ?? '', address: p.address ?? '', city: p.city ?? '', state: p.state ?? '', zip_code: p.zip_code ?? '' }))
            if (f.reverifications?.length) form.reverifications = f.reverifications.map((r) => ({ rehire_date: r.rehire_date ?? '', new_last_name: r.new_last_name ?? '', new_first_name: r.new_first_name ?? '', new_middle_initial: r.new_middle_initial ?? '', document_title: r.document_title ?? '', document_number: r.document_number ?? '', expiration_date: r.expiration_date ?? '', employer_representative_name: r.employer_representative_name ?? '', employer_signature: r.employer_signature ?? '', today_date: r.today_date ?? '', additional_information: r.additional_information ?? '', alternative_procedure_dhs: !!r.alternative_procedure_dhs }))
        }
    } catch (err) {
        console.error('Error fetching I-9 data:', err)
    }
}

async function submit() {
    submitError.value = ''
    if (!(form.employee_signature || '').trim()) {
        submitError.value = 'Signature of Employee is required.'
        return
    }
    if (section1.social_security_number && !isSSNValid(section1.social_security_number)) {
        submitError.value = 'Please enter a valid 9-digit U.S. Social Security Number (e.g. XXX-XX-XXXX).'
        return
    }

    const hasListAType = !!(form.list_a_doc_title_1 || '').trim()
    const hasListBType = !!(form.list_b_doc_title || '').trim()
    const hasListCType = !!(form.list_c_doc_title || '').trim()

    if (!hasListAType && !(hasListBType && hasListCType)) {
        submitError.value = 'Please provide either List A document type, or both List B and List C document types.'
        return
    }

    const hasListAFile = !!(listAFileRef.value?.files?.[0] || form.list_a_file_path)
    const hasListBFile = !!(listBFileRef.value?.files?.[0] || form.list_b_file_path)
    const hasListCFile = !!(listCFileRef.value?.files?.[0] || form.list_c_file_path)

    if (hasListAType && !hasListAFile) {
        submitError.value = 'List A document file is required for the selected List A type.'
        return
    }

    if (hasListBType && !hasListBFile) {
        submitError.value = 'List B document file is required for the selected List B type.'
        return
    }

    if (hasListCType && !hasListCFile) {
        submitError.value = 'List C document file is required for the selected List C type.'
        return
    }

    if ((hasListBType && !hasListCType) || (!hasListBType && hasListCType)) {
        submitError.value = 'List B and List C document types must both be selected together.'
        return
    }

    submitting.value = true
    try {
        const section1ToSend = {
            ...section1,
            social_security_number: section1.social_security_number ? formatSSN(section1.social_security_number) || section1.social_security_number : '',
        }
        const formPayload = {
            ...form,
            citizenship_status: form.citizenship_status,
            alternative_procedure: form.alternative_procedure,
            preparer_translators: form.preparer_translators.filter((r) => Object.values(r).some((v) => v !== '' && v !== null)),
            reverifications: form.reverifications.filter((r) => Object.values(r).some((v) => v !== '' && v !== null && v !== false)),
        }
        const hasFiles = listAFileRef.value?.files?.[0] || listBFileRef.value?.files?.[0] || listCFileRef.value?.files?.[0] || additionalDocFileRef.value?.files?.[0]

        if (hasFiles) {
            const fd = new FormData()
            fd.append('onboardingId', props.onboardingId)
            fd.append('section1', JSON.stringify(section1ToSend))
            fd.append('form', JSON.stringify(formPayload))
            fd.append('additional_document_label', form.additional_document_label || '')
            if (listAFileRef.value?.files?.[0]) fd.append('list_a_file', listAFileRef.value.files[0])
            if (listBFileRef.value?.files?.[0]) fd.append('list_b_file', listBFileRef.value.files[0])
            if (listCFileRef.value?.files?.[0]) fd.append('list_c_file', listCFileRef.value.files[0])
            if (additionalDocFileRef.value?.files?.[0]) fd.append('additional_document_file', additionalDocFileRef.value.files[0])
            await axios.post('/onboarding-process/save-i9', fd, {
                headers: getCsrfHeaders(),
                withCredentials: true,
            })
        } else {
            const payload = {
                onboardingId: props.onboardingId,
                section1: section1ToSend,
                ...formPayload,
            }
            await axios.post('/onboarding-process/save-i9', payload, {
                headers: { ...getCsrfHeaders(), 'Content-Type': 'application/json' },
                withCredentials: true,
            })
        }
        props.goToNextStep()
    } catch (err) {
        submitError.value = err.response?.data?.message || err.response?.data?.error || 'Failed to save Form I-9. Please try again.'
    } finally {
        submitting.value = false
    }
}
</script>

<style scoped>
.i9-form-wrap {
    margin: 0;
    padding: 1rem 1.25rem 1.5rem;
    font-size: 11px;
    line-height: 1.35;
    background: #fff;
    border-radius: 6px;
}
.i9-form-body {
    max-width: 100%;
}
.i9-form-body .inner {
    margin-bottom: 2.5rem;
    padding: 0 0.25rem;
}
.i9-form-body .inner:last-of-type {
    margin-bottom: 0;
}
/* User-friendly cell padding (overrides inline where needed for consistency) */
.i9-form-wrap :deep(table td),
.i9-form-wrap :deep(table th) {
    padding: 8px 12px !important;
}
.i9-form-wrap :deep(table td[style*="background-color: #d3d3d3"]) {
    padding: 10px 12px !important;
}
.i9-form-wrap :deep(.inner) > table {
    margin-bottom: 0.5rem;
}
.i9-form-wrap :deep(.inner) > table + table {
    margin-top: 0.25rem;
}
.i9-form-wrap :deep(hr) {
    margin: 1rem 0 1.25rem;
}
.i9-form-wrap :deep(.inner) > div:not([class]) {
    margin-bottom: 1rem;
}
/* Page 2 - Lists of Acceptable Documents */
.i9-lists-header {
    text-align: center;
    max-width: 700px;
    margin: 0 auto 15px;
    padding: 10px 0;
}
.i9-lists-title {
    margin: 0 0 10px;
    font-size: 18px;
    font-weight: 600;
}
.i9-lists-note {
    margin: 0 0 6px;
    font-size: 13px;
    line-height: 14px;
}
.i9-lists-ref {
    margin: 0 0 10px;
    font-size: 11px;
    line-height: 14px;
}
.i9-lists-subtitle {
    margin: 0 0 10px;
    font-size: 14px;
    font-weight: 600;
}
.i9-section2-select {
    width: 100%;
    font-size: 11px;
    padding: 4px 6px;
}
.i9-lists-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 10px;
    line-height: 14px;
    border: 1px solid #000;
}
.i9-lists-table th,
.i9-lists-table td {
    border: 1px solid #000;
    padding: 8px 10px;
    vertical-align: top;
}
.i9-list-a-col { width: 28%; text-align: center; background-color: #f5f5f5; }
.i9-or-col { width: 4%; text-align: center; font-weight: bold; font-size: 12px; background-color: #f5f5f5; }
.i9-list-b-col { width: 28%; text-align: center; background-color: #f5f5f5; }
.i9-and-col { width: 4%; text-align: center; font-weight: bold; font-size: 12px; background-color: #f5f5f5; }
.i9-list-c-col { width: 36%; text-align: center; background-color: #f5f5f5; }
.i9-list-td { padding: 10px 12px !important; }
.i9-or-td { background-color: #f5f5f5; }
.i9-and-td { background-color: #f5f5f5; }
.list-subtitle { font-size: 10px; font-weight: normal; }
.list-hint { font-size: 11px; font-weight: normal; font-style: italic; }
.i9-page-2 ol.i9-list {
    padding-left: 1.4em;
    list-style: decimal;
    margin: 0.5em 0;
}
.i9-page-2 .i9-list li {
    border-bottom: 1px solid #e5e5e5;
    padding: 6px 0;
    margin-bottom: 0;
    line-height: 1.4;
}
.i9-page-2 .i9-sublist {
    margin-top: 4px;
    padding-left: 1.2em;
}
.i9-page-2 .i9-subheader {
    list-style: none;
    margin-left: -1.4em;
    font-weight: bold;
    text-align: center;
    margin-top: 8px;
    margin-bottom: 4px;
}
.i9-page-2 .i9-list-note {
    font-size: 13px;
    margin: 6px 0 4px;
}
.i9-page-footer {
    font-size: 10px;
    margin-top: 10px;
}
.i9-page-footer label:first-child { float: left; }
.i9-page-footer label:last-child { float: right; }
.i9-page-2 .header {
    padding: 0.5rem 0;
}
.i9-error {
    color: #b1151d;
    font-size: 13px;
    margin: 1rem 0;
    padding: 0.75rem 1rem;
    background: #fef2f2;
    border-radius: 6px;
    border-left: 4px solid #b1151d;
}
.i9-form-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    margin-top: 2rem;
    padding: 1.25rem 0 0.5rem;
    border-top: 1px solid #e5e5e5;
}
.i9-btn {
    padding: 0.6rem 1.25rem;
    font-size: 14px;
    border-radius: 6px;
    cursor: pointer;
    border: 1px solid #ccc;
    background: #f5f5f5;
    transition: background 0.15s, border-color 0.15s;
}
.i9-btn:hover:not(:disabled) {
    background: #ebebeb;
    border-color: #aaa;
}
.i9-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}
.i9-btn-primary {
    background: #0d6efd;
    color: #fff;
    border-color: #0d6efd;
}
.i9-btn-primary:hover:not(:disabled) {
    background: #0b5ed7;
    border-color: #0b5ed7;
}
.i9-btn-back {
    background: #fff;
    color: #333;
}
.i9-btn-add {
    background: #f8f9fa;
    border: 1px dashed #0d6efd;
    color: #0d6efd;
    cursor: pointer;
    font-size: 13px;
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    margin-bottom: 1rem;
    transition: background 0.15s, color 0.15s;
}
.i9-btn-add:hover {
    background: #e7f1ff;
    text-decoration: none;
}
/* Document Upload section */
.i9-doc-upload {
    padding: 1rem 0;
}
.i9-doc-upload-title {
    margin: 0 0 0.75rem;
    font-size: 17px;    
    font-weight: 600;
    color: #333;
}
.i9-doc-upload-table {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid #ccc;
    font-size: 12px;
    margin-bottom: 1.5rem;
}
.i9-doc-upload-table th,
.i9-doc-upload-table td {
    border: 1px solid #ccc;
    padding: 12px;
    vertical-align: top;
}
.i9-doc-col-a {
    width: 28%;
    background: #f8f4f4;
    color: #BF162F;
    font-weight: 600;
}
.i9-doc-sub {
    font-size: 11px;
    font-weight: normal;
    color: #666;
}
.i9-doc-col-or {
    width: 4%;
    text-align: center;
    font-weight: bold;
    background: #f0f0f0;
}
.i9-doc-col-bc {
    width: 68%;
    background: #fafafa;
}
.i9-doc-or-cell {
    background: #f0f0f0;
}
.i9-doc-bc-row {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}
.i9-doc-half {
    flex: 1;
    min-width: 180px;
}
.i9-doc-label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    margin-bottom: 4px;
    color: #444;
}
.i9-select {
    display: block;
    width: 100%;
    font-size: 12px;
    padding: 6px 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    background: #fff;
    margin-bottom: 8px;
}
.i9-file-input {
    display: block;
    width: 100%;
    font-size: 12px;
    padding: 6px 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    background: #fff;
}
.i9-current-file {
    margin: 6px 0 0;
    font-size: 11px;
    color: #666;
}
.i9-additional-doc {
    border: 1px solid #e5e5e5;
    border-radius: 6px;
    padding: 1rem;
    background: #fafafa;
}
.i9-additional-doc-row {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    align-items: flex-start;
}
.i9-additional-doc-input {
    flex: 1;
    min-width: 200px;
}
.i9-additional-doc-file {
    flex: 0 0 220px;
}
.i9-text-input {
    width: 100%;
    font-size: 12px;
    padding: 6px 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
}
</style>
