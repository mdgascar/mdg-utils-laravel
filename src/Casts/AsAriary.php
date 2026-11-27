<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Laravel\Casts;

use Illuminate\Container\Container;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Mdgascar\MdgUtils\Ariary\Ariary;
use Mdgascar\MdgUtils\ErrorCode;
use Mdgascar\MdgUtils\Laravel\AriaryOptions;
use Mdgascar\MdgUtils\MdgUtilsException;

/**
 * Stores an amount as its exact decimal digits (an integer or decimal column) and reads it back
 * as an `Ariary`, never rounded. Assigning accepts an `Ariary`, a number, or text such as `'Ar 1 500'` read with
 * the configured convention; anything else throws: validate input with `mg_ariary` first.
 *
 * Example: `protected function casts(): array { return ['total' => AsAriary::class]; }`
 *
 * @implements CastsAttributes<Ariary, Ariary|int|float|string>
 */
final readonly class AsAriary implements CastsAttributes
{
    // An integer or decimal column, as the database driver returns it.
    private const string COLUMN_DIGITS = '/^[0-9]+(?:\.[0-9]+)?$/D';

    private AriaryOptions $options;

    /**
     * @param AriaryOptions|null $options how to read assigned text; the app's config when
     *                                    omitted, since Eloquent builds casts with `new`
     */
    public function __construct(?AriaryOptions $options = null)
    {
        $this->options = $options ?? Container::getInstance()->make(AriaryOptions::class);
    }

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
            \is_string($value) && preg_match(self::COLUMN_DIGITS, $value) === 1 => Ariary::fromText($value),
            default => throw new MdgUtilsException(
                ErrorCode::InvalidType,
                \sprintf('Column "%s" must hold a number, got %s.', $key, var_export($value, true)),
            ),
        };
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : $this->options->read($value)->digits;
    }
}
