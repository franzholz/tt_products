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
 * url marker functions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use JambageCom\Div2007\Utility\FrontendUtility;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class tx_ttproducts_url_view implements SingletonInterface
{
    public $conf;
    public $urlArray;

    /**
     * Initialized the marker object
     * $basket is the TYPO3 default shopping basket array from ses-data.
     *
     * @param		array		array urls which should be overridden with marker key as index
     */
    public function init($conf)
    {
        $this->conf = $conf;
    }

    public function getSingleExcludeList($excludeList)
    {
        $excludeListArray = GeneralUtility::trimExplode(',', $excludeList);
        $singleExcludeListArray =
            [
                'article',
                'product',
                'variants',
                'dam',
                'fal',
            ];
        $singleExcludeListArray = [...$excludeListArray, ...$singleExcludeListArray];

        if (!$singleExcludeListArray[0]) {
            unset($singleExcludeListArray[0]);
        }
        $singleExcludeList = implode(',', $singleExcludeListArray);

        return $singleExcludeList;
    }

    public function setUrlArray($urlArray)
    {
        $this->urlArray = $urlArray;
    }

    /**
     * Adds link markers to a wrapped subpart array.
     */
    public function getWrappedSubpartArray(
        &$wrappedSubpartArray,
        $addQueryString = [],
        $css_current = '',
        $useBackPid = true
    ) {
        $cObj = FrontendUtility::getContentObjectRenderer();
        $commandArray =
            [
                'basket',
                'info',
                'payment',
                'finalize',
                'thanks',
                'search',
                'memo',
                'tracking',
                'billing',
                'delivery',
                'agb',
            ];

        foreach ($commandArray as $command) {
            $pidBasket = (!empty($this->conf['PID' . $command]) ? $this->conf['PID' . $command] : $GLOBALS['TSFE']->id);
            $pageLink = FrontendUtility::getTypoLink_URL(
                $cObj,
                $pidBasket,
                $this->getLinkParams(
                    '',
                    $addQueryString,
                    true,
                    $useBackPid
                )
            );
            $wrappedSubpartArray['###LINK_' . strtoupper($command) . '###'] =
                ['<a href="' . htmlspecialchars($pageLink) . '"' . $css_current . '>', '</a>'];
        }
    }

    /**
     * Adds URL markers to a markerArray.
     */
    public function addURLMarkers(
        $pidNext,
        $markerArray,
        $addQueryString = [],
        $excludeList = '',
        $useBackPid = true,
        $backPid = 0,
        $bExcludeSingleVar = true
    ) {
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $charset = 'UTF-8';
        $urlMarkerArray = [];
        $conf = [];
        $target = '';

        // disable caching as soon as someone enters products into the basket, enters user data etc.
        // $addQueryString['no_cache'] = 1;
        // Add's URL-markers to the $markerArray and returns it
        $basketPid = ($this->conf['PIDbasket'] ?: $GLOBALS['TSFE']->id);
        $formUrlPid = ($pidNext ?: $GLOBALS['TSFE']->id);
        $singleExcludeList = $this->getSingleExcludeList($excludeList);

        $useBackPid = ($useBackPid && $pidNext && $pidNext != $GLOBALS['TSFE']->id);

        $urlExcludeList = $excludeList;
        if (
            $formUrlPid != $GLOBALS['TSFE']->id &&
            $bExcludeSingleVar
        ) {
            $urlExcludeList = $singleExcludeList;
        }

        $urlConfig = [
            'FORM_URL' => [
                    'pid' => $formUrlPid,
                    'excludeList' => $urlExcludeList,
                ],
            'FORM_URL_CURRENT' => [
                    'pid' => $GLOBALS['TSFE']->id,
                    'excludeList' => $excludeList,
                ],
        ];

        foreach ($urlConfig as $markerKey => $keyConfig) {
            $url = FrontendUtility::getTypoLink_URL(
                $cObj,
                $keyConfig['pid'],
                $this->getLinkParams(
                    $keyConfig['excludeList'],
                    $addQueryString,
                    true,
                    $useBackPid,
                    $backPid
                ),
                $target,
                $conf
            );

            $urlMarkerArray['###' . $markerKey . '###'] = htmlspecialchars($url, ENT_NOQUOTES, $charset);
            $urlMarkerArray['###' . $markerKey . '_VALUE###'] =
                $url;
        }

        $commandArray =
            [
                'basket',
                'info',
                'payment',
                'finalize',
                'thanks',
                'search',
                'memo',
                'tracking',
                'billing',
                'delivery',
                'agb',
                'user1',
                'user2',
                'user3',
                'user4',
                'user5',
            ];

        foreach ($commandArray as $command) {
            $linkPid = (!empty($this->conf['PID' . $command]) ? $this->conf['PID' . $command] : $basketPid);
            $url = FrontendUtility::getTypoLink_URL(
                $cObj,
                $linkPid,
                $this->getLinkParams(
                    $singleExcludeList,
                    $addQueryString,
                    true,
                    $useBackPid,
                    $backPid
                ),
                $target,
                $conf
            );
            $urlMarkerArray['###FORM_URL_' . strtoupper($command) . '###'] =
                htmlspecialchars(
                    $url,
                    ENT_NOQUOTES,
                    $charset
                );
            $urlMarkerArray['###FORM_URL_' . strtoupper($command) . '_VALUE###'] =
                $url;
        }

        $urlMarkerArray['###FORM_URL_TARGET###'] = '_self';

        if (
            isset($this->urlArray) &&
            is_array($this->urlArray) &&
            !empty($this->urlArray)
        ) {
            foreach ($this->urlArray as $k => $urlValue) {
                $urlMarkerArray['###' . strtoupper($k) . '###'] = $urlValue;
            }
        }

        // Call all addURLMarkers hooks at the end of this method
        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['addURLMarkers']) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['addURLMarkers'])
        ) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['addURLMarkers'] as $classRef) {
                $hookObj = GeneralUtility::makeInstance($classRef);
                if (method_exists($hookObj, 'addURLMarkers')) {
                    $hookObj->addURLMarkers(
                        $cObj,
                        $pidNext,
                        $urlMarkerArray,
                        $addQueryString,
                        $excludeList,
                        $singleExcludeList,
                        $useBackPid,
                        $backPid,
                        $bExcludeSingleVar
                    );
                }
            }
        }

        if (isset($markerArray) && is_array($markerArray)) {
            $markerArray = array_merge($markerArray, $urlMarkerArray);
        } else {
            $markerArray = $urlMarkerArray;
        }

        return $markerArray;
    } // addURLMarkers

    /**
     * Returns a url for use in forms and links.
     */
    public function addQueryStringParam(&$queryString, $param, $bUsePrefix = false)
    {
        $piVars = tx_ttproducts_model_control::getPiVars();
        $prefixId = tx_ttproducts_model_control::getPrefixId();

        $temp = $piVars[$param] ?? '';
        $temp = ($temp ?: (GeneralUtility::_GP($param) && ($param != 'pid') ? GeneralUtility::_GP($param) : 0));

        if ($temp) {
            if ($bUsePrefix) {
                $queryString[$prefixId . '[' . $param . ']'] = $temp;
            } else {
                $queryString[$param] = $temp;
            }
        }
    }

    /**
     * Returns a url for use in forms and links.
     */
    public function getLinkParams(
        $excludeList = '',
        $addQueryString = [],
        $bUsePrefix = false,
        $useBackPid = true,
        $backPid = 0,
        $piVarSingle = 'product',
        $piVarCat = 'cat'
    ) {
        $prefixId = tx_ttproducts_model_control::getPrefixId();

        $queryString = [];
        if ($useBackPid) {
            if (!$backPid) {
                $backPid = $GLOBALS['TSFE']->id;
            }

            if (
                $bUsePrefix &&
                empty($addQueryString[$prefixId . '[backPID]'])
            ) {
                $queryString[$prefixId . '[backPID]'] = $backPid;
            } elseif (empty($addQueryString['backPID'])) {
                $queryString['backPID'] = $backPid;
            }
        }

        if ($excludeList != '' && $bUsePrefix) {
            $excludeArray = explode(',', $excludeList);
            foreach ($excludeArray as $k => $v) {
                $excludeArray[$k] = $prefixId . '[' . $v . ']';
            }
            $excludeList = implode(',', $excludeArray);
        }

        $this->addQueryStringParam($queryString, 'C', $bUsePrefix);
        if ($piVarSingle != '') {
            $this->addQueryStringParam($queryString, $piVarSingle, $bUsePrefix);
        }
        if ($piVarCat != '') {
            $this->addQueryStringParam($queryString, $piVarCat, $bUsePrefix);
        }

        $listPointerParam = tx_ttproducts_model_control::getPointerPiVar('LIST');
        $this->addQueryStringParam($queryString, $listPointerParam, $bUsePrefix);
        $catlistPointerParam = tx_ttproducts_model_control::getPointerPiVar('CATLIST');

        $this->addQueryStringParam($queryString, 'mode', false);
        $this->addQueryStringParam($queryString, $listPointerParam, $bUsePrefix);
        $this->addQueryStringParam($queryString, 'newitemdays', $bUsePrefix);
        $this->addQueryStringParam($queryString, 'searchbox', $bUsePrefix);
        $this->addQueryStringParam($queryString, 'sword', $bUsePrefix);

        if ($bUsePrefix) {
            $excludeListArray = [];
            $tmpArray = GeneralUtility::trimExplode(',', $excludeList);
            if (isset($tmpArray) && is_array($tmpArray) && $tmpArray['0']) {
                foreach ($tmpArray as $param) {
                    if (strpos($param, (string)$prefixId) === false) {
                        $excludeListArray[] = $prefixId . '[' . $param . ']';
                    }
                }
                if (count($excludeListArray)) {
                    $excludeList .= ',' . implode(',', $excludeListArray);
                }
            }
        }

        if (is_array($addQueryString)) {
            foreach ($addQueryString as $param => $value) {
                if ($bUsePrefix) {
                    $queryString[$prefixId . '[' . $param . ']'] = $value;
                } else {
                    $queryString[$param] = $value;
                }
            }
        }

        foreach ($queryString as $key => $val) {
            if (
                $val == '' ||
                (
                    strlen($excludeList) &&
                    GeneralUtility::inList($excludeList, $key)
                )
            ) {
                unset($queryString[$key]);
            }
        }

        // Call all getLinkParams hooks at the end of this method
        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['getLinkParams']) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['getLinkParams'])
        ) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['getLinkParams'] as $classRef) {
                $hookObj = GeneralUtility::makeInstance($classRef);
                if (method_exists($hookObj, 'getLinkParams')) {
                    $hookObj->getLinkParams(
                        $this,
                        $queryString,
                        $excludeList,
                        $addQueryString,
                        $bUsePrefix,
                        $useBackPid,
                        $piVarSingle,
                        $piVarCat
                    );
                }
            }
        }

        return $queryString;
    }
}
