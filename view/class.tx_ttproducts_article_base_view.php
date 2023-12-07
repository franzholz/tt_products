
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
 * functions for the product
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


use TYPO3\CMS\Core\Utility\GeneralUtility;



abstract class tx_ttproducts_article_base_view extends tx_ttproducts_table_base_view {
	private $dataArray = []; // array of read in products
	private $table;	 // object of the type tx_table_db

	public $tabledesc;
	public $fields = [];
	public $type; 	// the type of table 'article' or 'product'
			// this gets in lower case also used for the URL parameter
	public $variant;       // object for the product variant attributes, must initialized in the init function
	public $editVariant; 	// object for the product editable variant attributes, must initialized in the init function
	protected $mm_table = ''; // only set if a mm table is used
	protected $graduatedPriceObject = false;


	public function init ($modelObj) {
		$result = parent::init($modelObj);

		if ($result) {
			if (!isset($this->variant)) {
				$this->variant = GeneralUtility::makeInstance('tx_ttproducts_variant_dummy_view');
			}
			if (!isset($this->editVariant)) {
				$this->editVariant = GeneralUtility::makeInstance('tx_ttproducts_edit_variant_dummy_view');
			}

			$this->variant->init($modelObj->variant);
			$this->editVariant->init($modelObj->editVariant);

			$type = $modelObj->getType();
			if (
				$type == 'product' ||
				$type == 'article'
			) {
				$graduatedPriceViewObj =
					GeneralUtility::makeInstance('tx_ttproducts_graduated_price_view');
				$graduatedPriceObj = $modelObj->getGraduatedPriceObject();
				$graduatedPriceViewObj->init($graduatedPriceObj);
				$this->setGraduatedPriceObject($graduatedPriceViewObj);
			}
		}

		return $result;
	}

	public function setGraduatedPriceObject ($value) {
		$this->graduatedPriceObject = $value;
	}


	public function getGraduatedPriceObject () {
		return $this->graduatedPriceObject;
	}

	public function getEditVariant () {
		return $this->editVariant;
	}

	public function getVariant () {
		return $this->variant;
	}

	public function getItemMarkerSubpartArrays (
		$templateCode,
		$functablename,
		$row,
		&$markerArray,
		&$subpartArray,
		&$wrappedSubpartArray,
		$tagArray,
		$multiOrderArray = [],
		$productRowArray = [],
		$theCode = '',
		$basketExtra = [],
		$basketRecs = [],
		$iCount = ''
	) {
		$this->getItemSubpartArrays(
			$templateCode,
			$functablename,
			$row,
			$subpartArray,
			$wrappedSubpartArray,
			$tagArray,
			$theCode,
			$basketExtra,
			$basketRecs,
			$iCount
		);
	}

    public function getItemSubpartArrays (
        &$templateCode,
        $functablename,
        $row,
        &$subpartArray,
        &$wrappedSubpartArray,
        $tagArray,
        $theCode = '',
        $basketExtra = [],
        $basketRecs = [],
        $id = '',
        $checkPriceZero = false
    ) {
		parent::getItemSubpartArrays(
			$templateCode,
			$functablename,
			$row,
			$subpartArray,
			$wrappedSubpartArray,
			$tagArray,
			$theCode,
			$basketExtra,
			$basketRecs,
			$id
		);
	}


	public function getCurrentPriceMarkerArray (
		&$markerArray,
		$markerKey,
		$originalName,
		$originalRow,
		$mergedName,
		$mergedRow,
		$id,
		$theCode,
		$basketExtra,
		$basketRecs,
		$bEnableTaxZero = false,
        $notOverwritePriceIfSet = true
	) {
		if (is_array($mergedRow)) {
			$row = $mergedRow;
			if (is_array($originalRow) && count($originalRow)) {
				if ($mergedName != '') {
					$id .= 'from-' . str_replace('_', '-', $mergedName);
				}

				$row['uid'] = $originalRow['uid'];
				foreach ($originalRow as $k => $v) {
					if (!isset($row[$k])) {
						$row[$k] = $v;
					}
				}
			}
		} else {
			$row = $originalRow;
		}

		$this->getPriceMarkerArray(
			$basketExtra,
			$basketRecs,
			$markerArray,
			$row,
			$markerKey,
			$id,
			$theCode,
			$bEnableTaxZero,
            $notOverwritePriceIfSet
		);
	}


	public function getPriceMarkerArray (
		$basketExtra,
		$basketRecs,
		&$markerArray,
		$row,
		$markerKey,
		$id,
		$theCode,
		$bEnableTaxZero = false,
        $notOverwritePriceIfSet = true
	) {
		$modelObj = $this->getModelObj();
		$priceViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_price_view');
		$functablename = $modelObj->getFuncTablename();

		$mainId = $this->getId($row, $id, $theCode);

		foreach ($GLOBALS['TCA'][$functablename]['columns'] as $field => $fieldTCA) {
			if (strpos($field, 'price') === 0) {
				$priceViewObj->getModelMarkerArray(
					$functablename,
					$basketExtra,
					$basketRecs,
					$field,
					$row,
					$markerArray,
					$markerKey,
					$mainId,
					$bEnableTaxZero,
                    $notOverwritePriceIfSet
				);
			}
		}
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
	 * @return	array		Returns a markerArray ready for substitution with information
	 * 			 		for the tt_producst record, $row
	 * @access private
	 */
	public function getModelMarkerArray (
		$row,
		$markerKey,
		&$markerArray,
		$catTitle,
		$imageNum = 0,
		$imageRenderObj = 'image',
		$tagArray = [],
		$forminfoArray = [],
		$theCode = '',
		$basketExtra = [],
		$basketRecs = [],
		$id = '',
		$prefix = '',
		$suffix = '',
		$linkWrap = '',
		$bHtml = true,
		$charset = '',
		$hiddenFields = '',
		$multiOrderArray = [],
		$productRowArray = [],
		$bEnableTaxZero = false,
        $notOverwritePriceIfSet = true
	) {
		$modelObj = $this->getModelObj();
		$imageObj = GeneralUtility::makeInstance('tx_ttproducts_field_image_view');
		$cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
		$conf = $cnfObj->getConf();

		if ($markerKey) {
			$marker = $markerKey;
		} else {
			$marker = $this->getMarker();
		}

		if (!$marker) {
			return [];
		}
		$variantFieldArray = $modelObj->variant->getFieldArray();
		$variantMarkerArray = [];

		$this->getRowMarkerArray(
			$modelObj->getFuncTablename(),
			$row,
			$marker,
			$markerArray,
			$variantFieldArray,
			$variantMarkerArray,
			$tagArray,
			$theCode,
			$basketExtra,
			$basketRecs,
			$bHtml,
			$charset,
			$imageNum,
			$imageRenderObj,
			$id,
			$prefix,
			$suffix,
			$linkWrap,
			$bEnableTaxZero
		);

		$this->getPriceMarkerArray(
			$basketExtra,
			$basketRecs,
			$markerArray,
			$row,
			$markerKey,
			$id,
			$theCode,
			$bEnableTaxZero,
            $notOverwritePriceIfSet
		);
	}
}

