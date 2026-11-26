# mdg-utils-laravel

[![CI](https://github.com/mdgascar/mdg-utils-laravel/actions/workflows/ci.yml/badge.svg)](https://github.com/mdgascar/mdg-utils-laravel/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/mdgascar/mdg-utils-laravel)](https://packagist.org/packages/mdgascar/mdg-utils-laravel)

Laravel validation rules, Eloquent casts and Blade directives for Malagasy mobile phone
numbers, Ariary amounts, number words and dates. Laravel 12 and 13, PHP 8.3+.

All the parsing and formatting comes from [mdgascar/mdg-utils](https://github.com/mdgascar/mdg-utils-php),
which follows the shared [mdg-utils spec](https://github.com/mdgascar/mdg-utils-spec). This
package only wires it into Laravel. Docs and playground:
[mdgascar.github.io/mdg-utils-docs](https://mdgascar.github.io/mdg-utils-docs/).

```sh
composer require mdgascar/mdg-utils-laravel
```

Laravel discovers the service provider on its own.

## Validation

```php
use Mdgascar\MdgUtils\Laravel\Rules\AriaryAmount;
use Mdgascar\MdgUtils\Laravel\Rules\MalagasyPhone;

$request->validate([
    'phone' => ['required', 'mg_phone'],   // or new MalagasyPhone()
    'total' => ['required', 'mg_ariary'],  // or new AriaryAmount()
]);
```

| Rule | Passes for | Fails for |
| --- | --- | --- |
| `mg_phone` / `MalagasyPhone` | `034 12 345 67`, `+261 34 12 345 67`, `0341234567` | `031 12 345 67` (no operator), anything not text |
| `mg_ariary` / `AriaryAmount` | `Ar 1 500`, `1.500 Ar`, `1 500,50`, `1500` from JSON | `-1`, `1.5` (a dot is not a decimal point by default), `abc` |

Messages come in English, French and Malagasy and follow the app locale. To change them,
run `php artisan vendor:publish --tag=mdg-utils-lang` and edit
`lang/vendor/mdg-utils/{locale}/validation.php`, or use Laravel's usual custom messages.

## Eloquent casts

```php
use Mdgascar\MdgUtils\Laravel\Casts\AsAriary;
use Mdgascar\MdgUtils\Laravel\Casts\AsPhoneNumber;

final class Order extends Model
{
    protected function casts(): array
    {
        return [
            'phone' => AsPhoneNumber::class, // string column, stored as E.164: +261341234567
            'total' => AsAriary::class,      // integer or decimal column
        ];
    }
}

$order = Order::create(['phone' => '034 12 345 67', 'total' => 'Ar 1 500']);

$order->phone->operator;  // Operator::Yas
$order->phone->e164;      // '+261341234567'
$order->total->amount;    // 1500
$order->total->toWords(); // 'dimanjato sy arivo ariary'
```

Assigning parses the value, so an invalid phone number or amount throws
`MdgUtilsException` before it reaches the database. Validate input first.

## Blade

```blade
@ariary($order->total)     {{-- Ar 1 500 --}}
@mgPhone($order->phone)    {{-- 034 12 345 67 --}}
@mgWords(2026)             {{-- enina amby roapolo sy roa arivo --}}
@mgDate($order->created_at) {{-- 27 Novambra 2026 --}}

@mgPhone($order->phone, \Mdgascar\MdgUtils\Phone\PhoneNumberFormat::International)
@mgDate($order->created_at, \Mdgascar\MdgUtils\Date\DateStyle::Numeric)
```

Each directive takes the typed value (`Ariary`, `PhoneNumber`, any `DateTimeInterface`
such as Carbon) or the raw one (an int, phone text, an ISO date such as `2026-11-27`). It
prints nothing for `null`, and escapes its output.

## Configuration

Only Ariary amounts have options. Publish the config with
`php artisan vendor:publish --tag=mdg-utils`, or set the environment variables:

| Key | Env | Values | Default |
| --- | --- | --- | --- |
| `ariary.grouping` | `MDG_UTILS_ARIARY_GROUPING` | `space`, `dot`, `comma` | `space` |
| `ariary.symbol_position` | `MDG_UTILS_ARIARY_SYMBOL_POSITION` | `before`, `after` | `before` |
| `ariary.decimal` | `MDG_UTILS_ARIARY_DECIMAL` | `comma`, `dot` | `comma` |

`@ariary` writes with these options. `mg_ariary` and `AsAriary` read every Malagasy
separator (space or dot) with the configured decimal separator. With `grouping` set to
`comma` they read English-style text such as `1,500.50` instead. An unknown value, or the
same character for grouping and decimal, throws `InvalidArgumentException` naming the key.

## Versioning

This package has its own version number, following Semantic Versioning, and it says which
`mdgascar/mdg-utils` versions it works with. Changes are listed in [CHANGELOG.md](CHANGELOG.md).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) and the organisation
[contributing guide](https://github.com/mdgascar/.github/blob/main/CONTRIBUTING.md).

## License

[MIT](LICENSE)
