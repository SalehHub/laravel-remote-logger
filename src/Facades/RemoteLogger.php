<?php

namespace RemoteLogger\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void setCategory(?string $category)
 * @method static void setSubcategory(?string $subcategory)
 * @method static void setContext(?string $category, ?string $subcategory = null)
 * @method static ?string getCategory()
 * @method static ?string getSubcategory()
 * @method static void flush()
 *
 * @see \RemoteLogger\RemoteLogger
 */
class RemoteLogger extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'remote-logger-global';
    }
}
