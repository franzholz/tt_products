<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2009 Franz Holzinger (franz@ttproducts.de)
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
 * article functions without object instance
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */




class tx_ttproducts_variant_view implements tx_ttproducts_variant_view_int, t3lib_Singleton {
	public $modelObj;
	public $cObj;
	public $langObj;


	public function init($langObj, $modelObj)	{
		$this->langObj = $langObj;
		$this->cObj = $langObj->cObj;
		$this->modelObj = $modelObj;
	}

	public function getVariantSubpartMarkerArray (&$markerArray, &$subpartArray, &$wrappedSubpartArray, &$row, &$tempContent, $bUseSelects, &$conf, $bHasAdditional, $bGiftService)  {

		if ($bUseSelects) {
			$areaArray = array();
			if (is_array($this->conf))	{
				foreach ($this->conf as $key => $field)	{
					if ($field != 'additional')	{	// no additional here
						if (trim($row[$field]) != '')  {
							$areaArray[] = 'display_variant' . $key;
						}
					}
				}
			}

			if ($bHasAdditional) {
				$areaArray[] = 'display_variant5_isSingle';
			} else {
				$areaArray[] = 'display_variant5_isNotSingle';
			}

			if ($bGiftService)	{
				$areaArray[] = 'display_variant5_giftService';
			} else {
				$areaArray[] = 'display_variant5_NoGiftService';
			}

			foreach ($areaArray as $k => $area) {
				$subpartArray['###'.$area.'###'] = $this->cObj->getSubpart($tempContent,'###'.$area.'###');
			}
		}
		$this->removeEmptyMarkerSubpartArray($markerArray, $subpartArray, $wrappedSubpartArray, $row, $conf, $bHasAdditional, $bGiftService);
	}

	public function removeEmptyMarkerSubpartArray (&$markerArray, &$subpartArray, &$wrappedSubpartArray, &$row, &$conf, $bHasAdditional, $bGiftService) {

		$areaArray = array();
		$remMarkerArray = array();
		$variantConf = &$this->modelObj->conf;

        $maxKey = 0;
		if (is_array($variantConf))	{
			foreach ($variantConf as $key => $field)	{
				if ($field != 'additional')	{	// no additional here
					if (
						!isset($row[$field]) ||
						trim($row[$field]) == '' ||
						!$conf['select' . ucfirst($field)]
					) {
						$remSubpartArray[] = 'display_variant'.$key;
					} else {
						$remMarkerArray[] = 'display_variant'.$key;
					}
				}
                if ($key > $maxKey) {
                    $maxKey = $key;
                }
			}
		}

	//	if ($this->itemTable->hasAdditional($row,'isSingle')) {
		if ($bHasAdditional)	{
			$remSubpartArray[] = 'display_variant5_isNotSingle';
			$remMarkerArray[] = 'display_variant5_isSingle';
		} else {
			$remSubpartArray[] = 'display_variant5_isSingle';
			$remMarkerArray[] = 'display_variant5_isNotSingle';
		}

		if ($bGiftService)	{
			$remSubpartArray[] = 'display_variant5_NoGiftService';
			$remMarkerArray[] = 'display_variant5_giftService';
		} else {
			$remSubpartArray[] = 'display_variant5_giftService';
			$remMarkerArray[] = 'display_variant5_NoGiftService';
		}

        for ($i = $maxKey + 1; $i <= 32; ++$i) { // remove more variants from the future
            $remSubpartArray[] = 'display_variant' . $i;
        }

        foreach ($remSubpartArray as $k => $subpart) {
			$subpartArray['###'.$subpart.'###'] = '';
		}

		foreach ($remMarkerArray as $k => $marker)	{
			$markerArray['<!-- ###'.$marker.'### -->'] = '';
			$wrappedSubpartArray['###'.$marker.'###'] = '';
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_variant_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_variant_view.php']);
}

?>