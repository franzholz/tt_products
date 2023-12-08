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
 * JavaScript marker functions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

 
use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_javascript_marker implements \TYPO3\CMS\Core\SingletonInterface {
    protected $marker = 'JAVASCRIPT';


    /**
     * Template marker substitution
     * Fills in the markerArray with data for a JavaScript
     *
     * @param	array		reference to an item array with all the data of the item
     * @param	string		title of the category
     * @param	integer		number of images to be shown
     * @param	object		the image cObj to be used
     * @param	array		information about the parent HTML form
     * @return	array
     * @access private
     */
    public function getMarkerArray (&$markerArray, $itemMarkerArray, $cObj) {

        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnfObj->getConf();
        $templateService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Service\MarkerBasedTemplateService::class);

        if (isset($conf['javaScript.'])) {
            $javaScriptObj = GeneralUtility::makeInstance('tx_ttproducts_javascript');

            $jsItemMarkerArray = [];
            foreach ($itemMarkerArray as $marker => $value) {
                $jsItemMarkerArray[$marker] = $javaScriptObj->jsspecialchars($value);
            }

            foreach ($conf['javaScript.'] as $key => $confJS) {
                $marker = rtrim($key, '.');
                $jsText =
                    $templateService->substituteMarkerArray($confJS['value'], $jsItemMarkerArray);
                $paramsArray = [$marker => $jsText];
                $javaScriptObj->set('direct', $paramsArray, $cObj->currentRecord);
                $marker = '###' . $this->marker . '_' . strtoupper($marker) . '###';
                $markerArray[$marker] = '';
            }
        }
    }
}


