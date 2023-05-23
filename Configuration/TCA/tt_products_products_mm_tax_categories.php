<?php
defined('TYPO3') || die('Access denied.');

$languageLglPath = 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.';

$result = [
    'ctrl' => [
        'title' => 'unused product tax category relations',
        'label' => 'uid_local',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'prependAtCopy' => $languageLglPath . 'prependAtCopy',
        'hideTable' => true,
    ],
    'columns' => [
        'uid_local' => [
            'label' => 'inactive',
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ]
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => ''
        ]
    ]
];

return $result;

