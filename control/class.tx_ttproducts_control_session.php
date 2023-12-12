<?php

use TYPO3\CMS\Core\Utility\ArrayUtility;

/***************************************************************
*  Copyright notice
*
*  (c) 2012-2012 Franz Holzinger <franz@ttproducts.de>
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
 * data functions for the customer.
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
class tx_ttproducts_control_session
{
    public static function filterExtensionData($session)
    {
        $result = '';
        if (is_array($session) && isset($session['tt_products'])) {
            $result = $session['tt_products'];
        }

        return $result;
    }

    public static function readSession($key)
    {
        $result = [];
        $data = $GLOBALS['TSFE']->fe_user->getKey('ses', $key);
        if (!empty($data)) {
            $result = $data;
        }

        return $result;
    }

    public static function writeSession($key, $value)
    {
        // Storing value ONLY if there is a confirmed cookie set,
        // otherwise a shellscript could easily be spamming the fe_sessions table
        // with bogus content and thus bloat the database

        if (
            !$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['checkCookies'] ||
            $GLOBALS['TSFE']->fe_user->isCookieSet()
        ) {
            $GLOBALS['TSFE']->fe_user->setKey('ses', $key, $value);
            $GLOBALS['TSFE']->fe_user->storeSessionData();  // The basket shall not get lost when coming back from external scripts
        }
    }

    // FE USER SESSION DATA HANDLING
    /**
     * Retrieves session data.
     *
     * @return	array	session data
     */
    public static function readSessionData($readAll = false)
    {
        $sessionData = [];
        $extKey = TT_PRODUCTS_EXT;
        $allSessionData = static::readSession('feuser');

        if (isset($allSessionData) && is_array($allSessionData)) {
            if ($readAll) {
                $sessionData = $allSessionData;
            } elseif (isset($allSessionData[$extKey])) {
                $sessionData = $allSessionData[$extKey];
            }
        }

        return $sessionData;
    }

    /**
     * Writes data to FE user session data.
     *
     * @return	array	session data
     */
    public static function writeSessionData(
        array $data
    ) {
        $clearSession = empty($data);
        $extKey = TT_PRODUCTS_EXT;
        // Read all session data
        $allSessionData = static::readSessionData(true);

        if (is_array($allSessionData[$extKey])) {
            $keys = array_keys($allSessionData[$extKey]);
            if ($clearSession) {
                foreach ($keys as $key) {
                    unset($allSessionData[$extKey][$key]);
                }
            }
            ArrayUtility::mergeRecursiveWithOverrule($allSessionData[$extKey], $data);
        } else {
            $allSessionData[$extKey] = $data;
        }
        static::writeSession('feuser', $allSessionData);
    }
}
