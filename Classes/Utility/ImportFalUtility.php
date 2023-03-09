<?php

namespace JambageCom\TtProducts\Utility;

/***************************************************************
*  Copyright notice
*
*  (c) 2017 Franz Holzinger <franz@ttproducts.de>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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
 * functions for the import of images into FAL
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;


class ImportFalUtility {
    const ORIGINAL_DIRECTORY = 'uploads/pics/';
    const TARGET_DIRECTORY   = 'user_upload/';

    static public function importAll (
        &$infoArray,
        $currId
    ) {
        $result = true;
        $infoArray = [];

        if (!$currId) {
            return false;
        }

        /** @var $storageRepository \TYPO3\CMS\Core\Resource\StorageRepository */
        $storageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
        $storage = $storageRepository->findByUid(1);
        $targetDirectory = self::TARGET_DIRECTORY;
        $targetFolder = null;
        if (!$storage->hasFolder('/' . $targetDirectory)) {
            $targetFolder = $storage->createFolder('/' . $targetDirectory);
        } else {
            $targetFolder = $storage->getFolder('/' . $targetDirectory);
        }
        $content = '';
        $pid = intval($currId);
        $tableMediaArray = [
            'tt_products' => ['image', 'smallimage'],
            'tt_products_language' => ['image'],
            'tt_products_cat' => ['image'],
            'tt_products_articles' => ['image'],
        );

        foreach ($tableMediaArray as $tablename => $imageFieldnameArray) {
            $imageCount = 0;
            foreach ($imageFieldnameArray as $imageFieldname) {
                $imageFalFieldname =  $imageFieldname . '_uid';

                $rowArray =
                    $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                        'uid,' . $imageFieldname . ',' . $imageFalFieldname,
                        $tablename,
                        'pid=' . $pid . ' AND deleted=0'
                    );

                if (is_array($rowArray) && count($rowArray)) {

                    foreach ($rowArray as $k => $row) {
    //                  if ($k != 3) continue; // Test

                        if (!empty($row[$imageFieldname])) {
                            $imageArray = explode(',', $row[$imageFieldname]);
                            $sysfileRowArray = [];
                            if (intval($row[$imageFalFieldname]) != 0) {

                                $where_clause = 'uid_foreign=' . intval($row['uid']) . ' AND tablenames="' . $tablename . '" AND fieldname="' . $imageFalFieldname . '"' ;
                                $where_clause .= \JambageCom\Div2007\Utility\TableUtility::deleteClause('sys_file_reference');

                                $sysfileRowArray =
                                    $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                                        '*',
                                        'sys_file_reference',
                                        $where_clause,
                                        '',
                                        'sorting_foreign',
                                        '',
                                        'uid_local'
                                    );
                            }

                            if (!empty($imageArray)) {
                                $imageCount = count($imageArray);
                                $needsCountUpdate = false;
                                foreach ($imageArray as $imageKey => $image) {
                                    $imageFile = '/user_upload/' . $image;
                                    $fileIdentifier = '1:' . $imageFile;

                                    // Check if the file is already known by FAL, if not add it
                                    $targetFileName = 'fileadmin/' . $targetDirectory . $image;
//                                      $file = $storage->getFile($targetFileName);

//                                      if (!($file instanceof \TYPO3\CMS\Core\Resource\File)) {

                                    if (!file_exists(\TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/' . $targetFileName)) {
                                        $fullSourceFileName = \TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/' . self::ORIGINAL_DIRECTORY . $image;
                                        // Move the file to the storage and index it (indexing creates the sys_file entry)
                                        $file = $storage->addFile($fullSourceFileName, $targetFolder, '', 'cancel');
//                                         $fileRepository->addToIndex($file);
                                    }

                                    $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
                                    $file = $resourceFactory->getFileObjectFromCombinedIdentifier($fileIdentifier);
                                    if ($file instanceof \TYPO3\CMS\Core\Resource\File) {
                                        $fileUid = $file->getUid();
    //                                  $properties = $file->getProperties();
    //                                  $fileInfo = $storage->getFileInfo($file);

                                        if (
                                            empty($sysfileRowArray) ||
                                            !isset($sysfileRowArray[$fileUid])
                                        ) {
                                            $data = [];
                                            $data['sys_file_reference']['NEW1234'] = [
                                                'uid_local' => $fileUid,
                                                'uid_foreign' => $row['uid'], // uid of your table record
                                                'tablenames' => $tablename,
                                                'fieldname' => $imageFalFieldname,
                                                'pid' => $pid, // parent id of the parent page
                                                'table_local' => 'sys_file',
                                            ];

                                            if (!empty($sysfileRowArray)) {
                                                $needsCountUpdate = true;
                                            } else {
                                                $data[$tablename][$row['uid']] = [$imageFalFieldname => 'NEW1234'];
                                            }

                                            /** @var \TYPO3\CMS\Core\DataHandling\DataHandler $tce */
                                            $tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\DataHandling\DataHandler'); // create TCE instance
                                            $tce->start($data, []);
                                            $tce->process_datamap();

                                            if ($tce->errorLog) {
                                                $content .= 'TCE->errorLog:' . \TYPO3\CMS\Core\Utility\DebugUtility::viewArray($tce->errorLog);
                                            } else {
                                                // nothing
                                            }
                                        }
                                    }
                                    $imageCount++;
                                } // foreach ($imageArray

                                if ($needsCountUpdate) {
                                    $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
                                        $tablename,
                                        'uid=' . intval($row['uid']),
                                        [$imageFalFieldname => $imageCount]
                                    );
                                }
                            }
                        }
                    }
                }
            }
            $infoArray[$tablename] = $imageCount;
        } // foreach

        return $result;
    }
}

