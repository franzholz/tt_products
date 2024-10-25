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
 * functions for the page
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

class tx_ttproducts_page_view extends tx_ttproducts_category_base_view
{
    public $noteArray = []; 	// array of pages with notes
    public $piVar = 'pid';
    public $pageAsCategory;		// > 0 if pages are used as categories

    /**
     * Template marker substitution
     * Fills in the markerArray with data for a product.
     *
     * @param	array		reference to an item array with all the data of the item
     * @param	int		number of images to be shown
     * @param	object		the image cObj to be used
     * @param	array		information about the parent HTML form
     *
     * @return	array		Returns a markerArray ready for substitution with information
     * 		 			for the tt_producst record, $row
     *
     * @access private
     */
    public function getMarkerArray(
        array &$markerArray,
        $markerKey,
        $category,
        $pid,
        array $viewCatTagArray,
        $theCode,
        $imageNum = 0,
        $imageRenderObj = 'image',
        $forminfoArray = [],
        $pageAsCategory = 0,
        $basketExtra = [],
        $basketRecs = [], // neu
        $id = '',
        $prefix = '',
        $linkWrap = ''
    ): void {
        $funcTablename = $this->modelObj->getFuncTablename();
        $row = $this->modelObj->get($pid);

        $pageCatTitle = htmlentities((string) $row['title'], ENT_QUOTES, 'UTF-8');
        $this->setMarkerArrayCatTitle($markerArray, $pageCatTitle, $prefix);
        $markerArray['###' . $prefix . $this->marker . '_SUBTITLE###'] =
            htmlentities(
                (string) $row['subtitle'],
                ENT_QUOTES,
                'UTF-8'
            );

        parent::getRowMarkerArray(
            $funcTablename,
            $row,
            $markerKey,
            $markerArray,
            $variantFieldArray,
            $variantMarkerArray,
            $viewCatTagArray,
            $theCode,
            $basketExtra,
            $basketRecs,
            true,
            '',
            $imageNum,
            $imageRenderObj,
            $id,
            $prefix
        );
    }
}
