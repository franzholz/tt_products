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
 * functions for the data sheets
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 */

use JambageCom\Div2007\Utility\FrontendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class tx_ttproducts_field_datafield_view extends tx_ttproducts_field_base_view
{
    protected function getFuncFieldname($row, $fieldname)
    {
        $result = false;
        $uidPosition = strpos($fieldname, '_uid');

        if (
            $uidPosition &&
            isset($row[$fieldname])
        ) {
            $result = ($uidPosition ? substr($fieldname, 0, $uidPosition) : $fieldname);
        }

        return $result;
    }

    public function getLinkArray(
        &$wrappedSubpartArray,
        $tagArray,
        $marker,
        $dirname,
        $dataFile,
        $fieldname,
        $tableConf
    ): void {
        $cObj = FrontendUtility::getContentObjectRenderer();
        if (isset($tagArray[$marker])) {
            if (
                isset($tableConf['fieldLink.']) &&
                is_array($tableConf['fieldLink.']) &&
                isset($tableConf['fieldLink.'][$fieldname . '.'])
            ) {
                $typolinkConf = $tableConf['fieldLink.'][$fieldname . '.'];
            } else {
                $typolinkConf = [];
            }
            $typolinkConf['parameter'] = ($dirname != '' ? $dirname . '/' : '') . $dataFile;
            $linkTxt = microtime();
            $typoLink = $cObj->typoLink($linkTxt, $typolinkConf);
            $wrappedSubpartArray['###' . $marker . '###'] = GeneralUtility::trimExplode($linkTxt, $typoLink);
        }
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
        $dirname = $this->modelObj->getDirname($row, $fieldname);
        $upperField = strtoupper($fieldname);
        $marker = $markerKey . '_LINK_' . $upperField;

        $this->getLinkArray(
            $wrappedSubpartArray,
            $tagArray,
            $marker,
            $dirname,
            $value,
            $fieldname,
            $tableConf
        );

        return true;
    }

    public function getItemSubpartArrays(
        &$templateCode,
        $markerKey,
        $funcTablename,
        &$row,
        $fieldname,
        $tableConf,
        &$subpartArray,
        &$wrappedSubpartArray,
        &$tagArray,
        $theCode = '',
        $basketExtra = [],
        $basketRecs = [],
        $id = '1'
    ): void {
        $upperField = '';
        $funcFieldname = $this->getFuncFieldname($row, $fieldname);
        if ($funcFieldname != '') {
            $dirname = '';
            $dataFileArray =
                $this->getModelObj()->getDataFileArray(
                    $funcTablename,
                    $row,
                    $fieldname
                );

            $this->getRepeatedSubpartArrays(
                $subpartArray,
                $wrappedSubpartArray,
                $templateCode,
                $markerKey,
                $funcTablename,
                $row,
                $fieldname,
                $tableConf,
                $tagArray,
                $theCode,
                $id
            );
            $upperField = strtoupper($funcFieldname);
            if (count($dataFileArray) && $dataFileArray[0]) {
                foreach ($dataFileArray as $k => $dataFile) {
                    $marker = $markerKey . '_LINK_' . $upperField . ($k + 1);
                    $this->getLinkArray(
                        $wrappedSubpartArray,
                        $tagArray,
                        $marker,
                        $dirname,
                        $dataFile,
                        $funcFieldname,
                        $tableConf
                    );
                }

                $marker = $markerKey . '_LINK_' . $upperField;

                $this->getLinkArray(
                    $wrappedSubpartArray,
                    $tagArray,
                    $marker,
                    $dirname,
                    $dataFileArray[0],
                    $fieldname,
                    $tableConf
                );
            }
        }

        if ($upperField != '') {
            // empty all image fields with no available image
            foreach ($tagArray as $value => $k1) {
                $keyMarker = '###' . $value . '###';
                if (
                    strpos((string) $value, $markerKey . '_LINK_' . $upperField) !== false &&
                    empty($wrappedSubpartArray[$keyMarker])
                ) {
                    $wrappedSubpartArray[$keyMarker] = ['<!--', '-->'];
                }
            }
        }
    }

    public function getRepeatedRowMarkerArray(
        &$markerArray,
        $markerKey,
        $funcTablename,
        $row,
        $fieldname,
        $key,
        $value,
        $tableConf,
        $tagArray,
        $theCode = '',
        $id = '1'
    ): bool {
        $dirname = $this->modelObj->getDirname($row, $fieldname);
        $upperField = strtoupper($fieldname);
        $marker = $markerKey . '_' . $upperField;

        $imageRenderObj = 'datasheetIcon';
        $imageConf = $this->conf[$imageRenderObj . '.'];

        $this->getSingleValueArray(
            $markerArray,
            $marker,
            $tagArray,
            $theCode,
            $imageConf,
            $dirname,
            $value
        );
        $marker1 = 'ICON_' . strtoupper($fieldname);
        $this->getIconMarker(
            $markerArray,
            $marker1,
            $tagArray,
            $theCode,
            'datasheetIcon',
            $value
        );

        return true;
    }

    public function getSingleValueArray(
        &$markerArray,
        $marker,
        $tagArray,
        $theCode,
        $imageConf,
        $dirname,
        $dataFile
    ): void {
        $imageConf['file'] = $dirname . '/' . $dataFile;
        // 		$iconImgCode = $this->cObj->IMAGE($imageConf);
        $imageObj = GeneralUtility::makeInstance('tx_ttproducts_field_image_view');
        $iconImgCode =
            $imageObj->getImageCode(
                $imageConf,
                $theCode
            );

        if (isset($tagArray[$marker])) {
            $markerArray['###' . $marker . '###'] = $iconImgCode; // new marker now
        }

        if (isset($tagArray[$marker . '_FILE'])) {
            $markerArray['###' . $marker . '_FILE###'] = basename($imageConf['file']);
        }
    }

    public function getIconMarker(
        &$markerArray,
        $marker,
        $tagArray,
        $theCode,
        $imageRenderObj,
        $filename
    ): void {
        $extensionPos = strrpos($filename, '.');
        $extension = '';
        if ($extensionPos !== false) {
            $extension = substr($filename, $extensionPos + 1);
        }

        if (!empty($imageRenderObj)) {
            $imageObj = GeneralUtility::makeInstance('tx_ttproducts_field_image_view');
            $imageConf = $this->conf[$imageRenderObj . '.'];

            if (isset($tagArray[$marker]) && isset($this->conf['datasheetIcon.'])) {
                $imageFilename = '';

                if ($this->conf['datasheetIcon.']['file'] != '{$plugin.tt_products.file.datasheetIcon}') {
                    $imageFilename = $this->conf['datasheetIcon.']['file'];
                }

                foreach ($this->conf['datasheetIcon.'] as $confKey => $confArray) {
                    if (
                        strpos($confKey, '.') == strlen($confKey) - 1 &&
                        isset($confArray) && is_array($confArray) &&
                        $confArray['fileext'] == $extension
                    ) {
                        $imageFilename = $confArray['file'];
                        break;
                    }
                }

                if ($imageFilename != '') {
                    $imageConf['file'] = $imageFilename;
                    $iconImgCode =
                        $imageObj->getImageCode(
                            $imageConf,
                            $theCode
                        );

                    $markerArray['###' . $marker . '###'] = $iconImgCode;
                } else {
                    $markerArray['###' . $marker . '###'] = '';
                }
            } else {
                $markerArray['###' . $marker . '###'] = '';
            }
        } elseif (isset($tagArray[$marker])) {
            $markerArray['###' . $marker . '###'] = '';
        }
    }

    /**
     * Template marker substitution
     * Fills in the markerArray with data for a product.
     *
     * @param	string		name of the marker prefix
     * @param	array		reference to an item array with all the data of the item
     * 				for the tt_producst record, $row
     *
     * @access private
     */
    public function getRowMarkerArray(
        $funcTablename,
        $fieldname,
        $row,
        $markerKey,
        &$markerArray,
        $fieldMarkerArray,
        $tagArray,
        $theCode,
        $id,
        $basketExtra,
        $basketRecs,
        &$bSkip,
        $bHtml = true,
        $charset = '',
        $prefix = '',
        $suffix = '',
        $imageNum = 0,
        $imageRenderObj = '',
        $linkWrap = false,
        $bEnableTaxZero = false
    ) {
        $funcFieldname = $this->getFuncFieldname($row, $fieldname);
        $val = $row[$fieldname];
        $dataFileArray = [];
        $fileArray = [];
        $marker1 = 'ICON_' . strtoupper($funcFieldname);
        $marker2 = $markerKey . '1';

        if (
            !empty($imageRenderObj) &&
            $val &&
            (isset($tagArray[$marker1]) || isset($tagArray[$marker2]))
        ) {
            $imageObj = GeneralUtility::makeInstance('tx_ttproducts_field_image_view');
            $imageConf = $this->conf[$imageRenderObj . '.'];

            if (isset($tagArray[$marker1]) && isset($this->conf['datasheetIcon.'])) {
                if ($this->conf['datasheetIcon.']['file'] != '{$plugin.tt_products.file.datasheetIcon}') {
                    $imageConf['file'] = $this->conf['datasheetIcon.']['file'];
                    if (isset($imageConf['imageLinkWrap'])) {
                        unset($imageConf['imageLinkWrap']);
                        if (isset($imageConf['imageLinkWrap.'])) {
                            unset($imageConf['imageLinkWrap.']);
                        }
                    }
                    $iconImgCode =
                        $imageObj->getImageCode(
                            $imageConf,
                            $theCode
                        );
                    $markerArray['###' . $marker1 . '###'] = $iconImgCode;
                } else {
                    $markerArray['###' . $marker1 . '###'] = '';
                }
            } else {
                $markerArray['###' . $marker1 . '###'] = '';
            }

            if (
                isset($tagArray[$markerKey]) ||
                isset($tagArray[$marker2])
            ) {
                //  alle Files holen
                $dataFileArray =
                    $this->getModelObj()->getDataFileArray(
                        $funcTablename,
                        $row,
                        $fieldname
                    );
                foreach ($dataFileArray as $dataFile) {
                    $fileArray[] = basename($dataFile);
                }
            }

            if (isset($tagArray[$markerKey])) {
                $markerArray['###' . $markerKey . '###'] = implode(',', $fileArray);
            }

            if (isset($tagArray[$marker2])) {
                $imageConf['file'] = $dataFileArray['0'];
                $iconImgCode =
                    $imageObj->getImageCode(
                        $imageConf,
                        $theCode
                    );
                $markerArray['###' . $marker2 . '###'] = $iconImgCode;
            }
        } else {
            if (isset($tagArray[$marker1])) {
                $markerArray['###' . $marker1 . '###'] = '';
            }
            if (isset($tagArray[$marker2])) {
                $markerArray['###' . $marker2 . '###'] = '';
            }
        }
    }
}
