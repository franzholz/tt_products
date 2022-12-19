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
 * configuration
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */

 
use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_config implements \TYPO3\CMS\Core\SingletonInterface {
	public $conf;
	public $config;
	private $bHasBeenInitialised = false;


	/**
	 * Getting the configurations
	 */
	public function init ($conf, $config) {
		$this->conf = $conf;

		$this->config = $config;
		$this->bHasBeenInitialised = true;
	} // init


	public function needsInit () {
		return !$this->bHasBeenInitialised;
	}


	public function setConf ($key, $value) {
		if ($key != '') {
			$this->conf[$key] = $value;
		} else {
			$this->conf = $value;
		}
	}


	public function getConf () {
		$result = $this->conf;
		if (self::needsInit()) {
			$result = false;
		}

		return $result;
	}


	public function setConfig ($value) {
		$this->config = $value;
	}


	public function getConfig () {
		return $this->config;
	}


	public function getUseArticles () {
		$result = false;
		$conf = $this->getConf();
		if (isset($conf) && is_array($conf)) {
			$result = $conf['useArticles'];
		}
		return $result;
	}


	public function getTableDesc ($functablename, $type = '') {
		$tableDesc = [];
		if (
			isset($this->conf['table.']) &&
			isset($this->conf['table.'][$functablename . '.'])
		) {
			$tableDesc = $this->conf['table.'][$functablename . '.'];
		}

		if ($type && isset($tableDesc[$type])) {
			$result = $tableDesc[$type];
		} else {
			$result = $tableDesc;
		}
		return $result;
	}


	public function getTableName ($functablename) {
		if (
			isset($this->conf['table.']) &&
			is_array($this->conf['table.']) &&
			isset($this->conf['table.'][$functablename])
		) {
			$result = $this->conf['table.'][$functablename];
		} else {
			$result = $functablename;
		}
		return $result;
	}


	public function getSpecialConf ($type, $tablename = '', $theCode = '') {
		$specialConf = [];

		if (isset($this->conf[$type . '.'])) {

			if ($tablename != '' && isset($this->conf[$type . '.'][$tablename . '.'])) {
				if (
					is_array($this->conf[$type . '.'][$tablename . '.']['ALL.'])
				) {
					$specialConf = $this->conf[$type . '.'][$tablename . '.']['ALL.'];
				}
				if (
					$theCode &&
					isset($this->conf[$type . '.'][$tablename . '.'][$theCode . '.'])
				) {
					$tempConf = $this->conf[$type . '.'][$tablename . '.'][$theCode . '.'];
					tx_div2007_core::mergeRecursiveWithOverrule($specialConf, $tempConf);
				}
				if (
                    isset($specialConf['orderBy']) &&
                    $specialConf['orderBy'] == '{$plugin.' . TT_PRODUCTS_EXT . '.orderBy}'
                ) {
					$specialConf['orderBy'] = '';
				}
			} else {
				if (isset($this->conf[$type . '.']['ALL.'])) {
					$specialConf = $this->conf[$type . '.']['ALL.'];
				}
				if (
					$theCode &&
					isset($this->conf[$type . '.'][$theCode . '.'])
				) {
					$tempConf = $this->conf[$type . '.'][$theCode . '.'];
					tx_div2007_core::mergeRecursiveWithOverrule($specialConf, $tempConf);
				}
			}
		}
		return $specialConf;
	}


	public function getTableConf ($functablename, $theCode = '') {
		$tableConf = $this->getSpecialConf('conf', $functablename, $theCode);
		return $tableConf;
	}


	public function getCSSConf ($functablename, $theCode = '') {
		$cssConf = $this->getSpecialConf('CSS', $functablename, $theCode);

		return $cssConf;
	}


	public function getJsConf ($theCode = '') {
		$result = $this->getSpecialConf('js', '', $theCode);

		return $result;
	}


	public function getFormConf ($theCode = '') {
		$result = $this->getSpecialConf('form', '', $theCode);

		return $result;
	}


	public function getViewControlConf ($theCode) {
		$viewConf = $this->getSpecialConf('control', '', $theCode);

		return $viewConf;
	}


	public function getTypeConf ($type, $feature, $detail = '') {

		$rc = [];

		if (isset($this->conf[$type . '.'])) {
			if ($detail != '') {
				if (
					isset($this->conf[$type . '.'][$feature . '.']) &&
					is_array($this->conf[$type . '.'][$feature . '.'])
				) {
					if (isset($this->conf[$type . '.'][$feature . '.'][$detail])) {
						$rc = $this->conf[$type . '.'][$feature . '.'][$detail];
					} else if (isset($this->conf[$type . '.'][$feature . '.'][$detail . '.'])) {
						$rc = $this->conf[$type . '.'][$feature . '.'][$detail . '.'];
					}
				}
			} else {
				if (
					isset($this->conf[$type . '.'][$feature]) &&
					$this->conf[$type . '.'][$feature] != ''
				) {
					$rc = $this->conf[$type . '.'][$feature];
				} else if (isset($this->conf[$type . '.'][$feature . '.'])) {
					$rc = $this->conf[$type . '.'][$feature . '.'];
				}
			}
		}
		return $rc;
	}


	public function getBasketConf ($feature, $detail = '') {
		$result = $this->getTypeConf('basket', $feature, $detail);
		return $result;
	}


	public function getFinalizeConf ($feature, $detail = '') {
		$result = $this->getTypeConf('finalize', $feature, $detail);
		return $result;
	}


	public function getDownloadConf ($feature, $detail = '') {
		$result = $this->getTypeConf('download', $feature, $detail);
		return $result;
	}


	public function getFallback ($tableConf) {
		$result = false;
		if (
			isset($tableConf) &&
			is_array($tableConf) &&
			isset($tableConf['language.']) &&
			$tableConf['language.']['type'] == 'table' &&
			isset($tableConf['language.']['mode']) &&
			$tableConf['language.']['mode'] == 'fallback'
		) {
			$result = true;
		}

		return $result;
	}

	public function getTranslationFields ($tableConf) {
		$fieldArray = [];
		if (isset($tableConf['language.']) && is_array($tableConf['language.']) && isset($tableConf['language.']['type']) && $tableConf['language.']['type'] == 'field') {
			$langConf = $tableConf['language.']['field.'];
			if (is_array($langConf)) {
				foreach ($langConf as $field => $langfield) {
					$fieldArray[$field] = $langfield;
				}
			}
		}
		return $fieldArray;
	}


	public function getImageFields ($tableConf) {
		$retArray = [];

		$generateArray = ['generateImage', 'generatePath'];
		foreach ($generateArray as $k => $generate) {
			if (is_array($tableConf) && isset($tableConf[$generate . '.']) && is_array($tableConf[$generate . '.'])) {
				$genPartArray = $tableConf[$generate . '.'];
				if ($genPartArray['type'] == 'tablefields') {
					$fieldArray = $genPartArray['field.'];
					if (is_array($fieldArray)) {
						foreach ($fieldArray as $field => $count) {
							$retArray[] = $field;
						}
					}
				}
			}
		}
		return $retArray;
	}


	/**
	 * Returns true if the item has the $check value checked
	 *
	 */
	public function hasConfig (
		&$row,
		$check,
		$configField = 'config'
	) {
		$hasConfig = false;

		if (isset($row[$configField])) {
			$config = GeneralUtility::xml2array($row[$configField]);
			$hasConfig = \JambageCom\Div2007\Utility\FlexformUtility::get($config, $check);
		}

		return $hasConfig;
	}


	public function getColumnFields ($tableConf) {
		$retArray = [];

		$generateArray = ['generateColumn'];
		if (is_array($tableConf)) {
            foreach ($generateArray as $k => $generate) {
                if (isset($tableConf[$generate . '.']) && is_array($tableConf[$generate . '.'])) {
                    $genPartArray = $tableConf[$generate . '.'];
                    if ($genPartArray['type'] == 'tablefields') {
                        $fieldArray = $genPartArray['field.'];

                        if (is_array($fieldArray)) {
                            foreach ($fieldArray as $field => $value) {
                                $retArray[$field] = $value;
                            }
                        }
                    }
                }
            }
        }
		return $retArray;
	}


	public function getAJAXConf () {
		$result = [];
		if (isset($this->conf['ajax.']) && is_array($this->conf['ajax.']['conf.'])) {
			$result = $this->conf['ajax.']['conf.'];
		}
		return $result;
	}


	public function getTemplateFile ($theCode) {
		$result = '';

		if (
			isset($this->conf['templateFile.']) &&
			!empty($this->conf['templateFile.'][$theCode])
		) {
			$result = $this->conf['templateFile.'][$theCode];
		} else {
			$result = $this->conf['templateFile'];
		}

		return $result;
	}

}




