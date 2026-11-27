<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Laravel\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Mdgascar\MdgUtils\Laravel\Translation;
use Mdgascar\MdgUtils\Phone\PhoneLineType;
use Mdgascar\MdgUtils\Phone\PhoneNumber;

/**
 * Passes for a Malagasy phone number written in any common way (`034 12 345 67`,
 * `+261 34 12 345 67`, `020 22 421 19`, ...). Pass a line type to accept only mobile or only
 * fixed numbers, e.g. for SMS. Also available as the string rules `mg_phone` and
 * `mg_phone:mobile`.
 *
 * Example: `['phone' => ['required', new MalagasyPhone(PhoneLineType::Mobile)]]`
 */
final readonly class MalagasyPhone implements ValidationRule
{
    public const string NAME = 'mg_phone';

    /**
     * @param PhoneLineType|null $type only this kind of line, or any when null
     */
    public function __construct(
        private ?PhoneLineType $type = null,
    ) {}

    /**
     * The rule of the string form: `mg_phone` or `mg_phone:mobile`.
     *
     * @param array<mixed> $parameters
     */
    public static function fromParameters(array $parameters): self
    {
        $type = $parameters[0] ?? null;

        return new self(\is_string($type) ? PhoneLineType::from($type) : null);
    }

    /**
     * Example: `(new MalagasyPhone(PhoneLineType::Mobile))->passes('020 22 421 19')` is `false`.
     */
    public function passes(mixed $value): bool
    {
        $phone = PhoneNumber::tryParse($value);

        return $phone !== null && ($this->type === null || $phone->type === $this->type);
    }

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (!$this->passes($value)) {
            $parameters = $this->type === null ? [] : [$this->type->value];
            $fail(Translation::validationKey(Translation::messageName(self::NAME, $parameters)))->translate();
        }
    }
}
