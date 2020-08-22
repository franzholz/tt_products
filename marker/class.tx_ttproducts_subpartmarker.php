<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2009 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * subpart marker functions
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_subpartmarker implements \TYPO3\CMS\Core\SingletonInterface {
	public $cObj; // reference to object
	public $conf;


	/**
	 * Initialized the marker object
	 * $basket is the TYPO3 default shopping basket array from ses-data
	 *
	 * @param	string		$fieldname is the field in the table you want to create a JavaScript for
	* @param	array		array urls which should be overridden with marker key as index
	 * @return	  void
 	 */
	public function init ($cObj)	{
 		$this->cObj = $cObj;
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');

 		$this->conf = &$cnf->conf;
	}


	/**
	 * Returning template subpart marker
	 */
	public function spMarker ($subpartMarker) {
		$altSPM = '';
		if (isset($this->conf['altMainMarkers.'])) {
            $sPBody = substr($subpartMarker, 3, -3);
			$altSPM = trim($this->cObj->stdWrap($this->conf['altMainMarkers.'][$sPBody], $this->conf['altMainMarkers.'][$sPBody.'.']));
            if (
                version_compare(TYPO3_version, '8.5.0', '<')
            ) {
                $GLOBALS['TT']->setTSlogMessage('Using alternative subpart marker for "' . $subpartMarker . '": ' . $altSPM, 1);
            }
		}
		$rc = $altSPM ? $altSPM : $subpartMarker;
		return $rc;
	} // spMarker


	/**
	 * Returning template subpart array
	 */
	function getTemplateSubParts ($templateCode, $subItemMarkerArray)	{
		$rc = array();
		foreach ($subItemMarkerArray as $key => $subItemMarker)	{
			$rc[$subItemMarker] = substr($this->spMarker('###'.$subItemMarker.'_TEMPLATE###'),3,-3);
		}
		return $rc;
	} // getTemplate


	/**
	 * Returns a subpart from the input content stream.
	 * A subpart is a part of the input stream which is encapsulated in a
	 * string matching the input string, $marker. If this string is found
	 * inside of HTML comment tags the start/end points of the content block
	 * returned will be that right outside that comment block.
	 * Example: The contennt string is
	 * "Hello <!--###sub1### begin--> World. How are <!--###sub1### end--> you?"
	 * If $marker is "###sub1###" then the content returned is
	 * " World. How are ". The input content string could just as well have
	 * been "Hello ###sub1### World. How are ###sub1### you?" and the result
	 * would be the same
	 * Wrapper for t3lib_parsehtml::getSubpart which behaves identical
	 *
	 * @param	string		The content stream, typically HTML template content.
	 * @param	string		The marker string, typically on the form "###[the marker string]###"
	 * @return	string		The subpart found, if found.
	 * @see substituteSubpart(), t3lib_parsehtml::getSubpart()
	 */
	public function getSubpart ($content, $marker, &$errorCode) {
        $result = tx_div2007_core::getSubpart($content, $marker);
		if (!$result)	{
			$templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
			$errorCode[0] = 'no_subtemplate';
			$errorCode[1] = $marker;
			$errorCode[2] = $templateObj->getTemplateFile();
		}

		return $result;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/marker/class.tx_ttproducts_subpartmarker.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/marker/class.tx_ttproducts_subpartmarker.php']);
}


