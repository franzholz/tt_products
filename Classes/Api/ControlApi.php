<?php

namespace JambageCom\TtProducts\Api;

/***************************************************************
*  Copyright notice
*
*  (c) 2016 Franz Holzinger <franz@ttproducts.de>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Part of the tt_products (Shop System) extension.
 *
 * control functions
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 */
class ControlApi
{
    protected static $conf = [];
    protected static $cObj;

    public static function init($conf, $cObj): void
    {
        static::$conf = $conf;
        static::$cObj = $cObj;
    }

    public static function getConf()
    {
        return static::$conf;
    }

    public static function getCObj()
    {
        return static::$cObj;
    }

    public static function isOverwriteMode($infoArray)
    {
        $overwriteMode = false;
        $conf = self::getConf();

        $checkField = CustomerApi::getPossibleCheckField();

        if (
            (
                empty($infoArray['billing']) ||
                !empty($checkField) && empty($infoArray['billing'][$checkField]) ||
                !empty($infoArray['billing']['error']) ||
                !empty($conf['editLockedLoginInfo'])
            ) &&
            !empty($conf['lockLoginUserInfo'])
        ) {
            $overwriteMode = true;
        }

        return $overwriteMode;
    }

    public static function getTagId(
        $jsTableNamesId,
        $theCode,
        $uid,
        $field
    ) {
        $result = $jsTableNamesId . '-' . strtolower($theCode) . '-' . $uid . '-' . $field;

        return $result;
    }
}
