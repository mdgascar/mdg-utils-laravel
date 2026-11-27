<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Laravel;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Validator;
use Mdgascar\MdgUtils\Laravel\Rules\AriaryAmount;
use Mdgascar\MdgUtils\Laravel\Rules\MalagasyPhone;

/**
 * Registered automatically by Laravel package discovery.
 */
final class MdgUtilsServiceProvider extends ServiceProvider
{
    /** Config key and `vendor:publish` tag of the config. */
    public const string NAME = 'mdg-utils';
    /** `vendor:publish` tag of the translations. */
    public const string LANG_TAG = self::NAME . '-lang';

    private const string CONFIG_FILE = __DIR__ . '/../config/mdg-utils.php';
    private const string LANG_DIR = __DIR__ . '/../lang';
    private const string ARIARY_SECTION = self::NAME . '.ariary';

    /** Blade directive name => Formatter method. */
    private const array DIRECTIVES = [
        'ariary' => 'ariary',
        'mgPhone' => 'phone',
        'mgSpell' => 'spell',
        'mgDate' => 'date',
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_FILE, self::NAME);
        $this->app->singleton(
            AriaryOptions::class,
            static function (Application $app): AriaryOptions {
                $section = $app->make(Config::class)->get(self::ARIARY_SECTION);

                return AriaryOptions::fromConfig(\is_array($section) ? $section : []);
            },
        );
        $this->app->singleton(Formatter::class);
    }

    public function boot(): void
    {
        $this->publishes([self::CONFIG_FILE => $this->app->configPath(self::NAME . '.php')], self::NAME);
        $this->publishes([self::LANG_DIR => $this->app->langPath('vendor/' . Translation::NAMESPACE)], self::LANG_TAG);
        $this->loadTranslationsFrom(self::LANG_DIR, Translation::NAMESPACE);
        $this->callAfterResolving(ValidationFactory::class, $this->registerRules(...));
        self::registerBladeDirectives();
    }

    /**
     * The string rules use the rule objects, so both forms always agree.
     */
    private function registerRules(ValidationFactory $validation): void
    {
        $rules = [
            MalagasyPhone::NAME => static fn(): MalagasyPhone => new MalagasyPhone(),
            // Built per validation, so it reads the options the app has at that moment.
            AriaryAmount::NAME => fn(): AriaryAmount => new AriaryAmount($this->app->make(AriaryOptions::class)),
        ];
        foreach ($rules as $name => $rule) {
            $validation->extend($name, static fn(string $attribute, mixed $value): bool => $rule()->passes($value));
            $validation->replacer($name, self::defaultMessage(...));
        }
    }

    private static function registerBladeDirectives(): void
    {
        foreach (self::DIRECTIVES as $directive => $method) {
            Blade::directive($directive, static fn(string $expression): string => self::echo($method, $expression));
        }
    }

    /**
     * Laravel gives a string rule's message precedence to the app (custom messages, then
     * `validation.<rule>`); when the app has none, the message is still the bare key, and
     * this swaps in the package translation instead.
     *
     * @param array<mixed> $parameters
     */
    private static function defaultMessage(string $message, string $attribute, string $rule, array $parameters, Validator $validator): string
    {
        if ($message !== 'validation.' . $rule) {
            return $message;
        }
        $translated = $validator->getTranslator()->get(Translation::validationKey($rule));

        return $validator->makeReplacements(\is_string($translated) ? $translated : $message, $attribute, $rule, $parameters);
    }

    private static function echo(string $method, string $expression): string
    {
        return \sprintf('<?php echo e(app(\\%s::class)->%s(%s)); ?>', Formatter::class, $method, $expression);
    }
}
