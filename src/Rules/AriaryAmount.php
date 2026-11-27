<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Laravel\Rules;

use Illuminate\Container\Container;
use Illuminate\Contracts\Validation\ValidationRule;
use Mdgascar\MdgUtils\Laravel\AriaryOptions;
use Mdgascar\MdgUtils\Laravel\Translation;

/**
 * Passes for an Ariary amount: text such as `Ar 1 500` or `1 500,50` (read with the
 * configured convention), or a non-negative number from JSON. Also available as the string
 * rule `mg_ariary`.
 *
 * Example: `['total' => ['required', new AriaryAmount()]]`
 */
final readonly class AriaryAmount implements ValidationRule
{
    public const string NAME = 'mg_ariary';

    private AriaryOptions $options;

    /**
     * @param AriaryOptions|null $options how to read amounts; the app's config when omitted,
     *                                    since rules are usually built with `new` in a rules array
     */
    public function __construct(?AriaryOptions $options = null)
    {
        $this->options = $options ?? Container::getInstance()->make(AriaryOptions::class);
    }

    /**
     * Example: with the default config, `(new AriaryAmount())->passes('Ar 1 500')` is `true`.
     */
    public function passes(mixed $value): bool
    {
        return $this->options->tryRead($value) !== null;
    }

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (!$this->passes($value)) {
            $fail(Translation::validationKey(self::NAME))->translate();
        }
    }
}
