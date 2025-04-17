<?php

namespace JambageCom\TtProducts\Model\Field;

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
 * interface for database table fields.
 */
interface FieldInterface
{
    final public const DISCOUNT = 'discount';
    final public const DISCOUNT_DISABLE = 'discount_disable';
    final public const PRICE_CALCULATED = 'calc';
    final public const PRICE_CALCULATED_ADDITION = 'calc_add';
    final public const TCA_PRICE = 'price';
    final public const PRICE_TAX = 'pricetax';
    final public const PRICE_NOTAX = 'pricenotax';
    final public const PRICE_ADDED = 'addedPrice';
    final public const PRICE_DEPOSIT = 'deposit';
    final public const PRICE_SURCHARGE = 'surcharge';
    final public const EXTERNAL_FIELD_PREFIX = 'tx_ttproducts_';
}
