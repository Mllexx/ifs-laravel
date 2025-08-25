<?php

namespace Mllexx\IFS\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Mllexx\IFS\IFS
 */
class IFS extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Mllexx\IFS\IFS::class;
    }
}
