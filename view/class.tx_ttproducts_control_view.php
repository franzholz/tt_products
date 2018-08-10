<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2009 Franz Holzinger (franz@ttproducts.de)
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
 * functions for the control of views
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


class tx_ttproducts_control_view implements t3lib_Singleton {

	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a country
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	array		marker array
	 * @return	array
	 * @access private
	 */
	function getMarkerArray (&$markerArray, &$allMarkers, $tableConfArray)	{
		global $TSFE;

		if (isset($tableConfArray) && is_array($tableConfArray))	{
			$langObj = t3lib_div::makeInstance('tx_ttproducts_language');
			$allValueArray = array();
			$controlArray = tx_ttproducts_model_control::getControlArray();

			foreach ($tableConfArray as $functablename => $tableConf)	{

				if (isset($tableConf['view.']) && is_array($tableConf['view.']))	{
					foreach ($tableConf['view.'] as $type => $typeConf)	{

						if (is_array($typeConf))	{
							$type = substr($type,0,strpos($type,'.'));
							foreach ($typeConf as $numberx => $numberConf)	{
								$number = substr($numberx,0,strpos($numberx,'.'));
								$markerkey = strtoupper($type).$number;
								if ($allMarkers[$markerkey] != '')	{
									$allValueArray[$type.';'.$number] = $numberConf;
								}
							}
						}
					}
				}
			}

			if (isset($allValueArray) && is_array($allValueArray))	{

				foreach ($allValueArray as $key => $xValueArray)	{
					$keyArray = t3lib_div::trimExplode(';',$key);
					$type = $keyArray[0];
					$valueArray = tx_ttproducts_form_div::fetchValueArray($xValueArray['valueArray.']);
					$attributeArray = $xValueArray['attribute.'];

					if (in_array($type, array('sortSelect', 'filterSelect')))	{
						$out = tx_ttproducts_form_div::createSelect (
							$langObj,
							$valueArray,
							tx_ttproducts_model_control::getPrefixId() . '[' . tx_ttproducts_model_control::getControlVar() . '][' . $keyArray[0] . '][' . $keyArray[1] . ']',
							$controlArray[$keyArray[0]][$keyArray[1]],
							TRUE,
							TRUE,
							array(),
							'select',
							$attributeArray
						);
					} else if ($type == 'filterInput')	{
						$out = tx_ttproducts_form_div::createTag	(
							'input',
							tx_ttproducts_model_control::getPrefixId() . '['.tx_ttproducts_model_control::getControlVar().']['.$keyArray[0].']['.$keyArray[1].']',
							$controlArray[$keyArray[0]][$keyArray[1]],
							$attributeArray
						);
					}
					$markerkey = strtoupper($keyArray[0]  .$keyArray[1]);
					$markerArray['###'.$markerkey.'_LABEL###'] = $xValueArray['label'];
					$markerArray['###'.$markerkey.'###'] = $out;
				}
			}
		}
	} // function getMarkerArray
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_control_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_control_view.php']);
}


?>
