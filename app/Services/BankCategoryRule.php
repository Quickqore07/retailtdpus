<?php

namespace App\Services;

use App\Models\Settings\BankCategoryRule as BankCategoryRuleModel;
use Illuminate\Support\Facades\Cache;

class BankCategoryRule
{
    /**
     * Get category label for a bank transaction description based on user-defined rules
     * 
     * @param string|null $description
     * @return string|null
     */
    private $rules;
    public function __construct(){
        $this->rules = BankCategoryRuleModel::with('conditions')->get();
    }
    public function getCategory($description)
    {
        if (empty($description)) {
            return null;
        }

        $description = trim($description);
        
        // Process each rule to find a match
        foreach ($this->rules as $rule) {
            if ($this->matchesRule($description, $rule)) {
                return $rule->label;
            }
        }

        return null;
    }

    /**
     * Check if description matches the rule conditions
     * 
     * @param string $description
     * @param BankCategoryRuleModel $rule
     * @return bool
     */
    protected function matchesRule($description, $rule)
    {
        $conditions = $rule->conditions;
        
        if ($conditions->isEmpty()) {
            return false;
        }

        $descriptionUpper = strtoupper($description);
        $descriptionUpper = preg_replace('/\s+/', ' ', trim($descriptionUpper));
        $matchedCount = 0;

        foreach ($conditions as $condition) {
            $value = strtoupper(trim($condition->value));
            $value = preg_replace('/\s+/', ' ', trim($value));
            $matched = false;

            switch ($condition->condition_type) {
                case 'Contains':
                    $matched = str_contains($descriptionUpper, $value);
                    break;
                    
                case 'Matches':
                    $matched = str_contains($descriptionUpper, $value);
                    break;
                    
                case 'Starts With':
                    $matched = str_starts_with($descriptionUpper, $value);
                    break;
                    
                case 'Ends With':
                    $matched = str_ends_with($descriptionUpper, $value);
                    break;
                    
                case 'Is Equal To':
                    $matched = $descriptionUpper === $value;
                    break;
                    
                default:
                    $matched = str_contains($descriptionUpper, $value);
                    break;
            }

            if ($matched) {
                $matchedCount++;
            }
        }

        // Check condition type: All or Some
        if ($rule->condition === 'All') {
            return $matchedCount === $conditions->count();
        } else {
            return $matchedCount > 0;
        }
    }

    /**
     * Clear the cache for bank category rules
     * Useful after creating/updating/deleting rules
     * 
     * @return void
     */
    public static function clearCache()
    {
        Cache::forget('bank_category_rules');
    }
}
