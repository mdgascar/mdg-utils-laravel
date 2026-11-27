# Changelog

`mdgascar/mdg-utils-laravel` follows Semantic Versioning with its own version number.
Merging `dev` into `main` tags the version in the first heading below and creates the
GitHub release; Packagist picks up the tag.

## 0.2.0

- **Breaking**: `MdgUtilsServiceProvider::validationMessageKey()` is now
  `Translation::validationKey()`, and the rules' static `passes()` is now an instance method:
  `(new MalagasyPhone())->passes($value)`.
- `AriaryAmount` and `AsAriary` take an optional `AriaryOptions` in their constructor; without
  it they use the app's config as before.

## 0.1.0

First release. Laravel 12 and 13, PHP 8.3+, `mdgascar/mdg-utils` ^0.7.

- Validation rules `mg_phone` / `MalagasyPhone` and `mg_ariary` / `AriaryAmount`, with
  messages in English and French.
- Eloquent casts `AsPhoneNumber` (stored as E.164) and `AsAriary` (stored as a number).
- Blade directives `@ariary`, `@mgPhone`, `@mgWords` and `@mgDate`.
- Config `mdg-utils.ariary`: grouping, symbol position and decimal separator.
