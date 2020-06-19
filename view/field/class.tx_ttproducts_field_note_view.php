<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2008 Franz Holzinger (franz@ttproducts.de)
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
 * functions for the note field view
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
 

class tx_ttproducts_field_note_view extends tx_ttproducts_field_base_view {

	public function getRowMarkerArray ($functablename, $fieldname, $row, $markerKey, &$markerArray, $tagArray, $theCode, $id, $basketExtra, &$bSkip, $bHtml=true, $charset='', $prefix='', $suffix='', $imageRenderObj='')	{

		if (
			$bHtml
			&& ($theCode != 'EMAIL' || $this->conf['orderEmail_htmlmail'])
		)	{
            $local_cObj = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);

			$value = $this->getModelObj()->getFieldValue($basketExtra, $row, $fieldname);

				// Extension CSS styled content
			if (\JambageCom\Div2007\Utility\FrontendUtility::hasRTEparser()) {
				$value = \JambageCom\Div2007\Utility\FrontendUtility::RTEcssText($local_cObj, $value);
			} else if (is_array($this->conf['parseFunc.'])) {
				$value = $local_cObj->parseFunc($value, $this->conf['parseFunc.']);
			} else if ($this->conf['nl2brNote']) {
				$value = nl2br($value);
			}
		}

		return $value;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_note_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_note_view.php']);
}


