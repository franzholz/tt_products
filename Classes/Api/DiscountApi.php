<?php

declare(strict_types=1);

namespace JambageCom\TtProducts\Api;

/***************************************************************
*  Copyright notice
*
*  (c) 2018 Franz Holzinger <franz@ttproducts.de>
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
 * discount price functions
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 */
use JambageCom\Div2007\Utility\SystemUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DiscountApi
{
    protected static $feuserDiscountField = 'tt_products_discount';
    protected static $fegroupDiscountField = 'tt_products_discount';

    public static function getFeuserDiscounts($feUserRecord)
    {
        $discountArray = [];

        $feGroupRows = SystemUtility::readFeGroupsRecords();

        if (!empty($feGroupRows)) {
            foreach ($feGroupRows as $feGroupRow) {
                if (
                    floatval($feGroupRow[static::$fegroupDiscountField])
                ) {
                    $discountArray[] = floatval($feGroupRow[static::$fegroupDiscountField]);
                }
            }
        }

        if (
            isset($feUserRecord) &&
            !empty($feUserRecord['username']) &&

            floatval($feUserRecord[static::$feuserDiscountField])
        ) {
            $discountArray[] = floatval($feUserRecord[static::$feuserDiscountField]);
        }

        return $discountArray;
    }

    public static function getMaximumFeuserDiscount($feUserRecord)
    {
        $result = 0.0;
        $discountArray = static::getFeuserDiscounts($feUserRecord);
        if (!empty($discountArray)) {
            $result = max($discountArray);
        }

        return $result;
    }
}
