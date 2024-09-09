<?php
declare(strict_types=1);

namespace Budgetcontrol\Entry\Entity\Validations;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class PlannedType implements ValidationRule {

    const TYPES = ['monthly', 'yearly', 'daily', 'weekly'];

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, $value, Closure $fail): void
    {
        if(!in_array($value, self::TYPES)) {
            $fail("The $attribute must be one of the following: " . implode(', ', self::TYPES));
        }
    }

}