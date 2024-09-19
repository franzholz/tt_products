<?php

declare(strict_types=1);

namespace JambageCom\TtProducts\UserFunc;

/***************************************************************
*  Copyright notice
*
*  (c) 2021 Franz Holzinger (franz@ttproducts.de)
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
 * functions for the TypoScript conditions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use JambageCom\TtProducts\Api\BasketApi;

class MatchCondition
{
    public function checkShipping(
        $params
    ) {
        $result = false;

        if (
            ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface &&
            ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend() &&
            isset($params) &&
            is_array($params)
        ) {
            \tx_ttproducts_control_basket::storeNewRecs();
            $recs = \tx_ttproducts_control_basket::getStoredRecs();
            \tx_ttproducts_control_basket::setRecs($recs);
            $infoArray = \tx_ttproducts_control_basket::getStoredInfoArray();

            \tx_ttproducts_control_basket::fixCountries($infoArray);
            $type = $params[0];
            $field = $params[1];
            $operator = '=';

            if (isset($infoArray[$type][$field])) {
                $result = $infoArray[$type][$field];
            }

            if (
                !$result &&
                (
                    !is_array($infoArray) ||
                    !is_array($infoArray[$type]) ||
                    !isset($infoArray[$type][$field])
                )
            ) {
                $context = GeneralUtility::makeInstance(Context::class);
                $isLoggedIn = $context->getPropertyFromAspect('frontend.user', 'isLoggedIn', false);

                if (
                    $isLoggedIn &&
                    $field == 'country_code'
                ) {
                    $field = 'static_info_country';
                }
                $feUserRecord = $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.user')->user;
                $result = $feUserRecord[$field];
            }
        }

        return $result;
    }

    public function hasBulkilyItem($params)
    {
        $basketApi = GeneralUtility::makeInstance(BasketApi::class);
        $typo3VersionArray =
        VersionNumberUtility::convertVersionStringToArray(VersionNumberUtility::getCurrentTypo3Version());
        $typo3VersionMain = $typo3VersionArray['version_main'];
        $conf = [];
        if ($typo3VersionMain < 12) {
            $conf = $GLOBALS['TSFE']->tmpl->setup['plugin.'][TT_PRODUCTS_EXT . '.'] ?? null;
        } else {
            $conf = $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.typoscript')->getSetupArray()['plugin.'][TT_PRODUCTS_EXT . '.'] ?? null;
        }

        $rcArray = $basketApi->getRecords();

        $bBukily = false;
        foreach ($rcArray as $uid => $row) {
            if (!empty($row['bulkily'])) {
                $bBukily = true;
                break;
            }
        }

        return $bBukily;
    }

    public function checkWeight(
        $params
    ) {
        $result = false;
        $basketApi = GeneralUtility::makeInstance(BasketApi::class);
        $rcArray = $basketApi->getRecords();
        $weight = $basketApi->getWeight($rcArray);

        if (
            $weight >= floatval($params[0]) &&
            $weight <= floatval($params[1])
        ) {
            $result = true;
        }

        return $result;
    }
}
