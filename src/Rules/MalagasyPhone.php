<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Laravel\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Mdgascar\MdgUtils\Laravel\MdgUtilsServiceProvider;
use Mdgascar\MdgUtils\Phone\PhoneNumber;

/**
 * Passes for a Malagasy mobile number written in any common way (`034 12 345 67`,
 * `+261 34 12 345 67`, ...). Also available as the string rule `mg_phone`.
 *
 * Example: `['phone' => ['required', new MalagasyPhone()]]`
 */
final class MalagasyPhone implements ValidationRule
{
    public const string NAME = 'mg_phone';

    /**
     * @internal backs the string rule; use the rule object or the string rule instead
     */
    public static function passes(mixed $value): bool
    {
        return PhoneNumber::isValid($value);
    }

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (!self::passes($value)) {
            $fail(MdgUtilsServiceProvider::validationMessageKey(self::NAME))->translate();
        }
    }
}
