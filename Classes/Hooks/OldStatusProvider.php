<?php

namespace JambageCom\TtProducts\Hooks;

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
use JambageCom\Div2007\Base\OldStatusProviderBase;
use JambageCom\Div2007\Utility\StatusUtility;

/**
 * Hook into the backend module "Reports" checking the configuration required for agency.
 */

class OldStatusProvider extends OldStatusProviderBase
{
    const EXTENSION_KEY =  'tt_products';

    /**
     * @var string Extension key
     */
    protected $extensionKey = self::EXTENSION_KEY;

    /**
     * @var string Extension name
     */
    protected $extensionName = 'Shop System (' . self::EXTENSION_KEY . ')';

    public function getGlobalVariables()
    {
        $result = '';

        return $result;
    }

    /**
     * Compiles a collection of system status checks as a status report.
     *
     * @return array List of status
     */
    public function getStatus()
    {
        $result = [
            'requiredExtensionsAreInstalled' => $this->checkIfRequiredExtensionsAreInstalled(),
            'noConflictingExtensionIsInstalled' => $this->checkIfNoConflictingExtensionIsInstalled(),
            'globalVariablesAreSet' => StatusUtility::checkIfGlobalVariablesAreSet($this->getExtensionName(), $this->getGlobalVariables()),
        ];

        return $result;
    }
}
