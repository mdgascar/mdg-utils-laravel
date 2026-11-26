<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Laravel\Tests;

use Illuminate\Support\ServiceProvider;
use Mdgascar\MdgUtils\Laravel\MdgUtilsServiceProvider;

final class PublishTest extends TestCase
{
    public function testTheConfigAndTheTranslationsCanBePublished(): void
    {
        $config = ServiceProvider::pathsToPublish(MdgUtilsServiceProvider::class, MdgUtilsServiceProvider::NAME);
        $lang = ServiceProvider::pathsToPublish(MdgUtilsServiceProvider::class, MdgUtilsServiceProvider::LANG_TAG);

        self::assertSame([config_path('mdg-utils.php')], array_values($config));
        self::assertSame([lang_path('vendor/mdg-utils')], array_values($lang));
    }
}
