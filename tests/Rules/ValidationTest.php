<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Laravel\Tests\Rules;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Mdgascar\MdgUtils\Laravel\Rules\AriaryAmount;
use Mdgascar\MdgUtils\Laravel\Rules\MalagasyPhone;
use Mdgascar\MdgUtils\Laravel\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class ValidationTest extends TestCase
{
    private const string FIELD = 'field';
    private const string VALID_PHONE = '034 12 345 67';
    // 031 is not assigned to any operator.
    private const string UNKNOWN_OPERATOR_PHONE = '031 12 345 67';
    private const string VALID_AMOUNT = 'Ar 1 500,50';
    private const int JSON_AMOUNT = 1500;
    // A dot is not a decimal point by default.
    private const string DOT_DECIMAL_TEXT = '1.5';
    private const string PHONE_MESSAGE_EN = 'The field field must be a valid Malagasy mobile number.';
    private const string AMOUNT_MESSAGE_EN = 'The field field must be a valid Ariary amount.';

    /**
     * @return iterable<string, array{mixed, mixed, bool}>
     */
    public static function cases(): iterable
    {
        foreach (['string rule' => MalagasyPhone::NAME, 'rule object' => new MalagasyPhone()] as $form => $rule) {
            yield "phone {$form}, valid" => [$rule, self::VALID_PHONE, true];
            yield "phone {$form}, international" => [$rule, '+261 34 12 345 67', true];
            yield "phone {$form}, unknown operator" => [$rule, self::UNKNOWN_OPERATOR_PHONE, false];
            yield "phone {$form}, not text" => [$rule, [self::VALID_PHONE], false];
        }
        foreach (['string rule' => AriaryAmount::NAME, 'rule object' => new AriaryAmount()] as $form => $rule) {
            yield "ariary {$form}, text" => [$rule, self::VALID_AMOUNT, true];
            yield "ariary {$form}, JSON number" => [$rule, self::JSON_AMOUNT, true];
            yield "ariary {$form}, negative" => [$rule, -self::JSON_AMOUNT, false];
            yield "ariary {$form}, a dot is not a decimal point" => [$rule, self::DOT_DECIMAL_TEXT, false];
            yield "ariary {$form}, not an amount" => [$rule, 'abc', false];
        }
    }

    #[DataProvider('cases')]
    public function testEachRulePassesOrFailsInBothForms(mixed $rule, mixed $value, bool $passes): void
    {
        self::assertSame($passes, !$this->validate($value, $rule)->fails());
    }

    /**
     * @return iterable<string, array{mixed, string, string}>
     */
    public static function messages(): iterable
    {
        yield 'phone string rule, en' => [MalagasyPhone::NAME, 'en', self::PHONE_MESSAGE_EN];
        yield 'phone rule object, en' => [new MalagasyPhone(), 'en', self::PHONE_MESSAGE_EN];
        yield 'ariary string rule, en' => [AriaryAmount::NAME, 'en', self::AMOUNT_MESSAGE_EN];
        yield 'ariary rule object, en' => [new AriaryAmount(), 'en', self::AMOUNT_MESSAGE_EN];
        yield 'phone string rule, fr' => [MalagasyPhone::NAME, 'fr', 'Le champ field doit être un numéro de mobile malgache valide.'];
        yield 'ariary rule object, a locale without its own file falls back to en' => [new AriaryAmount(), 'mg', self::AMOUNT_MESSAGE_EN];
    }

    #[DataProvider('messages')]
    public function testTheMessageFollowsTheAppLocale(mixed $rule, string $locale, string $message): void
    {
        $this->app?->setLocale($locale);

        self::assertSame([$message], $this->validate('x', $rule)->errors()->get(self::FIELD));
    }

    public function testAnAppCustomMessageWins(): void
    {
        $validator = Validator::make(
            [self::FIELD => self::UNKNOWN_OPERATOR_PHONE],
            [self::FIELD => MalagasyPhone::NAME],
            [self::FIELD . '.' . MalagasyPhone::NAME => 'Bad :attribute.'],
        );

        self::assertSame(['Bad field.'], $validator->errors()->get(self::FIELD));
    }

    public function testAnAppTranslationForTheStringRuleWins(): void
    {
        Lang::addLines(['validation.' . MalagasyPhone::NAME => 'App :attribute.'], 'en');

        self::assertSame(['App field.'], $this->validate('x', MalagasyPhone::NAME)->errors()->get(self::FIELD));
    }

    public function testTheAriaryRuleReadsWithTheConfiguredConvention(): void
    {
        $this->useAriaryOption('grouping', 'comma');
        $this->useAriaryOption('decimal', 'dot');

        self::assertFalse($this->validate('1,500.50', AriaryAmount::NAME)->fails());
        self::assertTrue($this->validate(self::VALID_AMOUNT, new AriaryAmount())->fails());
    }

    private function validate(mixed $value, mixed $rule): ValidatorContract
    {
        return Validator::make([self::FIELD => $value], [self::FIELD => [$rule]]);
    }
}
