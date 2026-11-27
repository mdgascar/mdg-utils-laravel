<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Laravel;

/**
 * Where the package's messages live: the `mdg-utils` translation namespace (lang/ in this
 * package, or lang/vendor/mdg-utils in the app).
 */
final class Translation
{
    public const string NAMESPACE = 'mdg-utils';

    /**
     * The message of a rule with its parameters: `mg_phone:mobile` has its own,
     * `mg_phone_mobile`.
     *
     * @param array<mixed> $parameters
     */
    public static function messageName(string $rule, array $parameters): string
    {
        $parameter = $parameters[0] ?? null;

        return \is_string($parameter) && $parameter !== '' ? $rule . '_' . $parameter : $rule;
    }

    /**
     * The translation key of a validation rule's message, e.g. `mdg-utils::validation.mg_phone`.
     */
    public static function validationKey(string $rule): string
    {
        return self::NAMESPACE . '::validation.' . $rule;
    }
}
