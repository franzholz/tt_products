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
 * Creates a list of products for the shopping basket in TYPO3.
 * Also controls basket, searching and payment.
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 *
 */
use Psr\Http\Message\ServerRequestInterface;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

use JambageCom\Div2007\Compatibility\AbstractPlugin;
use JambageCom\TtProducts\Api\ParameterApi;
use JambageCom\TtProducts\Api\PluginApi;

class tx_ttproducts_pi_search_base extends AbstractPlugin implements SingletonInterface
{
    public $prefixId = TT_PRODUCTS_EXT;
    public $extKey = TT_PRODUCTS_EXT;	// The extension key.
    public $bRunAjax = false;		// overrride this

    public function setContentObjectRenderer(ContentObjectRenderer $cObj): void
    {
        $this->cObj = $cObj;
    }

    public function getContentObjectRenderer(): ContentObjectRenderer
    {
        return $this->cObj;
    }

    /**
     * Main method. Call this from TypoScript by a USER cObject.
     */
    public function main($content, $conf)
    {
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
        $parameterApi->setPrefixId($this->prefixId);
        PluginApi::init($conf);

        $typo3VersionArray =
        VersionNumberUtility::convertVersionStringToArray(VersionNumberUtility::getCurrentTypo3Version());
        $typo3VersionMain = $typo3VersionArray['version_main'];
        $confMain = $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.typoscript')->getSetupArray()['plugin.'][TT_PRODUCTS_EXT . '.'] ?? [];

        $this->conf = array_merge($confMain, $conf);
        $config = [];
        $mainObj = GeneralUtility::makeInstance('tx_ttproducts_control_search');	// fetch and store it as persistent object
        $errorCode = [];
        $bDoProcessing =
            $mainObj->init(
                $this->conf,
                $config,
                $this->getRequest(),
                $this->cObj,
                get_class($this),
                $errorCode
            );

        if ($bDoProcessing || !empty($errorCode)) {
            $content =
                $mainObj->run(
                    $this->cObj,
                    get_class($this),
                    $errorCode,
                    $content
                );
        }

        return $content;
    }

    public function set($bRunAjax): void
    {
        $this->bRunAjax = $bRunAjax;
    }

    private function getRequest(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }
}
