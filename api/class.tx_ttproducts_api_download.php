<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2014 Franz Holzinger (franz@ttproducts.de)
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
 * Download API functions
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */

use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class tx_ttproducts_api_download
{
    public static function fetchFal(
        $fileReferenceUid
    ) {
        $storageRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
        $storage = $storageRepository->findByUid(1);
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        $fileObj = $resourceFactory->getFileReferenceObject($fileReferenceUid);

        $fileInfo = $storage->getFileInfo($fileObj);
        $mimeType = $fileInfo['mimetype'];
        $content = $fileObj->getContents();
        $properties = $fileObj->getProperties();

        if (!empty($properties['mime_type'])) {
            $mimeType = $properties['mime_type'];
        }

        ob_end_clean();
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: ' . $mimeType);
        header('Content-Type: application/download');
        header('Content-Disposition: attachment; filename=' . $properties['name']);
        header('Content-Length: ' . $properties['size']);

        echo $content;
        exit;
    }
}
