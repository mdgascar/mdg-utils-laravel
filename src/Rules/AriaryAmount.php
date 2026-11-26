<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Laravel\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Mdgascar\MdgUtils\Laravel\AriaryOptions;
use Mdgascar\MdgUtils\Laravel\MdgUtilsServiceProvider;

/**
 * Passes for an Ariary amount: text such as `Ar 1 500` or `1 500,50` (read with the
 * configured convention), or a non-negative number from JSON. Also available as the string
 * rule `mg_ariary`.
 *
 * Example: `['total' => ['required', new AriaryAmount()]]`
 */
final class AriaryAmount implements ValidationRule
{
    public const string NAME = 'mg_ariary';

    /**
     * @internal backs the string rule; use the rule object or the string rule instead
     */
    public static function passes(mixed $value): bool
    {
        return app(AriaryOptions::class)->tryRead($value) !== null;
    }

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (!self::passes($value)) {
            $fail(MdgUtilsServiceProvider::validationMessageKey(self::NAME))->translate();
        }
    }
}
