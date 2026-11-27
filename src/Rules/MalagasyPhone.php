<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Laravel\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Mdgascar\MdgUtils\Laravel\Translation;
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
     * Example: `(new MalagasyPhone())->passes('034 12 345 67')` is `true`.
     */
    public function passes(mixed $value): bool
    {
        return PhoneNumber::isValid($value);
    }

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (!$this->passes($value)) {
            $fail(Translation::validationKey(self::NAME))->translate();
        }
    }
}
