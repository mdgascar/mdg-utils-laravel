<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Laravel\Tests;

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;
use Mdgascar\MdgUtils\Ariary\Ariary;
use Mdgascar\MdgUtils\MdgUtilsException;
use Mdgascar\MdgUtils\Phone\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;

final class BladeTest extends TestCase
{
    private const int AMOUNT = 1500;
    private const float DECIMAL_AMOUNT = 1500.5;
    private const string E164 = '+261341234567';
    private const int WORDS_NUMBER = 21;
    private const float WORDS_DECIMAL = 0.5;
    // 30 000 000: 'telopolo tapitrisa' in the standard system.
    private const int TRADITIONAL_NUMBER = 30_000_000;
    // A float this large has lost its decimals; text keeps them (core spec words rule 13).
    private const string EXACT_TEXT = '2000000000000000.9';
    private const int YEAR = 2026;
    private const int MONTH = 11;
    private const int DAY = 23;

    /**
     * @return iterable<string, array{string, array<string, mixed>, string}>
     */
    public static function directives(): iterable
    {
        yield 'ariary from an int' => ['@ariary($v)', ['v' => self::AMOUNT], self::nbsp('Ar 1 500')];
        yield 'ariary from an Ariary' => ['@ariary($v)', ['v' => Ariary::fromFloat(self::DECIMAL_AMOUNT)], self::nbsp('Ar 1 500,5')];
        yield 'phone from text' => ['@mgPhone($v)', ['v' => self::E164], '034 12 345 67'];
        yield 'phone with a format' => [
            '@mgPhone($v, \Mdgascar\MdgUtils\Phone\PhoneNumberFormat::International)',
            ['v' => PhoneNumber::parse(self::E164)],
            '+261 34 12 345 67',
        ];
        yield 'words from an int' => ['@mgSpell($v)', ['v' => self::WORDS_NUMBER], 'iraika amby roapolo'];
        yield 'words from a float' => ['@mgSpell($v)', ['v' => self::WORDS_DECIMAL], 'aotra faingo dimy'];
        yield 'spell exact decimal text' => ['@mgSpell($v)', ['v' => self::EXACT_TEXT], 'roa tapitrisa lavitrisa faingo sivy'];
        yield 'words in the traditional system' => [
            '@mgSpell($v, \Mdgascar\MdgUtils\Words\NumberSystem::Traditional)',
            ['v' => self::TRADITIONAL_NUMBER],
            'telo safatsiroa',
        ];
        yield 'date from a DateTime' => [
            '@mgDate($v)',
            ['v' => new \DateTimeImmutable(\sprintf('%d-%02d-%02d', self::YEAR, self::MONTH, self::DAY))],
            '23 Novambra 2026',
        ];
        yield 'date from text with a style' => [
            '@mgDate($v, \Mdgascar\MdgUtils\Date\DateStyle::Numeric)',
            ['v' => '2026-11-23'],
            '23/11/2026',
        ];
        foreach (['@ariary($v)', '@mgPhone($v)', '@mgSpell($v)', '@mgDate($v)'] as $template) {
            yield "{$template} prints nothing for null" => [$template, ['v' => null], ''];
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('directives')]
    public function testEachDirectivePrintsTheValueInMalagasy(string $template, array $data, string $expected): void
    {
        self::assertSame($expected, Blade::render($template, $data));
    }

    public function testAriaryFollowsTheConfig(): void
    {
        $this->useAriaryOption('grouping', 'dot');
        $this->useAriaryOption('symbol_position', 'after');

        self::assertSame(self::nbsp('1.500 Ar'), Blade::render('@ariary($v)', ['v' => self::AMOUNT]));
    }

    public function testOutputIsEscaped(): void
    {
        $compiled = Blade::compileString('@mgSpell($v)');

        self::assertStringContainsString('echo e(', $compiled);
    }

    public function testAnInvalidValueThrows(): void
    {
        try {
            Blade::render('@mgPhone($v)', ['v' => '031 12 345 67']);
            self::fail('Expected an exception');
        } catch (\Exception $exception) {
            self::assertInstanceOf(ViewException::class, $exception);
            self::assertInstanceOf(MdgUtilsException::class, $exception->getPrevious());
        }
    }

    /**
     * The core writes U+00A0 (no-break space) between groups and after the symbol.
     */
    private static function nbsp(string $text): string
    {
        return str_replace(' ', "\u{00A0}", $text);
    }
}
