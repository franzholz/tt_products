<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2016 Franz Holzinger (franz@ttproducts.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License or
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
 * functions for the FAL File Abstraction Layer
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */

use TYPO3\CMS\Core\Resource\ResourceFactory;

use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Div2007\Utility\FrontendUtility;


class tx_ttproducts_fal_view extends tx_ttproducts_article_base_view {
	public $marker = 'FAL';
	public $piVar = 'fal';


    public function getItemSubpartArrays (
        &$templateCode,
        $functablename,
        $row,
        &$subpartArray,
        &$wrappedSubpartArray,
        $tagArray,
        $theCode = '',
        $basketExtra = array(),
        $basketRecs = array(),
        $id = ''
    ) {
        parent::getItemSubpartArrays(
            $templateCode,
            $functablename,
            $row,
            $subpartArray,
            $wrappedSubpartArray,
            $tagArray,
            $theCode,
            $basketExtra,
            $basketRecs,
            $id
        );
    }


    /**
     * Template marker substitution
     * Fills in the markerArray with data for a product
     *
     * @param   array       reference to an item array with all the data of the item
     * @param   string      title of the category
     * @param   integer     number of images to be shown
     * @param   object      the image cObj to be used
     * @param   array       information about the parent HTML form
     * @return  array
     * @access private
     */
    public function getModelMarkerArray (
        $row,
        $markerParam,
        &$markerArray,
        $catTitle,
        $imageNum = 0,
        $imageRenderObj = 'image',
        $tagArray,
        $forminfoArray = array(),
        $theCode = '',
        $basketExtra = array(),
        $basketRecs = array(),
        $id = '',
        $prefix = '',
        $suffix = '',
        $linkWrap = '',
        $bHtml = true,
        $charset = '',
        $hiddenFields = '',
        $multiOrderArray = array(),
        $productRowArray = array(),
        $bEnableTaxZero = false
    ) {
        parent::getModelMarkerArray(
            $row,
            $markerParam,
            $markerArray,
            $catTitle,
            $imageNum,
            $imageRenderObj,
            $tagArray,
            $forminfoArray,
            $theCode,
            $basketExtra,
            $basketRecs,
            $id,
            $prefix,
            $suffix,
            $linkWrap,
            $bHtml,
            $charset,
            $hiddenFields,
            $multiOrderArray,
            $productRowArray,
            $bEnableTaxZero
        );

        $downloadMarker = $this->getMarker();
        $markerLink = $downloadMarker . '_' . strtoupper('download_link');
        if (isset($tagArray[$markerLink])) {
            $cObj = GeneralUtility::makeInstance('TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');
            $cObj->start(array());
            $paramArray = array();
            $postVar = tx_ttproducts_control_command::getCommandVar();
            $orderPivar = tx_ttproducts_model_control::getPiVar('sys_products_orders');
            $prefixId = tx_ttproducts_model_control::getPrefixId();
            $downloadVar = tx_ttproducts_model_control::getPiVar('tt_products_downloads');

            $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
            $orderObj = $tablesObj->get('sys_products_orders');
            $downloadUid = 0;

            $orderUid =
                $orderObj->getUidFromMultiOrderArray(
                    $downloadUid,
                    $row,
                    $multiOrderArray
                );
            if ($downloadUid) {
                $paramArray[$prefixId . '[' . $downloadVar . ']'] = $downloadUid;
            }

            if ($orderUid) {
                $paramArray[$prefixId . '[' . $orderPivar . ']'] = $orderUid;
            }

            $paramArray[$postVar . '[fal]'] = intval($row['uid']);
            $url = FrontendUtility::getTypoLink_URL(
                $cObj,
                $GLOBALS['TSFE']->id,
                $paramArray
            );

            $storageRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
            $storage = $storageRepository->findByUid(1);
            if (
                version_compare(TYPO3_version, '10.4.0', '<')
            ) {
                $resourceFactory = ResourceFactory::getInstance();
            } else {
                $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
            }

            $fileObj = $resourceFactory->getFileReferenceObject($row['uid']);
            $fileInfo = $storage->getFileInfo($fileObj);

            $path = \TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/';

            $file = $path . 'fileadmin' . $fileInfo['identifier'];
            $filename = basename($file);
            $downloadImageFile = \TYPO3\CMS\Core\Utility\PathUtility::getAbsoluteWebPath(PATH_BE_TTPRODUCTS . 'Resources/Public/Icons/system-extension-download.png');

            $content .= '<a href="' . htmlspecialchars($url) . '" title="' .
                $GLOBALS['TSFE']->sL(DIV2007_LANGUAGE_PATH . 'locallang_common.xml:download') . ' ' . $filename . '">' . $filename . '<img src="' . $downloadImageFile . '">' . '</a>';

            $markerArray['###' . $markerLink . '###'] = $content;
        }
    }
}

