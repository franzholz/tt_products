<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2011 Franz Holzinger (franz@ttproducts.de)
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


class tx_ttproducts_field_media_view extends tx_ttproducts_field_base_view {

	public function getImageCode ($imageConf, $theCode) {
        $local_cObj = \JambageCom\TtProducts\Api\ControlApi::getCObj();

        $contentObject = 'IMAGE';
        $imageCode =
            $local_cObj->getContentObject($contentObject)->render($imageConf); // neu

		if ($theCode == 'EMAIL' && $GLOBALS['TSFE']->absRefPrefix == '') {
			$absRefPrefix = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
			$fixImgCode = str_replace('index.php', $absRefPrefix . 'index.php', $imageCode);
			$fixImgCode = str_replace('src="', 'src="' . $absRefPrefix, $fixImgCode);
			$fixImgCode = str_replace('"uploads/', '"' . $absRefPrefix . 'uploads/', $fixImgCode);
			$imageCode = $fixImgCode;
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
		&$markerArray,
		&$imageConf,
		&$row
	)	{
        $local_cObj = \JambageCom\Div2007\Utility\FrontendUtility::getContentObjectRenderer();
        $parser = $local_cObj;
        if (
            defined('TYPO3_version') &&
            version_compare(TYPO3_version, '7.0.0', '>=')
        ) {
            $parser = tx_div2007_core::newHtmlParser(false);
        }
		$confArray = array('params', 'altText', 'titleText');
		if (is_array($markerArray) && !count($markerArray))	{
			$this->getExtItemMarkerArray($markerArray, $imageConf, $row);
		}
		foreach ($confArray as $conftype)	{
			if ($imageConf[$conftype])	{
				$text = $imageConf[$conftype];
				$text = $parser->substituteMarkerArray($text, $markerArray);
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
		&$row
	)	{
		$markerArray['###IMAGE_FILE###'] = $imageConf['file'];

		foreach ($row as $field => $val)	{
			$key = '###IMAGE_'.strtoupper($field).'###';
			$markerArray[$key] = $val;
		}
	}


	/* returns the key for the tag array and marker array without leading and ending '###' */
	public function getMarkerkey (
		&$imageMarkerArray,
		$markerKey,
		$imageName,
		$c = 1,
		$suffix=''
	)	{
		$keyArray = array();
		$keyArray[] = $markerKey;
		if ($suffix)	{
			$keyArray[] = $suffix;
		}
		if (is_array($imageMarkerArray))	{
			$imageNameArray = GeneralUtility::trimExplode('_', $imageName);
			$partsArray = GeneralUtility::trimExplode(',', $imageMarkerArray['parts']);
			foreach ($partsArray as $k2 => $part)	{
				$keyArray[] = $imageNameArray[$part-1];
			}
		}
		$tmp = implode('_', $keyArray);
		$tmpArray = GeneralUtility::trimExplode('.',$tmp);
		reset($tmpArray);
		$key = current($tmpArray);

		if (!is_array($imageMarkerArray))	{
			$key .= $c;
		}
		return $key;
	}


	public function getCodeMarkerArray (
		$functablename,
		$markerKey,
		$theCode,
		&$imageRow,
		&$imageArray,
		$dirname,
		$mediaNum,
		$imageRenderObj,
		$linkWrap,
		&$markerArray,
		&$specialConf
	)	{
		$cObj = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);	// Local cObj.
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$theTableObj = $tablesObj->get($functablename);
		$theTablename = $theTableObj->getTablename();
		$cObj->start($imageRow, $theTablename);

		$imgCodeArray = array();
		$tableConf = array();
		$markerArray['###'.$markerKey.'_PATH###'] = $dirname;

		if (is_array($imageArray) && count($imageArray))	{
			$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
			$tableConf = $cnf->getTableConf($functablename, $theCode);
			if (is_array($tableConf))	{
				$imageMarkerArray = $tableConf['imageMarker.'];
			}
			$imageConfStart = $this->conf[$imageRenderObj . '.'];
			$contentObject = $this->conf[$imageRenderObj];
			if ($contentObject == '') {
				$contentObject = 'IMAGE';
			}

			if ($linkWrap && $imageConfStart['imageLinkWrap'])	{
				$imageConfStart['imageLinkWrap'] = 0;
				unset($imageConfStart['imageLinkWrap.']);
				$imageConfStart['wrap'] = $linkWrap;
			}
			if ($linkWrap === false)	{
				$imageConfStart['imageLinkWrap'] = 0;
			}

			// first loop to get the general markers used also for replacement inside of JavaScript in the setup
			foreach($imageArray as $c => $val)	{
				if ($c == $mediaNum)	{
					break;
				}
				if (!$this->conf['separateImage']) {
					$key = 0;  // show all images together as one image
				} else {
					$key = ($val ? $val : $c);
				}
				$tagkey = '';
				if ($val)	{
					$tagkey = $this->getMarkerkey($imageMarkerArray, $markerKey, $key, $c + 1);

					$filetagkey = $this->getMarkerkey($imageMarkerArray, $markerKey, $key, $c + 1, 'FILE');
					$markerArray['###'.$filetagkey.'###'] = $val;
				}
			}

			foreach($imageArray as $c => $val)	{

				$imageConf = $imageConfStart;
				if ($c == $mediaNum)	{
					break;
				}
				$bUseImage = false;
				$meta = false;
				if ($val)	{
					$imageConf['file'] = $dirname.$val;
					$bUseImage = true;
				}

				if (!$this->conf['separateImage']) {
					$key = 0;  // show all images together as one image
				} else {
					$key = ($val ? $val : $c);
				}

				$tagkey = '';
				if ($val)	{
					$tagkey = $this->getMarkerkey($imageMarkerArray, $markerKey, $key, $c + 1);
				}

				$cObj->alternativeData = ($meta ? $meta : $imageRow);
				$imageConf['params'] = preg_replace('/\s+/',' ',$imageConf['params']);
				$this->replaceMarkerArray($markerArray, $imageConf, $cObj->alternativeData);
				$tmpImgCode = $this->getImageCode($imageConf, $theCode);

				if ($tmpImgCode != '')	{
					$imgCodeArray[$key] .= $tmpImgCode;
				}

				if ($tagkey && is_array($specialConf[$tagkey]))	{
					foreach ($specialConf[$tagkey] as $specialConfType => $specialImageConf)	{
						$theImageConf = array_merge($imageConf, $specialImageConf);
						$cObj->alternativeData = ($meta ? $meta : $imageRow); // has to be redone here
						$this->replaceMarkerArray($markerArray, $theImageConf, $cObj->alternativeData);
						$tmpImgCode = $this->getImageCode($theImageConf, $theCode);
						$key1 = $key . ':' . $specialConfType;
						$imgCodeArray[$key1] .= $tmpImgCode;
					}
				}
			}	// foreach
		} else if ($this->conf['noImageAvailable']!='') {	// if (count($imageArray))
			$imageConf = $this->conf[$imageRenderObj.'.'];
			$imageConf['file'] = $this->conf['noImageAvailable'];
			$tmpImgCode = $this->getImageCode($imageConf, $theCode);
			$imgCodeArray[0] = $tmpImgCode;
		}

		if (!$this->conf['separateImage']) {
			if (isset($tableConf['joinedImagesWrap.'])) {
 				$imgCodeArray[0] = $cObj->stdWrap($imgCodeArray[0], $tableConf['joinedImagesWrap.']);
			}
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
		$bHtml=true,
		$charset='',
		$prefix='',
		$suffix='',
		$imageRenderObj='image'
	)	{
		$imageRow = $row;
		$bImages = false;
		$dirname = '';
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$tableConf = $cnf->getTableConf($functablename, $theCode);
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');

			// Get image
		$specialImgCode = array();
		if (is_array($tableConf))	{
			$imageMarkerArray = $tableConf['imageMarker.'];
		}
		$imgs = array();
		$imageField = 'image';
		if ($functablename == 'pages')	{
			$imageField = 'media';
		}

		if (is_array($tableConf['fetchImage.']) &&
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

		if (!$bImages)	{
			$fieldconfParent = array();
			if (is_array($tableConf))	{
				$tempConf = '';
				if	(
					is_array($tableConf['generateImage.']) &&
					$tableConf['generateImage.']['type'] == 'foreigntable'
				)	{
					$tempConf = &$tableConf['generateImage.'];
				}

				if (is_array($tempConf) && $imageRow)	{
					$conftable = $tempConf['table'];
					$localfield = $tempConf['uid_local'];
					$foreignfield = $tempConf['uid_foreign'];
					$fieldconfParent['generateImage'] = $tempConf['field.'];
					$where_clause = $conftable.'.'.$foreignfield .'='. $imageRow[$localfield];
                    $enableFields = \JambageCom\Div2007\Utility\TableUtility::enableFields($conftable);
					$where_clause .= $enableFields;
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*',$conftable,$where_clause,'',$foreignfield,1);
						// only first found row will be used
					$imageRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				}
			}

			// $confParentTableConf = $this->getTableConf($conftable, $theCode);
			$conftable = ($conftable ? $conftable : $functablename);
			$generateArray = array('generateImage', 'generatePath');
			$nameArray = array();
			$conftableConf = $cnf->getTableConf($conftable, $theCode);

			foreach ($generateArray as $k => $generate)	{
				if (is_array($conftableConf) &&
				 	is_array($conftableConf[$generate.'.'])) {
				 	$genPartArray = $conftableConf[$generate.'.'];
				 	$tableFieldsCode = '';

				 	if ($genPartArray['type'] == 'tablefields')	{
				 		$nameArray[$generate] = '';
				 		$fieldConf = $genPartArray['field.'];

						if (is_array($fieldConf))	{
							if (is_array($fieldconfParent[$generate]))	{
								$fieldConf = array_merge($fieldConf, $fieldconfParent[$generate]);
							}

							foreach ($fieldConf as $field => $count)	{
								if ($imageRow[$field])	{
									$nameArray[$generate] .= substr($imageRow[$field], 0, $count);

									if ($generate == 'generateImage')	{
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

			if ($nameArray['generateImage'] && is_dir($dirname))	{
				$directory = dir($dirname);
				$separator = '_';

				if (
					is_array($conftableConf) &&
					is_array($conftableConf['generateImage.'])
				) {
					$separator = $conftableConf['separator'];
				}

				while($entry = $directory->read()) {
					if (strstr($entry, $nameArray['generateImage'] . $separator) !== false)	{
						$imgs[] = $entry;
					}
				}
				$directory->close();
			}

			if (is_array($imgs) && count($imgs))	{
				$bImages = true;
			}
		} // if (!$bImages) {

		if (!$bImages)	{
            $imgs = $this->getModelObj()->getFileArray($theTablename, $imageRow, $fieldname, true);
		}

		$specialConf = array();
		$tempImageConf = '';

		if (is_array($tableConf) &&
			is_array($tableConf['image.']))	{
			$tempImageConf = &$tableConf['image.'];
		}

		if (is_array($tempImageConf))	{
			foreach ($tagArray as $key => $value)	{
				$keyArray = GeneralUtility::trimExplode (':', $key);
				$specialConfType = strtolower($keyArray[1]);
				$tagKey = $keyArray[0];
				if ($specialConfType &&
					(!is_array($specialConf[$tagKey]) || !isset($specialConf[$tagKey][$specialConfType]) ) &&
					is_array($tempImageConf[$specialConfType.'.'])
				) {

					// add the special configuration
					if (!is_array($specialConf[$tagKey]))	{
						$specialConf[$tagKey] = array();
					}
					$specialConf[$tagKey][$specialConfType] = &$tempImageConf[$specialConfType.'.'];
				}
			}
		}

		if ($dirname != '') {
			$dirname .= '/';
		} else {
			$dirname = $this->getModelObj()->getDirname($imageRow);
		}

		$theImgCode = $this->getCodeMarkerArray($functablename, $markerKey, $theCode, $imageRow, $imgs, $dirname, $mediaNum, $imageRenderObj, $linkWrap, $markerArray, $specialConf);
		$actImgCode = current($theImgCode);
		$markerArray['###'.$markerKey.'###'] = $actImgCode ? $actImgCode : ''; // for compatibility only

		$c = 1;
		$countArray = array();

		foreach($theImgCode as $k1 => $val) {

			$bIsSpecial = true;
			if (strstr($k1, ':') === false)	{
				$bIsSpecial = false;
			} else {
				$c--; // the former index mus be used again
			}
			$key = $markerKey . intval($c);

 			if ($bIsSpecial)	{
				$keyArray = GeneralUtility::trimExplode(':', $k1);
				$count = $countArray[$keyArray[0]];
				$key =  $markerKey . intval($count);

				if (isset($count) && is_array($specialConf[$key]) && isset($specialConf[$key][$keyArray[1]]) && is_array($specialConf[$key][$keyArray[1]]))	{
					$combkey = $key.':'.strtoupper($keyArray[1]);
					if (isset($tagArray[$combkey]))	{
						$markerArray['###'.$combkey.'###'] = $val;
					}
				}
			} else {
				if (isset($tagArray[$key]))	{
					$markerArray['###'.$key.'###'] = $val;
				}
				$countArray[$k1] = $c;
			}

			$c++;
		} // foreach

		$bImageMarker = false;
		if (is_array($tableConf) &&
			is_array($tableConf['imageMarker.']) &&
			$tableConf['imageMarker.']['type'] == 'imagename' )	{
			$bImageMarker = true;
		}

		if ($bImageMarker)	{
			foreach ($theImgCode as $imageName => $imgValue)	{
				$nameArray = GeneralUtility::trimExplode(':', $imageName);
				$suffix = ($nameArray[1] ? ':'.$nameArray[1] : '');
				$tagkey = $this->getMarkerkey($imageMarkerArray, $markerKey, $imageName).strtoupper($suffix);
				if (isset($tagArray[$tagkey]))	{
					$markerArray['###'.$tagkey.'###'] = $imgValue;
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
		&$bSkip,
		$bHtml=true,
		$charset='',
		$prefix='',
		$suffix='',
		$imageRenderObj='image'
	)	{
		if ($bHtml) {
			$bSkip = true;

			if ($fieldname == 'smallimage') {
				$imageRenderObj = 'smallImage';
			}
			$mediaMarkerKeyArray = array();

			if (isset($tagArray) && is_array($tagArray)) {
				foreach ($tagArray as $value => $k1)	{
					if (strpos($value, $markerKey) !== false)	{
						$keyMarker = '###'.$value.'###';
						$foundPos = strpos($value, $markerKey.'_ID');

						if ($foundPos !== false)	{
							$c = substr($value, strlen($markerKey.'_ID'));
							$markerArray[$keyMarker] = $id.'-'.$c;
						} else {
							$mediaMarkerKeyArray[] = $keyMarker;
						}

						// empty all image fields with no available image
						if (!isset($markerArray[$keyMarker]))	{
							$markerArray[$keyMarker] = '';
						}
					}
				}
			}

			if (is_array($mediaMarkerKeyArray) && count($mediaMarkerKeyArray))	{
				$mediaNum =
					$this->getModelObj()->getMediaNum(
						$functablename,
						$fieldname,
						$theCode
					);

				if ($mediaNum)	{

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


