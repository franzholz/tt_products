<?php

namespace JambageCom\TtProducts\Middleware;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Initialization for the backend
 */
class Initialization implements MiddlewareInterface
{
    /**
     * Initialize the backend.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $extensionKey = 'tt_products';
        $pageType = 'ttproducts'; // a maximum of 10 characters
        $icons = [
            'apps-pagetree-folder-contains-' . $pageType => 'apps-pagetree-folder-contains-tt_products.svg',
        ];
        /** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
        $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
        foreach ($icons as $identifier => $filename) {
            $iconRegistry->registerIcon(
                $identifier,
                $iconRegistry->detectIconProvider($filename),
                    ['source' => 'EXT:' . $extensionKey . '/Resources/Public/Icons/Apps/' . $filename]
            );
        }

        return $handler->handle($request);
    }
}
