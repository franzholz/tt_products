<?php

namespace JambageCom\TtProducts\Controller;

/***************************************************************
*  Copyright notice
*
*  (c) 2007-2016 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * main class for taxajax
 *
 * @author  Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TaxajaxController
{
    /**
     * @return ResponseInterface
     */
    public function processRequest(
        ServerRequestInterface $request
    ) {
        // ******************************************************
        // Start with tt_products
        // ******************************************************
        if (method_exists($GLOBALS['TSFE'], 'getConfigArray')) {
            $GLOBALS['TSFE']->getConfigArray($request);
        }

        $conf = $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.typoscript')->getSetupArray()['plugin.'][TT_PRODUCTS_EXT . '.'] ?? null;

        if (!isset($conf)) {
            throw new \RuntimeException('Error in tt_products: No plugin setup found!', 1720723255);
        }

        $config = [];
        $config['LLkey'] = '';

        // tt_products specific parts

        // Make instance:
        $ajax = GeneralUtility::makeInstance('tx_ttproducts_ajax');
        $ajax->init();

        $SOBE = GeneralUtility::makeInstance('tx_ttproducts_db');
        $errorCode = '';
        $tmp = '';
        $cObj = GeneralUtility::makeInstance('TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');	// Local cObj.
        $cObj->start([]);

        $SOBE->init($conf, $config, $ajax, $tmp, $cObj, $errorCode);

        if (!empty($_POST['xajax'])) {
            $ajax->taxajax->processRequests();

            $SOBE->destruct();
            exit;
        }
        $SOBE->main();
        $SOBE->printContent();
        $SOBE->destruct();

        return new NullResponse();
    }
}
