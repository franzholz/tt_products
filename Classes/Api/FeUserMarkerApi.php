<?php

declare(strict_types = 1);

namespace JambageCom\TtProducts\Api;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
/**
 * Part of the tt_products (Shop System) extension.
 *
 * FE user marker functions
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 */
use JambageCom\Div2007\Api\Frontend;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class FeUserMarkerApi implements SingletonInterface
{
    public function getWrappedSubpartArray(
        $modelObj,
        $viewTagArray,
        array &$subpartArray,
        array &$wrappedSubpartArray
    ): void {
        $api =
            GeneralUtility::makeInstance(Frontend::class);
        $context = GeneralUtility::makeInstance(Context::class);

        $typoScriptFrontendController = $api->getTypoScriptFrontendController();
        $marker = 'FE_GROUP';
        $markerLogin = 'LOGIN';
        $markerNologin = 'NOLOGIN';
        foreach ($viewTagArray as $tag => $value) {
            if (strpos($tag, $marker . '_') === 0) {
                $tagPart1 = substr($tag, strlen($marker . '_'));
                $offset = strpos($tagPart1, '_TEMPLATE');
                if ($offset > 0) {
                    $groupNumber = substr($tagPart1, 0, $offset);

                    if (MathUtility::canBeInterpretedAsInteger($groupNumber)) {
                        $comparatorNumber = $groupNumber;
                        if (!$comparatorNumber) {
                            $comparatorNumber = -1; // Also a logged in Front End User has group 0!
                        }

                        if (
                            GeneralUtility::inList(
                                implode(
                                    ',',
                                    GeneralUtility::makeInstance(
                                        Context::class
                                    )->getPropertyFromAspect('frontend.user', 'groupIds')
                                ),
                                $comparatorNumber
                            )
                        ) {
                            $wrappedSubpartArray['###FE_GROUP_' . $groupNumber . '_TEMPLATE###'] = ['', ''];
                        } else {
                            $subpartArray['###FE_GROUP_' . $groupNumber . '_TEMPLATE###'] = '';
                        }
                    }
                }
            } elseif (strpos($tag, $markerLogin . '_') === 0) {
                if (
                    $context->getPropertyFromAspect('frontend.user', 'isLoggedIn')
                ) {
                    $wrappedSubpartArray['###LOGIN_TEMPLATE###'] = ['', ''];
                } else {
                    $subpartArray['###LOGIN_TEMPLATE###'] = '';
                }
            } elseif (strpos($tag, $markerNologin . '_') === 0) {
                if (
                    $context->getPropertyFromAspect('frontend.user', 'isLoggedIn')
                ) {
                    $subpartArray['###NOLOGIN_TEMPLATE###'] = '';
                } else {
                    $wrappedSubpartArray['###NOLOGIN_TEMPLATE###'] = ['', ''];
                }
            }
        }

        if (
            isset($viewTagArray['FE_CONDITION1_TRUE_TEMPLATE']) ||
            isset($viewTagArray['FE_CONDITION1_FALSE_TEMPLATE'])
        ) {
            if (
                method_exists($modelObj, 'getCondition') &&
                $modelObj->getCondition() ||
                method_exists($modelObj, 'getConditionRecord') &&
                !$modelObj->getConditionRecord()
            ) {
                $wrappedSubpartArray['###FE_CONDITION1_TRUE_TEMPLATE###'] = ['', ''];
                $subpartArray['###FE_CONDITION1_FALSE_TEMPLATE###'] = '';
            } else {
                $wrappedSubpartArray['###FE_CONDITION1_FALSE_TEMPLATE###'] = ['', ''];
                $subpartArray['###FE_CONDITION1_TRUE_TEMPLATE###'] = '';
            }
        }

        if (isset($viewTagArray['FEUSER_HAS_DISCOUNT'])) {
            $discountApi = GeneralUtility::makeInstance(DiscountApi::class);
            $discountArray = $discountApi->getFeuserDiscounts();
            $hasDiscount = is_array($discountArray) && (count($discountArray) > 0);

            if (
                $hasDiscount
            ) {
                $wrappedSubpartArray['###FEUSER_HAS_DISCOUNT###'] = ['', ''];
                $subpartArray['###FEUSER_HAS_NO_DISCOUNT###'] = '';
            } else {
                $subpartArray['###FEUSER_HAS_DISCOUNT###'] = '';
                $wrappedSubpartArray['###FEUSER_HAS_NO_DISCOUNT###'] = ['', ''];
            }
        }
    }

    public function getGlobalMarkerArray(
        &$markerArray
    ): void {
        $discountApi = GeneralUtility::makeInstance(DiscountApi::class);
        $discountValue = $discountApi->getMaximumFeuserDiscount();
        $markerArray['###FEUSER_TOTAL_DISCOUNT###'] = $discountValue;
        $markerArray['###FE_USER_TT_PRODUCTS_DISCOUNT###'] = $discountValue; // deprecated -> 2025
    }
}
