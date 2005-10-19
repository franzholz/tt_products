<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Part of the tt_products (Shopping System) extension.
 *
 * view functions
 *
 * $Id$
 *
 * @author	Kasper Sk�rh�j <kasperYYYY@typo3.com>
 * @author	Ren� Fritz <r.fritz@colorcube.de>
 * @author	Klaus Zierer <zierer@pz-systeme.de>
 * @author	Els Verberne <verberne@bendoo.nl>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_price_div.php');


class tx_ttproducts_view_div {


	/**
	 * Adds URL markers to a markerArray
	 */
	function addURLMarkers($markerArray)	{
		global $TSFE;

			// Add's URL-markers to the $markerArray and returns it
		$pid = ( $this->conf['PIDbasket'] ? $this->conf['PIDbasket'] : $TSFE->id);
		$markerArray['###FORM_URL###'] = $this->pi_getPageLink($pid,'',tx_ttproducts_view_div::getLinkParams()) ;	 // $this->getLinkUrl($this->conf['PIDbasket']);
		$pid = ( $this->conf['PIDinfo'] ? $this->conf['PIDinfo'] : ($this->conf['PIDbasket'] ? $this->conf['PIDbasket'] :$TSFE->id));
		$markerArray['###FORM_URL_INFO###'] = $this->pi_getPageLink($pid,'',tx_ttproducts_view_div::getLinkParams()) ; // $this->getLinkUrl($this->conf['PIDinfo'] ? $this->conf['PIDinfo'] : $this->conf['PIDbasket']);
		$pid = ( $this->conf['PIDfinalize'] ? $this->conf['PIDfinalize'] : ($this->conf['PIDbasket'] ? $this->conf['PIDbasket'] : $TSFE->id));
		$markerArray['###FORM_URL_FINALIZE###'] = $this->pi_getPageLink($pid,'',tx_ttproducts_view_div::getLinkParams()) ;// $this->getLinkUrl($this->conf['PIDfinalize'] ? $this->conf['PIDfinalize'] : $this->conf['PIDbasket']);
		$pid = ( $this->conf['PIDthanks'] ? $this->conf['PIDthanks'] : ($this->conf['PIDbasket'] ? $this->conf['PIDbasket'] : $TSFE->id));
		$markerArray['###FORM_URL_THANKS###'] = $this->pi_getPageLink($pid,'',tx_ttproducts_view_div::getLinkParams()) ;	 // $this->getLinkUrl($this->conf['PIDthanks'] ? $this->conf['PIDthanks'] : $this->conf['PIDbasket']);
		$markerArray['###FORM_URL_TARGET###'] = '_self';
		if ($this->basketExtra['payment.']['handleURL'])	{	// This handleURL is called instead of the THANKS-url in order to let handleScript process the information if payment by credit card or so.
			$markerArray['###FORM_URL_THANKS###'] = $this->basketExtra['payment.']['handleURL'];
		}
		if ($this->basketExtra['payment.']['handleTarget'])	{	// Alternative target
			$markerArray['###FORM_URL_TARGET###'] = $this->basketExtra['payment.']['handleTarget'];
		}
		return $markerArray;
	} // addURLMarkers



	/**
	 * Returning template subpart marker
	 */
	function spMarker($subpartMarker)	{
		$sPBody = substr($subpartMarker,3,-3);
		$altSPM = '';
		if (isset($this->conf['altMainMarkers.']))	{
			$altSPM = trim($this->cObj->stdWrap($this->conf['altMainMarkers.'][$sPBody],$this->conf['altMainMarkers.'][$sPBody.'.']));
			$GLOBALS['TT']->setTSlogMessage('Using alternative subpart marker for "'.$subpartMarker.'": '.$altSPM,1);
		}
		return $altSPM ? $altSPM : $subpartMarker;
	} // spMarker


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	string		title of the category
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param   array		information about the parent HTML form
	 * @return	string
	 * @access private
	 */
	function &getItemMarkerArray (&$item,$catTitle, $imageNum=0, $imageRenderObj='image', $forminfoArray = array())	{
			// Returns a markerArray ready for substitution with information for the tt_producst record, $row

		$row = &$item['rec'];
		$markerArray=array();
			// Get image
		$theImgCode=array();

		$imgs = explode(',',$row['image']);

		while(list($c,$val)=each($imgs))	{
			if ($c==$imageNum)	break;
			if ($val)	{
				$this->conf[$imageRenderObj.'.']['file'] = 'uploads/pics/'.$val;
			} else {
				$this->conf[$imageRenderObj.'.']['file'] = $this->conf['noImageAvailable'];
			}
			$i = $c;
			if (!$this->conf['separateImage'])
			{
				$i = 0;  // show all images together as one image
			}
			$theImgCode[$i] .= $this->cObj->IMAGE($this->conf[$imageRenderObj.'.']);
		}

		$iconImgCode = $this->cObj->IMAGE($this->conf['datasheetIcon.']);

			// Subst. fields
/* mkl:
		if ( ($this->language > 0) && $row['o_title'] )	{
			$markerArray['###PRODUCT_TITLE###'] = $row['o_title'];
		}
		else  {
			$markerArray['###PRODUCT_TITLE###'] = $row['title'];
		}

		if ( ($this->language > 0) && $row['o_unit'] )	{
			$markerArray['###UNIT###'] = $row['o_unit'];
		}
		else  {
			$markerArray['###UNIT###'] = $row['unit'];
		}

*/
		$markerArray['###UNIT###'] = $row['unit'];
		$markerArray['###UNIT_FACTOR###'] = $row['unit_factor'];

		$markerArray['###ICON_DATASHEET###']=$iconImgCode;

		$markerArray['###PRODUCT_TITLE###'] = $row['title'];
		$markerArray['###PRODUCT_NOTE###'] = nl2br($row['note']);

//		if ( ($this->language > 0) && $row['o_note'] )	{
////			$markerArray['###PRODUCT_NOTE###'] = nl2br($row['o_note']);
//			$markerArray['###PRODUCT_NOTE###'] = $this->pi_RTEcssText($row['o_note']);
//		}
//		else  {
////			$markerArray['###PRODUCT_NOTE###'] = nl2br($row['note']);
//			$markerArray['###PRODUCT_NOTE###'] = $this->pi_RTEcssText($row['note']);
//		}

		if (is_array($this->conf['parseFunc.']))	{
			$markerArray['###PRODUCT_NOTE###'] = $this->cObj->parseFunc($markerArray['###PRODUCT_NOTE###'],$this->conf['parseFunc.']);
		}
		$markerArray['###PRODUCT_ITEMNUMBER###'] = $row['itemnumber'];

		$markerArray['###PRODUCT_IMAGE###'] = $theImgCode[0]; // for compatibility only

		while ((list($c,$val)=each($theImgCode)))
		{
			$markerArray['###PRODUCT_IMAGE' .  intval($c + 1) . '###'] = $theImgCode[$c];
		}

			// empty all image fields with no availble image
		for ($i=1; $i<=15; ++$i) {
			if (!$markerArray['###PRODUCT_IMAGE' .  $i. '###']) {
				$markerArray['###PRODUCT_IMAGE' .  $i. '###'] = '';
			}
		}

		$markerArray['###PRODUCT_SUBTITLE###'] = $row['subtitle'];
		$markerArray['###PRODUCT_WWW###'] = $row['www'];
		$markerArray['###PRODUCT_ID###'] = $row['uid'];

/* Added Els4: cur_sym moved from after product_special to this place, necessary to put currency symbol */
		$markerArray['###CUR_SYM###'] = ' '.($this->conf['currencySymbol'] ? $this->conf['currencySymbol'] : '');

		$markerArray['###PRICE_TAX###'] = tx_ttproducts_price_div::printPrice(tx_ttproducts_view_div::priceFormat($item['priceTax']));
		$markerArray['###PRICE_NO_TAX###'] = tx_ttproducts_price_div::printPrice(tx_ttproducts_view_div::priceFormat($item['priceNoTax']));

/* Added els4: printing of pric_no_tax with currency symbol (used in totaal-_.tmpl and winkelwagen.tmpl) */
		if ($row['category'] == $this->conf['creditsCategory']) {
			$markerArray['###PRICE_NO_TAX_CUR_SYM###'] = tx_ttproducts_price_div::printPrice($item['priceNoTax']);
		} else {
			$markerArray['###PRICE_NO_TAX_CUR_SYM###'] = $markerArray['###CUR_SYM###'].'&nbsp;'.tx_ttproducts_price_div::printPrice(tx_ttproducts_view_div::priceFormat($item['priceNoTax']));
		}

		$oldPrice = tx_ttproducts_price_div::printPrice(tx_ttproducts_view_div::priceFormat(tx_ttproducts_price_div::getPrice($row['price'],1,$row['tax'])));
		$oldPriceNoTax = tx_ttproducts_price_div::printPrice(tx_ttproducts_view_div::priceFormat(tx_ttproducts_price_div::getPrice($row['price'],0,$row['tax'])));
		$priceNo = intval($this->config['priceNoReseller']);
		if ($priceNo == 0) {	// no old price will be shown when the new price has not been reducted
			$oldPrice = $oldPriceNoTax = '';
		}

		$markerArray['###OLD_PRICE_TAX###'] = $oldPrice;

/* Added els4: changed whole block: if OLD_PRICE_NO_TAX = 0 then print PRICE_NO_TAX and set PRICE_NO_TAX to empty,
/* Added els4: Markers SUB_NO_DISCOUNT and SUB_DISCOUNT used in detail template
		calculating with $item['priceNoTax'] */
/* Added els4: Exceptions for category = kurkenshop */
		if ($oldPriceNoTax == '0.00') {
			$markerArray['###OLD_PRICE_NO_TAX_CLASS###'] = 'rightalign';
			$markerArray['###OLD_PRICE_NO_TAX###'] = $markerArray['###PRICE_NO_TAX###'];
			if ($row['category'] == $this->conf['creditsCategory']) {
				$markerArray['###CUR_SYM###'] ="";
				$markerArray['###OLD_PRICE_NO_TAX###'] = $item['priceNoTax']."&nbsp;<img src='fileadmin/html/img/bullets/kurk.gif' width='17' height='17'>";
			}
			$markerArray['###PRICE_NO_TAX###'] = "";
			$markerArray['###PRICE_NO_TAX_CUR_SYM###'] = "";
			$markerArray['###DETAIL_PRICE_ITEMLIST###'] = '<span class="flesprijs">flesprijs&nbsp;'.$markerArray['###CUR_SYM###'].'&nbsp;'.$markerArray['###OLD_PRICE_NO_TAX###'].'</span>';
			$markerArray['###DETAIL_PRICE_ITEMLIST_PRESENT###'] = '<span class="flesprijs">prijs&nbsp;'.$markerArray['###CUR_SYM###'].'&nbsp;'.$markerArray['###OLD_PRICE_NO_TAX###'].'</span>';
			$markerArray['###DETAIL_PRICE_ITEMSINGLE###'] = '<p><span class="flesprijs"><nobr>flesprijs&nbsp;'.$markerArray['###CUR_SYM###'].'&nbsp;'.$markerArray['###OLD_PRICE_NO_TAX###'].'</nobr></span></p>';
			$markerArray['###DETAIL_PRICE_ITEMSINGLE_PRESENT###'] = '<p><span class="flesprijs"><nobr>prijs&nbsp;'.$markerArray['###CUR_SYM###'].'&nbsp;'.$markerArray['###OLD_PRICE_NO_TAX###'].'</nobr></span></p>';
		} else {
			$markerArray['###OLD_PRICE_NO_TAX_CLASS###'] = 'prijsvan';
			$markerArray['###OLD_PRICE_NO_TAX###'] = $oldPriceNoTax;
			if ($row['category'] == $this->conf['creditsCategory']) {
				$markerArray['###CUR_SYM###'] ="";
				$markerArray['###OLD_PRICE_NO_TAX###'] = tx_ttproducts_price_div::getPrice($row['price'],0,$row['tax'])."&nbsp;<img src='fileadmin/html/img/bullets/kurk.gif' width='17' height='17'>";
			}
			$markerArray['###DETAIL_PRICE_ITEMLIST###'] = '<span class="prijsvan">van&nbsp; '.$markerArray['###OLD_PRICE_NO_TAX###'].'</span> <span class="prijsvoor">voor '.$markerArray['###PRICE_NO_TAX###'].'</span>';
			$markerArray['###DETAIL_PRICE_ITEMLIST_PRESENT###'] = '<span class="prijsvan">van&nbsp; '.$markerArray['###OLD_PRICE_NO_TAX###'].'</span> <span class="prijsvoor">voor '.$markerArray['###PRICE_NO_TAX###'].'</span>';
			$markerArray['###DETAIL_PRICE_ITEMSINGLE###'] = '<p class="prijsvan">van&nbsp; '.$markerArray['###CUR_SYM###'].'&nbsp;'.$markerArray['###OLD_PRICE_NO_TAX###'].'</p> <p class="prijsvoor"><nobr>voor '.$markerArray['###CUR_SYM###'].'&nbsp;'.$markerArray['###PRICE_NO_TAX###'].'</nobr></p>';
			$markerArray['###DETAIL_PRICE_ITEMSINGLE_PRESENT###'] = '<p class="prijsvan">van&nbsp; '.$markerArray['###CUR_SYM###'].'&nbsp;'.$markerArray['###OLD_PRICE_NO_TAX###'].'</p> <p class="prijsvoor"><nobr>voor '.$markerArray['###CUR_SYM###'].'&nbsp;'.$markerArray['###PRICE_NO_TAX###'].'</nobr></p>';
		}

		$markerArray['###PRODUCT_INSTOCK_UNIT###'] = '';
		if ($row['inStock'] <> 0) {
			$markerArray['###PRODUCT_INSTOCK###'] = $row['inStock'];
			$markerArray['###PRODUCT_INSTOCK_UNIT###'] = $this->conf['inStockPieces'];
		} else {
			$markerArray['###PRODUCT_INSTOCK###'] = $this->conf['notInStockMessage'];
		}

		$markerArray['###CATEGORY_TITLE###'] = $catTitle;

		$markerArray['###FIELD_NAME###']='ttp_basket['.$row['uid'].'][quantity]';

//		$markerArray["###FIELD_NAME###"]="recs[tt_products][".$row["uid"]."]";

		$temp = $this->basketExt[$row['uid']][tx_ttproducts_article_div::getVariantFromRow ($row)];

		$markerArray['###FIELD_QTY###']= $temp ? $temp : '';
		$markerArray['###FIELD_NAME_BASKET###']='ttp_basket['.$row['uid'].']['.md5($row['extVars']).']';

		$markerArray['###FIELD_SIZE_NAME###']='ttp_basket['.$row['uid'].'][size]';
		$markerArray['###FIELD_SIZE_VALUE###']=$row['size'];
		$markerArray['###FIELD_SIZE_ONCHANGE']= ''; // TODO:  use $forminfoArray['###FORM_NAME###' in something like onChange="Go(this.form.Auswahl.options[this.form.Auswahl.options.selectedIndex].value)"

		$markerArray['###FIELD_COLOR_NAME###']='ttp_basket['.$row['uid'].'][color]';
		$markerArray['###FIELD_COLOR_VALUE###']=$row['color'];

		$markerArray['###FIELD_ACCESSORY_NAME###']='ttp_basket['.$row['uid'].'][accessory]';
		$markerArray['###FIELD_ACCESSORY_VALUE###']=$row['accessory'];

		$markerArray['###FIELD_GRADINGS_NAME###']='ttp_basket['.$row['uid'].'][gradings]';
		$markerArray['###FIELD_GRADINGS_VALUE###']=$row['gradings'];

/* Added Els4: total price is quantity multiplied with pricenottax mulitplied with unit_factor (exception for kurkenshop), _credits is necessary for "kurkenshop", without decimal and currency symbol */
		if ($row['category'] == $this->conf['creditsCategory']) {
			$markerArray['###PRICE_ITEM_X_QTY###'] = tx_ttproducts_price_div::printPrice($markerArray['###FIELD_QTY###']*$item['priceNoTax']*$row['unit_factor']);
		} else {
			$markerArray['###PRICE_ITEM_X_QTY###'] = $markerArray['###CUR_SYM###'].'&nbsp;'.tx_ttproducts_price_div::printPrice(tx_ttproducts_view_div::priceFormat($markerArray['###FIELD_QTY###']*$item['priceNoTax']*$row['unit_factor']));
		}

		$prodColorText = '';
		$prodTmp = explode(';', $row['color']);
		if ($this->conf['selectColor']) {
			foreach ($prodTmp as $prodCol)
				$prodColorText = $prodColorText . '<OPTION value="'.$prodCol.'">'.$prodCol.'</OPTION>';
		} else {
			$prodColorText = $prodTmp[0];
		}

		$prodSizeText = '';
		$prodTmp = explode(';', $row['size']);
		if ($this->conf['selectSize']) {
			foreach ($prodTmp as $prodSize) {
				$prodSizeText = $prodSizeText . '<OPTION value="'.$prodSize.'">'.$prodSize.'</OPTION>';
			}
		} else {
			$prodSizeText = $prodTmp[0];
		}

		$prodAccessoryText = '';
		$prodTmp = explode(';', $row['accessory']);
		if ($this->conf['selectAccessory']) {
			$message = $this->pi_getLL('accessory no');		
			$prodAccessoryText =  '<OPTION value="0">'.$message.'</OPTION>';
			$message = $this->pi_getLL('accessory yes');		
			$prodAccessoryText .= '<OPTION value="1">'.$message.'</OPTION>';
		} else {
			$prodAccessoryText = $prodTmp;
		}

		$prodGradingsText = '';
		$prodTmp = explode(';', $row['gradings']);
		if ($this->conf['selectGradings']) {
			foreach ($prodTmp as $prodGradings) {
				$prodGradingsText = $prodGradingsText . '<OPTION value="'.$prodGradings.'">'.$prodGradings.'</OPTION>';
			}
		} else {
			$prodGradingsText = $prodTmp[0];
		}

		$markerArray['###PRODUCT_WEIGHT###'] = doubleval($row['weight']);
		$markerArray['###BULKILY_WARNING###'] = $row['bulkily'] ? $this->conf['bulkilyWarning'] : '';
		$markerArray['###PRODUCT_COLOR###'] = $prodColorText;
		$markerArray['###PRODUCT_SIZE###'] = $prodSizeText;
		$markerArray['###PRODUCT_ACCESSORY###'] = $prodAccessoryText;
		$markerArray['###PRODUCT_GRADINGS###'] = $prodGradingsText;
		$markerArray['###PRICE_ACCESSORY_TAX###'] = tx_ttproducts_price_div::printPrice(tx_ttproducts_view_div::priceFormat(tx_ttproducts_price_div::getPrice($row['accessory'.$this->config['priceNoReseller']],1,$row['tax'])));
		$markerArray['###PRICE_ACCESSORY_NO_TAX###'] = tx_ttproducts_price_div::printPrice(tx_ttproducts_view_div::priceFormat(tx_ttproducts_price_div::getPrice($row['accessory'.$this->config['priceNoReseller']],0,$row['tax'])));
		$markerArray['###PRICE_WITH_ACCESSORY_TAX###'] = tx_ttproducts_price_div::printPrice(tx_ttproducts_view_div::priceFormat(tx_ttproducts_price_div::getPrice($row['accessory'.$this->conf['priceNoReseller']]+$row['price'.$this->config['priceNoReseller']],1,$row['tax'])));
		$markerArray['###PRICE_WITH_ACCESSORY_NO_TAX###'] = tx_ttproducts_price_div::printPrice(tx_ttproducts_view_div::priceFormat(tx_ttproducts_price_div::getPrice($row['accessory'.$this->conf['priceNoReseller']]+$row['price'.$this->config['priceNoReseller']],0,$row['tax'])));

		if ($row['special_preparation'])
			$markerArray['###PRODUCT_SPECIAL_PREP###'] = $this->cObj->substituteMarkerArray($this->conf['specialPreparation'],$markerArray);
		else
			$markerArray['###PRODUCT_SPECIAL_PREP###'] = '';

/* 		Added els4: cur_sym moved to above (after product_id)*/
			// Fill the Currency Symbol or not

		if ($this->conf['itemMarkerArrayFunc'])	{
			$markerArray = $this->userProcess('itemMarkerArrayFunc',$markerArray);
		}

		return $markerArray;
	} // getItemMarkerArray



	/**
	 * Returns a url for use in forms and links
	 */
	function getLinkParams($excludeList='',$addQueryString=array()) {
		global $TSFE;
		$queryString=array();
		$queryString['backPID']= $TSFE->id;
		$temp = t3lib_div::GPvar('C') ? t3lib_div::GPvar('C') : $this->currency;
		if ($temp)	{
			$queryString['C'] = $temp;
		}
		$temp =   t3lib_div::_GP('begin_at');
		if ($temp) {
			$queryString['begin_at'] = $temp;
		}
		$temp = t3lib_div::_GP('swords') ? rawurlencode(t3lib_div::_GP('swords')) : '';
		if ($temp) {
			$queryString['swords'] = $temp;
		}
		$temp = t3lib_div::GPvar('newitemdays') ? rawurlencode(stripslashes(t3lib_div::GPvar('newitemdays'))) : '';
		if ($temp) {
			$queryString['newitemdays'] = $temp;
		}
		foreach ($addQueryString as $param => $value){
			$queryString[$param] = $value;
		}
		reset($queryString);
		while(list($key,$val)=each($queryString))	{
			if (!$val || ($excludeList && t3lib_div::inList($excludeList,$key)))	{
				unset($queryString[$key]);
			}
		}

		return $queryString;
	}


	/**
	 * Formatting a price
	 */
	function priceFormat($double)	{
		return number_format($double,intval($this->conf['priceDec']),$this->conf['priceDecPoint'],$this->conf['priceThousandPoint']);
	} // priceFormat



	/**
	 * Fills in all empty fields in the delivery info array
	 */
	function mapPersonIntoToDelivery()	{
		
			// all of the delivery address will be overwritten when no address and no email address have been filled in
		if (!trim($this->deliveryInfo['address']) && !trim($this->deliveryInfo['email'])) {
/* Added Els: 'feusers_uid,' and more fields */
			$infoExtraFields = ($this->feuserextrafields ? ',tx_feuserextrafields_initials_name,tx_feuserextrafields_prefix_name,tx_feuserextrafields_gsm_tel,tx_feuserextrafields_company_deliv,tx_feuserextrafields_address_deliv,tx_feuserextrafields_housenumber,tx_feuserextrafields_housenumber_deliv,tx_feuserextrafields_housenumberadd,tx_feuserextrafields_housenumberadd_deliv,tx_feuserextrafields_pobox,tx_feuserextrafields_pobox_deliv,zip,tx_feuserextrafields_zip_deliv,tx_feuserextrafields_city_deliv,tx_feuserextrafields_country,tx_feuserextrafields_country_deliv':'');
			$infoFields = explode(',','feusers_uid,telephone,name,email,date_of_birth,company,address,city'.$infoExtraFields); // Fields...
			while(list(,$fName)=each($infoFields))	{
				$this->deliveryInfo[$fName] = $this->personInfo[$fName];
			}
		}

	} // mapPersonIntoToDelivery



	/**
	 * Checks if required fields are filled in
	 */
	function checkRequired()	{
		$flag = '';
		$requiredInfoFields = trim($this->conf['requiredInfoFields']);
		if ($this->basketExtra['payment.']['addRequiredInfoFields'] != '')
			$requiredInfoFields .= ','.trim($this->basketExtra['payment.']['addRequiredInfoFields']);

		if ($requiredInfoFields)	{
			$infoFields = t3lib_div::trimExplode(',',$requiredInfoFields);
			while(list(,$fName)=each($infoFields))	{
				if (trim($this->personInfo[$fName])=='' || trim($this->deliveryInfo[$fName])=='')	{
					$flag=$fName;
					break;
				}
			}
		}
		return $flag;
	} // checkRequired



}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_view_div.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_view_div.php']);
}


?>
