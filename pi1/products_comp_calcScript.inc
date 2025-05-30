<?php
/*

products_comp_calcScript.php


Calculation script for use with the default products.inc script

The script is based on the assumption that you're going to add a fee for using MasterCard/Eurocard, VISA or Diners Club.
MasterCard and VISA demands 5.75 % percent of the total amount in transaction fee. To compensate for this and let the customer pay the fee, this script calculates how much you need to add to the total in order to fully compensate.
To compensate 5,75% you must add 6.10%, then 5,75 % percent of that total is exactly the same as the amount of the fee.

TypoScript properties:

.feeTax	 = enter sales-tax percentage for the fee, if any
.feePercent = the percent to be compensated for, default is 5.75 %

*/

$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');

$baseAmount = $basketObj->calculatedArray['priceTax']['goodstotal']['ALL'] + $basketObj->calculatedArray['priceTax']['shipping'];		// The fee is calculated from the total of the goods AND shipping
$feePercent = floatval($confScript['feePercent'] ? $confScript['feePercent'] : 5.75);		// Fee percent is by default 5.75 %
	# Calculate conpensating percentage by:   100/(1-pFee/100)-100 = pComp, where pFee is the percentage, eg. Mastercard is taking (5,75%) and pComp will thus be 6.1 %
$compPercent = 100/(1-$feePercent/100)-100;		// The compensation percentage.

$basketObj->calculatedArray['priceNoTax']['payment'] = $baseAmount/100*$compPercent;		// Add the amount to the no_tax total
$basketObj->calculatedArray['priceTax']['payment'] = $basketObj->calculatedArray['priceNoTax']['payment'] * (1+$basketObj->conf['payment.']['TAXpercentage']/100);		// ..and add the amount to the tax total + tax of the amount, if any

