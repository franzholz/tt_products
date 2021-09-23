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
 * interface for database table fields
 */
interface FieldInterface
{
	const DISCOUNT = 'discount';
	const DISCOUNT_DISABLE = 'discount_disable';
	const PRICE_CALCULATED = 'calc';
	const PRICE_CALCULATED_ADDITION = 'calc_add';
}


