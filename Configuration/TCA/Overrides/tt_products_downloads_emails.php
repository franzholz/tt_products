<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}


$table = 'tt_products_downloads_emails';

if (
    version_compare(TYPO3_version, '8.7.0', '<')
) {
    $fieldArray = array('tstamp', 'crdate', 'starttime', 'endtime');

    foreach ($fieldArray as $field) {
        unset($GLOBALS['TCA'][$table]['columns'][$field]['config']['renderType']);
        $GLOBALS['TCA'][$table]['columns'][$field]['config']['max'] = '20';
    }
}

