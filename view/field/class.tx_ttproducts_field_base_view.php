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
 * base class for all database table fields view classes
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class tx_ttproducts_field_base_view implements tx_ttproducts_field_view_int, SingletonInterface
{
    private bool $bHasBeenInitialised = false;
    public $modelObj;
    public $cObj;
    public $conf;		// original configuration
    public $config;		// modified configuration

    public function init($modelObj): void
    {
        $this->modelObj = $modelObj;
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $this->conf = $cnf->getConf();
        $this->config = $cnf->getConfig();

        $this->bHasBeenInitialised = true;
    }

    public function needsInit()
    {
        return !$this->bHasBeenInitialised;
    }

    public function getModelObj()
    {
        return $this->modelObj;
    }

    public function getRepeatedRowSubpartArrays(
        &$subpartArray,
        &$wrappedSubpartArray,
        $markerKey,
        $row,
        $fieldname,
        $key,
        $value,
        $tableConf,
        $tagArray
    ): bool {
        // overwrite this!
        return false;
    }

    public function getRepeatedRowMarkerArray(
        &$markerArray,
        $markerKey,
        $functablename,
        $row,
        $fieldname,
        $key,
        $value,
        $tableConf,
        $tagArray,
        $theCode = '',
        $id = '1'
    ): bool {
        // overwrite this!
        return false;
    }

    public function getRepeatedSubpartArrays(
        &$subpartArray,
        &$wrappedSubpartArray,
        $templateCode,
        $markerKey,
        $functablename,
        $row,
        $fieldname,
        $tableConf,
        $tagArray,
        $theCode = '',
        $id = '1'
    ) {
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $result = false;
        $newContent = '';
        $markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
        $upperField = strtoupper($fieldname);
        $templateAreaList = $markerKey . '_' . $upperField . '_LIST';
        $t = [];
        $t['listFrameWork'] = $templateService->getSubpart($templateCode, '###' . $templateAreaList . '###');
        $templateAreaSingle = $markerKey . '_' . $upperField . '_SINGLE';
        $t['singleFrameWork'] = $templateService->getSubpart($t['listFrameWork'], '###' . $templateAreaSingle . '###');

        if ($t['singleFrameWork'] != '') {
            $repeatedTagArray = $markerObj->getAllMarkers($t['singleFrameWork']);

            $value = $row[$fieldname];
            $valueArray = GeneralUtility::trimExplode(',', $value);

            if (isset($valueArray) && is_array($valueArray) && $valueArray['0'] != '') {
                $content = '';
                foreach ($valueArray as $key => $value) {
                    $repeatedMarkerArray = [];
                    $repeatedSubpartArray = [];
                    $repeatedWrappedSubpartArray = [];

                    $resultRowMarker = $this->getRepeatedRowMarkerArray(
                        $repeatedMarkerArray,
                        $markerKey,
                        $functablename,
                        $row,
                        $fieldname,
                        $key,
                        $value,
                        $tableConf,
                        $tagArray,
                        $theCode,
                        $id
                    );

                    $this->getRepeatedRowSubpartArrays(
                        $repeatedSubpartArray,
                        $repeatedWrappedSubpartArray,
                        $markerKey,
                        $row,
                        $fieldname,
                        $key,
                        $value,
                        $tableConf,
                        $tagArray
                    );

                    $newContent = $templateService->substituteMarkerArrayCached(
                        $t['singleFrameWork'],
                        $repeatedMarkerArray,
                        $repeatedSubpartArray,
                        $repeatedWrappedSubpartArray
                    );

                    $result = $resultRowMarker;
                    if ($result) {
                        $content .= $newContent;
                    }
                }

                $newContent = $templateService->substituteMarkerArrayCached(
                    $t['listFrameWork'],
                    [],
                    ['###' . $templateAreaSingle . '###' => $content],
                    []
                );
            }
        }
        $subpartArray['###' . $templateAreaList . '###'] = $newContent;

        return $result;
    }
}
