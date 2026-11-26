<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Laravel;

use Mdgascar\MdgUtils\Ariary\Ariary;
use Mdgascar\MdgUtils\Ariary\DecimalSeparator;
use Mdgascar\MdgUtils\Ariary\Grouping;
use Mdgascar\MdgUtils\Ariary\SymbolPosition;
use Mdgascar\MdgUtils\ErrorCode;
use Mdgascar\MdgUtils\MdgUtilsException;

/**
 * How the app writes and reads Ariary amounts: the `ariary` section of config/mdg-utils.php.
 *
 * Writing follows the configured grouping, symbol position and decimal separator. Reading
 * accepts every Malagasy separator (space or dot) unless the app writes English-style
 * amounts (`grouping` = `comma`), in which case it reads exactly that convention.
 */
final readonly class AriaryOptions
{
    public const string GROUPING = 'grouping';
    public const string SYMBOL_POSITION = 'symbol_position';
    public const string DECIMAL = 'decimal';

    public function __construct(
        public Grouping $grouping = Grouping::DEFAULT,
        public SymbolPosition $symbolPosition = SymbolPosition::Before,
        public DecimalSeparator $decimal = DecimalSeparator::DEFAULT,
    ) {
        if ($grouping->value === $decimal->value) {
            throw new \InvalidArgumentException(\sprintf(
                'mdg-utils.ariary: %s and %s cannot both be "%s".',
                self::GROUPING,
                self::DECIMAL,
                $grouping->value,
            ));
        }
    }

    /**
     * Reads the `ariary` config section; a missing key keeps the core default.
     *
     * Example: `AriaryOptions::fromConfig(['grouping' => 'dot'])->grouping` is `Grouping::Dot`.
     *
     * @param array<mixed> $config
     *
     * @throws \InvalidArgumentException naming the key, for a value that is not an option
     */
    public static function fromConfig(array $config): self
    {
        return new self(
            self::option($config, self::GROUPING, Grouping::DEFAULT),
            self::option($config, self::SYMBOL_POSITION, SymbolPosition::Before),
            self::option($config, self::DECIMAL, DecimalSeparator::DEFAULT),
        );
    }

    /**
     * Example: with the default config, `format(Ariary::fromInt(1500))` returns `'Ar 1 500'`.
     */
    public function format(Ariary $amount): string
    {
        return $amount->format($this->grouping, $this->symbolPosition, $this->decimal);
    }

    /**
     * Turns form or JSON input into an amount: an `Ariary`, a non-negative int or float, or
     * text such as `'Ar 1 500,50'` read with the configured convention.
     *
     * @throws MdgUtilsException with the core error code, or `ErrorCode::InvalidType` for any
     *                           other type
     */
    public function read(mixed $value): Ariary
    {
        return match (true) {
            $value instanceof Ariary => $value,
            \is_int($value) => Ariary::fromInt($value),
            \is_float($value) => Ariary::fromFloat($value),
            \is_string($value) => Ariary::parse($value, $this->readingGrouping(), $this->decimal),
            default => throw new MdgUtilsException(
                ErrorCode::InvalidType,
                \sprintf('An Ariary amount must be an Ariary, int, float or string, got %s.', get_debug_type($value)),
            ),
        };
    }

    /**
     * Like `read()`, but returns `null` instead of throwing.
     */
    public function tryRead(mixed $value): ?Ariary
    {
        try {
            return $this->read($value);
        } catch (MdgUtilsException) {
            return null;
        }
    }

    private function readingGrouping(): ?Grouping
    {
        return $this->grouping === Grouping::Comma ? Grouping::Comma : null;
    }

    /**
     * @template T of \BackedEnum
     *
     * @param array<mixed> $config
     * @param T $default
     *
     * @return T
     */
    private static function option(array $config, string $key, \BackedEnum $default): \BackedEnum
    {
        $value = $config[$key] ?? null;
        if ($value === null) {
            return $default;
        }
        $option = \is_string($value) ? $default::tryFrom($value) : null;
        if ($option === null) {
            throw new \InvalidArgumentException(\sprintf(
                'mdg-utils.ariary.%s must be one of "%s", got %s.',
                $key,
                implode('", "', array_map(static fn(\BackedEnum $case): int|string => $case->value, $default::cases())),
                var_export($value, true),
            ));
        }

        return $option;
    }
}
