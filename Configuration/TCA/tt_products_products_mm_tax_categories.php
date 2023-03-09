<?php
defined('TYPO3') || die('Access denied.');

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

return $result;

