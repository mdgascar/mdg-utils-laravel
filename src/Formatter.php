<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Laravel;

use Mdgascar\MdgUtils\Ariary\Ariary;
use Mdgascar\MdgUtils\Date\DateStyle;
use Mdgascar\MdgUtils\Date\MalagasyDate;
use Mdgascar\MdgUtils\Phone\PhoneNumber;
use Mdgascar\MdgUtils\Phone\PhoneNumberFormat;
use Mdgascar\MdgUtils\Words\NumberSystem;
use Mdgascar\MdgUtils\Words\NumberWords;

/**
 * What the Blade directives print. Each method takes the typed value or the raw one an app
 * usually has at hand, and prints `''` for `null` so optional columns need no `@if`.
 *
 * Invalid input throws `MdgUtilsException`, like the core: a wrong value in a view is a bug
 * to see, not text to hide.
 */
final readonly class Formatter
{
    private const string NOTHING = '';

    public function __construct(
        private AriaryOptions $ariary,
    ) {}

    /**
     * `@ariary($order->total)` prints `Ar 1 500` with the default config.
     */
    public function ariary(Ariary|int|float|null $amount): string
    {
        return $amount === null ? self::NOTHING : $this->ariary->format($this->ariary->read($amount));
    }

    /**
     * `@mgPhone($user->phone)` prints `034 12 345 67`; pass a `PhoneNumberFormat` for another.
     */
    public function phone(PhoneNumber|string|null $phone, PhoneNumberFormat $format = PhoneNumberFormat::National): string
    {
        if ($phone === null) {
            return self::NOTHING;
        }

        return ($phone instanceof PhoneNumber ? $phone : PhoneNumber::parse($phone))->format($format);
    }

    /**
     * `@mgWords(21)` prints `iraika amby roapolo`; pass `NumberSystem::Traditional` for one name
     * per power of ten: `@mgWords(30000000, NumberSystem::Traditional)` prints `telo safatsiroa`.
     */
    public function words(int|float|null $number, NumberSystem $system = NumberSystem::Standard): string
    {
        return match (true) {
            $number === null => self::NOTHING,
            \is_int($number) => NumberWords::fromInt($number, $system),
            default => NumberWords::fromFloat($number, $system),
        };
    }

    /**
     * `@mgDate($order->created_at)` prints `23 Novambra 2026`; pass a `DateStyle` for another.
     * Text must be an ISO date such as `2026-11-23`.
     */
    public function date(\DateTimeInterface|string|null $date, DateStyle $style = DateStyle::DEFAULT): string
    {
        return match (true) {
            $date === null => self::NOTHING,
            $date instanceof \DateTimeInterface => MalagasyDate::fromDateTime($date)->format($style),
            default => MalagasyDate::fromString($date)->format($style),
        };
    }
}
