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
use TYPO3\CMS\Frontend\Resource\FilePathSanitizer;

use JambageCom\TtProducts\Api\PluginApi;
use JambageCom\Div2007\Utility\FrontendUtility;


class tx_ttproducts_control_search implements \TYPO3\CMS\Core\SingletonInterface, tx_ttproducts_field_int {
	public $cObj;
	public $conf;
	public $config;
	public $pibaseClass;			// class of the pibase object
	public $codeArray;			// Codes
	public $errorMessage;


	public function init (&$content, &$conf, &$config, $cObj, $pibaseClass, &$error_code) {
		$pibaseObj = GeneralUtility::makeInstance($pibaseClass);
		$this->cObj = $cObj;
		$parameterApi = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\ParameterApi::class);

		PluginApi::initFlexform($cObj);
		$flexformArray = \JambageCom\TtProducts\Api\PluginApi::getFlexform();
		$flexformTyposcript = \JambageCom\Div2007\Utility\FlexformUtility::get($flexformArray, 'myTS');
		if($flexformTyposcript) {
			$tsparser = GeneralUtility::makeInstance(
                \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::class
            );
			// Copy conf into existing setup
			$tsparser->setup = $conf;
			// Parse the new Typoscript
			$tsparser->parse($flexformTyposcript);
			// Copy the resulting setup back into conf
			$conf = $tsparser->setup;
		}
		$this->conf = &$conf;
		$this->config = &$config;
		$piVars = $parameterApi->getPiVars();
		$this->pibaseClass = $pibaseClass;

		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$cnf->init(
			$conf,
			$config
		);

		// $pibaseObj->pi_initPIflexForm();
		$this->cObj->data['pi_flexform'] = GeneralUtility::xml2array($this->cObj->data['pi_flexform'] ?? '');
		$newConfig = $this->getControlConfig($this->cObj, $conf, $this->cObj->data);
		$config = array_merge($config, $newConfig);
		$this->codeArray = GeneralUtility::trimExplode(',', $config['code'],1);
		$config['LLkey'] = $pibaseObj->LLkey;
		$config['templateSuffix'] = strtoupper($this->conf['templateSuffix']);
		$templateSuffix = \JambageCom\Div2007\Utility\FlexformUtility::get($flexformArray, 'template_suffix');
		$templateSuffix = strtoupper($templateSuffix);
		$config['templateSuffix'] = ($templateSuffix ? $templateSuffix : $config['templateSuffix']);
		$config['templateSuffix'] = ($config['templateSuffix'] ? '_'.$config['templateSuffix'] : '');

        $languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);
		$languageObj->loadLocalLang( 'EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'PiSearch/locallang.xlf');
		$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
		$markerObj->init(
            $conf,
			$piVars
		);

		$searchViewObj = GeneralUtility::makeInstance('tx_ttproducts_search_view');
		$searchViewObj->init(
			$this->cObj
		);

		return true;
	} // init


	public function getControlConfig ($cObj, &$conf, &$row) {
        $parameterApi = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\ParameterApi::class);
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$ctrlArray = $parameterApi->getParameterTable();

		$config = [];
        $config['code'] =
            \JambageCom\Div2007\Utility\ConfigUtility::getSetupOrFFvalue(
                $cObj,
                $conf['code'],
                $conf['code.'],
                $conf['defaultCode'],
                $row['pi_flexform'] ?? '',
                'display_mode',
                true
            );

		$flexformConfigArray = [
			'local_param',
			'foreign_param',
			'columns',
			'fields',
			'group_by_fields',
			'url',
			'all',
			'parameters',
			'delimiter',
		];

		foreach ($flexformConfigArray as $flexformConfig) {
			$tmpConfig = \JambageCom\Div2007\Utility\FlexformUtility::get($row['pi_flexform'] ?? '', $flexformConfig);
			$config[$flexformConfig] = $tmpConfig;
		}
		$config['local_table'] = $cnf->getTableName($ctrlArray[$config['local_param']]);
		$config['foreign_table'] = $cnf->getTableName($ctrlArray[$config['foreign_param']]);
		if ($config['url'] != '') {
			$url = str_replace('index.php?','',$config['url']);
			$urlArray = GeneralUtility::trimExplode('=', $url);
			if ($urlArray['0'] == 'id' && intval($urlArray['1'])) {
				$id = $urlArray['1'];
				$url = FrontendUtility::getTypoLink_URL($cObj, $id);
				$config['url'] = $url;
			}
		}

		return $config;
	}


	public function run ($cObj, $pibaseClass, &$errorCode, $content='') {

		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
		$languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);
		$pibaseObj = GeneralUtility::makeInstance($pibaseClass);
		$subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');
		$searchViewObj = GeneralUtility::makeInstance('tx_ttproducts_search_view');
		$error_code = [];
		$errorMessage = '';

		foreach($this->codeArray as $theCode) {

			$theCode = (string) trim($theCode);
			$contentTmp = '';
			$tmp = '';
			$templateCode =
				$templateObj->get(
					$theCode,
					$tmp,
					$errorCode
				);

			if ($errorCode) {
				$errorText =
					$languageObj->getLabel(
						'no_template'
					);
				$errorMessage = str_replace('|', 'plugin.tt_products.templateFile', $errorText);
			}

            $templateService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Service\MarkerBasedTemplateService::class);
			$theTemplateCode =
				$templateService->getSubpart(
					$templateCode,
					$subpartmarkerObj->spMarker(
						'###' . $theCode . $this->config['templateSuffix'] . '###'
					)
				);

			switch($theCode) {
				case 'FIRSTLETTER':
					$contentTmp = $searchViewObj->printFirstletter(
						$pibaseObj,
						$theTemplateCode,
						$this->config['columns'],
						$error_code
					);
				break;
				case 'FIELD':
					$contentTmp = $searchViewObj->printKeyField(
						$pibaseObj,
						$theTemplateCode,
						$this->config['columns'],
						2,
						'field' . $this->cObj->data['uid'],
						$tmp = [],
						$error_code
					);
				break;
				case 'KEYFIELD':
					$functablename = ($this->config['foreign_table'] ?? $this->config['local_table']);
					$tableConf = $cnf->getTableConf($functablename, $theCode);

					if (isset($tableConf['view.']) && is_array($tableConf['view.']) &&
						isset($tableConf['view.']['valueArray.']) && is_array($tableConf['view.']['valueArray.'])
					)	{
						$keyfieldConf = $tableConf['view.']['valueArray.'];
					} else {
						$keyfieldConf = [];
					}
					$contentTmp = $searchViewObj->printKeyField(
						$pibaseObj,
						$theTemplateCode,
						$this->config['columns'],
						1,
						'keyfield'.$this->cObj->data['uid'],
						$keyfieldConf,
						$error_code
					);
				break;
				case 'LASTENTRIES':
					$contentTmp = $searchViewObj->printLastEntries(
						$pibaseObj,
						$theTemplateCode,
						$this->config['columns'],
						$error_code
					);
				break;
				case 'TEXTFIELD':
					$contentTmp = $searchViewObj->printTextField(
						$pibaseObj,
						$theTemplateCode,
						$this->config['columns'],
						'textfield'.$this->cObj->data['uid'],
						$this->cObj->data,
						$error_code
					);
				break;
				case 'YEAR':
					$contentTmp = $searchViewObj->printYear(
						$pibaseObj,
						$theTemplateCode,
						$this->config['columns'],
						$error_code
					);
				break;
				default:	// 'HELP'
					$contentTmp = 'error';
				break;
			}

			if ($error_code[0]) {
				$contentTmp .= $errorObj->getMessage($error_code, $languageObj);
			}

			if ($contentTmp == 'error') {
                $sanitizer = GeneralUtility::makeInstance(FilePathSanitizer::class);
				$fileName = 'EXT:' . TT_PRODUCTS_EXT . '/template/products_help.tmpl';
                $pathFilename = $sanitizer->sanitize($fileName);
//                 $GLOBALS['TSFE']->tmpl->getFileName($fileName);
                $helpTemplate = file_get_contents($pathFilename);
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
				$content .=
                    FrontendUtility::wrapContentCode(
                        $contentTmp,
                        $theCode,
                        $pibaseObj->prefixId,
                        $this->cObj->data['uid']
                    );
			}
		}

		if ($errorMessage) {
			$content = '<p><b>' . $errorMessage . '</b></p>';
		}

		if ($bRunAjax || !intval($this->conf['wrapInBaseClass'])) {
			$rc = $content;
		} else {
			$content = $pibaseObj->pi_wrapInBaseClass($content);

			if (is_object($this->css) && ($this->css->conf['file'])) {
                $sanitizer = GeneralUtility::makeInstance(FilePathSanitizer::class);
                $pathFilename = $sanitizer->sanitize($this->css->conf['file']);
                $cssContent = file_get_contents($pathFilename);
				$rc = '<style type="text/css">' . $cssContent . '</style>' . chr(13) . $content;
			} else {
				$rc = $content;
			}
		}
		return $rc;
	}
}

