<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Franz Holzinger (franz@ttproducts.de)
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
 * API functions
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class tx_ttproducts_api implements \TYPO3\CMS\Core\SingletonInterface {

	static public function roundPrice ($value, $format) {

		$result = $oldValue = $value;
		$priceRoundFormatArray = array();
		$dotPos = strpos($value, '.');
		$floatLen = strlen($value) - $dotPos - 1;

		if (strpos($format, '.') !== false) {
			$priceRoundFormatArray = GeneralUtility::trimExplode('.', $format);
		} else {
			$priceRoundFormatArray['0'] = $format;
		}

		if ($priceRoundFormatArray['0'] != '') {
			$integerPart = intval($priceRoundFormatArray['0']);
			$floatPart = $oldValue - intval($oldValue);
			$faktor = pow(10, strlen($integerPart));
			$result = (intval($oldValue / $faktor) * $faktor) + $integerPart + $floatPart;

			if ($result < $oldValue) {
				$result += $faktor;
			}

			$oldValue = $result;
		}

		if (isset($priceRoundFormatArray['1'])) {

			$formatText = $priceRoundFormatArray['1'];
			$digits = 0;

			while(substr($formatText, $digits, 1) == 'X') {
				$digits++;
			}
			$floatValue = substr($formatText, $digits);
			$faktor = pow(10, $digits);

			if ($floatValue == '') {
				$result = round($oldValue, $digits);
			} else {
				$allowedChars = '';
				$lowestValuePart = 0;
				$length = strlen($floatValue);

				if ($length > 3 && strpos($floatValue, '[') === 0 && strpos($floatValue, ']') === ($length - 1)) {
					$allowedChars = substr($floatValue, 1, $length - 2);
					if ($allowedChars != '') {

						$digitValue = intval(round($value * $faktor * 10)) % 10;
						$countAllowedChars = strlen($allowedChars);
						$step = intval(10 / $countAllowedChars);
						$allowedPos = 0;
						$finalAddition = $digitValue;
						$lowChar = '';
						$lowValue = -20;

						$highChar = '';
						$highValue = 20;
						$bKeepChar = false;

						for ($allowedPos = 0; $allowedPos < $countAllowedChars; $allowedPos++) {

							$currentChar = substr($allowedChars, $allowedPos, 1);
							$currentValue = intval($currentChar);

							if ($lowChar == '') {
								$lowChar = $currentChar;
								$lowValue = $currentValue;
							}

							if ($highChar == '') {
								$highChar = $currentChar;
								$highValue = $currentValue;
							}

							if ($digitValue == $currentChar && $floatLen == ($length - 2)) { // '0' means '10'
								$bKeepChar = true;
								break;
							} else {
								$comparatorLow1 = $digitValue - $currentValue;
								if ($comparatorLow1 < 0) {
									$comparatorLow1 += 10;
								}

								$comparatorLow2 = $digitValue - $lowValue;

								if ($comparatorLow2 < 0) {
									$comparatorLow2 += 10;
								}

								if (
									$comparatorLow1 < $comparatorLow2
								) {
									$lowChar = $currentChar;
									$lowValue = $currentValue;
								}

								$comparatorHigh1 = $currentValue - $digitValue;
								if ($comparatorHigh1 < 0) {
									$comparatorHigh1 += 10;
								}

								$comparatorHigh2 = $highValue - $digitValue;
								if ($comparatorHigh2 < 0) {
									$comparatorHigh2 += 10;
								}

								if ($comparatorHigh1 < $comparatorHigh2) {
									$highChar = $currentChar;
									$highValue = $currentValue;
								}
							}

							if (
								!$bKeepChar &&
								$lowValue != $highValue
							) {
								$comparator2 = $highValue - $digitValue;
								$highAddition = 0;
								if ($comparator2 < 0) {
									$comparator2 += 10;
									$highAddition = 10;
								}

								if ($digitValue - $lowValue < $comparator2) {
									$finalAddition = $lowValue;
								} else {
									$finalAddition = $highValue + $highAddition;
								}
							}
						}

						$lowestValuePart = (intval($finalAddition) / ($faktor * 10));
					}
				} else if (
					tx_div2007_core::testInt($floatValue)
				) {
					$floatPart =  $floatValue * $faktor * 10;
					$lowestValuePart = (intval($floatPart) / ($faktor * 10));
				}

				if (!$bKeepChar) {
					$result = intval($oldValue * $faktor) / $faktor + $lowestValuePart;
				}
			}
		}

		return $result;
	}


	static public function createFeuser ($templateCode, $conf, $infoObj, $basketView, $calculatedArray, $fromArray) {

		$result = false;
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$infoArray = $infoObj->infoArray;
		$apostrophe = $conf['orderEmail_apostrophe'];

		$pid = ($conf['PIDuserFolder'] ? $conf['PIDuserFolder'] : ($conf['PIDbasket'] ? $conf['PIDbasket'] : $GLOBALS['TSFE']->id));
		$pid = intval($pid);
		$username = strtolower(trim($infoArray['billing']['email']));
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('username', 'fe_users', 'username=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($username, 'fe_users') . ' AND pid=' . $pid . ' AND deleted=0');
		$num_rows = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		if (!$num_rows) {
			$password = $infoObj->password = substr(md5(rand()), 0, 12);
            if (
                version_compare(TYPO3_version, '9.5.0', '>=')
            ) {
                try {
                    $hashInstance = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory::class)->getDefaultHashInstance('FE');
                    $password = $hashInstance->getHashedPassword($password);
                } catch (TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException $e) {
                    debug($tmp, 'no FE user could be generated!'); // keep this
                }
            } else if ($conf['useMd5Password']) {
                $password = md5($password);
            }
			$tableFieldArray = $tablesObj->get('fe_users')->getTableObj()->tableFieldArray;
			$insertFields = array(	// TODO: check with TCA
				'pid' => intval($pid),
				'tstamp' => time(),
				'crdate' => time(),
				'username' => $username,
				'password' => $password,
				'usergroup' => $conf['memberOfGroup'],
				'uid' => $infoArray['billing']['feusers_uid'],
			);

			foreach ($tableFieldArray as $fieldname => $value) {
				$fieldvalue = $infoArray['billing'][$fieldname];
				if (isset($fieldvalue)) {
					$insertFields[$fieldname] = $fieldvalue;
				}
			}

			if (
				ExtensionManagementUtility::isLoaded('agency') ||
				ExtensionManagementUtility::isLoaded('femanager') ||
				ExtensionManagementUtility::isLoaded('sr_feuser_register')
			) {
				if ($conf['useStaticInfoCountry'] && isset($infoArray['billing']['country_code'])) {
					$insertFields['static_info_country'] = $infoArray['billing']['country_code'];
				} else {
					$insertFields['static_info_country'] = '';
				}
			}

			if(
				$infoArray['billing']['date_of_birth'] &&
				(ExtensionManagementUtility::isLoaded('sr_feuser_register') || ExtensionManagementUtility::isLoaded('agency'))
			) {
				$date = str_replace('-', '/', $infoArray['billing']['date_of_birth']);
				$insertFields['date_of_birth'] = strtotime($date);
			}

			$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('fe_users', $insertFields);
			// send new user mail
			if (!empty($infoArray['billing']['email'])) {
				$emailContent = trim(
					$basketView->getView(
                        $errorCode,
						$templateCode,
						'EMAIL',
						$infoObj,
						false,
						false,
						$calculatedArray,
						false,
						'EMAIL_NEWUSER_TEMPLATE',
						array()
					)
				);

				if ($emailContent != '') {
					$parts = explode(chr(10), $emailContent, 2);
					$subject = trim($parts[0]);
					$plain_message = trim($parts[1]);

					tx_ttproducts_email_div::send_mail(
						$infoArray['billing']['email'],
						$apostrophe . $subject . $apostrophe,
						$plain_message,
						$tmp = '',
						$fromArray['shop']['email'],
						$fromArray['shop']['name']
					);
				}
			}
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid',
				'fe_users',
				'username='.$GLOBALS['TYPO3_DB']->fullQuoteStr($username, 'fe_users') .
					' AND pid=' . $pid . ' AND deleted=0');

			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$result = intval($row['uid']);
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}

		return $result;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/api/class.tx_ttproducts_api.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/api/class.tx_ttproducts_api.php']);
}
