<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Franz Holzinger (franz@ttproducts.de)
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
 * functions for the category
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

 
use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Div2007\Utility\FrontendUtility;


abstract class tx_ttproducts_category_base_view extends tx_ttproducts_table_base_view {
    public $dataArray;  // array of read in categories
    public $marker = 'CATEGORY';
    public $markerObj;
    public $mm_table = ''; // only set if a mm table is used
    public $parentField; // reference field name for parent


    public function setMarkerArrayCatTitle (
        &$markerArray,
        $catTitle,
        $prefix
    ) {
        $cObj = FrontendUtility::getContentObjectRenderer();
        $cObj->setCurrentVal($catTitle);
        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnfObj->getConf();

        $title = $cObj->cObjGetSingle($conf['categoryHeader'] ?? '', $conf['categoryHeader.'] ?? '', 'categoryHeader');
        $markerArray['###' . $prefix . $this->getMarker() . '_TITLE###'] = $title;
    }


    public function getMarkerArrayCatTitle (
        $markerArray,
        $prefix = ''
    ) {
        $markerKey = '###' . $prefix . $this->getMarker() . '_TITLE###';
        $result = $markerArray[$markerKey];
        return $result;
    }


    public function &getSubpartArrays (
        &$urlmarkerObj,
        $row,
        &$subpartArray,
        &$wrappedSubpartArray,
        &$tagArray,
        $pid,
        $linkMarker
    ) {
        $cObj = FrontendUtility::getContentObjectRenderer();
        $addQueryString = [];
        $addQueryString[$this->piVar] = $row['uid'];
        $wrappedSubpartArray['###' . $linkMarker . '###'] =
            [
                '<a href="' .
                    htmlspecialchars(
                        FrontendUtility::getTypoLink_URL(
                            $cObj,
                            $pid,
                            $urlmarkerObj->getLinkParams(
                                '',
                                $addQueryString,
                                true,
                                false,
                                0,
                                'product',
                                $this->piVar
                            ),
                            '',
                            []
                        )
                    )
            . '">',
                '</a>'
            ];
    }


    /**
     * Template marker substitution
     * Fills in the markerArray with data for a product
     *
     * @param	array		reference to an item array with all the data of the item
     * @param	integer		number of images to be shown
     * @param	object		the image cObj to be used
     * @param	array		information about the parent HTML form
     * @return	array		Returns a markerArray ready for substitution with information
     * 			 			for the tt_producst record, $row
     * @access private
     */
    abstract function getMarkerArray (
        &$markerArray,
        $markerKey,
        $category,
        $pid,
        $imageNum = 0,
        $imageRenderObj = 'image',
        &$viewCatTagArray,
        $forminfoArray = [],
        $pageAsCategory = 0,
        $theCode,
        $basketExtra,
        $basketRecs, 
        $id,
        $prefix,
        $linkWrap = ''
    );

    public function getParentMarkerArray (
        $parentArray,
        $row,
        &$markerArray,
        $category,
        $pid,
        $imageNum = 0,
        $imageRenderObj = 'image',
        &$viewCatTagArray,
        $forminfoArray = [],
        $pageAsCategory = 0,
        $code,
        $basketExtra,
        $basketRecs,
        $id,
        $prefix
    ) {
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $config = $cnf->getConfig();

        if (is_array($parentArray) && count($parentArray)) {
            $currentRow = $row;
            $count = 0;
            $currentCategory = $this->getModelObj()->getRowCategory($row);
            $parentCategory = '';

            foreach ($parentArray as $parent) {
                do {
                    $parentRow = $this->getModelObj()->getParent($currentCategory);
                    $parentCategory = $parentRow['uid'];
                    $parentPid = $this->getModelObj()->getRowPid($parentRow);
                    $count++;
                    if ($count < $parent) {
                        $currentCategory = $parentCategory;
                    }
                } while ($count < $parent && is_array($currentRow) && count($currentRow));
                $currentCategory = $parentCategory;

                if (is_array($currentRow) && count($currentRow)) {
                    $tmp = [];
                    $this->getMarkerArray(
                        $markerArray,
                        '',
                        $parentCategory,
                        $parentPid,
                        $config['limitImage'],
                        'listcatImage',
                        $viewCatTagArray,
                        $tmp,
                        $pageAsCategory,
                        'SINGLE',
                        $basketExtra,
                        $basketRecs,
                        1,
                        'PARENT' . $parent . '_',
                        $prefix
                    );
                }
            }
        }
    }


    public function addAllCatTagsMarker (&$markerArray, $tagArray, $prefix) {
        $outArray = [];

        if (isset($tagArray) && is_array($tagArray)) {
            foreach ($tagArray as $tag) {
                $outArray[] = $prefix . $tag;
            }
        }

        $markerArray['###ALLCATTAGS###'] = implode(' ', $outArray);
    }
}

