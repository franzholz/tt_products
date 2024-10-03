<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2017 Franz Holzinger (franz@ttproducts.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
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
 * control functions for an address item object
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 *
 */
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class tx_ttproducts_control_address
{
    protected static $addressExtKeyTable = [
        'tt_address' => 'tt_address',
        'tx_partner_main' => 'partner',
        'tx_party_addresses' => 'party',
        'tx_party_parties' => 'party',
        'fe_users' => '0',
    ];

    public static function getAddressExtKeyTable()
    {
        return self::$addressExtKeyTable;
    }

    public static function getAddressTablename(&$extKey)
    {
        $emClass = ExtensionManagementUtility::class;
        $extKey = '';
        $addressTable = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['addressTable'];

        if (!$addressTable) {
            $addressExtKeyTable = self::getAddressExtKeyTable();

            foreach ($addressExtKeyTable as $addressTable => $extKey) {
                $testIntResult = MathUtility::canBeInterpretedAsInteger($extKey);
                if (
                    $testIntResult
                ) {
                    $extKey = '';
                }

                if (
                    $extKey == '' ||
                    call_user_func($emClass . '::isLoaded', $extKey)
                ) {
                    break;
                }
            }
        }

        return $addressTable;
    }
}
