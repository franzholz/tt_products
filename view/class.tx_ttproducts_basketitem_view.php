<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2011 Franz Holzinger (franz@ttproducts.de)
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
 * view functions for a basket item object
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */


use TYPO3\CMS\Core\Utility\GeneralUtility;



class tx_ttproducts_basketitem_view implements \TYPO3\CMS\Core\SingletonInterface {
	private $conf;
	private $config;
	public $basketExt; 	// basket
	public $basketVar = 'ttp_basket';
	public $pibaseClass;
	protected $itemObj;


	/**
	 * Initialized the basket, setting the deliveryInfo if a users is logged in
	 * $basket is the TYPO3 default shopping basket array from ses-data
	 *
	 * @param		string		  $fieldname is the field in the table you want to create a JavaScript for
	 * @return	  void
	 */
	public function init ($pibaseClass, &$basketExt, $itemObj)	{
		$this->pibaseClass = $pibaseClass;
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;
		$this->basketExt = &$basketExt;
		$this->itemObj = $itemObj;
	} // init


	public function getQuantityName (
		$uid,
		$callFunctableArray
	)	{
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');

		$funcQuantityMarker = '';
		foreach ($callFunctableArray as $callFunctablename)	{
			$funcMarker = $tablesObj->get($callFunctablename,true)->getMarker();
			$funcQuantityMarker .= '[###'.$funcMarker.'_UID###]';
		}

		$basketQuantityName = $this->basketVar . '[' . $uid . ']' . $funcQuantityMarker . '[quantity]';

		return $basketQuantityName;
	}


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	string		title of the category
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param	array		information about the parent HTML form
	 * @return	array
	 * @access private
	 */
	function &getItemMarkerArray (
		$functablename,
		&$item,
		&$markerArray,
		&$tagArray,
		&$hiddenText,
		$theCode='',
		$id='1',
		$bSelect=true,
		$charset='',
		$callFunctableArray=array()
	)	{
		$pibaseObj = GeneralUtility::makeInstance(''.$this->pibaseClass);
		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$viewTable = $tablesObj->get($functablename);
		$fieldArray = $viewTable->variant->getFieldArray();
		$keyAdditional = $viewTable->variant->getAdditionalKey();
		$selectableArray = $viewTable->variant->getSelectableArray();
		$bUseXHTML = $GLOBALS['TSFE']->config['config']['xhtmlDoctype'] != '';
        $imageObj = GeneralUtility::makeInstance('tx_ttproducts_field_image_view');

		$row = &$item['rec'];
		$uid = $row['uid'];
		$presetVariantArray = tx_ttproducts_control_product::getPresetVariantArray($uid);

		if ($theCode=='SINGLE' && empty($presetVariantArray) && $bSelect && $functablename=='tt_products')	{
			$articleNo = tx_ttproducts_control_product::getActiveArticleNo();

			if ($articleNo !== false)	{
				$articleObj = $tablesObj->get('tt_products_articles');
				$articleRow = $articleObj->get($articleNo);

				if (isset($fieldArray) && is_array($fieldArray))	{

					foreach($fieldArray as $k => $field)	{
						$variantRow = $row[$field];
						$prodTmpRow = GeneralUtility::trimExplode(';', $variantRow);
						$imageFileArray = '';

						if ($variantRow && $prodTmpRow[0])	{
							$key = array_search(trim($articleRow[$field]), $prodTmpRow, true);

							if ($key !== false)	{
								$presetVariantArray[$field] = $key;
							}
						}
					}
				}
			}
		}

			// Returns a markerArray ready for substitution with information for the tt_producst record, $row
		$extArray = $row['ext'];

		if (is_array($extArray) && is_array($extArray['tt_products']))	{
			$variant = $viewTable->variant->getVariantFromRow($row);
		}
		$hiddenText = '';
		$basketQuantityName = $this->getQuantityName($row['uid'], $callFunctableArray);
		$quantity = $item['count'];
		$showAmount = ($theCode == 'BASKET' ? 'basket' : $cnf->getBasketConf('view','showAmount'));
		$quantity = $this->itemObj->getQuantity($item, $showAmount);
		$radioInputArray = $basketObj->getRadioInputArray($row);
		$bUseRadioBox = is_array($radioInputArray) && count($radioInputArray) > 0 && $radioInputArray['name'] != '';

		if (isset($callFunctableArray) && is_array($callFunctableArray) && count($callFunctableArray))	{
			require_once (PATH_BE_ttproducts.'control/class.tx_ttproducts_control_basketquantity.php');

			$quantityMarker = '###';
			foreach ($callFunctableArray as $marker => $callFunctablename)	{
				$quantityMarker .= tx_ttproducts_control_basketquantity::getQuantityMarker($marker, $uid, '###' . $marker . '_UID###');
			}
			$quantityMarker .= '###';
		} else {
			$quantityMarker = $quantity ? $quantity : '';
		}

		$jsTableName = str_replace('_','-',$functablename);
		$markerArray['###FIELD_NAME###'] = ($bUseRadioBox && $radioInputArray['name'] != '' ? $radioInputArray['name'] : $basketQuantityName);
		$markerArray['###FIELD_QTY###'] = $quantityMarker;
		$markerArray['###FIELD_ID###'] = $jsTableName . '-' . strtolower($theCode) . '-id-' . $id;
		$markerArray['###BASKET_ID###'] = $id;
		$basketIntoPrefix = tx_ttproducts_model_control::getBasketIntoIdPrefix();
		$markerArray['###BASKET_INTO_ID###'] = $basketIntoPrefix . '-' . $row['uid'];
		$markerArray['###BASKET_INPUT###'] = '';
		$markerArray['###DISABLED###'] = ($row['inStock'] > 0 ? '' : 'disabled');
		$markerArray['###IN_STOCK_ID###'] = 'in-stock-id-' . $row['uid'];
		$markerArray['###BASKET_IN_STOCK###'] = $languageObj->getLabel( ($row['inStock'] > 0 ? 'in_stock' : 'not_in_stock'));

		$basketFile = '';
        if ($this->conf['basketPic']) {
            if (
                version_compare(TYPO3_version, '9.4.0', '>=')
            ) {
                $sanitizer = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Resource\FilePathSanitizer::class);
                $basketFile = $sanitizer->sanitize($this->conf['basketPic']);
            } else {
                $basketFile = $GLOBALS['TSFE']->tmpl->getFileName($this->conf['basketPic']);
            }
        }
		$markerArray['###IMAGE_BASKET_SRC###'] = $basketFile;
		$fileresource =  \JambageCom\Div2007\Utility\FrontendUtility::fileResource($basketFile);
		$markerArray['###IMAGE_BASKET###'] = $fileresource;

		if (isset($fieldArray) && is_array($fieldArray))	{

			$formConf = $cnf->getFormConf($theCode);

			foreach($fieldArray as $k => $field)	{
				$fieldConf = $GLOBALS['TCA'][$viewTable->getTableObj()->name]['columns'][$field];
				$fieldMarker = strtoupper($field);

				if (is_array($fieldConf))	{
					$text = '';
					$variantRow = $row[$field];
					$prodTmpRow = explode(';', $variantRow);
					$imageFileArray = '';

					if ($bSelect && $variantRow && $prodTmpRow[0])	{
						$selectConfKey = $viewTable->variant->getSelectConfKey($field);

						if (is_array($formConf) && is_array($formConf[$selectConfKey . '.']))	{
							$theFormConf = $formConf[$selectConfKey . '.'];
							if (
								isset($theFormConf['image.']) &&
								is_array($theFormConf['image.']) &&
								isset($theFormConf['imageImport.']) &&
								is_array($theFormConf['imageImport.']) &&
								isset($theFormConf['layout'])
							)	{
								$imageConf = $theFormConf['image.'];
								$imageFileArray = array();
								foreach ($prodTmpRow as $k2 => $variantVal)	{
									$tmpImgCode = '';
									foreach ($theFormConf['imageImport.'] as $k3 => $imageImport)	{

										if (is_array($imageImport['prod.']))	{
											if (isset($imageImport['sql.']))	{
												$bIsValid = tx_ttproducts_sql::isValid($row, $imageImport['sql.']['where']);
												if (!$bIsValid)	{
													continue;
												}
											}
											$imageFile = $imageImport['prod.'][$k2];
											$imagePath = $imageImport['path'];

											if ($imageFile != '')	{
												$imageConf['file'] = $imagePath . $imageFile;
                                                $tmpImgCode =
                                                    $imageObj->getImageCode(
                                                        $imageConf,
                                                        $theCode
                                                    ); // neu
											}
										}
									}
									$imageFileArray[] = $tmpImgCode;
								}
							}
						} else {
							$theFormConf = '';
						}
						$prodTranslatedRow = $prodTmpRow;
						$type='';
						$selectedKey='0';

						switch($selectableArray[$k])	{
							case 1:
								$type='select';
								break;
							case 2:
								$type='radio';
								break;
							case 3:
								$type='checkbox';
								$selectedKey='';
								if ($quantity > 0)	{
									$selectedKey=$variant;
								}
								break;
						}

						if (isset($presetVariantArray[$field]))	{
							$selectedKey=$presetVariantArray[$field];
						}
						$viewTable->getTableObj()->substituteMarkerArray($prodTranslatedRow);
						$dataArray = array();
						$layout = '';
						if (isset($theFormConf) && is_array($theFormConf))	{
							if (isset($theFormConf['layout']))	{
								$layout = $theFormConf['layout'];
							}
							if (isset($theFormConf['dataArray.']))	{
								$dataArray = $theFormConf['dataArray.'];
							}
						}

						if ($type != '') {
							$text = tx_ttproducts_form_div::createSelect(
								$languageObj,
								$prodTranslatedRow,
								$this->basketVar . '[' . $row['uid'] . '][' . $field . ']',
								$selectedKey,
								false,
								false,
								array(),
								$type,
								$dataArray,
								$layout,
								$imageFileArray
							);
						} else {
							$text = $variantRow;
						}
					} else {
						$prodTmpRow = $row;
						$viewTable->variant->modifyRowFromVariant($prodTmpRow, $variant);
						$text = $prodTmpRow[$field]; // $prodTmpRow[0];
					}

					$markerArray['###FIELD_' . $fieldMarker . '_NAME###'] = $this->basketVar . '[' . $row['uid'] . '][' . $field . ']';

					$markerArray['###FIELD_' . $fieldMarker . '_VALUE###'] = $row[$field];
					$markerArray['###FIELD_' . $fieldMarker . '_ONCHANGE'] = ''; // TODO:  use $forminfoArray['###FORM_NAME###' in something like onChange="Go(this.form.Auswahl.options[this.form.Auswahl.options.selectedIndex].value)"

					$markerKey = '###' . $viewTable->marker . '_' . $fieldMarker . '###';
					$markerArray[$markerKey] = $text;
					$markerKey = '###' . $viewTable->marker . '_' . $fieldMarker . '_FUNCTION1###';
					if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('taxajax')) {
						$markerArray[$markerKey] = 'doFetchRow(\'' . $functablename . '\',\'' . strtolower($theCode) . '\',' . $row['uid'] . ');';
					} else {
						$markerArray[$markerKey] = '';
					}
				}
			}
		}
		$prodAdditionalText['single'] = '';
		if ($bUseRadioBox)	{

			$params = 'type="' . $radioInputArray['type'] . '"';
			$params .= ($radioInputArray['params'] != '' ? ' ' . $radioInputArray['params'] : '');
			if ($radioInputArray['checked'] == $uid)	{
				$params .= ' ' . ($bUseXHTML ? 'checked="checked"' : 'checked');
			}
			$markerArray['###BASKET_INPUT###'] = tx_ttproducts_form_div::createTag('input', $radioInputArray['name'], $uid, $params);
		}
		if ($keyAdditional !== false) {
			$isSingleProduct = $viewTable->hasAdditional($row,'isSingle');
			if ($isSingleProduct)	{
				$message = $languageObj->getLabel('additional_single');
				$prodAdditionalText['single'] = $message . '<input type="checkbox" name="' . $basketQuantityName . '" ' . ($quantity ? 'checked="checked"':'') . 'onchange = "this.form[this.name+\'[1]\'].value=(this.checked ? 1 : 0);"' . ' value="1">';
				$hiddenText .= '<input type="hidden" name="'.$basketQuantityName.'[1]" value="'.($quantity ? '1' : '0') . '">';
			}

			$isImageProduct = $viewTable->hasAdditional($row,'isImage');
			$giftService = !$viewTable->hasAdditional($row,'noGiftService');
 		}

		if ($giftService)	{
			$basketAdditionalName = $this->basketVar . '[' . $row['uid'] . '][additional][' . md5($variant) . ']';
			$bGiftService = $this->basketExt[$row['uid']][$variant . '.']['additional']['giftservice'];
			$giftServicePostfix = '[giftservice]';
			$message = $languageObj->getLabel('additional_gift_service');
			$value = ($bGiftService ? '1' : '0');
			$prodAdditionalText['giftService'] = $message . '<input type="checkbox" name="' . $basketAdditionalName . $giftServicePostfix . '" ' . ($value ? 'checked="checked"':'') . 'onchange = "this.form[this.name+\'[1]\'].value=(this.checked ? 1 : 0);"' . ' value="' . $value . '">';
			$hiddenText .= '<input type="hidden" name="' . $basketAdditionalName . $giftServicePostfix . '[1]" value="' . $value . '">';
		} else {
			$prodAdditionalText['giftService'] = '';
		}

		$markerArray['###FIELD_NAME_BASKET###'] = $this->basketVar . '[' . $row['uid'] . '][' . md5($variant) . ']';
		$markerArray['###PRODUCT_ADDITIONAL_SINGLE###'] = $prodAdditionalText['single'];
		$markerArray['###PRODUCT_ADDITIONAL_GIFT_SERVICE###'] = $prodAdditionalText['giftService'];
		$markerArray['###PRODUCT_ADDITIONAL_GIFT_SERVICE_DISPLAY###'] = ($value ? '1' : '');
		if (isset($tagArray['PRODUCT_HIDDEN_TEXT'])) {
			$markerArray['###PRODUCT_HIDDEN_TEXT###'] = $hiddenText;
			$hiddenText = '';
		}
	} // getItemMarkerArray
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_basketitem_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_basketitem_view.php']);
}



