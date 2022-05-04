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
	public $pibaseClass;
	public $cObj;
	public $conf;
	public $config;
	public $pidListObj; // pid where to go
	public $urlObj; // url functions
	protected $htmlTagMain = '';	// main HTML tag
	protected $htmlTagElement = ''; // HTML tag element
	public $htmlPartsMarkers = array('###ITEM_SINGLE_PRE_HTML###', '###ITEM_SINGLE_POST_HTML###');
	public $tableConfArray = [];
	public $viewConfArray = [];
	private $tMarkers;	// all markers which are found in the template subpart for the whole view $t['listFrameWork']


	public function init (
        $cObj,
		$pibaseClass,
		$pid_list,
		$recursive,
		$pid
	) {
		$this->pibaseClass = $pibaseClass;
		$this->pibase = GeneralUtility::makeInstance('' . $pibaseClass);
        $this->cObj = $cObj;
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$this->conf = $cnf->conf;
		$this->config = $cnf->config;
		$this->pid = $pid;

		$this->urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view');
		$this->pidListObj = GeneralUtility::makeInstance('tx_ttproducts_pid_list');
		$this->pidListObj->applyRecursive($recursive, $pid_list, true);
		$this->pidListObj->setPageArray();

		$this->htmlTagMain = ($this->htmlTagMain ? $this->htmlTagMain : $this->conf['displayCatListType']);

		if (!$this->htmlTagElement) {
			switch ($this->htmlTagMain) {
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


	public function getTabs ($depth) {
		$result = '';
		for ($i = 0; $i < $depth; $i++) {
			$result .= chr(9);
		}
		return $result;
	}


	public function getActiveRootline (
		$cat,
		$categoryArray
	) {
		$result = [];

		if ($cat) {
			$uid = $cat;
			$result[$uid] = true;
			// get all forefathers
			while ($uid = $categoryArray[$uid]['parent_category']) {
				$result[$uid] = true;
			}
		}
		return $result;
	}


	// sets the 'depth' field
	public function setDepths (
		&$categoryArray,
		&$catArray,
		$categoryRootArray
	) {
		$depth = 1;
		$childlessArray = [];
		foreach($categoryArray as $category => $row) {
				// is it a leaf in a tree ?
			if (
                empty($row['child_category'])
            ) {
				$childlessArray[] = (int) $category;
			}
		}

		foreach($childlessArray as $k => $category) {
			$count = 0;
			$lastCategory = $actCategory = (int) $category;
            $lastDepth = 0;

			// determine the highest parent
			while(
				$lastCategory &&
				empty($categoryArray[$lastCategory]['depth']) &&
				$count < 20
			) {
				$count++;
				$lastCategory = (int) $categoryArray[$lastCategory]['parent_category'];
			}

            if ($lastCategory > 0) {
                if (!isset($categoryArray[$lastCategory]['depth'])) {
                    $categoryArray[$lastCategory]['depth'] = 0;
                }
                $lastDepth = $categoryArray[$lastCategory]['depth'];
            }
			$depth = $lastDepth + $count;
			// now write the calculated count into the fields
			$lastCategory = $actCategory;

			while(
				$lastCategory &&
				isset($categoryArray[$lastCategory]) &&
				empty($categoryArray[$lastCategory]['depth'])
			) {
				$categoryArray[$lastCategory]['depth'] = $depth--;
				$lastCategory = (int) $categoryArray[$lastCategory]['parent_category'];
			}
		}

		foreach ($categoryArray as $uid => $row) {
            $depth = intval($row['depth']);
            if (!isset($catArray[$depth])) {
                $catArray[$depth] = [];
            }
            $catArray[$depth][] = $uid;
		}
		ksort($catArray);
	}

// if (is_array($rootArray) && count($rootArray)) {
// 		$catArray[(int) $depth] = $rootArray;
// 	}

	public function getTableConfArray () {
		return $this->tableConfArray;
	}


	public function setTableConfArray ($tableConfArray) {
		$this->tableConfArray = $tableConfArray;
	}


	public function getViewConfArray () {
		return $this->viewConfArray;
	}


	public function setViewConfArray ($viewConfArray) {
		$this->viewConfArray = $viewConfArray;
	}


	public function getTemplateMarkers (&$t) {
		if (is_array($this->tMarkers)) {
			$rc = &$this->tMarkers;
		} else {
			$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
			$rc = $markerObj->getAllMarkers($t['listFrameWork']);
			$this->setTemplateMarkers($rc);
		}
		return $rc;
	}


	protected function setTemplateMarkers (&$tMarkers) {
		$this->tMarkers = &$tMarkers;
	}


	public function getFrameWork (
		&$t,
		&$templateCode,
		$area
	) {
        $templateService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Service\MarkerBasedTemplateService::class);
		$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
		$subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');
		$subpart = $subpartmarkerObj->spMarker('###' . $area . '###');
		$t['listFrameWork'] = $templateService->getSubpart($templateCode,$subpart);

				// add Global Marker Array
		$globalMarkerArray = $markerObj->getGlobalMarkerArray();
		$t['listFrameWork'] = $templateService->substituteMarkerArrayCached($t['listFrameWork'], $globalMarkerArray);

		if ($t['listFrameWork']) {
			$t['categoryFrameWork'] = $templateService->getSubpart($t['listFrameWork'], '###CATEGORY_SINGLE###');

//		###SUBCATEGORY_A_1###
			$t['linkCategoryFrameWork'] = $templateService->getSubpart($t['categoryFrameWork'], '###LINK_CATEGORY###');
		}
	}


	public function getBrowserMarkerArray (
		&$markerArray,
		&$t,
		$resCount,
		$limit,
		$maxPages,
		$imageArray,
		$imageActiveArray
	) {
        $templateService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Service\MarkerBasedTemplateService::class); 
		$subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');
		$cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
		$conf = $cnfObj->getConf();
        $parameterApi = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\ParameterApi::class);

		$t['browseFrameWork'] = $templateService->getSubpart($t['listFrameWork'], $subpartmarkerObj->spMarker('###LINK_BROWSE###'));
		$markerArray['###BROWSE_LINKS###']='';

		if ($t['browseFrameWork'] != '') {
			$pibaseObj = GeneralUtility::makeInstance('' . $this->pibaseClass);
			$languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);
			$tableConfArray = $this->getTableConfArray();
			$piVars = tx_ttproducts_model_control::getPiVars();
			$browserConf = [];
			if (
				isset($tableConfArray['view.']) && $tableConfArray['view.']['browser'] == 'div2007' && isset($tableConfArray['view.']['browser.'])
			) {
				$browserConf = $tableConfArray['view.']['browser.'];
			}
			
            if (
                isset($browserConf) &&
                is_array($browserConf)
            ) {
                if (isset($browserConf['showFirstLast'])) {
                    $bShowFirstLast = $browserConf['showFirstLast'];
                }
                if (isset($browserConf['dontLinkActivePage'])) {
                    $dontLinkActivePage = $browserConf['dontLinkActivePage'];
                }
            }

			$pagefloat = 0;
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
				$imageActiveArray,
				$dontLinkActivePage
			);
			$markerArray['###BROWSE_LINKS###'] =
				BrowserUtility::render(
					$browseObj,
					$languageObj,
					$pibaseObj->cObj,
					$parameterApi->getPrefixId(),
					true,
					1,
					'',
					$browserConf
				);
			$wrappedSubpartArray['###LINK_BROWSE###'] = ['', ''];
		}
	}


	// returns the category view arrays
	protected function getPrintViewArrays (
		$functablename,
		&$templateCode,
		&$t,
		&$htmlParts,
		$theCode,
		&$error_code,
		$templateArea,
		$pageAsCategory,
		$templateSuffix,
		$basketExtra,
		$basketRecs,
		&$currentCat,
		&$categoryArray,
		&$catArray,
		&$activeRootline,
		&$rootpathArray,
		&$subCategoryMarkerArray,
		&$ctrlArray
	 ) {
		$pibaseObj = GeneralUtility::makeInstance('' . $this->pibaseClass);
		$templateService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Service\MarkerBasedTemplateService::class);
		$rc = true;
		$mode = '';
		$allowedCats = '';

		$this->getFrameWork($t, $templateCode, $templateArea . $templateSuffix);
		$checkExpression = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['templateCheck'];
		$piVars = tx_ttproducts_model_control::getPiVars();

		if (!empty($checkExpression)) {
			$wrongPounds = preg_match_all($checkExpression, $t['listFrameWork'], $matches);

			if ($wrongPounds) {
				$error_code[0] = 'template_invalid_marker_border';
				$error_code[1] = '###' . $templateArea . $templateSuffix . '###';
				$error_code[2] = htmlspecialchars(implode('|', $matches['0']));

				return false;
			}
		}

		$bUseFilter = false;
		$ctrlArray['bUseBrowser'] = false;
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');

		$functableArray = array($functablename);
		$tableConfArray = [];
		$viewConfArray = [];
		$searchVars = $piVars[tx_ttproducts_model_control::getSearchboxVar()];
		tx_ttproducts_model_control::getTableConfArrays(
			$pibaseObj->cObj,
			$functableArray,
			$theCode,
			$tableConfArray,
			$viewConfArray
		);

		$categoryTable = $tablesObj->get($functablename, 0);
		$tablename = $categoryTable->getTablename();
		$aliasPostfix = '';
		$alias = $categoryTable->getAlias() . $aliasPostfix;
		$categoryTable->clear();
		$tableConf = $tableConfArray[$functablename];
		$categoryTable->initCodeConf($theCode, $tableConf);
		$this->setTableConfArray($tableConfArray);
		$this->setViewConfArray($viewConfArray);

		$searchboxWhere = '';
		$bUseSearchboxArray = [];
		$sqlTableArray = [];
		$sqlTableIndex = 0;
		$latest = '';
		tx_ttproducts_model_control::getSearchInfo(
			$this->cObj,
			$searchVars,
			$functablename,
			$tablename,
			$searchboxWhere,
			$bUseSearchboxArray,
			$sqlTableArray,
			$sqlTableIndex,
			$latest
		);
		$orderBy = $GLOBALS['TYPO3_DB']->stripOrderBy($tableConf['orderBy']);
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');

		if (empty($error_code) && $t['listFrameWork'] && is_object($categoryTable)) {
//		###SUBCATEGORY_A_1###
			$subCategoryMarkerArray = [];
			$catArray = [];
			$dataArray = '';
			$offset = 0;
			$depth = 0;
			$allMarkers = $this->getTemplateMarkers($t);

			if ($allMarkers['BROWSE_LINKS'] != '') {
				$ctrlArray['bUseBrowser'] = true;
			}

			while(($pos = strpos($t['linkCategoryFrameWork'], '###SUBCATEGORY_', $offset)) !== false) {
				if (($posEnd = strpos($t['linkCategoryFrameWork'], '###', $pos + 1)) !== false) {
					$marker = substr($t['linkCategoryFrameWork'], $pos + 3, $posEnd - $pos - 3);
					$tmpArray = explode('_', $marker);
					$count = count($tmpArray);
					if ($count) {
						$theDepth = intval($tmpArray[$count-1]);
						if ($theDepth > $depth) {
							$depth = $theDepth;
						}
						$subCategoryMarkerArray[$theDepth] = $marker;
					}
				}
				$offset = $pos+1;
			}
			$subpartArray = [];
			$subpartArray['###LINK_CATEGORY###'] = '###CATEGORY_TMP###';
			$tmp = $templateService->substituteMarkerArrayCached($t['categoryFrameWork'], [],$subpartArray);
			$htmlParts = GeneralUtility::trimExplode('###CATEGORY_TMP###', $tmp);
			$rootCat = $categoryTable->getRootCat() ?? '';
			$currentCat = $categoryTable->getParamDefault($theCode, $piVars[tx_ttproducts_model_control::getPiVar($functablename)] ?? '');

			$startCat = $currentCat;
			if (strpos($theCode, 'SELECT') !== false) {
				$startCat = 0;
			}

			if ($pageAsCategory && $functablename == 'pages') {
				$excludeCat = $pibaseObj->cObj->data['pages'];

				if (!$rootCat) {
					$rootCat = $excludeCat;
				}
			} else {
				if (
					is_array($tableConf['special.']) &&
					isset($tableConf['special.']['all']) &&
					$startCat == $tableConf['special.']['all']
				) {
					$mode = 'all';
				}

				$where_clause = '';
				if (
					is_array($tableConf['filter.']) &&
					is_array($tableConf['filter.']['where.']) &&
					isset($tableConf['filter.']['where.']['field.']) &&
					is_array($tableConf['filter.']['where.']['field.'])
				) {
					foreach ($tableConf['filter.']['where.']['field.'] as $field => $value) {

						if (trim($value) != '') {
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

				if ($searchboxWhere != '') {
					if ($where_clause != '') {
						$where_clause = '(' . $where_clause . ') AND (' . $searchboxWhere.')';
					} else {
						$where_clause = $searchboxWhere;
					}
				}

				if ($rootCat != '' && $where_clause != '') {
					$where_clause .= ' OR ' . $alias . '.uid IN (' . $rootCat . ')';
				}

				if (
					is_array($tableConf['filter.']) &&
					is_array($tableConf['filter.']['param.']) &&
					$tableConf['filter.']['param.']['cat'] == 'gp'
				) {
					$bUseFilter = true;
					if ($mode == 'all') {
						$tmpRowArray = $dataArray = $categoryTable->get('0');
						unset($tmpRowArray[$startCat]);
						$childArray = array_keys($tmpRowArray);
					} else {
						$childArray = $categoryTable->getChildCategoryArray($startCat);
					}
					$allowedCatArray = [];

					foreach ($childArray as $k => $cat) {
						$bIsSpecial = $categoryTable->hasSpecialConf($cat, $theCode, 'no');

						if (!$bIsSpecial) {
							$dataArray =
								$categoryTable->get(
									$cat,
									$this->pidListObj->getPidlist(),
									true,
									'',
									'',
									$orderBy
								);	// read all categories

							if ($depth && !$tableConf['onlyChildsOfCurrent']) {
								$subChildArray = $categoryTable->getChildCategoryArray($cat);
								foreach ($subChildArray as $k2 => $subCat) {
									$categoryTable->get(
										$subCat,
										$this->pidListObj->getPidlist()
									);	// read the sub categories
								}
							}
							$allowedCatArray[] = $cat;
						}
					}
					$rootCat = $startCat;
					if (
						\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($rootCat)
					) {
						$allowedCatArray[] = $rootCat;
					}

					$allowedCats = implode(',', $allowedCatArray);
					$excludeCat = $rootCat;
				} else if ($tableConf['onlyChildsOfCurrent']) {
					$pids = $this->pidListObj->getPidlist();
					if (!$rootCat) {
						$rootCat =
							$categoryTable->getAllChildCats(
								$pids,
								$orderBy,
								''
							);
						if ($rootCat == '') {
							$rootCat = 0;
						}
					}
					$relatedArray =
						$categoryTable->getRelated(
							$rootCat,
							$startCat,
							$pids,
							$orderBy
						);	// read only related categories
				} else if ($tableConf['rootChildsOfCurrent']) {
					$pids = $this->pidListObj->getPidlist();
					$childrenCat =
						$categoryTable->getAllChildCats(
							$pids,
							$orderBy,
							$startCat
						);

					if ($childrenCat == '') {
						$rootCat = 0;
						if ($tableConf['stickIfNoChild']) {
							$parentRow = $categoryTable->getParent($startCat);
							if (is_array($parentRow)) {
								$parentCat = $parentRow['uid'];
								$rootCat =
									$categoryTable->getAllChildCats(
										$pids,
										$orderBy,
										$parentCat
									);
							}
						} else {
							$rootCat = '';
						}
					} else {
						if (
							!$rootCat ||
							$startCat
						) {
							$rootCat = $childrenCat;
						} else {
							$rootCatArray = explode(',', $rootCat);
							$childrenCatArray = explode(',', $childrenCat);
							$rootCatArray = array_intersect($rootCatArray, $childrenCatArray);
							$rootCat = implode(',', $rootCatArray);
						}
					}

					$relatedArray =
						$categoryTable->getRelated(
							$rootCat,
							0,
							$pids,
							$orderBy
						);	// read only related categories
				} else {
					// read in all categories
					$latest = ($latest ? $latest : '');
					$relatedArray =
						$categoryTable->get(
							'',
							$this->pidListObj->getPidlist(),
							true,
							$where_clause,
							'',
							$orderBy,
							$latest,
							'',
							false,
							$aliasPostfix
						);	// read all categories
					$excludeCat = 0;
				}

				if (is_array($relatedArray)) {
 					$excludeCat = 0;
					$categoryTable->translateByFields($relatedArray, $theCode);
 				}

				if (is_array($tableConf['special.']) && strlen($tableConf['special.']['no'])) {
					$excludeCat = $tableConf['special.']['no'];
				}
			} // if ($pageAsCategory && $functablename == 'pages')
			if ($functablename == 'pages') {
				$allowedCats = $this->pidListObj->getPidlist($rootCat);
			}

			$categoryArray =
				$categoryTable->getRelationArray(
					$relatedArray,
					$excludeCat,
					$rootCat,
					$allowedCats
				);
			$rootpathArray =
				$categoryTable->getRootpathArray(
					$categoryArray,
					$rootCat,
					$startCat
				);
			$rootArray =
				$categoryTable->getRootArray(
					$rootCat,
					$categoryArray,
					!isset($tableConf['autoRoot']) || $tableConf['autoRoot']
				);
			$activeRootline =
				$this->getActiveRootline(
					$startCat,
					$categoryArray
				);
			$this->setDepths($categoryArray, $catArray, $rootArray);
			$depth = 1;
			if ($bUseFilter) {
				$catArray[(int) $depth] = $allowedCatArray;
			}

			if ($ctrlArray['bUseBrowser']) {
				$ctrlArray['limit'] = $this->config['limit'];
				$this->getBrowserMarkerArray(
					$browseMarkerArray,
					$t,
					count($categoryArray) - count($rootArray),
					$ctrlArray['limit'],
					$maxPages,
					[],
					[]
				);
				$t['listFrameWork'] = $templateService->substituteMarkerArrayCached($t['listFrameWork'], $browseMarkerArray);
			}
		} else if (!$t['listFrameWork']) {
			$templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
			$error_code[0] = 'no_subtemplate';
			$error_code[1] = '###' . $templateArea . $templateSuffix . '###';
			$error_code[2] = $templateObj->getTemplateFile();
			$rc = false;
		} else if (!is_object($categoryTable)) {
			$error_code[0] = 'internal_error';
			$error_code[1] = 'TTP_1';
			$error_code[2] = $functablename;
			$rc = false;
		}
		return $rc;
	} // printView
}

