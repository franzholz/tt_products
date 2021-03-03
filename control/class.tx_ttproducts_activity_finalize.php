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
 * base class for the finalization activity
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


use TYPO3\CMS\Core\Utility\GeneralUtility;



class tx_ttproducts_activity_finalize {
	public $pibase; // reference to object of pibase
	public $cnf;
	public $conf;
	public $config;
	public $alwaysInStock;
	public $useArticles;

	public function init ($pibase)  {
		$this->pibase = $pibase;
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;

		if (intval($this->conf['alwaysInStock'])) {
			$this->alwaysInStock = 1;
		} else {
			$this->alwaysInStock = 0;
		}
		$this->useArticles = $this->conf['useArticles'];
	} // init


	public function splitSubjectAndText(
		$templateCode,
		&$subject,
		&$text
	) {
		$parts = preg_split('/[\n\r]+/', $templateCode, 2);	// First line is subject
		$subject=trim($parts[0]);
		$text=trim($parts[1]);
		if (empty($text)) {	// the user did not use the subject field
			$text = $subject;
		}
		$text = tx_div2007_core::substituteMarkerArrayCached($text,$markerArray);
		if (empty($subject)) {
			$subject = $this->conf['orderEmail_subject'];
		}
	}


	/**
	 * Finalize an order
	 *
	 * This finalizes an order by saving all the basket info in the current order_record.
	 * A finalized order is then marked 'not deleted' and with status=1
	 * The basket is also emptied, but address info is preserved for any new orders.
	 * $orderUid is the order-uid to finalize
	 * $mainMarkerArray is optional and may be pre-prepared fields for substitutiong in the template.
	 *
	 * returns the email address of the customer to whom the order notification has been sent
	 */
	public function doProcessing (
		$templateCode,
		&$mainMarkerArray,
		$functablename,
		$orderUid,
		$basketExtra,
		&$errorCode,
		&$errorMessage
	) {
		if ($this->conf['errorLog']) {
			error_log('doProcessing $orderUid = ' . $orderUid . chr(13), 3, $this->conf['errorLog']);
		}

		$basketView = GeneralUtility::makeInstance('tx_ttproducts_basket_view');
		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$billdeliveryObj = GeneralUtility::makeInstance('tx_ttproducts_billdelivery');
		$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
		$cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
		$infoViewObj = GeneralUtility::makeInstance('tx_ttproducts_info_view');
		$emailObj = $tablesObj->get('tt_products_emails');
		$calculObj = GeneralUtility::makeInstance('tx_ttproducts_basket_calculate');
		$activityConf = $cnfObj->getBasketConf('activity', 'finalize');
        $languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);

		$fileArray = array(); // bill or delivery
		$empty = '';
		$parser = $this->pibase->cObj;
        if (
            defined('TYPO3_version') &&
            version_compare(TYPO3_version, '7.0.0', '>=')
        ) {
            $parser = tx_div2007_core::newHtmlParser(false);
        }

		if (isset($activityConf) && is_array($activityConf)) {
			if (isset($activityConf['clear'])) {
				$clearArray = GeneralUtility::trimExplode(',', $activityConf['clear']);
				foreach ($clearArray as $v) {
					switch ($v) {
						case 'memo':
							$feuserField = 'tt_products_memoItems';
							$memoItems = '';
							if (
                                \JambageCom\Div2007\Utility\CompatibilityUtility::isLoggedIn() &&
                                isset($GLOBALS['TSFE']->fe_user->user[$feuserField]) &&
                                $GLOBALS['TSFE']->fe_user->user[$feuserField] != ''
                            ) {
								$memoItems = $GLOBALS['TSFE']->fe_user->user[$feuserField];
							}
							$uidArray = $basketObj->getUidArray();
							if (isset($uidArray) && is_array($uidArray) && count($uidArray) && $memoItems != '') {
								$newMemoItems = $memoItems;
								foreach ($uidArray as $uid) {
									$newMemoItems = GeneralUtility::rmFromList($uid, $newMemoItems);
								}

								if ($newMemoItems != $memoItems) {
									tx_ttproducts_control_memo::saveMemo(
										'tt_products',
										$newMemoItems,
										$conf
									);
								}
							}
						break;
					}
				}
			}
		}

		$instockTableArray='';
		$itemArray = $basketObj->getItemArray();
		$customerEmail = $infoViewObj->getCustomerEmail();

		$defaultFromArray = array();
		$defaultFromArray['shop'] = array(
			'email' => $this->conf['orderEmail_from'],
			'name' => $this->conf['orderEmail_fromName']
		);
		$defaultFromArray['customer'] = array(
			'email' => $customerEmail,
			'name' => $infoViewObj->infoArray['billing']['name']
		);

		$markerArray = array_merge($mainMarkerArray, $markerObj->getGlobalMarkerArray());

		$emailControlArray = array();
		$emailControlArray['customer']['none']['template'] = 'EMAIL_PLAINTEXT_TEMPLATE'; // keep this on first position of the array
		$emailControlArray['customer']['none']['recipient'] = array();
		$emailControlArray['customer']['none']['recipient'][] = $customerEmail;

		$templateSubpart = 'EMAIL_HTML_TEMPLATE';

		if (strpos($templateCode, '###' . $templateSubpart . '###') === false) {
			$templateSubpart = 'BASKET_ORDERCONFIRMATION_TEMPLATE';
		}
		$emailControlArray['customer']['none']['htmltemplate'] = $templateSubpart;
		$emailControlArray['customer']['none']['from'] = $defaultFromArray['customer'];
		$emailControlArray['shop']['none']['from'] = $defaultFromArray['shop'];
		$emailControlArray['shop']['none']['recipient'][] = $this->conf['orderEmail_to'];

		if ($this->conf['errorLog']) {
			error_log('finalize Pos 1 $emailControlArray = ' . print_r($emailControlArray, true) . chr(13), 3, $this->conf['errorLog']);
		}

		$markerArray['###CUSTOMER_RECIPIENTS_EMAIL###'] = implode(',', $emailControlArray['customer']['none']['recipient']);

		$orderConfirmationHTML =
			$basketView->getView(
                $errorCode,
				$templateCode,
				'BASKET',
				$infoViewObj,
				false,
				false,
				$basketObj->getCalculatedArray(),
				true,
				'BASKET_ORDERCONFIRMATION_TEMPLATE',
				$mainMarkerArray,
				'',
				$itemArray,
				array(),
				$basketExtra
			);

		$orderConfirmationHTML =
            $parser->substituteMarkerArray(
                $orderConfirmationHTML,
                $markerArray
            );

		if ($GLOBALS['TSFE']->absRefPrefix == '') {
			$absRefPrefix = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
			$markerArray['"index.php'] = '"' . $absRefPrefix . 'index.php';
		}

		$customerEmailHTML =
			$basketView->getView(
                $errorCode,
				$templateCode,
				'EMAIL',
				$infoViewObj,
				false,
				false,
				$basketObj->getCalculatedArray(),
				true,
				$emailControlArray['customer']['none']['htmltemplate'],
				$markerArray,
				'',
				$itemArray,
				array(),
				$basketExtra
			);

		$result = $orderConfirmationHTML;
		$orderObj = $tablesObj->get('sys_products_orders');
		$apostrophe = $this->conf['orderEmail_apostrophe'];

		// Move the user creation in front so that when we create the order we have a fe_userid so that the order lists work.
		// Is no user is logged in --> create one
		if ($this->conf['createUsers'] && $infoViewObj->infoArray['billing']['email'] != '' && (trim($GLOBALS['TSFE']->fe_user->user['username']) == '')) {
			$feuserUid = tx_ttproducts_api::createFeuser(
				$this->conf,
				$infoViewObj,
				$basketView,
				$basketObj->getCalculatedArray(),
				$defaultFromArray
			);

			if ($feuserUid) {
				$infoViewObj->infoArray['billing']['feusers_uid'] = $feuserUid;
			}
		}

		$bdArray = $billdeliveryObj->getTypeArray();

		foreach ($bdArray as $type) {
			if (
                isset($this->conf[$type . '.']) &&
                is_array($this->conf[$type . '.']) &&
                $this->conf[$type . '.']['generation'] == 'auto'
            ) {
				$absFilename =
                    $billdeliveryObj->generateBill(
                        $errorCode,
                        $templateCode,
                        $mainMarkerArray,
                        $basketExtra,
                        $type,
                        $this->conf[$type . '.']
                    );
				$fileArray[$type] = $absFilename;
			}
		}

		$orderObj->setData($orderUid, $orderConfirmationHTML, 1, $basketExtra);
		$creditpointsObj = GeneralUtility::makeInstance('tx_ttproducts_field_creditpoints');
		$creditpointsObj->pay();

		// any gift orders in the extended basket?
		if ($basketObj->basketExt['gift']) {
			$pid = intval($this->conf['PIDGiftsTable']);

			if (!$pid) {
				$pid = intval($GLOBALS['TSFE']->id);
			}

			$rc = tx_ttproducts_gifts_div::saveOrderRecord(
				$orderUid,
				$pid,
				$basketObj->basketExt['gift']
			);
		}

		if (!$this->alwaysInStock) {
			$viewTable = $tablesObj->get($functablename);
			$instockTableArray =
				$viewTable->reduceInStockItems(
					$itemArray,
					$this->useArticles
				);
		}


		$orderObj->createMM($orderUid, $itemArray);
		$addcsv = '';

		// Generate CSV for each order
		if ($this->conf['generateCSV']) {
			// get bank account info
			$account = $tablesObj->get('sys_products_accounts');
			$accountUid = $account->getUid();
			$csv = GeneralUtility::makeInstance('tx_ttproducts_csv');
			$csv->init(
				$this->pibase,
				$itemArray,
				$basketObj->getCalculatedArray(),
				$accountUid
			);

            if (
                version_compare(TYPO3_version, '9.0.0', '>=')
            ) {
                $csvfilepath = \TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/' . $this->conf['CSVdestination'];
			} else {
                $csvfilepath = PATH_site . $this->conf['CSVdestination'];
			}
			$csvorderuid = $basketObj->order['orderUid'];
			$csv->create($functablename, $infoViewObj, $csvorderuid, $basketExtra, $csvfilepath, $errorMessage);
			if (!$this->conf['CSVnotInEmail']) {
				$addcsv = $csvfilepath;
			}
		}

		$markerArray['###MESSAGE_PAYMENT_SCRIPT###'] = '';
		$empty = '';

		if ($this->conf['orderEmail_toAddress']) {
			$infoViewObjArray = $basketObj->getAddressArray();
			if (is_array($infoViewObjArray) && count($infoViewObjArray)) {
				foreach ($infoViewObjArray as $infoViewObjUid => $infoViewObjRow) {
					if (!in_array($infoViewObjRow['email'], $emailControlArray['shop']['none']['recipient'])) {
						$emailControlArray['shop']['none']['recipient'][] = $infoViewObjRow['email'];
					}
				}
			}
		}

		if (isset($this->conf['orderEmail.']) && is_array($this->conf['orderEmail.'])) {

			foreach ($this->conf['orderEmail.'] as $k => $emailConfig) {

				$suffix = strtolower($emailConfig['suffix']);
				if (!isset($suffix)) {
					$suffix = 'shop';
				}

				if ($emailConfig['to'] != '' || $emailConfig['to.'] != '' || $suffix == 'shop' || $suffix == 'customer') {
					if ($emailConfig['shipping_point'] != '') {
						$shippingPoint = strtolower($emailConfig['shipping_point']);
					} else {
						$shippingPoint = 'none';
					}

					$emailControlArray[$suffix][$shippingPoint]['attachmentFile'] = array();
					if ($emailConfig['to.'] != '') {
						$toConfig = $emailConfig['to.'];
						if (
							trim($GLOBALS['TSFE']->fe_user->user['username']) != ''
							&& $toConfig['table'] == 'fe_users'
							&& $toConfig['field'] != ''
							&& $toConfig['foreign_table'] != ''
							&& $toConfig['foreign_field'] != ''
							&& $toConfig['foreign_email_field'] != ''
							&& $GLOBALS['TSFE']->fe_user->user[$toConfig['field']] != ''
						) {
							$where_clause = $toConfig['foreign_table'] . '.' . $toConfig['foreign_field'] . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($GLOBALS['TSFE']->fe_user->user[$toConfig['field']], $toConfig['foreign_table']);
							$recordArray = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($toConfig['foreign_email_field'], $toConfig['foreign_table'], $where_clause);
							if (isset($recordArray) && is_array($recordArray)) {
								foreach ($recordArray as $record) {
									if ($record[$toConfig['foreign_email_field']] != '') {
										$emailControlArray[$suffix][$shippingPoint]['recipient'][] = $record[$toConfig['foreign_email_field']];
									}
								}
							}
						}
					}
					if ($emailConfig['to'] != '') {
						$emailArray = GeneralUtility::trimExplode(',', $emailConfig['to']);

						foreach ($emailArray as $email) {
							if (
								!isset($emailControlArray[$suffix][$shippingPoint]['recipient'])
								|| is_array($emailControlArray[$suffix][$shippingPoint]['recipient'])
								&& !in_array($email, $emailControlArray[$suffix][$shippingPoint]['recipient'])
							) {
								$emailControlArray[$suffix][$shippingPoint]['recipient'][] = $email;
							}
						}
					}

					if ($emailConfig['attachment'] != '') {
						$emailControlArray[$suffix][$shippingPoint]['attachment'] = GeneralUtility::trimExplode(',', $emailConfig['attachment']);

						foreach($emailControlArray[$suffix][$shippingPoint]['attachment'] as $attachmentType) {
							$emailControlArray[$suffix][$shippingPoint]['attachmentFile'][] = $fileArray[$attachmentType];
						}
					}

					if ($suffix != 'customer') {

						$templateSubpart = 'EMAIL_PLAINTEXT_TEMPLATE_' . strtoupper($suffix);
						$htmlTemplateSubpart = 'EMAIL_HTML_TEMPLATE_' . strtoupper($suffix);
						if (
							$suffix == 'shop'
						) {
							if (strpos($templateCode, $templateSubpart) === false) {
								$templateSubpart = $emailControlArray['customer']['none']['template'];
							}
							if (strpos($templateCode, $htmlTemplateSubpart) === false) {
								$htmlTemplateSubpart = $emailControlArray['customer']['none']['htmltemplate'];
							}
						}

						$emailControlArray[$suffix][$shippingPoint]['template'] = $templateSubpart;
						$emailControlArray[$suffix][$shippingPoint]['htmltemplate'] = $htmlTemplateSubpart;

						if (isset($addcsv)) {
							$emailControlArray[$suffix][$shippingPoint]['attachmentFile'][] = $addcsv;
						}
					}

					if ($suffix == 'shop') {
						$emailControlArray[$suffix][$shippingPoint]['bcc'] = $this->conf['orderEmail_bcc'];
					}

					if (!$emailConfig['from'] || $emailConfig['from'] == 'shop') {
						$emailControlArray[$suffix][$shippingPoint]['from'] = $defaultFromArray['shop'];
					} else if ($emailConfig['from'] == 'customer') {
						$emailControlArray[$suffix][$shippingPoint]['from'] = $defaultFromArray['customer'];
					} else if (isset($emailConfig['from.'])) {
						$emailControlArray[$suffix][$shippingPoint]['from'] = array(
							'email' => $emailConfig['from.']['email'],
							'name' => $emailConfig['from.']['name']
						);
					}

					if ($shippingPoint != 'none') {
						$emailControlArray[$suffix][$shippingPoint]['recipient'] = array_unique(GeneralUtility::trimExplode(',', $emailConfig['to']));
						if ($emailConfig['subject'] != '') {
							$emailControlArray[$suffix][$shippingPoint]['subject'] = $emailConfig['subject'];
						}
					}

					if (isset($emailConfig['returnPath'])) {
						$emailControlArray[$suffix][$shippingPoint]['returnPath'] = $emailConfig['returnPath'];
					}
				}
			}
		}

		if (
			isset($this->conf['orderEmail_radio.']) && is_array($this->conf['orderEmail_radio.']) &&
			isset($this->conf['orderEmail_radio.']['1.']) && is_array($this->conf['orderEmail_radio.']) &&
			isset($this->conf['orderEmail_radio.']['1.'][$infoViewObj->infoArray['delivery']['radio1']])
		) {
			$emailControlArray['radio1']['none']['recipient'][] = $this->conf['orderEmail_radio.']['1.'][$infoViewObj->infoArray['delivery']['radio1']];
		}

		if (isset($emailControlArray['radio1']['none']['recipient'])) {
			$emailControlArray['radio1']['none']['template'] = 'EMAIL_PLAINTEXT_TEMPLATE_RADIO1';
			$emailControlArray['radio1']['none']['htmltemplate'] = 'EMAIL_HTML_TEMPLATE_RADIO1';
		}

		if ($this->conf['orderEmail_order2']) {
			$emailControlArray['customer']['none']['recipient'] = array_merge($emailControlArray['customer']['none']['recipient'], $emailControlArray['shop']['none']['recipient']);
			$emailControlArray['customer']['none']['recipient'] = array_unique($emailControlArray['customer']['none']['recipient']);
		}
		$customerHTMLmailContent = '';
		$posEmailPlaintext = strpos($templateCode, $emailControlArray['customer']['none']['template']);

		if ($posEmailPlaintext !== false || $this->conf['orderEmail_htmlmail']) {

			if ($this->conf['orderEmail_htmlmail']) {	// If htmlmail lib is included, then generate a nice HTML-email
				$HTMLmailShell = tx_div2007_core::getSubpart($templateCode, '###EMAIL_HTML_SHELL###');
				$customerHTMLmailContent = $parser->substituteMarker($HTMLmailShell, '###HTML_BODY###', $customerEmailHTML);

				$customerHTMLmailContent =
					$parser->substituteMarkerArray(
						$customerHTMLmailContent,
						$markerArray
					);

					// Remove image tags to the products:
				if ($this->conf['orderEmail_htmlmail.']['removeImagesWithPrefix']) {

					$htmlParser = tx_div2007_core::newHtmlParser();
					$htmlMailParts = $htmlParser->splitTags('img', $customerHTMLmailContent);

					foreach($htmlMailParts as $kkk => $vvv) {
						if ($kkk%2) {
							list($attrib) = $htmlParser->get_tag_attributes($vvv);
							if (GeneralUtility::isFirstPartOfStr($attrib['src'],$this->conf['orderEmail_htmlmail.']['removeImagesWithPrefix'])) {
								$htmlMailParts[$kkk]='';
							}
						}
					}
					$customerHTMLmailContent = implode('', $htmlMailParts);
				}
			} else {	// ... else just plain text...
				// nothing to initialize
			}

			$agbAttachment = ($this->conf['AGBattachment'] ? GeneralUtility::getFileAbsFileName($this->conf['AGBattachment']) : '');

			if ($agbAttachment != '') {
				$emailControlArray['customer']['none']['attachmentFile'][] = $agbAttachment;
				if (isset($emailControlArray['radio1']['none']['recipient'])) {

					$emailControlArray['radio1']['none']['attachmentFile'][] = $agbAttachment;
				}
			}

			$categoryInserted = array();
			$shippingPointInserted = array();

			// send distributor emails from email entered at the category level
			foreach ($itemArray as $sort => $actItemArray) {
				foreach ($actItemArray as $k1 => $actItem) {
					$row = $actItem['rec'];
					$category = $row['category'];
					$shipping_point = strtolower($row['shipping_point']);
					$suffix = 'shop';

					if ($categoryInserted[$category] != '') {
						$suffix = $categoryInserted[$category];
					} else if ($category) {
						$categoryArray = $tablesObj->get('tt_products_cat')->get($category);
						$emailRow = $emailObj->getEmail($categoryArray['email_uid']);

						if (isset($emailRow) && is_array($emailRow)) {
							$email = $emailRow['email'];
							$emailArray = array();
							if ($emailRow['name'] != '') {
								$emailArray = array($email => $emailArray['name']);
							} else {
								$emailArray = array($email);
							}

							if ($emailRow['suffix'] != '') {
								$suffix = strtolower($emailRow['suffix']);
							}

							if (
								!isset($emailControlArray[$suffix]['none']['recipient'])
								|| is_array($emailControlArray[$suffix]['none']['recipient'])
								&& !in_array($email, $emailControlArray[$suffix]['none']['recipient'])
							) {
								$emailControlArray[$suffix]['none']['recipient'][] = $emailArray;
							}
						}
						$categoryInserted[$category] = $suffix;
					}
					$emailControlArray[$suffix]['none']['itemArray'][$sort][] = $actItem;

					if ($shipping_point) {
						foreach ($emailControlArray as $suffix => $shippingControl) {
							foreach ($shippingControl as $shippingKey => $shippingPointControl) {
								$shippingKeyArray = GeneralUtility::trimExplode(',', $shippingKey);
								foreach ($shippingKeyArray as $key) {
									if ($key == $shipping_point && isset($shippingControl[$shippingKey]) && is_array($shippingControl[$shippingKey])) {
										$emailControlArray[$suffix][$shippingKey]['itemArray'][$sort][] = $actItem;
									}
								}
							}
						}
					}
				}
			}

			$calculatedArray = $calculObj->getCalculatedArray(); // Todo: allow calculation on reduced products
			foreach ($emailControlArray as $suffix => $shippingControlArray) {
				foreach ($shippingControlArray as $shippingPoint => $suffixControlArray) {
					if (isset($suffixControlArray) && is_array($suffixControlArray)) {

						if (
							isset($suffixControlArray['itemArray'])
							&& is_array($suffixControlArray['itemArray'])
						) {
							$basketItemArray = $suffixControlArray['itemArray'];
						} else if ($suffix == 'customer' || $suffix == 'shop') {
							$basketItemArray = $itemArray;
						} else {
							$basketItemArray = '';
						}
                        $basketText = '';
                        $basketHtml = '';

						if (isset($basketItemArray) && is_array($basketItemArray)) {

							$basketText =
								$basketView->getView(
                                    $errorCode,
									$templateCode,
									'EMAIL',
									$infoViewObj,
									false,
									true,
									$calculatedArray,
									false,
									$suffixControlArray['template'],
									$mainMarkerArray,
									'',
									$basketItemArray,
                                    array(),
                                    $basketExtra
								);
							$basketText = trim($basketText);

                            if ($this->conf['orderEmail_htmlmail']) {
                                $basketHtml =
                                    $basketView->getView(
                                        $errorCode,
                                        $templateCode,
                                        'EMAIL',
                                        $infoViewObj,
                                        false,
                                        false,
                                        $calculatedArray,
                                        true,
                                        $suffixControlArray['htmltemplate'],
                                        $mainMarkerArray,
                                        '',
                                        $basketItemArray,
                                        array(),
                                        $basketExtra
                                    );
                            }
                            $basketHtml = trim($basketHtml);
                        }

						if ($basketText != '') {
							$this->splitSubjectAndText(
								$basketText,
								$subject,
								$textContent
							);
							$subject = ($suffixControlArray['subject'] != '' ? $suffixControlArray['subject'] : $subject);
							$HTMLmailContent = '';

                            if ($basketHtml != '') {
                                $HTMLmailContent = $parser->substituteMarker($HTMLmailShell, '###HTML_BODY###', $basketHtml);

                                $HTMLmailContent =
                                    $parser->substituteMarkerArray(
                                        $HTMLmailContent,
                                        $markerArray
                                    );
                            }

							$fromArray = array();

							if (
								isset($suffixControlArray['from'])
								&& is_array($suffixControlArray['from'])
							) {
								$fromArray = $suffixControlArray['from'];
							} else {
								$fromArray = $defaultFromArray['shop'];
							}

							if (isset($suffixControlArray['recipient']) && is_array($suffixControlArray['recipient'])) {
								foreach ($suffixControlArray['recipient'] as $recipientEmail) {

									tx_ttproducts_email_div::send_mail(
										$recipientEmail,
										$apostrophe . $subject . $apostrophe,
										$textContent,
										$HTMLmailContent,
										$fromArray['email'],
										$fromArray['name'],
										$suffixControlArray['attachmentFile'],
										$suffixControlArray['bcc'],
										$suffixControlArray['returnPath']
									);
								}
							}
						}
					}
				}
			}

			$finalizeConf = &$cnfObj->getFinalizeConf('productsFilter');

			if (is_array($finalizeConf) && count($finalizeConf)) {

				foreach ($finalizeConf as $k => $conf) {
					$reducedItemArray = array();

					if (isset($conf['pid']) && isset($conf['email'])) {
						foreach ($itemArray as $sort => $actItemArray) {
							$reducedActItemArray = array();
							foreach ($actItemArray as $k1 => $actItem) {
								$row = $actItem['rec'];
								if ($row['pid'] == $conf['pid']) {
									$reducedActItemArray[] = $actItem;
								}
							}
							if (count($reducedActItemArray)) {
								$reducedItemArray[$sort] = $reducedActItemArray;
							}
						}

						if ($emailControlArray['shop']['none']['content'] != '') {
							$emailKey = 'shop';
						} else {
							$emailKey = 'customer';
						}

						$calculatedArray = $calculObj->getCalculatedArray();  // TODO: use a different calculation
						$reducedBasketPlaintext =
							trim (
								$basketView->getView(
                                    $errorCode,
									$templateCode,
									'EMAIL',
									$infoViewObj,
									false,
									true,
									$calculatedArray,
									$this->conf['orderEmail_htmlmail'],
									$emailControlArray[$emailKey]['none']['template'],
									$mainMarkerArray,
									'',
									$reducedItemArray,
                                    array(),
                                    $basketExtra
								)
							);
						$this->splitSubjectAndText(
							$reducedBasketPlaintext,
							$subject,
							$textContent
						);
						$textContent =
							$parser->substituteMarkerArray(
								$textContent,
								$markerArray
							);

                        $HTMLmailContent = $customerHTMLmailContent;
						if ($this->conf['orderEmail_htmlmail']) {
							$reducedBasketHtml =
								trim (
									$basketView->getView(
                                        $errorCode,
										$templateCode,
										'EMAIL',
										$infoViewObj,
										false,
										false,
										$calculatedArray,
										true,
										$emailControlArray[$emailKey]['none']['htmltemplate'],
										$mainMarkerArray,
										'',
										$reducedItemArray,
										array(),
										$basketExtra
									)
								);

                            if ($reducedBasketHtml != '') {
                                $HTMLmailContent =
                                    $this->pibase->cObj->substituteMarker(
                                        $HTMLmailShell,
                                        '###HTML_BODY###',
                                        $reducedBasketHtml
                                    );

                                $HTMLmailContent =
                                    $parser->substituteMarkerArray(
                                        $HTMLmailContent,
                                        $markerArray
                                    );
                            }
                        } else {
                            $HTMLmailContent = '';
                        }

						tx_ttproducts_email_div::send_mail(
							$conf['email'],
							$apostrophe . $subject . $apostrophe,
							$textContent,
							$HTMLmailContent,
							$emailControlArray['customer']['none']['from']['email'],
							$emailControlArray['customer']['none']['from']['name'],
							'',
							'',
							$emailControlArray['customer']['none']['returnPath']
						);
					}
				}
			}

			if ($emailControlArray['radio1']['none']['plaintext'] && is_array($emailControlArray['radio1']['none']['recipient'])) {
				foreach ($emailControlArray['radio1']['none']['recipient'] as $key => $recipient) {
                    tx_ttproducts_email_div::send_mail(
						$recipient,
						$apostrophe . $emailControlArray['radio1']['none']['subject'] . $apostrophe,
						$emailControlArray['radio1']['none']['plaintext'],
						$customerHTMLmailContent,
						$emailControlArray['shop']['none']['from']['email'],
						$emailControlArray['shop']['none']['from']['name'],
						$emailControlArray['radio1']['none']['attachmentFile'],
						'',
						$emailControlArray['shop']['none']['returnPath']
					);
				}
			}

			if (is_array($instockTableArray) && $this->conf['warningInStockLimit']) {
				$tableDescArray = array ('tt_products' => 'product', 'tt_products_articles' => 'article');
				foreach ($instockTableArray as $tablename => $instockArray) {
					$tableDesc = $languageObj->getLabel($tableDescArray[$tablename]);

					if (isset($instockArray) && is_array($instockArray)) {
						foreach ($instockArray as $instockTmp => $count) {
							$uidItemnrTitle = GeneralUtility::trimExplode(',', $instockTmp);
							if ($count <= $this->conf['warningInStockLimit']) {

                                $content =
									sprintf(
                                        $languageObj->getLabel('instock_warning'),
										$tableDesc . ' ' . $uidItemnrTitle[2] . '',
										$uidItemnrTitle[1],
										intval($count)
									);
                                $subject =
									sprintf(
                                        $languageObj->getLabel('instock_warning_header'),
										$uidItemnrTitle[2] . '',
										intval($count)
									);
								if (
									isset($emailControlArray['shop']['none']['recipient']) && is_array($emailControlArray['shop']['none']['recipient'])
								) {

									foreach ($emailControlArray['shop']['none']['recipient'] as $key => $recipient) {
										// $headers variable removed everywhere!
										tx_ttproducts_email_div::send_mail(
											$recipient,
											$apostrophe . $subject . $apostrophe,
											$content,
											$tmp='',	// no HTML order confirmation email for shop admins
											$emailControlArray['shop']['none']['from']['email'],
											$emailControlArray['shop']['none']['from']['name'],
											'',
											'',
											$emailControlArray['shop']['none']['returnPath']
										);
									}
								}
							}
						}
					}
				}
			}
		}

		// 3 different hook methods - There must be one for your needs, too.

			// This cObject may be used to call a function which clears settings in an external order system.
			// The output is NOT included anywhere
		tx_div2007_alpha5::getExternalCObject_fh003($this->pibase, 'externalFinalizing');
		if ($this->conf['externalOrderProcessFunc']) {
			tx_div2007_alpha5::userProcess_fh002($this->pibase, $this->conf, 'externalOrderProcessFunc', $basketObj);
		}

			// Call all finalizeOrder hooks
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['finalizeOrder'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['finalizeOrder'] as $classRef) {
				$hookObj= GeneralUtility::makeInstance($classRef);
				if (method_exists($hookObj, 'finalizeOrder')) {
					$hookObj->finalizeOrder(
						$this,
						$infoViewObj,
						$templateCode,
						$basketView,
						$functablename,
						$orderUid,
						$orderConfirmationHTML,
						$errorMessage,
						$result
					);
				}
			}
		}
		$orderObj->clearUid();

		return $result;

	} // doProcessing
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_activity_finalize.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_activity_finalize.php']);
}

