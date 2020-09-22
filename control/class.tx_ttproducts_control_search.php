<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2009 Franz Holzinger (franz@ttproducts.de)
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
 * main loop for search
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */


use TYPO3\CMS\Core\Utility\GeneralUtility;



class tx_ttproducts_control_search implements \TYPO3\CMS\Core\SingletonInterface {
	public $cObj;
	public $conf;
	public $config;
	public $piVars;
	public $pibaseClass;			// class of the pibase object
	public $codeArray;			// Codes
	public $errorMessage;


	public function init (&$content, &$conf, &$config, $pibaseClass, &$errorCode) {
		$pibaseObj = GeneralUtility::makeInstance(''.$pibaseClass);
		$this->cObj = $pibaseObj->cObj;

		$flexformArray = GeneralUtility::xml2array($this->cObj->data['pi_flexform']);
		$flexformTyposcript = tx_div2007_ff::get($flexformArray, 'myTS');
		if($flexformTyposcript) {
			$tsparser = tx_div2007_core::newTsParser();

			// Copy conf into existing setup
			$tsparser->setup = $conf;
			// Parse the new Typoscript
			$tsparser->parse($flexformTyposcript);
			// Copy the resulting setup back into conf
			$conf = $tsparser->setup;
		}
		$this->conf = &$conf;
		$this->config = &$config;
		$this->piVars = &$pibaseObj->piVars;
		$this->pibaseClass = $pibaseClass;

		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$cnf->init(
			$conf,
			$config
		);

		$allText = tx_div2007_alpha5::getLL_fh003($pibaseObj, 'all');

			// get all extending TCAs
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['extendingTCA']))	{
			tx_div2007_alpha5::loadTcaAdditions_fh002($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['extendingTCA']);
		}
		// $pibaseObj->pi_initPIflexForm();
		$this->cObj->data['pi_flexform'] = GeneralUtility::xml2array($this->cObj->data['pi_flexform']);
		$newConfig = $this->getControlConfig($this->cObj, $conf, $this->cObj->data);
		$config = array_merge($config, $newConfig);
		$this->codeArray = GeneralUtility::trimExplode(',', $config['code'],1);
		$config['LLkey'] = $pibaseObj->LLkey;
		$config['templateSuffix'] = strtoupper($this->conf['templateSuffix']);
		$templateSuffix = tx_div2007_ff::get($flexformArray, 'template_suffix');
		$templateSuffix = strtoupper($templateSuffix);
		$config['templateSuffix'] = ($templateSuffix ? $templateSuffix : $config['templateSuffix']);
		$config['templateSuffix'] = ($config['templateSuffix'] ? '_'.$config['templateSuffix'] : '');

        $languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);
		$languageObj->loadLocalLang( 'EXT:' . TT_PRODUCTS_EXT . '/pi_search/locallang.xml');

		$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
		$markerObj->init(
			$this->cObj,
			$pibaseObj->piVars
		);

		$searchViewObj = GeneralUtility::makeInstance('tx_ttproducts_search_view');
		$searchViewObj->init(
			$this->cObj
		);

		return true;
	} // init


	public function &getControlConfig ($cObj, &$conf, &$row)	{
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$ctrlArray = tx_ttproducts_model_control::getParamsTableArray();

		$config = array();
		$config['code'] =
			tx_div2007_alpha5::getSetupOrFFvalue_fh002(
				$cObj,
	 			$conf['code'],
	 			$conf['code.'],
				$conf['defaultCode'],
				$row['pi_flexform'],
				'display_mode',
				true
			);

		$flexformConfigArray = array(
			'local_param',
			'foreign_param',
			'columns',
			'fields',
			'group_by_fields',
			'url',
			'all',
			'parameters',
			'delimiter',
		);

		foreach ($flexformConfigArray as $flexformConfig)	{
			$tmpConfig = tx_div2007_ff::get($row['pi_flexform'], $flexformConfig);
			$config[$flexformConfig] = $tmpConfig;
		}
		$config['local_table'] = $cnf->getTableName($ctrlArray[$config['local_param']]);
		$config['foreign_table'] = $cnf->getTableName($ctrlArray[$config['foreign_param']]);
		if ($config['url'] != '')	{
			$url = str_replace('index.php?','',$config['url']);
			$urlArray = GeneralUtility::trimExplode('=',$url);
			if ($urlArray['0'] == 'id' && intval($urlArray['1']))	{
				$id = $urlArray['1'];
				$url = tx_div2007_alpha5::getPageLink_fh003($cObj,$id);
				$config['url'] = $url;
			}
		}
		return $config;
	}


	public function &run ($pibaseClass, &$errorCode, $content = '') {
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
		$languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);
		$pibaseObj = GeneralUtility::makeInstance('' . $pibaseClass);
		$subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');
		$searchViewObj = GeneralUtility::makeInstance('tx_ttproducts_search_view');
		$errorCode = array();
		$errorMessage = '';

		foreach($this->codeArray as $theCode)	{

			$theCode = (string) trim($theCode);
			$contentTmp = '';
			$templateCode = $templateObj->get($theCode, $languageObj, $this->cObj, $tmp='', $errorMessage);
			$theTemplateCode = tx_div2007_core::getSubpart($templateCode,$subpartmarkerObj->spMarker('###' . $theCode . $this->config['templateSuffix'] . '###'));

			switch($theCode)	{
				case 'FIRSTLETTER':
					$contentTmp = $searchViewObj->printFirstletter(
						$pibaseObj,
						$theTemplateCode,
						$this->config['columns'],
						$errorCode
					);
				break;
				case 'FIELD':
					$contentTmp = $searchViewObj->printKeyField(
						$pibaseObj,
						$theTemplateCode,
						$this->config['columns'],
						2,
						'field'.$this->cObj->data['uid'],
						$tmp=array(),
						$errorCode
					);
				break;
				case 'KEYFIELD':
					$functablename = ($this->config['foreign_table'] != '' ? $this->config['foreign_table'] : $this->config['local_table']);
					$tableConf = $cnf->getTableConf($functablename, $theCode);

					if (isset($tableConf['view.']) && is_array($tableConf['view.']) &&
						isset($tableConf['view.']['valueArray.']) && is_array($tableConf['view.']['valueArray.'])
					)	{
						$keyfieldConf = $tableConf['view.']['valueArray.'];
					} else {
						$keyfieldConf = array();
					}
					$contentTmp = $searchViewObj->printKeyField(
						$pibaseObj,
						$theTemplateCode,
						$this->config['columns'],
						1,
						'keyfield'.$this->cObj->data['uid'],
						$keyfieldConf,
						$errorCode
					);
				break;
				case 'LASTENTRIES':
					$contentTmp = $searchViewObj->printLastEntries(
						$pibaseObj,
						$theTemplateCode,
						$this->config['columns'],
						$errorCode
					);
				break;
				case 'TEXTFIELD':
					$contentTmp = $searchViewObj->printTextField(
						$pibaseObj,
						$theTemplateCode,
						$this->config['columns'],
						'textfield'.$this->cObj->data['uid'],
						$this->cObj->data,
						$errorCode
					);
				break;
				case 'YEAR':
					$contentTmp = $searchViewObj->printYear(
						$pibaseObj,
						$theTemplateCode,
						$this->config['columns'],
						$errorCode
					);
				break;
				default:	// 'HELP'
					$contentTmp = 'error';
				break;
			}

			if ($errorCode[0]) {
				$contentTmp .= $errorObj->getMessage($errorCode, $languageObj);
			}

			if ($contentTmp == 'error') {
				$fileName = 'EXT:'.TT_PRODUCTS_EXT.'/template/products_help.tmpl';
				$helpTemplate = $this->cObj->fileResource($fileName);
				$content .=
                    \JambageCom\Div2007\Utility\ViewUtility::displayHelpPage(
                        $languageObj,
                        $this->cObj,
                        $helpTemplate,
                        TT_PRODUCTS_EXT,
                        $errorMessage,
                        $theCode
                    );
				unset($errorMessage);
				break; // while
			} else {
				$content .= tx_div2007_alpha5::wrapContentCode_fh004($contentTmp,$theCode,$pibaseObj->prefixId,$this->cObj->data['uid']);
			}
		}

		if ($errorMessage) {
			$content = '<p><b>' . $errorMessage . '</b></p>';
		}

		if ($bRunAjax || !intval($this->conf['wrapInBaseClass']))	{
			$rc = $content;
		} else {
			$content = $pibaseObj->pi_wrapInBaseClass($content);
			if (is_object($this->css) && ($this->css->conf['file']))	{
				$rc = '<style type="text/css">' . $this->cObj->fileResource($this->css->conf['file']) . '</style>' . chr(13) . $content;
			} else {
				$rc = $content;
			}
		}
		return $rc;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_control_search.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_control_search.php']);
}


