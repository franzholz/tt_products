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
 * functions for the template file
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_template implements \TYPO3\CMS\Core\SingletonInterface {
	private $templateFile;
	protected $templateSuffix = '';

	public function getTemplateFile () {
		return $this->templateFile;
	}


	public function setTemplateSuffix ($value) {
		$this->templateSuffix = $value;
	}


	public function getTemplateSuffix () {
		return $this->templateSuffix;
	}


	public function get (
		$theCode,
		&$templateFile,
		&$errorCode
	) {

		$templateCode = '';
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$conf = $cnf->getConf();
		$templateFile = $cnf->getTemplateFile($theCode);
		$pathFilename = '';
        if ($templateFile) {
            $pathFilename = GeneralUtility::getFileAbsFileName($templateFile);
        }

		if (file_exists($pathFilename)) {
			// template file is fetched. The whole template file from which the various subpart are extracted.
			$templateCode = file_get_contents($pathFilename);
		}

		if (
			(!$templateFile || empty($templateCode))
		) {
            $tmplText = '';
			if (!empty($conf['templateFile.'][$theCode])) {
				$tmplText = $theCode . '.';
			}
			$tmplText .= 'templateFile';

			if (empty($errorCode)) {
				$errorCode[0] = 'no_template';
				$errorCode[1] =  ' plugin.' . TT_PRODUCTS_EXT . '.' . $tmplText . ' = ' .
					($templateFile ? $templateFile : '');
			}
		}

		if (
			$theCode != 'ERROR' &&
			$templateFile != '' &&
			!empty($templateCode)
		) {
			$this->templateFile = $templateFile;
		}

		return $templateCode;
	}
}




