<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2009 Franz Holzinger (franz@ttproducts.de)
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
 * marker functions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;



class tx_ttproducts_marker implements \TYPO3\CMS\Core\SingletonInterface {
	public $cObj;
	public $conf;
	public $config;
	public $markerArray;
	public $globalMarkerArray;
	public $urlArray;
	private $langArray;
	private $errorCode = array();
	private $specialArray = array('eq', 'ne', 'lt', 'le', 'gt', 'ge', 'id', 'fn');

	/**
	 * Initialized the marker object
	 * $basket is the TYPO3 default shopping basket array from ses-data
	 *
	 * @param	string		$fieldname is the field in the table you want to create a JavaScript for
	 * @param	array		array urls which should be overridden with marker key as index
	 * @return	  void
	 */
	public function init ($cObj, $piVars, $conf, $config)	{
		$this->cObj = $cObj;
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$this->conf = $conf;
		$this->config = $config;
		$this->markerArray = array('CATEGORY', 'PRODUCT', 'ARTICLE');
		$markerFile = $this->conf['markerFile'];
		$languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);
		$defaultMarkerFile = 'EXT:' . TT_PRODUCTS_EXT . '/marker/locallang.xml';
		$languageObj->loadLocalLang($defaultMarkerFile);
		$language = $languageObj->getLanguage();

		if ($language == '' || $language == 'default' || $language == 'en') {
            if (!$markerFile) {
                $markerFile = $defaultMarkerFile;
            }
            if ($markerFile) {
                if (
                    version_compare(TYPO3_version, '9.4.0', '>=')
                ) {
                    $sanitizer = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Resource\FilePathSanitizer::class);
                    $markerFile = $sanitizer->sanitize($markerFile);
                } else {
                    $markerFile = $GLOBALS['TSFE']->tmpl->getFileName($markerFile);
                }

                $languageObj->loadLocalLang($markerFile);
            }
		} else {
			if (!$markerFile || $markerFile == '{$plugin.tt_products.file.markerFile}') {
				if ($language == 'de') {
					$markerFile = 'EXT:' . TT_PRODUCTS_EXT . '/marker/' . $language . '.locallang.xml';
				} else if (ExtensionManagementUtility::isLoaded(ADDONS_EXT)) {
					$markerFile = 'EXT:' . ADDONS_EXT . '/' . $language . '.locallang.xml';
				}
                if (!$markerFile) {
                    $markerFile = $defaultMarkerFile;
                }
			} else if (substr($markerFile, 0, 4) == 'EXT:') {	// extension
				list($extKey, $local) = explode('/', substr($markerFile, 4), 2);
				$filename='';
				if (
					strcmp($extKey, '') &&
					!ExtensionManagementUtility::isLoaded($extKey) &&
					strcmp($local, '')
				) {
					$errorCode = array();
					$errorCode[0] = 'extension_missing';
					$errorCode[1] = $extKey;
					$errorCode[2] = $markerFile;
					$this->setErrorCode($errorCode);
				}
			}

            if ($markerFile) {
                if (
                    version_compare(TYPO3_version, '9.4.0', '>=')
                ) {
                    $sanitizer = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Resource\FilePathSanitizer::class);
                    $markerFile = $sanitizer->sanitize($markerFile);
                } else {
                    $markerFile = $GLOBALS['TSFE']->tmpl->getFileName($markerFile);
                }
            }

            if (!$markerFile) {
                throw new \RuntimeException('Error in tt_products: No marker file for language "' . $language . '" set.', 50011);
            }
			$languageObj->loadLocalLang($markerFile);
		}
		$locallang = $languageObj->getLocallang();
		$LLkey = $languageObj->getLocalLangKey();
		$this->setGlobalMarkerArray($piVars, $locallang, $LLkey);
		$errorCode = $this->getErrorCode();
		return (count($errorCode) == 0 ? true : false);
	}

	public function getErrorCode ()	{
		return $this->errorCode;
	}

	public function setErrorCode (array $errorCode)	{
		$this->errorCode = $errorCode;
	}

	public function setLangArray (&$langArray)	{
		$this->langArray = $langArray;
	}

	public function getLangArray ()	{
		return $this->langArray;
	}

	public function getGlobalMarkerArray ()	{
		return $this->globalMarkerArray;
	}

	public function replaceGlobalMarkers (&$content, $markerArray = array())	{
		$globalMarkerArray = $this->getGlobalMarkerArray();
		$markerArray = array_merge($globalMarkerArray, $markerArray);
		$rc = tx_div2007_core::substituteMarkerArrayCached($content, $markerArray);
		return $rc;
	}

	/**
	 * getting the global markers
	 */
	public function setGlobalMarkerArray ($piVars, $locallang, $LLkey)	{
		$markerArray = array();

			// globally substituted markers, fonts and colors.
		$splitMark = md5(microtime());
		list($markerArray['###GW1B###' ],$markerArray['###GW1E###']) = explode($splitMark,$this->cObj->stdWrap($splitMark,$this->conf['wrap1.']));
		list($markerArray['###GW2B###'],$markerArray['###GW2E###']) = explode($splitMark,$this->cObj->stdWrap($splitMark,$this->conf['wrap2.']));
		list($markerArray['###GW3B###'],$markerArray['###GW3E###']) = explode($splitMark,$this->cObj->stdWrap($splitMark,$this->conf['wrap3.']));
		$markerArray['###GC1###'] = $this->cObj->stdWrap($this->conf['color1'], $this->conf['color1.']);
		$markerArray['###GC2###'] = $this->cObj->stdWrap($this->conf['color2'], $this->conf['color2.']);
		$markerArray['###GC3###'] = $this->cObj->stdWrap($this->conf['color3'], $this->conf['color3.']);
		$markerArray['###DOMAIN###'] = $this->conf['domain'];
		$markerArray['###PATH_FE_REL###'] = PATH_FE_TTPRODUCTS_REL;
		$markerArray['###PATH_FE_ICONS###'] =  PATH_FE_TTPRODUCTS_REL . 'res/icons/fe/';;
		if (ExtensionManagementUtility::isLoaded(ADDONS_EXTkey)) {
			$markerArray['###PATH_FE_REL###'] = PATH_FE_ADDONS_TT_PRODUCTS_REL;
			$markerArray['###PATH_FE_ICONS###'] = PATH_FE_ADDONS_TT_PRODUCTS_ICON_REL;
		}
		$pidMarkerArray = array('agb','basket','info','finalize','payment',
			'thanks','itemDisplay','listDisplay','revocation','search','storeRoot',
			'memo','tracking','billing','delivery'
		);
		foreach ($pidMarkerArray as $k => $function)	{
			$markerArray['###PID_'.strtoupper($function).'###'] = intval($this->conf['PID'.$function]);
		}
		$markerArray['###SHOPADMIN_EMAIL###'] = $this->conf['orderEmail_from'];
		$lang =  GeneralUtility::_GET('L');

		if ($lang!='')	{
			$markerArray['###LANGPARAM###'] = '&amp;L=' . $lang;
		} else {
			$markerArray['###LANGPARAM###'] = '';
		}
		$markerArray['###LANG###'] = $lang;
		$markerArray['###LANGUAGE###'] = $GLOBALS['TSFE']->config['config']['language'];
		$markerArray['###LOCALE_ALL###'] = $GLOBALS['TSFE']->config['config']['locale_all'];

		$backPID = $piVars['backPID'];
		$backPID = ($backPID ? $backPID : GeneralUtility::_GP('backPID'));
		$backPID = ($backPID  ? $backPID : ($this->conf['PIDlistDisplay'] ? $this->conf['PIDlistDisplay'] : $GLOBALS['TSFE']->id));
		$markerArray['###BACK_PID###'] = $backPID;

			// Call all addURLMarkers hooks at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['addGlobalMarkers'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['addGlobalMarkers'] as $classRef) {
				$hookObj= GeneralUtility::makeInstance($classRef);
				if (method_exists($hookObj, 'addGlobalMarkers')) {
					$hookObj->addGlobalMarkers($markerArray);
				}
			}
		}

		if (isset($locallang[$LLkey]))	{
			if (isset($locallang['default']) && is_array($locallang['default'])) {
				$langArray = array_merge($locallang['default'],$locallang[$LLkey]);
			} else {
				$langArray = $locallang[$LLkey];
			}
		} else {
			$langArray = $locallang['default'];
		}

		if(isset($langArray) && is_array($langArray))	{
			foreach ($langArray as $key => $value)	{
				if (
					is_array($value)
				) {
					if ($value[0]['target']) {
						$value = $value[0]['target'];
					} else {
						$value = $value[0]['source'];
					}
				}

				$langArray[$key] = $value;
				$markerArray['###' . strtoupper($key) . '###'] = $value;
			}
		} else {
			$langArray = array();
		}

		if (is_array($this->conf['marks.']))	{
				// Substitute Marker Array from TypoScript Setup
			foreach ($this->conf['marks.'] as $key => $value)	{

				if (is_array($value))	{
					switch($key)	{
						case 'image.':
							foreach ($value as $k2 => $v2)	{
                                $fileresource =  \JambageCom\Div2007\Utility\FrontendUtility::fileResource($v2);
								$markerArray['###IMAGE' . strtoupper($k2) . '###'] = $fileresource;
							}
						break;
					}
				} else {
					if(isset($this->conf['marks.'][$key.'.']) && is_array($this->conf['marks.'][$key.'.']))	{
						$out = $this->cObj->cObjGetSingle($this->conf['marks.'][$key], $this->conf['marks.'][$key.'.']);
					} else {
						$langArray[$key] = $value;
						$out = $value;
					}
					$markerArray['###' . strtoupper($key) . '###'] = $out;
				}
			}
		}

		$this->globalMarkerArray = $markerArray;
		$this->setLangArray($langArray);
	} // setGlobalMarkerArray

	public function reduceMarkerArray ($templateCode, $markerArray) {
		$result = array();

		$tagArray = $this->getAllMarkers($templateCode);

		foreach ($tagArray as $tag => $v) {
			$marker = '###' . $tag. '###';
			if (isset($markerArray[$marker])) {
				$result[$marker] = $markerArray[$marker];
			}
		}
		return $result;
	}

	public function getAllMarkers ($templateCode) {

		$treffer = array();
		$tagArray = false;

		preg_match_all('/###([\w:-]+)###/', $templateCode, $treffer);
		if (
			isset($treffer) &&
			is_array($treffer) &&
			isset($treffer['1'])
		) {
			$tagArray = array_unique($treffer['1']);
		}

		if (is_array($tagArray)) {
			$tagArray = array_flip($tagArray);
		}

		return $tagArray;
	}

	/**
	 * finds all the markers for a product
	 * This helps to reduce the data transfer from the database
	 *
	 * @access private
	 */
	public function getMarkerFields (
		$templateCode,
		&$tableFieldArray,
		&$requiredFieldArray,
		&$addCheckArray,
		$prefixParam,
		&$tagArray,
		&$parentArray
	) {
		$retArray = (count($requiredFieldArray) ? $requiredFieldArray : array());
		// obligatory fields uid and pid

		$prefix = $prefixParam . '_';
		$prefixLen = strlen($prefix);
		$bFieldaddedArray = array();
		$tagArray = $this->getAllMarkers($templateCode);

		if (is_array($tagArray)) {
			$retTagArray = $tagArray;
			foreach ($tagArray as $tag => $v1) {
				$prefixFound = strpos($tag, $prefix);

				if ($prefixFound !== false) {
					$fieldTmp = substr($tag, $prefixFound + $prefixLen);
					$fieldTmp = strtolower($fieldTmp);
					$fieldTmp = preg_replace('/[0-9]$/', '', $fieldTmp); // remove trailing numbers

					$field = $fieldTmp;

					if (isset($tableFieldArray[EXTERNAL_FIELD_PREFIX . $field])) {
						$field = EXTERNAL_FIELD_PREFIX . $field;
					}

					if (!isset($tableFieldArray[$field])) {

						$fieldPartArray = GeneralUtility::trimExplode('_', $fieldTmp);
						$fieldTmp = $fieldPartArray[0];
						$subFieldPartArray = GeneralUtility::trimExplode(':', $fieldTmp);
						$colon = (count($subFieldPartArray) > 1);
						$field = $subFieldPartArray[0];

                        if (
                            !isset($tableFieldArray[$field]) ||
                            isset($tableFieldArray[$field . '_uid'])
                        ) { // wird für ###PRODUCT_IMAGE1:M### benötigt
                            $field = preg_replace('/[0-9]$/', '', $field); // remove trailing numbers
                            if (isset($tableFieldArray[$field . '_uid'])) {
                                $field = $field . '_uid';

                                if (   
                                    isset($tableFieldArray[$field]) &&
                                    is_array($tableFieldArray[$field])
                                ) {
                                    $retArray[] = $field;
                                    $bFieldaddedArray[$field] = true;
                                }
                            }
                        }

                        if (
                            !$colon &&
                            !isset($tableFieldArray[$field])
                        ) {
							$newFieldPartArray = array();
							foreach ($fieldPartArray as $k => $v) {
								if (in_array($v, $this->specialArray)) {
									break;
								} else {
									$newFieldPartArray[] = $v;
								}
							}
							$field = implode('_', $newFieldPartArray);
						}

						if (
                            !$colon &&
                            (
                                !isset($tableFieldArray[$field]) ||
                                !is_array($tableFieldArray[$field])
							)
						) {	// find similar field names with letters in other cases
							$upperField = strtoupper($field);
							foreach ($tableFieldArray as $k => $v) {
								if (strtoupper($k) == $upperField) {
									$field = $k;
									break;
								}
							}
						}
					}
					$field = strtolower($field);
					if (
						isset($tableFieldArray[$field]) &&
						is_array($tableFieldArray[$field])
					) {
						$retArray[] = $field;
						$bFieldaddedArray[$field] = true;
					}

					$parentFound = strpos($tag, 'PARENT');

					if ($parentFound !== false) {
						$parentEnd = strpos($tag, '_');
						$parentLen = strlen('PARENT');
						$temp = substr($tag, $parentLen, ($parentEnd - $parentFound) - $parentLen);
						$parentArray[] = $temp;
					}
				} else {
					// unset the tags of different tables

					foreach ($this->markerArray as $k => $marker) {
						if ($marker != $prefixParam) {
							$bMarkerFound = strpos($tag, $marker);
							if ($bMarkerFound == 0 && $bMarkerFound !== false) {
								unset($retTagArray[$tag]);
							}
						}
					}
				}
			}
			$tagArray = $retTagArray;
		} else {
            $tagArray = array();
		}

		$parentArray = array_unique($parentArray);
		sort($parentArray);

		if (is_array($addCheckArray)) {
			foreach ($addCheckArray as $marker => $field) {
				if (!$bFieldaddedArray[$field] && isset($tableFieldArray[$field])) { 	// TODO: check also if the marker is in the $tagArray
					$retArray[] = $field;
				}
			}
		}

		if (is_array($retArray)) {
			$retArray = array_unique($retArray);
		}
		return $retArray;
	}
}
