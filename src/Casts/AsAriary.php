<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Laravel\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Mdgascar\MdgUtils\Ariary\Ariary;
use Mdgascar\MdgUtils\ErrorCode;
use Mdgascar\MdgUtils\Laravel\AriaryOptions;
use Mdgascar\MdgUtils\MdgUtilsException;

/**
 * Stores an amount as a plain number (an integer or decimal column) and reads it back as an
 * `Ariary`. Assigning accepts an `Ariary`, a number, or text such as `'Ar 1 500'` read with
 * the configured convention; anything else throws: validate input with `mg_ariary` first.
 *
 * Example: `protected function casts(): array { return ['total' => AsAriary::class]; }`
 *
 * @implements CastsAttributes<Ariary, Ariary|int|float|string>
 */
final class AsAriary implements CastsAttributes
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Ariary
    {
        return match (true) {
            $value === null => null,
            \is_int($value) => Ariary::fromInt($value),
            \is_float($value) => Ariary::fromFloat($value),
            // Database drivers return integer and decimal columns as text such as "1500.20".
            \is_string($value) && is_numeric($value) => self::fromNumericText($value),
            default => throw new MdgUtilsException(
                ErrorCode::InvalidType,
                \sprintf('Column "%s" must hold a number, got %s.', $key, var_export($value, true)),
            ),
        };
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): int|float|null
    {
        return $value === null ? null : app(AriaryOptions::class)->read($value)->amount;
    }

    /**
     * @param numeric-string $value
     */
    private static function fromNumericText(string $value): Ariary
    {
        $number = $value + 0;

        return \is_int($number) ? Ariary::fromInt($number) : Ariary::fromFloat($number);
    }
}
