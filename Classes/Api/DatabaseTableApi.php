<?php

declare(strict_types=1);

namespace JambageCom\TtProducts\Api;

/***************************************************************
*  Copyright notice
*
*  (c) 2019 Franz Holzinger <franz@ttproducts.de>
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
 * functions for the basket
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 */
use JambageCom\Div2007\Utility\CompatibilityUtility;
use JambageCom\Div2007\Utility\FileAbstractionUtility;
use JambageCom\Div2007\Utility\SystemCategoryUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DatabaseTableApi
{
    /**
     * This returns the order-number (opposed to the order's uid) for display in the shop, confirmation notes and so on.
     * Basically this prefixes the .orderNumberPrefix, if any.
     */
    public static function generateOrderNo(
        $orderUid,
        $orderNumberPrefix
    ) {
        $result = '';

        if ($orderUid) {
            $orderNumberPrefix = substr($orderNumberPrefix, 0, 30);
            if (
                strlen($orderNumberPrefix) > 1 &&
                ($position = strpos($orderNumberPrefix, '%')) !== false
            ) {
                $orderDate = date(substr($orderNumberPrefix, $position + 1));
                $orderNumberPrefix = substr($orderNumberPrefix, 0, $position) . $orderDate;
            }

            $result = $orderNumberPrefix . $orderUid;
        }

        return $result;
    }
}
