<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2014 Franz Holzinger (franz@ttproducts.de)
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
 * functions for the control of the access
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 *
 */
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\TtProducts\Api\ParameterApi;

class tx_ttproducts_control_access implements SingletonInterface
{
    public static function getVariables(
        $conf,
        &$updateCode,
        &$bIsAllowed,
        &$bValidUpdateCode,
        &$trackingCode
    ) {
        if (!$conf['update_code']) {
            throw new Exception('ERROR in tt_products: The setup "update_code" must not be empty');
        }

        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
        $updateCode = $parameterApi->getParameter('update_code') ?? '';
        $bRequireBEAdmin = (isset($conf['shopAdmin']) && $conf['shopAdmin'] == 'BE');
        $bIsAllowed = self::isAllowed($bRequireBEAdmin);

        $bValidUpdateCode =
            self::isValidUpdateCode(
                $bRequireBEAdmin,
                $conf['update_code'],
                $updateCode
            );

        if (!$bValidUpdateCode) {
            $updateCode = ''; // the update code must not be used if it is wrong
        }

        $trackingCode = $parameterApi->getParameter('tracking');
    }

    public static function isAllowed($bRequireBEAdmin)
    {
        $beUserLogin = false;
        $context = GeneralUtility::makeInstance(Context::class);
        $beUserLogin = $context->getPropertyFromAspect('backend.user', 'isLoggedIn');

        $result = (!$bRequireBEAdmin || $beUserLogin);

        return $result;
    }

    /**
     * Returns 1 if user is a shop admin.
     */
    public static function isValidUpdateCode($bRequireBEAdmin, $password, &$updateCode)
    {
        $result = false;

        if (self::isAllowed($bRequireBEAdmin)) {
            $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
            $updateCode = $parameterApi->getParameter('update_code');

            if ($updateCode == $password) {
                $result = true;	// Means that the administrator of the website is authenticated.
            }
        }

        return $result;
    }
}
