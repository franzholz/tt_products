<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Franz Holzinger (franz@ttproducts.de)
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
 * functions for the product
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_product_view extends tx_ttproducts_article_base_view {
	public $relatedArray = array(); // array of related products
	public $marker = 'PRODUCT';
	public $type = 'product';
	public $piVar = 'product';
	public $articleArray = array();
	public $datafield;


	public function init ($modelObj)	{

		$this->variant = GeneralUtility::makeInstance('tx_ttproducts_variant_view');
		parent::init($modelObj);
	}


	public function getItemMarkerSubpartArrays (
		&$templateCode,
		$functablename,
		&$row,
		$markerArray,
		&$subpartArray,
		&$wrappedSubpartArray,
		&$tagArray,
		$theCode='',
		$basketExtra=array(),
		$iCount=''
	)	{
		parent::getItemMarkerSubpartArrays(
			$templateCode,
			$functablename,
			$row,
			$markerArray,
			$subpartArray,
			$wrappedSubpartArray,
			$tagArray,
			$theCode,
			$basketExtra,
			$iCount
		);
		$extArray = array();
		if (isset($row['ext'])) {
			$extArray = $row['ext'];
		}

		if (is_array($extArray) && is_array($extArray['tt_products']))	{
			$variant = $extArray['tt_products'][0]['vars'];
		}
		$bGiftService = true;
		if ($this->getModelObj()->hasAdditional($row,'noGiftService'))	{
			$bGiftService = false;
		}

		$this->variant->getVariantSubpartMarkerArray(
			$markerArray,
			$subpartArray,
			$wrappedSubpartArray,
			$row,
			$templateCode,
			$bSelectVariants,
			$this->conf,
			$this->getModelObj()->hasAdditional($row,'isSingle'),
			$bGiftService
		);

		$datafieldViewObj = $this->getFieldObj('datasheet');
		if (isset($datafieldViewObj) && is_object($datafieldViewObj))	{
			$datafieldViewObj->getItemSubpartArrays(
				$templateCode,
				$this->marker,
				$functablename,
				$row,
				($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['falDatasheet'] ?  'datasheet_uid' : 'datasheet'),
				$this->getModelObj()->getTableConf($theCode),
				$subpartArray,
				$wrappedSubpartArray,
				$tagArray,
				$theCode,
				$basketExtra
			);
		}
	}


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	string		title of the category
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param	array		information about the parent HTML form
	 * @return	array
	 * @access private
	 */
	public function getModelMarkerArray (
		&$row,
		$markerParam,
		&$markerArray,
		$catTitle,
		$imageNum=0,
		$imageRenderObj='image',
		$tagArray,
		$forminfoArray=array(),
		$theCode='',
		$basketExtra=array(),
		$id='',
		$prefix='',
		$suffix='',
		$linkWrap='',
		$bHtml=true,
		$charset=''
	) {
			// Returns a markerArray ready for substitution with information for the tt_producst record, $row
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$modelObj = $this->getModelObj ();
		$local_cObj = \JambageCom\Div2007\Utility\FrontendUtility::getContentObjectRenderer();
        $parser = $local_cObj;
        if (
            defined('TYPO3_version') &&
            version_compare(TYPO3_version, '7.0.0', '>=')
        ) {
            $parser = tx_div2007_core::newHtmlParser(false);
        }

		parent::getModelMarkerArray(
			$row,
			$markerParam,
			$markerArray,
			$catTitle,
			$imageNum,
			$imageRenderObj,
			$tagArray,
			$forminfoArray,
			$theCode,
			$basketExtra,
			$id,
			$prefix,
			$suffix,
			$linkWrap,
			$bHtml,
			$charset
		);
// Todo: remove datasheet part from here:
		$datafieldViewObj = $this->getFieldObj('datasheet');
		if (isset($datafieldViewObj) && is_object($datafieldViewObj))	{
			$datafieldViewObj->getRowMarkerArray(
				$modelObj->getTablename(),
				($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['falDatasheet'] ?  'datasheet_uid' : 'datasheet'),
				$row,
				$markerParam.'_DATASHEET',
				$markerArray,
				$tagArray,
				$theCode,
				$id,
				$basketExtra,
				$tmp,
				false,
				'',
				'',
				'',
				'datasheetIcon'
			);
		}

			// Subst. fields
		$markerArray['###'.$this->marker.'_UNIT###'] = $row['unit'];
		$markerArray['###'.$this->marker.'_UNIT_FACTOR###'] = $row['unit_factor'];
		$markerArray['###'.$this->marker.'_WWW###'] = $row['www'];
//		$markerArray['###CATEGORY_TITLE###'] = $catTitle;

//		$markerArray["###FIELD_NAME###"]="recs[tt_products][".$row["uid"]."]";

//		$markerArray['###FIELD_ID###'] = TT_PRODUCTS_EXT.'_'.strtolower($theCode).'_id_'.$id;
		$markerArray['###BULKILY_WARNING###'] = $row['bulkily'] ? $this->conf['bulkilyWarning'] : '';

		if ($this->conf['itemMarkerArrayFunc'])	{
			$markerArray = tx_div2007_alpha5::userProcess_fh002($this, $this->conf, 'itemMarkerArrayFunc', $markerArray);
		}

		if ($theCode == 'SINGLE')	{
			$addressUid = intval($row['address']);
			$addressRow = array();
			$addressViewObj = $tablesObj->get('address',true);

			if (($this->conf['table.']['address'] != 'tt_address' || \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded(TT_ADDRESS_EXTkey)) && $addressUid && $modelObj->fieldArray['address'])	{
				$addressObj = $addressViewObj->getModelObj();
				$addressRow = $addressObj->get($addressUid);
			}
			$adressMarkerArray = array();
			$addressViewObj->getRowMarkerArray($addressRow, '', $adressMarkerArray, $tmp='', $tmp='', $tagArray, $theCode, $bHtml, $charset, $imageNum, $imageRenderObj, $id, $prefix, $suffix,$linkWrap);
			if (is_array($adressMarkerArray))	{
				$markerArray = array_merge($markerArray, $adressMarkerArray);
			}
// 			if (is_array($tagArray))	{
// 				foreach ($tagArray as $tag)	{
//
// 				}
// 			}

			if ($row['note_uid']) {
				$pageObj = $tablesObj->get('pages');

				$notePageArray = $pageObj->getNotes ($row['uid']);
				$confObj = GeneralUtility::makeInstance('tx_ttproducts_config');
				$contentConf = $confObj->getTableConf('tt_content', $code);

				foreach($notePageArray as $k => $pid)	{
					$pageRow = $pageObj->get($pid);
					$pageMarkerKey = 'PRODUCT_NOTE_UID_'.($k + 1);
					$contentArray = $tablesObj->get('tt_content')->getFromPid($pid);
					$countArray = array();
					foreach($contentArray as $k2 => $contentEl)	{
						$cType = $contentEl['CType'];
						$countArray[$cType] = intval($countArray[$cType]) + 1;
						$markerKey = $pageMarkerKey.'_'.$countArray[$cType].'_'.strtoupper($cType);

						foreach($tagArray as $index => $v)	{
							$pageFoundPos = strpos($index, $pageMarkerKey);
							if ($pageFoundPos == 0 && $pageFoundPos !== false)	{
								$fieldName = str_replace($pageMarkerKey.'_','',$index);
								if (isset($pageRow[$fieldName]))	{
									$markerArray['###'.$index.'###'] = $pageRow[$fieldName];
								}
							}
							if (strstr($index, $markerKey) === false)	{
								continue;
							}
							$fieldPos = strrpos($index, '_');
							$fieldName = substr($index, $fieldPos+1);
							$markerArray['###'.$index.'###'] = $contentEl[$fieldName];
							if (isset ($contentConf['displayFields.']) && is_array ($contentConf['displayFields.']) && $contentConf['displayFields.'][$fieldName] == 'RTEcssText')	{
									// Extension CSS styled content
								if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('css_styled_content')) {
									$markerArray['###'.$index.'###'] =
									tx_div2007_alpha5::RTEcssText($local_cObj, $contentEl[$fieldName]);
								} else if (is_array($this->conf['parseFunc.']))	{
									$markerArray['###'.$index.'###'] =
										$local_cObj->parseFunc($contentEl[$fieldName],$this->conf['parseFunc.']);
								}
							}
						}
					}
				}
			}

			foreach ($tagArray as $key => $val)	{
				if (strstr($key,'PRODUCT_NOTE_UID') !== false)	{
					if (!isset($markerArray['###'.$key.'###']))	{
						$markerArray['###'.$key.'###'] = '';
					}
				}
			}
			$conf = $this->getConf();

				// check need for rating
			if (($tagArray['RATING'] || $tagArray['RATING_STATIC']) && isset($conf['RATING']) && isset($conf['RATING.']))	{
				$cObjectType = $conf['RATING'];
				$conf1 = $conf['RATING.'];
				$extKey = $conf['RATING.']['extkey'];
				$api = $conf['RATING.']['api'];
			} else {
				$extKey = '';
			}

			if ($extKey != '' && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extKey) && $api != '' && class_exists($api)) {
				$apiObj = GeneralUtility::makeInstance($api);
				if (method_exists($apiObj, 'getDefaultConfig'))	{
					$ratingConf = $apiObj->getDefaultConfig();
					if (isset($ratingConf) && is_array($ratingConf))	{
                        $tmpConf = $conf1;
						tx_div2007_core::mergeRecursiveWithOverrule($tmpConf, $ratingConf);
						$ratingConf = $tmpConf;
					} else {
						$ratingConf = $conf1;
					}
				} else {
					$ratingConf = $conf1;
				}
				$ratingConf['ref'] = TT_PRODUCTS_EXT . '_' . $row['uid'];

				$cObj = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
				/* @var $cObj \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer */
				$cObj->start(array());
				$markerArray['###RATING###'] = $cObj->cObjGetSingle($cObjectType, $ratingConf);
				$cObj = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
				/* @var $cObj \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer */
				$cObj->start(array());
				$ratingConf['mode'] = 'static';
				$markerArray['###RATING_STATIC###'] = $cObj->cObjGetSingle($cObjectType, $ratingConf);
			} else {
				$markerArray['###RATING###'] = '';
				$markerArray['###RATING_STATIC###'] = '';
			}

				// check need for comments
			if ($tagArray['COMMENT'] && isset($conf['COMMENT']) && isset($conf['COMMENT.']))	{
				$cObjectType = $conf['COMMENT'];
				$conf1 = $conf['COMMENT.'];
				$extKey = $conf['COMMENT.']['extkey'];
				$api = $conf['COMMENT.']['api'];
				$param = $conf['COMMENT.']['param'];
				if ($param == '')	{
					$param = 'list';
				}
			} else {
				$extKey = '';
			}

			if ($extKey != '' && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extKey) && $api != '' && class_exists($api)) {

				$apiObj = GeneralUtility::makeInstance($api);
				if (method_exists($apiObj, 'getDefaultConfig'))	{
					$commentConf = $apiObj->getDefaultConfig($param);
					if (isset($commentConf) && is_array($commentConf))	{
                        $tmpConf = $conf1;
						tx_div2007_core::mergeRecursiveWithOverrule($tmpConf, $commentConf);
						$commentConf = $tmpConf;
					} else {
						$commentConf = $conf1;
					}
				} else {
					$commentConf = $conf1;
				}
				$commentConf['ref'] = TT_PRODUCTS_EXT . '_' . $row['uid'];
				$urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view');
				$linkParams = $urlObj->getLinkParams(
					'',
					array(
						'product' => $row['uid']
					),
					true,
					false,
					''
				);
				$commentConf['linkParams'] = $linkParams;

				$cObj = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
				$cObj->start(array());
				$markerArray['###COMMENT###'] = $cObj->cObjGetSingle($cObjectType, $commentConf);
			} else {
				$markerArray['###COMMENT###'] = '';
			}
		}

		if ($row['special_preparation'])	{
			$markerArray['###' . $this->marker . '_SPECIAL_PREP###'] = $parser->substituteMarkerArray($this->conf['specialPreparation'], $markerArray);
		} else	{
			$markerArray['###' . $this->marker . '_SPECIAL_PREP###'] = '';
		}
	} // getModelMarkerArray
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_product_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_product_view.php']);
}

