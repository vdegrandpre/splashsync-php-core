<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2020 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace   Splash\Models\Objects;

use Splash\Models\Helpers\PricesHelper;

/**
 * @abstract    This class implements access to Prices Fields Helper.
 */
trait PricesTrait
{
    /**
     * @var PricesHelper
     */
    private static $PricesHelper;

    /**
     *      @abstract   Get a singleton Prices Helper Class
     *
     *      @return     PricesHelper
     */
    public static function prices()
    {
        // Helper Class Exists
        if (isset(self::$PricesHelper)) {
            return self::$PricesHelper;
        }
        // Initialize Class
        self::$PricesHelper = new PricesHelper();
        // Return Helper Class
        return self::$PricesHelper;
    }
}
