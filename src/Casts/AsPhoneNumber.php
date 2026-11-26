<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Laravel\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Mdgascar\MdgUtils\ErrorCode;
use Mdgascar\MdgUtils\MdgUtilsException;
use Mdgascar\MdgUtils\Phone\PhoneNumber;

/**
 * Stores a phone number as E.164 text (`+261341234567`) and reads it back as a `PhoneNumber`.
 * Assigning text parses it, so an invalid number throws before it reaches the database:
 * validate input with `mg_phone` first.
 *
 * Example: `protected function casts(): array { return ['phone' => AsPhoneNumber::class]; }`
 *
 * @implements CastsAttributes<PhoneNumber, PhoneNumber|string>
 */
final class AsPhoneNumber implements CastsAttributes
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?PhoneNumber
    {
        return $value === null ? null : self::toPhoneNumber($value);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : self::toPhoneNumber($value)->e164;
    }

    private static function toPhoneNumber(mixed $value): PhoneNumber
    {
        return match (true) {
            $value instanceof PhoneNumber => $value,
            \is_string($value) => PhoneNumber::parse($value),
            default => throw new MdgUtilsException(
                ErrorCode::InvalidType,
                \sprintf('A phone number must be a PhoneNumber or a string, got %s.', get_debug_type($value)),
            ),
        };
    }
}
