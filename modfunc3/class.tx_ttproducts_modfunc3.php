<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2015 Franz Holzinger (franz@ttproducts.de)
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * Module extension (addition to function menu3 'Import images into FAL' for the 'tt_products' extension.
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @package	TYPO3
 * @subpackage	tx_ttproducts
 */
class tx_ttproducts_modfunc3 extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule {
	public $localLangFile = 'locallang.xml';

	const ORIGINAL_DIRECTORY = 'uploads/pics/';
	const TARGET_DIRECTORY = 'user_upload/';

	/**
	* Returns the module menu
	*
	* @return	Array with menuitems
	*/
	public function modMenu () {
		return Array (
		);
	}

	/**
	* Main method of the module
	*
	* @return	HTML
	*/
	public function main () {
		$content = '';

			// Initializes the module. Done in this function because we may need to re-initialize if data is submitted!
		$theOutput .= $this->pObj->doc->spacer(5);
		if ($GLOBALS['BE_USER']->user['admin']) {
			$content = $GLOBALS['LANG']->getLL('warning') . '<br /><br />';
			$currId = $this->pObj->id;
			$content .= sprintf($GLOBALS['LANG']->getLL('pid_src'), $currId);
		} else {
			$content = $GLOBALS['LANG']->getLL('only_admin');
		}
		$theOutput .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('title'), $content, 0, 1);

		if($_REQUEST['import'] != '' && !empty($currId)) {
			/** @var $storageRepository \TYPO3\CMS\Core\Resource\StorageRepository */
			$storageRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
			$storage = $storageRepository->findByUid(1);
			$targetDirectory = self::TARGET_DIRECTORY;
            if (!$storage->hasFolder('/' . $targetDirectory)) {
                $targetFolder = $storage->createFolder('/' . $targetDirectory);
			}
			/** @var $fileRepository \TYPO3\CMS\Core\Resource\FileRepository */
			$fileRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository');

			$content = '';
			$pid = intval($currId);
			$tableMediaArray = array(
				'tt_products' => array('image', 'smallimage'),
				'tt_products_language' => array('image'),
				'tt_products_cat' => array('image'),
				'tt_products_articles' => array('image'),
			);
			foreach ($tableMediaArray as $tablename => $imageFieldnameArray) {
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

							if ($row[$imageFieldname] != '') {
								$imageArray = explode(',', $row[$imageFieldname]);
								$sysfileRowArray = array();

								if (intval($row[$imageFalFieldname]) != 0) {

									$where_clause = 'uid_foreign=' . intval($row['uid']) . ' AND tablenames="' . $tablename . '" AND fieldname="' . $imageFalFieldname . '"' ;
									$where_clause .= \JambageCom\Div2007\Utility\TableUtility::deleteClause('sys_file_reference');

									$sysfileRowArray =
										$GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
											'*',
											'sys_file_reference',
											$where_clause,
											'',
											'sorting',
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

                                        if (
                                            version_compare(TYPO3_version, '9.0.0', '>=')
                                        ) {
                                            $path = \TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/';
                                        } else {
                                            $path = = PATH_site;
                                        }

										if (!file_exists($path . $targetFileName)) {
											$fullSourceFileName = $path . self::ORIGINAL_DIRECTORY . $image;
											// Move the file to the storage and index it (indexing creates the sys_file entry)
											$file = $storage->addFile($fullSourceFileName, $targetFolder, '', 'cancel');
											$fileRepository->addToIndex($file);
										}

										$fac = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ResourceFactory'); // create instance to storage repository

										$file = $fac->getFileObjectFromCombinedIdentifier($fileIdentifier);

										if ($file instanceof \TYPO3\CMS\Core\Resource\File) {
											$fileUid = $file->getUid();

											if (!isset($sysfileRowArray[$fileUid])) {
												$data = array();
												$data['sys_file_reference']['NEW1234'] = array(
													'uid_local' => $fileUid,
													'uid_foreign' => $row['uid'], // uid of your table record
													'tablenames' => $tablename,
													'fieldname' => $imageFalFieldname,
													'pid' => $pid, // parent id of the parent page
													'table_local' => 'sys_file',
												);

												if (is_array($sysfileRowArray) && count($sysfileRowArray)) {
													$needsCountUpdate = true;
												} else {
													$data[$tablename][$row['uid']] = array($imageFalFieldname => 'NEW1234');
												}

												/** @var \TYPO3\CMS\Core\DataHandling\DataHandler $tce */
												$tce = GeneralUtility::makeInstance('TYPO3\CMS\Core\DataHandling\DataHandler'); // create TCE instance
												$tce->start($data, array());
												$tce->process_datamap();

												if ($tce->errorLog) {
													$content .= 'TCE->errorLog:' . \TYPO3\CMS\Core\Utility\DebugUtility::viewArray($tce->errorLog);
												} else {
												// nothing
												}
											}
										}
									} // foreach ($imageArray

									if ($needsCountUpdate) {
										$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
											$tablename,
											'uid=' . intval($row['uid']),
											array($imageFalFieldname => $imageCount)
										);
									}
								}
							}
						}
					}
				}
			}

			$theOutput = $GLOBALS['LANG']->getLL('finished');
		}

		if ($GLOBALS['BE_USER']->user['admin']) {
			$menu = array();
			$content = '';
			$content .= '<br /><input type="submit" name="import" value="' . $GLOBALS['LANG']->getLL('start') . '">';
			$menu[] = $content;
			$theOutput .= $this->pObj->doc->spacer(5);
			$theOutput .= $this->pObj->doc->section('Menu', implode(' - ' , $menu) , 0, 1);
		}

		return $theOutput;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/modfunc3/class.tx_ttproducts_modfunc3.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/modfunc3/class.tx_ttproducts_modfunc3.php']);
}
