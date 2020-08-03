<?php

namespace Booni3\DynamicWms;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Booni3\DynamicWms\Skeleton\SkeletonClass
 */
class DynamicWmsFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'dynamic-wms';
    }
}
