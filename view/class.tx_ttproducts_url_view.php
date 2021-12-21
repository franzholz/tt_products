<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2011 Franz Holzinger (franz@ttproducts.de)
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
 * url marker functions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Div2007\Utility\FrontendUtility;

class tx_ttproducts_url_view implements \TYPO3\CMS\Core\SingletonInterface {
	public $pibase; // reference to object of pibase
	public $cObj;
	public $conf;
	public $urlArray;


	/**
	 * Initialized the marker object
	 * $basket is the TYPO3 default shopping basket array from ses-data
	 *
	 * @param		string		$fieldname is the field in the table you want to create a JavaScript for
	* @param		array		array urls which should be overridden with marker key as index
	 * @return	  void
 	 */
	public function init ($pibase, $conf)	{
 		$this->pibase = $pibase;
		$this->cObj = $this->pibase->cObj;
 		$this->conf = $conf;
	}


	public function getSingleExcludeList ($excludeList) {
		$excludeListArray = GeneralUtility::trimExplode(',', $excludeList);
		$singleExcludeListArray =
			array(
				'article',
				'product',
				'variants'
			);
		$singleExcludeListArray = array_merge($excludeListArray, $singleExcludeListArray);

		if (!$singleExcludeListArray[0]) {
			unset($singleExcludeListArray[0]);
		}
		$singleExcludeList = implode(',', $singleExcludeListArray);
		return $singleExcludeList;
	}


	public function setUrlArray ($urlArray)	{
		$this->urlArray = $urlArray;
	}


	/**
	 * Adds link markers to a wrapped subpart array
	 */
	public function getWrappedSubpartArray (
		&$wrappedSubpartArray,
		$addQueryString=array(),
		$css_current='',
		$bUseBackPid = true
	)	{
        $pid = $GLOBALS['TSFE']->id;
		$pidBasket = ($this->conf['PIDbasket'] ? $this->conf['PIDbasket'] : $pid);
		$pageLink = tx_div2007_alpha5::getPageLink_fh003(
			$this->cObj,
			$pidBasket,
			'',
			$this->getLinkParams(
				'',
				$addQueryString,
				true,
				$bUseBackPid
			)
		) ;
		$wrappedSubpartArray['###LINK_BASKET###'] = array('<a href="' . htmlspecialchars($pageLink) . '"' . $css_current . '>', '</a>');
	}


    /**
    * Adds URL markers to a markerArray
    */
    public function addURLMarkers (
        $pidNext,
        $markerArray,
        $addQueryString = array(),
        $excludeList = '',
        $bUseBackPid = true,
        $backPid = 0,
        $bExcludeSingleVar = true
    )	
    {
        $pid = $GLOBALS['TSFE']->id;
		$charset = 'UTF-8';
		$urlMarkerArray = array();
		$conf = array('useCacheHash' => true);
		$target = '';

		// disable caching as soon as someone enters products into the basket, enters user data etc.
		// $addQueryString['no_cache'] = 1;
			// Add's URL-markers to the $markerArray and returns it
		$pidBasket = ($this->conf['PIDbasket'] ? $this->conf['PIDbasket'] : $pid);
		$pidFormUrl = ($pidNext ? $pidNext : $pid);

		if ($pidFormUrl != $pid && $bExcludeSingleVar)	{
			$newExcludeListArray =
				array(
					'tt_products[article]',
					'tt_products[product]',
					'tt_products[variants]'
				);
			$excludeListArray = GeneralUtility::trimExplode(',', $excludeList);
			$excludeListArray = array_merge($excludeListArray, $newExcludeListArray);
			if (!$excludeListArray[0])	{
				unset($excludeListArray[0]);
			}
			$excludeList = implode(',', $excludeListArray);
		}
		$bUseBackPid = ($bUseBackPid && $pidNext && $pidNext != $pid);

		$url = FrontendUtility::getTypoLink_URL(
			$this->cObj,
			$pidFormUrl,
			$this->getLinkParams(
				$excludeList,
				$addQueryString,
				true,
				$bUseBackPid
			),
			$target,
			$conf
		);

		$urlConfig = array(
			'FORM_URL' => array(
					'pid' => $pidFormUrl,
					'excludeList' => $urlExcludeList
				),
			'FORM_URL_CURRENT' => array(
					'pid' => $pid,
					'excludeList' => $excludeList
				)
		);

		foreach ($urlConfig as $markerKey => $keyConfig) {
			$url = FrontendUtility::getTypoLink_URL(
				$this->cObj,
				$keyConfig['pid'],
				$this->getLinkParams(
					$keyConfig['excludeList'],
					$addQueryString,
					true,
					$bUseBackPid,
					$backPid
				),
				$target,
				$conf
			);
			$urlMarkerArray['###' . $markerKey . '###'] = htmlspecialchars($url, ENT_NOQUOTES, $charset);
			$urlMarkerArray['###' . $markerKey . '_VALUE###'] =
				$url;
		}

		$commandArray = array('basket', 'info', 'payment', 'finalize', 'thanks', 'search', 'memo', 'tracking', 'billing', 'delivery', 'agb', 'user1', 'user2', 'user3', 'user4', 'user5');

		foreach ($commandArray as $command) {
			$linkPid = ($this->conf['PID' . $command] ? $this->conf['PID' . $command] : $pidBasket);
			
			$url = FrontendUtility::getTypoLink_URL(
				$this->cObj,
				$linkPid,
				$this->getLinkParams(
					$excludeList,
					$addQueryString,
					true,
					$bUseBackPid
				),
				$target,
				$conf
			);

			$urlMarkerArray['###FORM_URL_' . strtoupper($command) . '###'] =
				htmlspecialchars(
					$url,
					ENT_NOQUOTES,
					$charset
				);
		}

		$urlMarkerArray['###FORM_URL_TARGET###'] = '_self';

		if (isset($this->urlArray) && is_array($this->urlArray))	{
			foreach ($this->urlArray as $k => $urlValue)	{
				$urlMarkerArray['###' . strtoupper($k) . '###'] = $urlValue;
			}
		}
			// Call all addURLMarkers hooks at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['addURLMarkers'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['addURLMarkers'] as $classRef) {
				$hookObj= GeneralUtility::makeInstance($classRef);
				if (method_exists($hookObj, 'addURLMarkers')) {
					$hookObj->addURLMarkers($this->pibase,$pidNext,$urlMarkerArray,$addQueryString,$excludeList,$bUseBackPid,$bExcludeSingleVar);
				}
			}
		}

		if (isset($markerArray) && is_array($markerArray))	{
			$markerArray = array_merge($markerArray, $urlMarkerArray);
		} else {
			$markerArray = $urlMarkerArray;
		}

		return $markerArray;
	} // addURLMarkers


	/**
	 * Returns a url for use in forms and links
	 */
	public function addQueryStringParam (&$queryString, $param, $bUsePrefix=false) {
		$parameterApi = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\ParameterApi::class);
		$piVars = $parameterApi->getPiVars();
		$prefixId = $parameterApi->getPrefixId();

		$temp = $piVars[$param];
		$temp = ($temp ? $temp : (GeneralUtility::_GP($param) && ($param!='pid') ? GeneralUtility::_GP($param) : 0));

		if ($temp)	{
			if ($bUsePrefix)	{
				$queryString[$prefixId . '[' . $param . ']'] = $temp;
			} else {
				$queryString[$param] = $temp;
			}
		}
	}


	/**
	 * Returns a url for use in forms and links
	 */
	public function getLinkParams (
		$excludeList='',
		$addQueryString=array(),
		$bUsePrefix=false,
		$bUseBackPid=true,
		$piVarSingle='product',
		$piVarCat='cat'
	) {
        $pid = $GLOBALS['TSFE']->id;
		$prefixId = $this->pibase->prefixId;
		$queryString=array();
		if ($bUseBackPid)	{
			if ($bUsePrefix && !$addQueryString[$prefixId . '[backPID]'])	{
				$queryString[$prefixId . '[backPID]'] = $pid;
			} else if (!$addQueryString['backPID'])	{
				$queryString['backPID'] = $pid;
			}
		}

		if ($excludeList != '' && $bUsePrefix) {
			$excludeArray = explode(',', $excludeList);
			foreach ($excludeArray as $k => $v) {
				$excludeArray[$k] = $prefixId . '[' . $v . ']';
			}
			$excludeList = implode(',', $excludeArray);
		}

		$this->addQueryStringParam($queryString, 'C', $bUsePrefix);
		if ($piVarSingle != '') {
			$this->addQueryStringParam($queryString, $piVarSingle, $bUsePrefix);
		}
		if ($piVarCat != '')	{
			$this->addQueryStringParam($queryString, $piVarCat, $bUsePrefix);
		}
		$listPointerParam = tx_ttproducts_model_control::getPointerPiVar('LIST');
		$this->addQueryStringParam($queryString, $listPointerParam, $bUsePrefix);
		$catlistPointerParam = tx_ttproducts_model_control::getPointerPiVar('CATLIST');

		$this->addQueryStringParam($queryString, 'mode', false);
		$this->addQueryStringParam($queryString, $listPointerParam, $bUsePrefix);
		$this->addQueryStringParam($queryString, 'newitemdays', $bUsePrefix);
		$this->addQueryStringParam($queryString, 'searchbox', $bUsePrefix);
		$this->addQueryStringParam($queryString, 'sword', $bUsePrefix);

		if ($bUsePrefix)	{
			$excludeListArray = array();
			$tmpArray = GeneralUtility::trimExplode(',',$excludeList);
			if (isset($tmpArray) && is_array($tmpArray) && $tmpArray['0'])	{
				foreach($tmpArray as $param)	{
					if (strpos($param, $prefixId) === false)	{
						$excludeListArray[] = $prefixId . '[' . $param . ']';
					}
				}
				if (count($excludeListArray))	{
					$excludeList .= ',' . implode(',', $excludeListArray);
				}
			}
		}

		if (is_array($addQueryString))	{
			foreach ($addQueryString as $param => $value){
				if ($bUsePrefix)	{
					$queryString[$prefixId . '[' . $param . ']'] = $value;
				} else {
					$queryString[$param] = $value;
				}
			}
		}

		foreach($queryString as $key => $val)	{
			if ($val=='' || (strlen($excludeList) && GeneralUtility::inList($excludeList,$key)))	{
				unset($queryString[$key]);
			}
		}

			// Call all getLinkParams hooks at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['getLinkParams'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['getLinkParams'] as $classRef) {
				$hookObj= GeneralUtility::makeInstance($classRef);
				if (method_exists($hookObj, 'getLinkParams')) {
					$hookObj->getLinkParams($this,$queryString,$excludeList,$addQueryString,$bUsePrefix,$bUseBackPid,$piVarSingle,$piVarCat);
				}
			}
		}

		return $queryString;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_url_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_url_view.php']);
}


