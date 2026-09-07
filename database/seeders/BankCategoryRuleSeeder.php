<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Settings\BankCategoryRule;
use App\Models\Settings\BankCategoryRuleCondition;

class BankCategoryRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This seeder migrates the hard-coded rules from the old BankCategoryRule service
     */
    public function run(): void
    {
        $rules = [
            [
                'name' => 'Automatic Transfer (Internal)',
                'label' => 'Automatic Transfer (Internal)',
                'condition' => 'All',
                'conditions' => [
                    ['condition_type' => 'Contains', 'value' => 'AUTOMATIC TRANSFER TRANSFER'],
                ],
            ],
            [
                'name' => 'Force Post Credit - Bankcard',
                'label' => 'FORCE POST CREDIT 5/3 BANKCARD SYS',
                'condition' => 'All',
                'conditions' => [
                    ['condition_type' => 'Contains', 'value' => 'FORCE POST CREDIT 5/3 BANKCARD SYS'],
                ],
            ],
            [
                'name' => 'Force Post Debit - AMEX',
                'label' => 'FORCE POST DEBIT AMEX EPAYMENT ACH PMT',
                'condition' => 'All',
                'conditions' => [
                    ['condition_type' => 'Contains', 'value' => 'FORCE POST DEBIT AMEX EPAYMENT ACH PMT'],
                ],
            ],
            [
                'name' => '5/3 Bankcard System',
                'label' => '5/3 BANKCARD SYS',
                'condition' => 'All',
                'conditions' => [
                    ['condition_type' => 'Contains', 'value' => '5/3 BANKCARD SYS'],
                ],
            ],
            [
                'name' => 'Papa Johns USA',
                'label' => "PAPA JOHN'S USA",
                'condition' => 'All',
                'conditions' => [
                    ['condition_type' => 'Contains', 'value' => "PAPA JOHN'S USA"],
                ],
            ],
            [
                'name' => 'Online Transfer',
                'label' => 'ONLINE TRANSFER',
                'condition' => 'some',
                'conditions' => [
                    ['condition_type' => 'Contains', 'value' => 'ONLINE T RANSFER'],
                    ['condition_type' => 'Contains', 'value' => 'ONLINE TRANSFER'],
                ],
            ],
            [
                'name' => 'Check Payment',
                'label' => 'Check Paid',
                'condition' => 'All',
                'conditions' => [
                    ['condition_type' => 'Contains', 'value' => 'ECP INCLEARING CHECK'],
                ],
            ],
            [
                'name' => 'Paychex Invoice',
                'label' => 'PAYCHEX EIB INVOICE',
                'condition' => 'All',
                'conditions' => [
                    ['condition_type' => 'Contains', 'value' => 'PAYCHEX EIB INVOICE'],
                ],
            ],
            [
                'name' => 'Baltimore Gas Bill Payment',
                'label' => 'BALTIMORE GAS AN BILLPAY',
                'condition' => 'All',
                'conditions' => [
                    ['condition_type' => 'Contains', 'value' => 'BALTIMORE GAS AN BILLPAY'],
                ],
            ],
            [
                'name' => 'Public Service PSEG',
                'label' => 'PUBLIC SERVICE PSEG',
                'condition' => 'All',
                'conditions' => [
                    ['condition_type' => 'Contains', 'value' => 'PUBLIC SERVICE PSEG'],
                ],
            ],
            [
                'name' => "Cockey's Enterprise",
                'label' => "COCKEY'S ENTERPR",
                'condition' => 'All',
                'conditions' => [
                    ['condition_type' => 'Contains', 'value' => "COCKEY'S ENTERPR"],
                ],
            ],
            [
                'name' => 'Studebaker Bank Sweep',
                'label' => 'STUDEBAKERSUBINC BANK SWEEP',
                'condition' => 'All',
                'conditions' => [
                    ['condition_type' => 'Contains', 'value' => 'STUDEBAKERSUBINC BANK SWEEP'],
                ],
            ],
        ];

        foreach ($rules as $ruleData) {
            $rule = BankCategoryRule::create([
                'name' => $ruleData['name'],
                'label' => $ruleData['label'],
                'condition' => $ruleData['condition'],
            ]);

            foreach ($ruleData['conditions'] as $condition) {
                BankCategoryRuleCondition::create([
                    'bank_category_rule_id' => $rule->id,
                    'condition_type' => $condition['condition_type'],
                    'value' => $condition['value'],
                ]);
            }
        }

        $this->command->info('Bank category rules seeded successfully!');
    }
}
