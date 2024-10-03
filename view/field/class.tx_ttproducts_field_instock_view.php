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
 * functions for the inStock field view
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 */
class tx_ttproducts_field_instock_view extends tx_ttproducts_field_base_view
{
    public function getRowMarkerArray(
        $funcTablename,
        $fieldname,
        $row,
        $markerKey,
        &$markerArray,
        $fieldMarkerArray,
        $tagArray,
        $theCode,
        $id,
        $basketExtra,
        $basketRecs,
        &$bSkip,
        $bHtml = true,
        $charset = '',
        $prefix = '',
        $suffix = '',
        $imageNum = 0,
        $imageRenderObj = '',
        $linkWrap = false,
        $bEnableTaxZero = false
    ) {
        if ($row[$fieldname]) {
            $markerArray['###' . $markerKey . '_INSTOCK_UNIT###'] = $this->conf['inStockPieces'];
        } else {
            $value = $this->conf['notInStockMessage'];
            $markerArray['###' . $markerKey . '_INSTOCK_UNIT###'] = '';
        }

        return $value;
    }
}
