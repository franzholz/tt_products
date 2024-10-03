<?php

namespace JambageCom\TtProducts\Hooks;

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/***************************************************************
*  Copyright notice
*
*  (c) 2017 Franz Holzinger <franz@ttproducts.de>
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
*  but WITHOUT ANY WARRANTY; w+ithout even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Part of the tt_products (Shop System) extension.
 *
 * hook for the page title in the product single view
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */

use TYPO3\CMS\Core\Utility\VersionNumberUtility;

use JambageCom\TtProducts\Api\ParameterApi;

class ContentPostProcessor
{
    public function setPageTitle(&$params, TypoScriptFrontendController &$pObj): void
    {
        $typo3VersionArray =
        VersionNumberUtility::convertVersionStringToArray(VersionNumberUtility::getCurrentTypo3Version());
        $typo3VersionMain = $typo3VersionArray['version_main'];
        $conf = [];
        if ($typo3VersionMain < 12) {
            $conf = $GLOBALS['TSFE']->tmpl->setup['plugin.'][TT_PRODUCTS_EXT . '.'] ?? null;
        } else {
            $conf = $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.typoscript')->getSetupArray()['plugin.'][TT_PRODUCTS_EXT . '.'] ?? null;
        }
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
        $piVars = $parameterApi->getPiVars();
        $funcTablename = 'tt_products';
        $piVar = $parameterApi->getPiVar($funcTablename);

        $productUid = 0;
        $row = [];

        if (
            isset($piVars) &&
            is_array($piVars) &&
            isset($piVars[$piVar])
        ) {
            $productUid = $piVars[$piVar];
        }

        if ($productUid) {
            $row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
                'title,subtitle',
                $funcTablename,
                'uid=' . intval($productUid)
            );
        }

        if (!empty($row)) {
            $pageTitle = '';
            // set the page title of the single view
            switch ($conf['substitutePagetitle']) {
                case 1:
                    $pageTitle = $row['title'];
                    break;
                case 2:
                    $pageTitle = $row['subtitle'] ?: $row['title'];
                    break;
                case 12:
                    $pageTitle = $row['title'] . ' / ' . $row['subtitle'];
                    break;
                case 21:
                    $pageTitle = $row['subtitle'] . ' / ' . $row['title'];
                    break;
                case 3:
                    $pageTitle = implode(' : ', $rootlineArray);
                    break;
            }

            if (isset($pageTitle)) {
                $pObj->content = preg_replace(
                    '#<title>.*<\/title>#',
                    '<title>' . htmlspecialchars($pageTitle) . '</title>',
                    $pObj->content
                );
            }
        }
    }
}
