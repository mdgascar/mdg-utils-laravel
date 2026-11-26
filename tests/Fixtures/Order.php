<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtils\Laravel\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Mdgascar\MdgUtils\Laravel\Casts\AsAriary;
use Mdgascar\MdgUtils\Laravel\Casts\AsPhoneNumber;

/**
 * @property \Mdgascar\MdgUtils\Ariary\Ariary|null $total
 * @property \Mdgascar\MdgUtils\Phone\PhoneNumber|null $phone
 */
final class Order extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'total' => AsAriary::class,
            'phone' => AsPhoneNumber::class,
        ];
    }
}
