<?php
defined('TYPO3_MODE') || die('Access denied.');

$result = array(
    'ctrl' => array(
        'title' => 'unused product tax category relations',
        'label' => 'uid_local',
        'delete' => 'deleted',
        'enablecolumns' => array (
            'disabled' => 'hidden',
        ),
        'prependAtCopy' => DIV2007_LANGUAGE_LGL . 'prependAtCopy',
        'hideTable' => true,
    ),
    'columns' => array(
        'uid_local' => array (
            'label' => 'inactive',
            'config' => array (
                'type' => 'passthrough',
                'default' => '',
            )
        ),
    ),
    'types' => array(
        '0' => array(
            'showitem' => ''
        )
    )
);

if (
    defined('TYPO3_version') &&
    version_compare(TYPO3_version, '10.0.0', '<')
) {
    $result['interface'] = [];
    $result['interface']['showRecordFieldList'] =   
        '';
}

return $result;

