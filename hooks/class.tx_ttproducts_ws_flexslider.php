<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2015 Franz Holzinger (franz@ttproducts.de)
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
 * hook functions for the extension ws_flexslider
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_ws_flexslider {

    public function getUid (
        $pObj,
        &$imageField,
        &$imageArray
    ) {
        $uid = 0;

        $params =  GeneralUtility::_GP('tt_products');
        if (isset($params) && is_array($params)) {
            $uid = $params['cat'];
        }

        if ($uid) {
            $imageField = 'sliderimage';
            $imageArray =
                $this->getImages(
                    $pObj,
                    $uid,
                    'tt_products_cat',
                    $imageField
                );
            $imageField = 'catimages';
        }

        return $uid;
    }

    protected function getImages (
        WapplerSystems\WsFlexslider\Controller\FlexsliderController $pObj,
        $uid,
        $table,
        $imageField
    ) {
        $images =
            $pObj->getImageRepository()->getImages(
                $table,
                $imageField,
                $uid
            );
        $imageElement =
            explode(
                ',',
                $images[0][$imageField]
            );
        $imageArray = [];

        foreach ($imageElement as $k => $value) {
            $imageArray[$k]['image'] = $value;
        }

        return $imageArray;
    }
}

