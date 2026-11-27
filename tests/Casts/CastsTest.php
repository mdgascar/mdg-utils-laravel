<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Laravel\Tests\Casts;

use Mdgascar\MdgUtils\Ariary\Ariary;
use Mdgascar\MdgUtils\Ariary\DecimalSeparator;
use Mdgascar\MdgUtils\Ariary\Grouping;
use Mdgascar\MdgUtils\Ariary\SymbolPosition;
use Mdgascar\MdgUtils\ErrorCode;
use Mdgascar\MdgUtils\Laravel\AriaryOptions;
use Mdgascar\MdgUtils\Laravel\Casts\AsAriary;
use Mdgascar\MdgUtils\Laravel\Tests\Fixtures\Order;
use Mdgascar\MdgUtils\Laravel\Tests\TestCase;
use Mdgascar\MdgUtils\MdgUtilsException;
use Mdgascar\MdgUtils\Phone\Operator;
use Mdgascar\MdgUtils\Phone\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;

final class CastsTest extends TestCase
{
    private const string E164 = '+261341234567';
    private const int AMOUNT = 1500;
    private const float DECIMAL_AMOUNT = 1500.2;
    private const int PHONE_AS_INT = 341234567;
    // How database drivers return integer and decimal columns.
    private const string INTEGER_COLUMN_TEXT = '1500';
    private const string DECIMAL_COLUMN_TEXT = '1500.20';
    private const string DECIMAL_DIGITS = '1500.2';
    // More digits than a float holds: a decimal column keeps them, and so does the cast.
    private const string EXACT_COLUMN_TEXT = '2000000000000000.9';

    public function testAPhoneNumberIsStoredAsE164AndReadAsAPhoneNumber(): void
    {
        $order = new Order(['phone' => '034 12 345 67']);

        self::assertSame(self::E164, $order->getAttributes()['phone']);
        self::assertInstanceOf(PhoneNumber::class, $order->phone);
        self::assertSame(Operator::Yas, $order->phone->operator);
    }

    public function testAPhoneNumberObjectIsStoredAsIs(): void
    {
        $order = new Order(['phone' => PhoneNumber::parse(self::E164)]);

        self::assertSame(self::E164, $order->getAttributes()['phone']);
    }

    public function testAnInvalidPhoneNumberThrowsBeforeReachingTheDatabase(): void
    {
        $this->expectException(MdgUtilsException::class);

        new Order(['phone' => '031 12 345 67']);
    }

    public function testANonTextPhoneNumberThrowsInvalidType(): void
    {
        $this->assertInvalidType(static fn(): Order => new Order(['phone' => self::PHONE_AS_INT]));
    }

    public function testNullStaysNull(): void
    {
        $order = new Order(['phone' => null, 'total' => null]);

        self::assertNull($order->getAttributes()['phone']);
        self::assertNull($order->phone);
        self::assertNull($order->getAttributes()['total']);
        self::assertNull($order->total);
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function assignedAmounts(): iterable
    {
        yield 'text' => ['Ar 1 500', self::INTEGER_COLUMN_TEXT];
        yield 'decimal text' => ['1 500,2 Ar', self::DECIMAL_DIGITS];
        yield 'an int' => [self::AMOUNT, self::INTEGER_COLUMN_TEXT];
        yield 'an Ariary' => [Ariary::fromFloat(self::DECIMAL_AMOUNT), self::DECIMAL_DIGITS];
    }

    #[DataProvider('assignedAmounts')]
    public function testAnAmountIsStoredAsItsExactDigits(mixed $assigned, string $stored): void
    {
        self::assertSame($stored, (new Order(['total' => $assigned]))->getAttributes()['total']);
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function storedAmounts(): iterable
    {
        yield 'an int' => [self::AMOUNT, self::INTEGER_COLUMN_TEXT];
        yield 'a float' => [self::DECIMAL_AMOUNT, self::DECIMAL_DIGITS];
        yield 'integer column text' => [self::INTEGER_COLUMN_TEXT, self::INTEGER_COLUMN_TEXT];
        yield 'decimal column text' => [self::DECIMAL_COLUMN_TEXT, self::DECIMAL_DIGITS];
        yield 'a decimal column beyond any float' => [self::EXACT_COLUMN_TEXT, self::EXACT_COLUMN_TEXT];
    }

    #[DataProvider('storedAmounts')]
    public function testAStoredAmountIsReadAsAnAriaryWithoutRounding(mixed $stored, string $digits): void
    {
        $order = (new Order())->setRawAttributes(['total' => $stored]);

        self::assertInstanceOf(Ariary::class, $order->total);
        self::assertSame($digits, $order->total->digits);
    }

    public function testAStoredValueThatIsNotANumberThrowsInvalidType(): void
    {
        $order = (new Order())->setRawAttributes(['total' => 'Ar 1 500']);

        $this->assertInvalidType(static fn(): mixed => $order->total);
    }

    public function testAnInvalidAmountThrowsBeforeReachingTheDatabase(): void
    {
        $this->expectException(MdgUtilsException::class);

        new Order(['total' => -1]);
    }

    public function testACastCanBeGivenItsOwnOptions(): void
    {
        $cast = new AsAriary(new AriaryOptions(Grouping::Comma, SymbolPosition::Before, DecimalSeparator::Dot));

        self::assertSame(self::DECIMAL_DIGITS, $cast->set(new Order(), 'total', '1,500.2', []));
    }

    private function assertInvalidType(\Closure $action): void
    {
        try {
            $action();
            self::fail('Expected MdgUtilsException');
        } catch (MdgUtilsException $exception) {
            self::assertSame(ErrorCode::InvalidType, $exception->errorCode);
        }
    }
}
