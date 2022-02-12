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
 * functions for digital medias view
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use JambageCom\Div2007\Utility\FrontendUtility;


class tx_ttproducts_field_media_view extends tx_ttproducts_field_base_view {

	public function getImageCode ($imageConf, $theCode, $domain = '') {
        $cObj = \JambageCom\TtProducts\Api\ControlApi::getCObj();

        $contentObject = 'IMAGE';
        $imageCode =
            $cObj->getContentObject($contentObject)->render($imageConf);

		if ($theCode == 'EMAIL') {
            FrontendUtility::fixImageCodeAbsRefPrefix(
                $imageCode,
                $domain
            );
        } else {
            $imageCode = str_replace('"fileadmin', '"/fileadmin', $imageCode);
        }

		return $imageCode;
	}


	/**
	 * replaces a text string with its markers
	 * used for JavaScript functions
	 *
	 * @access private
	 */
	protected function replaceMarkerArray (
		$markerArray,
		$row,
		$bUseExtItemMarkerArray,
		&$imageConf
	) {
        $templateService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Service\MarkerBasedTemplateService::class);
		$this->getExtItemMarkerArray($markerArray, $imageConf, $row);

		foreach ($imageConf as $conftype => $text) {

			if ($text != '') {
				if (is_array($text)) {
					$bModifiedArray = [];
					foreach ($text as $k1 => $v1) {
						$level = 0;
						$bModifiedArray[$level] = false;
						if (isset($v1) && is_array($v1)) {
							foreach ($v1 as $k2 => $v2) {
								$level = 1;
								$bModified = false;
								if (isset($v2) && is_array($v2)) {
									foreach ($v2 as $k3 => $v3) {
										$level = 2;
										$bModified = false;
										if (isset($v3) && is_array($v3)) {
                                            foreach ($v3 as $k4 => $v4) {
                                                $level = 3;
                                                $bModified = false;
                                                if (isset($v4) && is_array($v4)) {
                                                    foreach ($v4 as $k5 => $v5) {
                                                        $level = 4;
                                                        $bModified = false;
                                                        if (isset($v5) && is_array($v5)) {
                                                            foreach ($v5 as $k6 => $v6) {
                                                                $level = 5;
                                                                $bModified = false;
                                                                if (isset($v6) && is_array($v6)) {
                                                                    foreach ($v6 as $k7 => $v7) {
                                                                        $level = 6;
                                                                        $bModified = false;
                                                                        if (isset($v7) && is_array($v7)) {
                                                                            // TODO
                                                                        } else if (str_contains($v7, '###')) {
                                                                            $v7 = $templateService->substituteMarkerArray($v7, $markerArray);
                                                                            $bModifiedArray[$level] = true;
                                                                        }
                                                                        if (!empty($bModifiedArray['6'])) {
                                                                            $text[$k1][$k2][$k3][$k4][$k5][$k6][$k7] = $v7;
                                                                        }
                                                                    }
                                                                } else if (str_contains($v6, '###')) {
                                                                    $v6 = $templateService->substituteMarkerArray($v6, $markerArray);
                                                                    $bModifiedArray[$level] = true;
                                                                }
                                                                if (!empty($bModifiedArray['5'])) {
                                                                    $text[$k1][$k2][$k3][$k4][$k5][$k6] = $v6;
                                                                }
                                                            }
                                                        } else if (str_contains($v5, '###')) {
                                                            $v5 = $templateService->substituteMarkerArray($v5, $markerArray);
                                                            $bModifiedArray[$level] = true;
                                                        }
                                                        if (!empty($bModifiedArray['4'])) {
                                                            $text[$k1][$k2][$k3][$k4][$k5] = $v5;
                                                        }
                                                    }
                                                } else if (str_contains($v4, '###')) {
                                                    $v4 = $templateService->substituteMarkerArray($v4, $markerArray);
                                                    $bModifiedArray[$level] = true;
                                                }
                                                if (!empty($bModifiedArray['3'])) {
                                                    $text[$k1][$k2][$k3][$k4] = $v4;
                                                }
                                            }
										} else if (str_contains($v3, '###')) {
											$v3 = $templateService->substituteMarkerArray($v3, $markerArray);
											$bModifiedArray[$level] = true;
										}
										if (!empty($bModifiedArray['2'])) {
											$text[$k1][$k2][$k3] = $v3;
										}
									}
								} else if (str_contains($v2, '###')) {
									$v2 = $templateService->substituteMarkerArray($v2, $markerArray);
									$bModifiedArray[$level] = true;
								}
								if (!empty($bModifiedArray['1'])) {
									$text[$k1][$k2] = $v2;
								}
							}
						} else if (str_contains($v1, '###')) {
							$v1 = $templateService->substituteMarkerArray($v1, $markerArray);
							$bModifiedArray[$level] = true;
						}
					}
					if (!empty($bModifiedArray['0'])) {
						$text[$k1] = $v1;
					}
				} else {
					$text = $templateService->substituteMarkerArray($text, $markerArray);
				}
				$imageConf[$conftype] = $text;
			}
		}
	}


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @return	array		Returns a markerArray ready for substitution with information
	 * 				for the tt_products record, $row
	 * @access private
	 */
	protected function getExtItemMarkerArray (
		&$markerArray,
		$imageConf,
		$row
	) {
		$markerArray['###IMAGE_FILE###'] = $imageConf['file'];

		foreach ($row as $field => $val) {
			$key = '###IMAGE_' . strtoupper($field) . '###';
			if (!isset($markerArray[$key])) {
				$markerArray[$key] = $val;
			}
		}
	}


	/* returns the key for the tag array and marker array without leading and ending '###' */
	public function getMarkerkey (
		&$imageMarkerArray,
		$markerKey,
		$imageName,
		$noMarkerArraySuffix = 1,
		$suffix = ''
	) {
		$keyArray = array();
		$keyArray[] = $markerKey;
		$imageNameUsed = false;

		if (
			is_array($imageMarkerArray) &&
			isset($imageMarkerArray['parts']) &&
			!empty($imageMarkerArray['parts']) &&
			$imageMarkerArray['type'] == 'imagename'
		) {
			$imageNameUsed = true;
		}

		if ($suffix) {
			$keyArray[] = $suffix;
		}

		if ($imageNameUsed) {
			$imageNameArray = GeneralUtility::trimExplode('_', $imageName);
			$partsArray = GeneralUtility::trimExplode(',', $imageMarkerArray['parts']);
			foreach ($partsArray as $k2 => $part) {
				if (isset($imageNameArray[$part - 1])) {
					$keyArray[] = strtoupper($imageNameArray[$part - 1]);
				}
			}
		}
		$tmp = implode('_', $keyArray);
		$tmpArray = GeneralUtility::trimExplode('.', $tmp);
		reset($tmpArray);
		$key = current($tmpArray);

		if (!$imageNameUsed) {
			$key .= $noMarkerArraySuffix;
		}
		return $key;
	}


	public function getCodeMarkerArray (
		$functablename,
		$markerKey,
		$theCode,
		$imageRow,
		$imageArray,
		$dirname,
		$mediaNum = 0,
		$imageRenderObj,
		$linkWrap,
		&$markerArray,
		&$mediaRowArray,
		&$specialConf
	) {
		$cObj = GeneralUtility::makeInstance('TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');	// Local cObj.
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$theTableObj = $tablesObj->get($functablename);
		$theTablename = $theTableObj->getTablename();
		$cObj->start($imageRow, $theTablename);
		$tableConf = array();

		$imgCodeArray = array();
		$markerArray['###' . $markerKey . '_PATH###'] = $dirname;
		$markerArray['###PATH###'] = $dirname;

		if (is_array($imageArray) && count($imageArray)) {
			$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
			$tableConf = $cnf->getTableConf($functablename, $theCode);
			if (is_array($tableConf) && isset($tableConf['imageMarker.'])) {
				$imageMarkerArray = $tableConf['imageMarker.'];
			}
			$imageConfStart = $this->conf[$imageRenderObj . '.'];
			$contentObject = $this->conf[$imageRenderObj] ?? '';
			if ($contentObject == '') {
				$contentObject = 'IMAGE';
			}

			if ($linkWrap && $imageConfStart['imageLinkWrap']) {
				$imageConfStart['imageLinkWrap'] = 0;
				unset($imageConfStart['imageLinkWrap.']);
				$imageConfStart['wrap'] = $linkWrap;
			}

			if ($linkWrap === false) {
				$imageConfStart['imageLinkWrap'] = 0;
			}

			// first loop to get the general markers used also for replacement inside of JavaScript in the setup
			$count = 0;
			foreach($imageArray as $c => $val) {
				if ($count == $mediaNum) {
					break;
				}

				if (!$this->conf['separateImage']) {
					$key = 0;  // show all images together as one image
				} else if (is_array($val)) {
					$key = $val['name'];
				} else {
					$key = (!empty($val) ? $val : $count);
				}
				$tagkey = '';
				if ($val) {
					$filetagkey =
						$this->getMarkerkey(
							$imageMarkerArray,
							$markerKey,
							$key,
							$count + 1,
							'FILE'
						);

					$filename = '';
					if (is_array($val)) {
						if (isset($val['name'])) {
							$filename = $val['name'];
						}
					} else {
						$filename = $val;
					}
					$markerArray['###' . $filetagkey . '###'] = $filename;
				}
				$count++;
			}

			$count = 0;
			foreach($imageArray as $c => $val) {
				$imageConf = $imageConfStart;
				$imageConfFile = $imageConf['file'];
				if ($count == $mediaNum) {
					break;
				}
				$bUseImage = false;
				$meta = false;
				if (!empty($val)) {
					$filename = '';
					if (is_array($val)) {
						if (isset($val['name'])) {
							$filename = 'fileadmin' . $val['identifier'];
						}
					} else {
						$filename = $dirname . $val;
					}
					$imageConfFile = $filename;
					$bUseImage = true;
				}

				if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('dam') && $bUseImage && $bImages) {
					$damObj = GeneralUtility::makeInstance('tx_dam');
					if(method_exists($damObj,'meta_getDataForFile')) {
						$fieldList = 'uid,pid,tstamp,crdate,active,media_type,title,category,index_type,file_mime_type,file_mime_subtype,
							file_type,file_type_version,file_name,file_path,file_size,file_mtime,file_inode,file_ctime,file_hash,file_status,
							file_orig_location,file_orig_loc_desc,file_creator,file_dl_name,file_usage,meta,ident,creator,
							keywords,description,alt_text,caption,abstract,search_content,language,pages,publisher,copyright,
							instructions,date_cr,date_mod,loc_desc,loc_country,loc_city,hres,vres,hpixels,vpixels,color_space,
							width,height,height_unit';
						$meta = $damObj->meta_getDataForFile($imageConfFile, $fieldList);
					}
				}

				if (!$this->conf['separateImage']) {
					$key = 0;  // show all images together as one image
				} else if (is_array($val)) {
					$key = $val['name'];
				} else {
					$key = (!empty($val) ? $val : $count);
				}
				$tagkey = '';
				if (!empty($val)) {
					$tagkey =
						$this->getMarkerkey(
							$imageMarkerArray,
							$markerKey,
							$key,
							$count + 1
						);
				}
				if (is_array($val)) {
					$meta = $val;
				}

				$cObj->alternativeData = ($meta ? $meta : $imageRow);
				if (isset($imageConf['params'])) {
                    $imageConf['params'] = preg_replace('/\s+/', ' ', $imageConf['params']);
                }

				$bGifBuilder = isset($imageConf['file']) && ($imageConf['file'] == 'GIFBUILDER');
				$imageConf['file'] = $imageConfFile;
				$filename = '';
				if (is_array($val)) {
					$filename = $imageConfFile;
				} else {
					$filename = $val;
				}

				$markerArray['###FILE###'] = $filename;

				$this->replaceMarkerArray(
					$markerArray,
					$cObj->alternativeData,
					$meta,
					$imageConf
				);

				if ($bGifBuilder) {
					$imageConf['file'] = 'GIFBUILDER';
				}

				$imageCode = '';
				$imageCode = $cObj->getContentObject($contentObject)->render($imageConf);

				if (
					$theCode == 'EMAIL' &&
					$GLOBALS['TSFE']->absRefPrefix == ''
				) {
                    $domain = $this->conf['domain'];
                    FrontendUtility::fixImageCodeAbsRefPrefix(
                        $imageCode,
                        $domain
                    );
                } else {
                    $imageCode = str_replace('"fileadmin', '"/fileadmin', $imageCode);
                }

				if ($imageCode != '') {
                    if (!isset($imgCodeArray[$key])) {
                        $imgCodeArray[$key] = '';
                    }
					$imgCodeArray[$key] .= $imageCode;
				}
				if ($meta) {
					$mediaRowArray[$key] = $meta;
				}

				if ($tagkey && isset($specialConf[$tagkey])) {
					foreach ($specialConf[$tagkey] as $specialConfType => $specialImageConf) {
						$theImageConf = array_merge($imageConf, $specialImageConf);
						$cObj->alternativeData = ($meta ? $meta : $imageRow); // has to be redone here

						$this->replaceMarkerArray(
							$markerArray,
							$cObj->alternativeData,
							$meta,
							$theImageConf
						);

						if ($theImageConf['file'] != 'GIFBUILDER') {

							$theImageConf['file'] = $imageConfFile;
						}
						$tmpImgCode = $cObj->getContentObject($contentObject)->render($theImageConf);

						$key1 = $key . ':' . $specialConfType;
						$imgCodeArray[$key1] .= $tmpImgCode;
					}
				}
				$count++;
			}	// foreach
		} else if (
			$this->conf['noImageAvailable'] != '' &&
			$this->conf['noImageAvailable'] != '{$plugin.tt_products.file.noImageAvailable}'
		) {	// if (count($imageArray))
			$imageConf = $this->conf[$imageRenderObj . '.'];
			$imageConf['file'] = $this->conf['noImageAvailable'];
            $tmpImgCode = $this->getImageCode($imageConf, $theCode);
			$imgCodeArray[0] = $tmpImgCode;
		}

		if (
			!$this->conf['separateImage'] &&
			isset($tableConf['joinedImagesWrap.'])
		) {
 			$imgCodeArray[0] =
				$cObj->stdWrap(
					$imgCodeArray[0],
					$tableConf['joinedImagesWrap.']
				);
		}

		return $imgCodeArray;
	}


	private function getMediaMarkerArray (
		$functablename,
		$fieldname,
		&$row,
		$mediaNum,
		$markerKey,
		&$markerArray,
		$tagArray,
		$theCode,
		$id,
		&$bSkip,
		$bHtml = true,
		$charset = '',
		$prefix = '',
		$suffix = '',
		$imageRenderObj = 'image'
	) {
		$imageRow = $row;
		$bImages = false;
		$imageMarkerArray = array();
		$dirname = '';
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$tableConf = $cnf->getTableConf($functablename, $theCode);
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$theTableObj = $tablesObj->get($functablename);
		$theTablename = $theTableObj->getTablename();
		$cObj = FrontendUtility::getContentObjectRenderer();

			// Get image
		$mediaRowArray = array();
		$specialImgCode = array();
		if (
			is_array($tableConf) &&
			isset($tableConf['imageMarker.'])
		) {
			$imageMarkerArray = $tableConf['imageMarker.'];
		}
		$imgs = array();
		$imageField = 'image';
		if ($functablename == 'pages') {
			$imageField = 'media';
		}

		if (isset($tableConf['fetchImage.']) &&
			$tableConf['fetchImage.']['type'] == 'foreigntable'  &&
			isset($tableConf['fetchImage.']['table'])) {
			$pageContent = $tablesObj->get($tableConf['fetchImage.']['table'])->getFromPid($pid);
			foreach ($pageContent as $pid => $contentRow) {
				if ($contentRow[$imageField]) {
					$imgs[] = $contentRow[$imageField];
				}
			}
			$bImages = true;
		}

		if (!$bImages) {
			$fieldconfParent = array();
			if (is_array($tableConf)) {
				$tempConf = '';
				if (
					isset($tableConf['generateImage.']) &&
					$tableConf['generateImage.']['type'] == 'foreigntable'
				) {
					$tempConf = &$tableConf['generateImage.'];
				}

				$conftable = '';
				if (is_array($tempConf) && $imageRow) {
					$conftable = $tempConf['table'];
					$localfield = $tempConf['uid_local'];
					$foreignfield = $tempConf['uid_foreign'];
					$fieldconfParent['generateImage'] = $tempConf['field.'];
					$where_clause = $conftable . '.' . $foreignfield . '=' . $imageRow[$localfield];
					$enableFields = \JambageCom\Div2007\Utility\TableUtility::enableFields($conftable);
					$where_clause .= $enableFields;
					$res =
						$GLOBALS['TYPO3_DB']->exec_SELECTquery(
							'*',
							$conftable,
							$where_clause,
							'',
							$foreignfield,
							1
						);
						// only first found row will be used
					$imageRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				}
			}

			// $confParentTableConf = $this->getTableConf($conftable, $theCode);
			$conftable = ($conftable ? $conftable : $functablename);
			$generateArray = array('generateImage', 'generatePath');
			$nameArray = array();

			$conftableConf = $cnf->getTableConf($conftable, $theCode);

			foreach ($generateArray as $k => $generate) {
				if (is_array($conftableConf) &&
				 	isset($conftableConf[$generate . '.'])) {
				 	$genPartArray = $conftableConf[$generate . '.'];
				 	$tableFieldsCode = '';

				 	if ($genPartArray['type'] == 'tablefields') {
				 		$nameArray[$generate] = '';
						if ($genPartArray['prefix'] != '')	{
							$nameArray[$generate] = $genPartArray['prefix'];
						}
				 		$fieldConf = $genPartArray['field.'];

						if (is_array($fieldConf)) {
							if (isset($fieldconfParent[$generate]) && is_array($fieldconfParent[$generate])) {
								$fieldConf = array_merge($fieldConf, $fieldconfParent[$generate]);
							}

							foreach ($fieldConf as $field => $count) {
								if ($imageRow[$field]) {
									$nameArray[$generate] .= substr($imageRow[$field], 0, $count);
									if ($generate == 'generateImage') {
										$bImages = true;
									}
								}
							}
				 		}
				 	}

					if ($generate == 'generatePath') {
						$dirname = $conftableConf['generatePath.']['base'];
						if ($dirname != '' && $nameArray['generatePath'] != '') {
							$dirname .= '/';
						}
						$dirname .= $nameArray['generatePath'];
					}
				}
			}

			if (!empty($nameArray['generateImage']) && is_dir($dirname)) {
				$directory = dir($dirname);
				$separator = '_';

				if (
					is_array($conftableConf) &&
					is_array($conftableConf['generateImage.'])
				) {
					$separator = $conftableConf['separator'];
				}

				while($entry = $directory->read()) {
					if (strpos($entry, $nameArray['generateImage'] . $separator) !== false) {
						$imgs[] = $entry;
					}
				}
				$directory->close();
			}

			if (is_array($imgs) && count($imgs)) {
				$bImages = true;
			}
		} // if (!$bImages) {

		if (!$bImages) {
			$imgs = $this->getModelObj()->getFileArray($theTablename, $imageRow, $fieldname, true);
		}

		$specialConf = array();
		$tempImageConf = '';

		if (is_array($tableConf) &&
			is_array($tableConf['image.'])) {
			$tempImageConf = &$tableConf['image.'];
		}

		if (is_array($tempImageConf)) {
			foreach ($tagArray as $key => $value) {
				$keyArray = GeneralUtility::trimExplode (':', $key);
				$specialConfType = strtolower($keyArray[1]);
				$tagKey = $keyArray[0];
				if ($specialConfType &&
					(
						!is_array($specialConf[$tagKey]) ||
						!isset($specialConf[$tagKey][$specialConfType])
					) &&
					is_array($tempImageConf[$specialConfType . '.'])
				) {
					// add the special configuration
					if (!is_array($specialConf[$tagKey])) {
						$specialConf[$tagKey] = array();
					}
					$specialConf[$tagKey][$specialConfType] = &$tempImageConf[$specialConfType . '.'];
				}
			}
		}

		if ($dirname != '') {
			$dirname .= '/';
		} else {
			$dirname = $this->getModelObj()->getDirname($imageRow);
		}

		$linkWrap = false;
		$theImgCode =
			$this->getCodeMarkerArray(
				$functablename,
				$markerKey,
				$theCode,
				$imageRow,
				$imgs,
				$dirname,
				$mediaNum,
				$imageRenderObj,
				$linkWrap,
				$markerArray,
				$mediaRowArray,
				$specialConf
			);

		$actImgCode = current($theImgCode);
		$markerArray['###'.$markerKey.'###'] = $actImgCode ? $actImgCode : ''; // for compatibility only

		$c = 1;
		$countArray = array();

		foreach($theImgCode as $k1 => $val) {

			$bIsSpecial = true;
			if (strstr($k1, ':') === false) {
				$bIsSpecial = false;
			} else {
				$c--; // the former index mus be used again
			}
			$key = $markerKey . intval($c);

 			if ($bIsSpecial) {
				$keyArray = GeneralUtility::trimExplode(':', $k1);
				$count = $countArray[$keyArray[0]];
				$key =  $markerKey . intval($count);

				if (
					isset($count) &&
					is_array($specialConf[$key]) &&
					isset($specialConf[$key][$keyArray[1]]) &&
					is_array($specialConf[$key][$keyArray[1]])
				) {
					$combkey = $key.':'.strtoupper($keyArray[1]);

					if (isset($tagArray[$combkey])) {
						$markerArray['###' . $combkey . '###'] = $val;
					}
				}
			} else {
				if (isset($tagArray[$key])) {
					$markerArray['###' . $key . '###'] = $val;
				}
				$countArray[$k1] = $c;
			}

			if (is_array($mediaRowArray[$k1])) {

				foreach ($mediaRowArray[$k1] as $field => $val2) {
					$key1 = $key . '_' . strtoupper($field);
					if (isset($tagArray[$key1])) {
						$markerArray['###' . $key1 . '###'] = $val2;
					}
				}
			}
			$c++;
		} // foreach

		$bImageMarker = false;
		if (
			!empty($imageMarkerArray) &&
			isset($imageMarkerArray['type']) &&
			!empty($imageMarkerArray['type'])
		) {
			$bImageMarker = true;
		}

		if ($bImageMarker) {
			$k = 0;
			foreach ($theImgCode as $imageName => $imgValue) {
				$k++;
				$suffix = $k;
				if ($imageMarkerArray['type'] == 'imagename') {
					$suffix = '';
				}

				$tagkey =
					$this->getMarkerkey(
						$imageMarkerArray,
						$markerKey,
						$imageName,
						$suffix
					);

				if ($imageMarkerArray['type'] == 'imagename') {
					$nameArray = GeneralUtility::trimExplode(':', $imageName);
					$tagkey .= (empty($nameArray) || empty($nameArray['1']) ? '' : ':' . $nameArray['1']);
				}

				if (isset($tagArray[$tagkey])) {
					$markerArray['###' . $tagkey . '###'] = $imgValue;
				}

				if (is_array($mediaRowArray[$imageName])) {
					foreach ($mediaRowArray[$imageName] as $field => $val2) {
						$key1 = $tagkey . '_' . strtoupper($field);
                        $markerArray['###' . $key1 . '###'] = $val2;
					}
				}
			}
		}
	}


	public function getRowMarkerArray (
		$functablename,
		$fieldname,
		$row,
		$markerKey,
		&$markerArray,
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
		$mediaNum = 0,
		$imageRenderObj = 'image',
		$bEnableTaxZero = false
	) {
		if ($bHtml) {

			$bSkip = true;
			if (strpos($fieldname, 'smallimage') !== false) {
				$imageRenderObj = 'smallImage';
			}
			$mediaMarkerKeyArray = array();

			if (isset($tagArray) && is_array($tagArray)) {
				foreach ($tagArray as $value => $k1) {
					if (strpos($value, $markerKey) !== false) {
						$keyMarker = '###' . $value . '###';
						$foundPos = strpos($value, $markerKey . '_ID');

						if ($foundPos !== false) {
							$c = substr($value, strlen($markerKey . '_ID'));
							$markerArray[$keyMarker] = $id . '-' . $c;
						} else {
							$mediaMarkerKeyArray[] = $keyMarker;
						}

						// empty all image fields with no available image
						if (!isset($markerArray[$keyMarker])) {
							$markerArray[$keyMarker] = '';
						}
					}
				}
			}

			if (is_array($mediaMarkerKeyArray) && count($mediaMarkerKeyArray)) {
				// example: plugin.tt_products.conf.tt_products.ALL.limitImage = 10
				if (!$mediaNum) {
					$mediaNum =
						$this->getModelObj()->getMediaNum(
							$functablename,
							$fieldname,
							$theCode
						);
				}

				if ($mediaNum) {

					$this->getMediaMarkerArray(
						$functablename,
						$fieldname,
						$row,
						$mediaNum,
						$markerKey,
						$markerArray,
						$tagArray,
						$theCode,
						$id,
						$bSkip,
						$bHtml,
						$charset,
						$prefix,
						$suffix,
						$imageRenderObj
					);
				}
			}
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_media_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_media_view.php']);
}


