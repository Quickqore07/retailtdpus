<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\Onboarding\OnboardingList;
use App\Models\Settings\Company as SettingsCompany;

class BenefitElection
{
    public static function resolveStateCode(?string $raw): string
    {
        $normalized = strtoupper(trim((string) $raw));
        $supported = config('benefit_election.supported_states', []);
        $nameMap = config('benefit_election.state_name_to_code', []);

        if (in_array($normalized, $supported, true)) {
            return $normalized;
        }

        if (isset($nameMap[$normalized])) {
            return $nameMap[$normalized];
        }

        if (str_contains($normalized, 'MD')) {
            return 'MD';
        }

        return 'PA';
    }

    public static function stateDisplayName(string $stateCode): string
    {
        return config('benefit_election.state_names.' . $stateCode, $stateCode);
    }

    public static function activePlan(): ?array
    {
        $plans = config('benefit_election.plans', []);

        foreach ($plans as $plan) {
            if (! empty($plan['active'])) {
                return $plan;
            }
        }

        return $plans[0] ?? null;
    }

    public static function resolveRate(mixed $value, string $stateCode): string
    {
        if (! is_array($value)) {
            return (string) $value;
        }

        if (isset($value[$stateCode])) {
            return (string) $value[$stateCode];
        }

        if (isset($value['PA'])) {
            return (string) $value['PA'];
        }

        if (isset($value['VA'])) {
            return (string) $value['VA'];
        }

        $first = reset($value);

        return is_string($first) ? $first : '';
    }

    public static function resolveCompanyStateName(?OnboardingList $onboarding = null, ?Employee $employee = null): string
    {
        if ($employee?->benefits_state) {
            return $employee->benefits_state;
        }

        $companyId = $onboarding?->company_id;

        if (! $companyId && $employee) {
            $employee->loadMissing(['employeeRates', 'employeeRatesRequests']);
            $rate = $employee->employeeRates->sortByDesc('created_at')->first()
                ?? $employee->employeeRatesRequests->sortByDesc('created_at')->first();
            $companyId = $rate?->company_id;
        }

        if (! $companyId) {
            return 'PA';
        }

        $company = SettingsCompany::select('id', 'state_id')->with('state')->find($companyId);
        $state = $company?->state?->name ?? 'PA';

        if ($state && str_contains($state, 'MD')) {
            return 'MD';
        }

        return $state;
    }

    public static function resolveBenefitStateCode(?OnboardingList $onboarding = null, ?Employee $employee = null): string
    {
        return self::resolveStateCode(self::resolveCompanyStateName($onboarding, $employee));
    }

    public static function resolvedPlanForState(string $stateCode): ?array
    {
        $plan = self::activePlan();

        if (! $plan) {
            return null;
        }

        $resolvePlanSection = static function (?array $section) use ($stateCode): array {
            if (! $section) {
                return [];
            }

            return array_map(
                static fn ($value) => self::resolveRate($value, $stateCode),
                $section
            );
        };

        return [
            'year' => $plan['year'] ?? '',
            'plan_year' => $plan['plan_year'] ?? '',
            'next_plan_year' => $plan['next_plan_year'] ?? '',
            'date' => $plan['date'] ?? '',
            'health_care_plan' => [
                'op1' => $resolvePlanSection($plan['health_care_plan']['op1'] ?? null),
                'op2' => $resolvePlanSection($plan['health_care_plan']['op2'] ?? null),
            ],
            'dental_plan' => $resolvePlanSection($plan['dental_plan'] ?? null),
            'vision_plan' => $resolvePlanSection($plan['vision_plan'] ?? null),
        ];
    }
}
