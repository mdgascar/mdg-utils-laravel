<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Laravel\Tests;

use Mdgascar\MdgUtils\Ariary\Ariary;
use Mdgascar\MdgUtils\Ariary\DecimalSeparator;
use Mdgascar\MdgUtils\Ariary\Grouping;
use Mdgascar\MdgUtils\Ariary\SymbolPosition;
use Mdgascar\MdgUtils\ErrorCode;
use Mdgascar\MdgUtils\Laravel\AriaryOptions;
use Mdgascar\MdgUtils\MdgUtilsException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AriaryOptionsTest extends TestCase
{
    private const int AMOUNT = 1500;
    private const float DECIMAL_AMOUNT = 1500.5;

    public function testAnEmptyConfigKeepsTheCoreDefaults(): void
    {
        $options = AriaryOptions::fromConfig([]);

        self::assertSame(Grouping::Space, $options->grouping);
        self::assertSame(SymbolPosition::Before, $options->symbolPosition);
        self::assertSame(DecimalSeparator::Comma, $options->decimal);
    }

    public function testTheConfigPicksEachOption(): void
    {
        $options = AriaryOptions::fromConfig([
            AriaryOptions::GROUPING => 'comma',
            AriaryOptions::SYMBOL_POSITION => 'after',
            AriaryOptions::DECIMAL => 'dot',
        ]);

        self::assertSame(self::nbsp('1,500.5 Ar'), $options->format(Ariary::fromFloat(self::DECIMAL_AMOUNT)));
    }

    public function testAnUnknownValueNamesTheKeyAndTheChoices(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('mdg-utils.ariary.grouping must be one of "space", "dot", "comma", got \'tab\'.');

        AriaryOptions::fromConfig([AriaryOptions::GROUPING => 'tab']);
    }

    public function testANonTextValueIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('mdg-utils.ariary.decimal must be one of "comma", "dot", got true.');

        AriaryOptions::fromConfig([AriaryOptions::DECIMAL => true]);
    }

    public function testTheSameGroupingAndDecimalIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('mdg-utils.ariary: grouping and decimal cannot both be "dot".');

        AriaryOptions::fromConfig([AriaryOptions::GROUPING => 'dot', AriaryOptions::DECIMAL => 'dot']);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function readableAmounts(): iterable
    {
        yield 'an Ariary' => [Ariary::fromInt(self::AMOUNT)];
        yield 'an int' => [self::AMOUNT];
        yield 'a float' => [(float) self::AMOUNT];
        yield 'Malagasy text with spaces' => ['Ar 1 500'];
        yield 'Malagasy text with a dot' => ['1.500 Ar'];
    }

    #[DataProvider('readableAmounts')]
    public function testReadAcceptsAnAmountInEveryFormAnAppHasIt(mixed $value): void
    {
        self::assertEquals(self::AMOUNT, AriaryOptions::fromConfig([])->read($value)->amount);
    }

    public function testReadUsesTheConfiguredDecimalSeparator(): void
    {
        $options = AriaryOptions::fromConfig([AriaryOptions::DECIMAL => 'dot']);

        self::assertSame(self::DECIMAL_AMOUNT, $options->read('1 500.5')->amount);
    }

    public function testEnglishStyleConfigReadsEnglishStyleText(): void
    {
        $options = AriaryOptions::fromConfig([AriaryOptions::GROUPING => 'comma', AriaryOptions::DECIMAL => 'dot']);

        self::assertSame(self::DECIMAL_AMOUNT, $options->read('1,500.5')->amount);
        self::assertNull($options->tryRead('1 500'));
    }

    public function testReadRejectsOtherTypesWithInvalidType(): void
    {
        try {
            AriaryOptions::fromConfig([])->read([self::AMOUNT]);
            self::fail('Expected MdgUtilsException');
        } catch (MdgUtilsException $exception) {
            self::assertSame(ErrorCode::InvalidType, $exception->errorCode);
        }
    }

    public function testTryReadReturnsNullForInvalidInput(): void
    {
        $options = AriaryOptions::fromConfig([]);

        self::assertNull($options->tryRead(-1));
        self::assertNull($options->tryRead('abc'));
        self::assertNull($options->tryRead(null));
    }

    /**
     * The core writes U+00A0 (no-break space) between groups and after the symbol.
     */
    private static function nbsp(string $text): string
    {
        return str_replace(' ', "\u{00A0}", $text);
    }
}
