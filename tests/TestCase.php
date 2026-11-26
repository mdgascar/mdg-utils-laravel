<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Laravel\Tests;

use Mdgascar\MdgUtils\Laravel\AriaryOptions;
use Mdgascar\MdgUtils\Laravel\Formatter;
use Mdgascar\MdgUtils\Laravel\MdgUtilsServiceProvider;
use Orchestra\Testbench\TestCase as Testbench;

abstract class TestCase extends Testbench
{
    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [MdgUtilsServiceProvider::class];
    }

    /**
     * Sets one `mdg-utils.ariary` option before the package reads it.
     */
    protected function useAriaryOption(string $key, string $value): void
    {
        $this->app?->forgetInstance(AriaryOptions::class);
        $this->app?->forgetInstance(Formatter::class);
        config()->set(MdgUtilsServiceProvider::NAME . '.ariary.' . $key, $value);
    }
}
