<?php

declare(strict_types=1);

namespace JambageCom\TtProducts\View;

/***************************************************************
*  Copyright notice
*
*  (c) 2020 Franz Holzinger (franz@ttproducts.de)
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
 * Part of the tt_products (Shopping System) extension.
 *
 * bill generation functions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 *
 */
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Bill implements SingletonInterface
{
    /**
     * generates the bill as a PDF file.
     *
     * @param	string		reference to an item array with all the data of the item
     *
     * @return	string / boolean	returns the absolute filename of the PDF bill or false
     * 		 			for the tt_producst record, $row
     *
     * @access private
     */
    public function generate(
        $cObj,
        $basketView,
        $infoViewObj,
        $templateCode,
        $mainMarkerArray,
        $itemArray,
        $calculatedArray,
        $orderArray,
        $productRowArray,
        $basketExtra,
        $basketRecs,
        $feUserRecord, // neu FHO
        $typeCode,
        $generationConf,
        $absFileName,
        $useArticles, // neu
        $theCode //  neu
    ) {
        $result = false;

        $infoArray = $infoViewObj->getInfoArray();

        if (
            !empty($itemArray) &&
            !empty($infoArray) &&
            isset($generationConf['handleLib'])
        ) {
            switch (strtoupper($generationConf['handleLib'])) {
                case 'PHPWORD':
                    $phpWord =
                        GeneralUtility::makeInstance(
                            PhpWord::class
                        );
                    $result =
                        $phpWord->generate(
                            $basketView,
                            $infoViewObj,
                            $templateCode,
                            $mainMarkerArray,
                            $itemArray,
                            $calculatedArray,
                            $orderArray,
                            $productRowArray,
                            $basketExtra,
                            $basketRecs,
                            $feUserRecord,
                            $typeCode,
                            $generationConf,
                            $absFileName,
                            $useArticles,
                            $theCode
                        );
                    break;
                default:
                    // Hook
                    // Call all billing delivery hooks
                    if (
                        isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['bill']) &&
                        is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['bill'])
                    ) {
                        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['bill'] as $classRef) {
                            $hookObj = GeneralUtility::makeInstance($classRef);

                            if (method_exists($hookObj, 'generate')) {
                                $result = $hookObj->generate(
                                    $basketView,
                                    $infoViewObj,
                                    $templateCode,
                                    $mainMarkerArray,
                                    $itemArray,
                                    $calculatedArray,
                                    $orderArray,
                                    $productRowArray,
                                    $basketExtra,
                                    $basketRecs,
                                    $typeCode,
                                    $generationConf,
                                    $absFileName,
                                    $useArticles,
                                    $theCode
                                );
                            }
                        }
                    }
                    break;
            }
        }

        return $result;
    }
}
