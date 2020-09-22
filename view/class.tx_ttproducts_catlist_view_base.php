<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2019 Franz Holzinger (franz@ttproducts.de)
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
// *  A copy is found in the textfile GPL.txt and important notices to the license
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
 * category list view functions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;


use JambageCom\Div2007\Utility\FrontendUtility;


abstract class tx_ttproducts_catlist_view_base implements \TYPO3\CMS\Core\SingletonInterface {
	protected $pibase; // reference to object of pibase
	public $pibaseClass;
	public $cObj;
	public $conf;
	public $config;
	public $pidListObj; // pid where to go
	public $subpartmarkerObj; // marker functions
	public $urlObj; // url functions
	public $htmlTagMain = '';	// main HTML tag
	public $htmlTagElement = ''; // HTML tag element
	public $htmlPartsMarkers = array('###ITEM_SINGLE_PRE_HTML###', '###ITEM_SINGLE_POST_HTML###');
	public $tableConfArray = array();
	public $viewConfArray = array();
	private $tMarkers;	// all markers which are found in the template subpart for the whole view $t['listFrameWork']


	public function init (
		$pibaseClass,
		&$pid_list,
		$recursive,
		$pid
	) {
		$this->pibaseClass = $pibaseClass;
		$this->pibase = GeneralUtility::makeInstance('' . $pibaseClass);
		$this->cObj = $this->pibase->cObj;
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;
		$this->pid = $pid;

		$this->urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view');
		$this->pidListObj = GeneralUtility::makeInstance('tx_ttproducts_pid_list');
		$this->pidListObj->init($this->pibase->cObj);
		$this->pidListObj->applyRecursive($recursive, $pid_list, true);
		$this->pidListObj->setPageArray();

		$this->subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');
		$this->subpartmarkerObj->init($pibase->cObj);

		$this->htmlTagMain = ($this->htmlTagMain ? $this->htmlTagMain : $this->conf['displayCatListType']);

		if (!$this->htmlTagElement)	{
			switch ($this->htmlTagMain)	{
				case 'ul':
					$this->htmlTagElement = 'li';
					break;
				case 'div':
					$this->htmlTagElement = 'div';
					break;
				case 'tr':
					$this->htmlTagElement = 'td';
					break;
			}
		}
	}


	public function &getIsParentArray (
		$cat,
		&$categoryArray
	)	{
		$isParentArray = array();

		if ($cat)	{
			$uid = $cat;
			$isParentArray[$uid] = true;
			// get all forefathers
			while ($uid = $categoryArray[$uid]['parent_category'])	{
				$isParentArray[$uid] = true;
			}
		}
		return $isParentArray;
	}


	// sets the 'depth' field
	public function setDepths (
		&$categoryRootArray,
		&$categoryArray
	)	{
		$depth = 1;
		$endArray = array();
		foreach($categoryArray as $category => $row)	{
				// is it a leaf in a tree ?
			if (!is_array($row['child_category']))	{
				$endArray [] = (int) $category;
			}
		}
		foreach($endArray as $k => $category)	{
			$count = 0;
			$actCategory = (int) $category;
			// determine the highest parent
			while($actCategory && !$categoryArray[$actCategory]['depth'] && $count < 100) {
				$count++;
				$lastCategory = $actCategory;
				$actCategory = (int) $categoryArray[$actCategory]['parent_category'];
			}

			$depth = $count + $categoryArray[$lastCategory]['depth'];
			// now write the calculated count into the fields
			$actCategory = $category;
			while($actCategory && isset($categoryArray[$actCategory]) && !$categoryArray[$actCategory]['depth']) {
				$categoryArray[$actCategory]['depth'] = $depth--;
				$actCategory = (int) $categoryArray[$actCategory]['parent_category'];
			}
		}
	}


	public function getTableConfArray ()	{
		return $this->tableConfArray;
	}


	public function setTableConfArray ($tableConfArray)	{
		$this->tableConfArray = $tableConfArray;
	}


	public function getViewConfArray ()	{
		return $this->viewConfArray;
	}


	public function setViewConfArray ($viewConfArray)	{
		$this->viewConfArray = $viewConfArray;
	}


	public function &getTemplateMarkers (&$t)	{
		if (is_array($this->tMarkers))	{
			$rc = &$this->tMarkers;
		} else {
			$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
			$rc = &$markerObj->getAllMarkers($t['listFrameWork']);
			$this->setTemplateMarkers($rc);
		}
		return $rc;
	}


	protected function setTemplateMarkers (&$tMarkers)	{
		$this->tMarkers = &$tMarkers;
	}


	public function getFrameWork (
		&$t,
		&$templateCode,
		$area
	)	{
		$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
		$subpart = $this->subpartmarkerObj->spMarker('###'.$area.'###');
		$t['listFrameWork'] = tx_div2007_core::getSubpart($templateCode, $subpart);

				// add Global Marker Array
		$globalMarkerArray = $markerObj->getGlobalMarkerArray();
		$t['listFrameWork'] = tx_div2007_core::substituteMarkerArrayCached($t['listFrameWork'], $globalMarkerArray);

		if ($t['listFrameWork'])	{
			$t['categoryFrameWork'] = tx_div2007_core::getSubpart($t['listFrameWork'], '###CATEGORY_SINGLE###');

//		###SUBCATEGORY_A_1###
			$t['linkCategoryFrameWork'] = tx_div2007_core::getSubpart($t['categoryFrameWork'], '###LINK_CATEGORY###');
		}
	}


	public function getBrowserMarkerArray (&$markerArray, &$t, $resCount, $limit, $maxPages, $bShowFirstLast, $bAlwaysPrev, $pagefloat, $imageArray, $imageActiveArray)	{
		$subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');

		$t['browseFrameWork'] = tx_div2007_core::getSubpart($t['listFrameWork'], $subpartmarkerObj->spMarker('###LINK_BROWSE###'));
		$markerArray['###BROWSE_LINKS###']='';

		if ($t['browseFrameWork'] != '')	{
			$pibaseObj = GeneralUtility::makeInstance(''.$this->pibaseClass);
			$languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);
			$tableConfArray = $this->getTableConfArray();
			$browserConf = array();
			if (isset($tableConfArray['view.']) && $tableConfArray['view.']['browser'] == 'div2007' && isset($tableConfArray['view.']['browser.']))	{
				$browserConf = $tableConfArray['view.']['browser.'];
			}
			$bShowFirstLast = (isset($browserConf['showFirstLast']) ? $browserConf['showFirstLast'] : true);

			$piVars = tx_ttproducts_model_control::getPiVars();

			$browseObj = GeneralUtility::makeInstance(\JambageCom\Div2007\Base\BrowserBase::class);
			$browseObj->init(
				$conf,
				$piVars,
				array(),
				false,	// no autocache used yet
				tx_ttproducts_control_pibase::$pi_USER_INT_obj,
				$resCount,
				$limit,
				10000,
				$bShowFirstLast,
				false,
				$pagefloat,
				$imageArray,
				$imageActiveArray
			);
			$markerArray['###BROWSE_LINKS###'] =
				FrontendUtility::listBrowser(
					$browseObj,
					$languageObj,
					$pibaseObj->cObj,
					tx_ttproducts_model_control::getPrefixId(),
					true,
					1,
					'',
					$browserConf
				);
			$wrappedSubpartArray['###LINK_BROWSE###'] = array('', '');

		}
	}


	// returns the list view arrays
	protected function getPrintViewArrays (
		$functablename,
		&$templateCode,
		&$t,
		&$htmlParts,
		$theCode,
		&$errorCode,
		$templateArea,
		$pageAsCategory,
		$templateSuffix,
		&$currentCat,
		&$categoryArray,
		&$catArray,
		&$isParentArray,
		&$subCategoryMarkerArray,
		&$ctrlArray
	 ) {
		$pibaseObj = GeneralUtility::makeInstance('' . $this->pibaseClass);
		$rc = true;
		$mode = '';
		$this->getFrameWork($t, $templateCode, $templateArea . $templateSuffix);
		$checkExpression = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['templateCheck'];
        $parser = $pibaseObj->cObj;
        if (
            defined('TYPO3_version') &&
            version_compare(TYPO3_version, '7.0.0', '>=')
        ) {
            $parser = tx_div2007_core::newHtmlParser(false);
        }

		if (!empty($checkExpression)) {
			$wrongPounds = preg_match_all($checkExpression, $t['listFrameWork'], $matches);

			if ($wrongPounds) {
				$errorCode[0] = 'template_invalid_marker_border';
				$errorCode[1] = '###' . $templateArea . $templateSuffix . '###';
				$errorCode[2] = htmlspecialchars(implode('|', $matches['0']));

				return false;
			}
		}

		$bUseFilter = false;
		$ctrlArray['bUseBrowser'] = false;
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');

		$functableArray = array($functablename);
		$tableConfArray = array();
		$viewConfArray = array();
		$searchVars = $pibaseObj->piVars[tx_ttproducts_model_control::getSearchboxVar()];
		tx_ttproducts_model_control::getTableConfArrays(
			$pibaseObj->cObj,
			$functableArray,
			$theCode,
			$tableConfArray,
			$viewConfArray
		);
		$categoryTable = $tablesObj->get($functablename,0);
		$tablename = $categoryTable->getTablename();
		$aliasPostfix = '';
		$alias = $categoryTable->getAlias() . $aliasPostfix;
		$categoryTable->clear();
		$tableConf = $tableConfArray[$functablename];
		$categoryTable->initCodeConf($theCode,$tableConf);
		$this->setTableConfArray($tableConfArray);
		$this->setViewConfArray($viewConfArray);

		$searchboxWhere = '';
		$bUseSearchboxArray = array();
		$sqlTableArray = array();
		$sqlTableIndex = 0;
		$latest = '';
		tx_ttproducts_model_control::getSearchInfo($this->cObj,$searchVars,$functablename,$tablename,$searchboxWhere,$bUseSearchboxArray, $sqlTableArray,$sqlTableIndex,$latest);
		$orderBy = $GLOBALS['TYPO3_DB']->stripOrderBy($tableConf['orderBy']);
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');

		if (empty($errorCode) && $t['listFrameWork'] && is_object($categoryTable))	{
//		###SUBCATEGORY_A_1###
			$subCategoryMarkerArray = array();
			$catArray = array();
			$offset = 0;
			$depth = 0;
			$allMarkers = $this->getTemplateMarkers($t);

			if ($allMarkers['BROWSE_LINKS'] != '')	{
				$ctrlArray['bUseBrowser'] = true;
			}

			while(($pos = strpos($t['linkCategoryFrameWork'], '###SUBCATEGORY_', $offset)) !== false)	{
				if (($posEnd = strpos($t['linkCategoryFrameWork'], '###', $pos + 1)) !== false)	{
					$marker = substr ($t['linkCategoryFrameWork'], $pos + 3, $posEnd - $pos - 3);
					$tmpArray = explode('_', $marker);
					$count = count($tmpArray);
					if ($count)	{
						$theDepth = intval($tmpArray[$count-1]);
						if ($theDepth > $depth)	{
							$depth = $theDepth;
						}
						$subCategoryMarkerArray[$theDepth] = $marker;
					}
				}
				$offset = $pos+1;
			}
			$subpartArray = array();
			$subpartArray['###LINK_CATEGORY###'] = '###CATEGORY_TMP###';
			$tmp = tx_div2007_core::substituteMarkerArrayCached($t['categoryFrameWork'], array(), $subpartArray);
			$htmlParts = GeneralUtility::trimExplode('###CATEGORY_TMP###', $tmp);
			$rootCat = $categoryTable->getRootCat();
			$currentCat = $categoryTable->getParamDefault($theCode, $pibaseObj->piVars[tx_ttproducts_model_control::getPiVar($functablename)]);
			$startCat = $currentCat;
			if (strpos($theCode, 'SELECT') !== false) {
				$startCat = 0;
			}

			if ($pageAsCategory && $functablename == 'pages')	{
				$excludeCat = $pibaseObj->cObj->data['pages'];

				if (!$rootCat)	{
					$rootCat = $excludeCat;
				}
			} else {
				if (
					is_array($tableConf['special.']) &&
					isset($tableConf['special.']['all']) &&
					$startCat == $tableConf['special.']['all']
				)	{
					$mode = 'all';
				}

				$where_clause = '';
				if (
					is_array($tableConf['filter.']) &&
					is_array($tableConf['filter.']['where.']) &&
					isset($tableConf['filter.']['where.']['field.']) &&
					is_array($tableConf['filter.']['where.']['field.'])
				)	{
					foreach ($tableConf['filter.']['where.']['field.'] as $field => $value)	{

						if (trim($value) != '')	{
							$where_clause =
								tx_ttproducts_model_control::getWhereByFields(
									$tablename,
									$alias,
									'',
									$field,
									$value,
									$tableConf['filter.']['delimiter.']['field.'][$field]
								);
						}
					}
				}

				if ($searchboxWhere != '')	{
					if ($where_clause != '')	{
						$where_clause = '(' . $where_clause . ') AND (' . $searchboxWhere.')';
					} else {
						$where_clause = $searchboxWhere;
					}
				}

				if ($rootCat != '' && $where_clause != '')	{
					$where_clause .= ' OR ' . $alias . '.uid IN (' . $rootCat . ')';
				}

				if (
					is_array($tableConf['filter.']) &&
					is_array($tableConf['filter.']['param.']) &&
					$tableConf['filter.']['param.']['cat'] == 'gp'
				)	{
					$bUseFilter = true;
					if ($mode == 'all')	{
						$tmpRowArray = $categoryTable->get('0');
						unset($tmpRowArray[$startCat]);
						$childArray = array_keys($tmpRowArray);
					} else {
						$childArray = $categoryTable->getChildCategoryArray($startCat);
					}
					$allowedCatArray = array();

					foreach ($childArray as $k => $cat)	{
						$bIsSpecial = $categoryTable->hasSpecialConf($cat, $theCode, 'no');

						if (!$bIsSpecial)	{
							$categoryTable->get($cat, $this->pidListObj->getPidlist(), true,'','',$orderBy);	// read all categories

							if ($depth && !$tableConf['onlyChildsOfCurrent'])	{
								$subChildArray = $categoryTable->getChildCategoryArray($cat);
								foreach ($subChildArray as $k2 => $subCat)	{
									$categoryTable->get($subCat, $this->pidListObj->getPidlist());	// read the sub categories
								}
							}
							$allowedCatArray[] = $cat;
						}
					}
					$rootCat = $startCat;
					if (
						tx_div2007_core::testInt($rootCat)
					) {
						$allowedCatArray[] = $rootCat;
					}
					$allowedCats = implode(',', $allowedCatArray);
					$excludeCat = $rootCat;
				} else if ($tableConf['onlyChildsOfCurrent'])	{
					$pids = $this->pidListObj->getPidlist();
					if (!$rootCat) {
						$rootCat = $categoryTable->getAllChildCats($pids, $orderBy, '');// +++ neu
						if ($rootCat == '') {
							$rootCat = 0;
						}
					}
					$relatedArray = $categoryTable->getRelated($rootCat, $startCat, $pids, $orderBy);	// read only related categories
				} else if ($tableConf['rootChildsOfCurrent']) {
					$pids = $this->pidListObj->getPidlist();
					$rootCat = $categoryTable->getAllChildCats($pids, $orderBy, $startCat);
					if ($rootCat == '') {
						$rootCat = 0;
					}

					$relatedArray = $categoryTable->getRelated($rootCat, 0, $pids, $orderBy);	// read only related categories
				} else {
					// read in all categories
					$latest = ($latest ? $latest : '');
					$relatedArray = $categoryTable->get('', $this->pidListObj->getPidlist(),true,$where_clause,'',$orderBy,$latest,'',false,$aliasPostfix);	// read all categories
					$excludeCat = 0;
				}

				if (is_array($relatedArray)) {
 					$excludeCat = 0;
					$categoryTable->translateByFields($relatedArray, $theCode);
 				}

				if (is_array($tableConf['special.']) && strlen($tableConf['special.']['no']))	{
					$excludeCat = $tableConf['special.']['no'];
				}
			} // if ($pageAsCategory && $functablename == 'pages')
			if ($functablename == 'pages')	{
				$allowedCats = $this->pidListObj->getPidlist($rootCat);
			}
			$categoryArray = $categoryTable->getRelationArray($relatedArray, $excludeCat, $rootCat, $allowedCats);
			$rootpathArray = $categoryTable->getRootpathArray($categoryArray, $rootCat, $startCat);
			$rootArray = $categoryTable->getRootArray($rootCat, $categoryArray);
			$isParentArray = $this->getIsParentArray($startCat, $categoryArray);
			$this->setDepths($rootArray, $categoryArray);
			$depth = 1;

			if ($bUseFilter)	{
				$catArray [(int) $depth] = &$allowedCatArray;
			} else {
				$catArray [(int) $depth] = &$rootArray;
			}

			if ($ctrlArray['bUseBrowser'])	{
				$ctrlArray['limit'] = $this->config['limit'];
				$this->getBrowserMarkerArray(
					$browseMarkerArray,
					$t,
					count($categoryArray) - count($rootArray),
					$ctrlArray['limit'],
					$maxPages,
					$bShowFirstLast,
					$bAlwaysPrev,
					$pagefloat,
					array(),
					array()
				);
				$t['listFrameWork'] = $parser->substituteMarkerArray($t['listFrameWork'],  $browseMarkerArray);
			}
		} else if (!$t['listFrameWork']) {
			$templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
			$errorCode[0] = 'no_subtemplate';
			$errorCode[1] = '###'.$templateArea.$templateSuffix.'###';
			$errorCode[2] = $templateObj->getTemplateFile();
			$rc = false;
		} else if (!is_object($categoryTable))	{
			$errorCode[0] = 'internal_error';
			$errorCode[1] = 'TTP_1';
			$errorCode[2] = $functablename;
			$rc = false;
		}
		return $rc;
	} // printView
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_catlist_view_base.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_catlist_view_base.php']);
}


