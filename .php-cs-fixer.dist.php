<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

$finder = (new \Symfony\Component\Finder\Finder())
    ->in(__DIR__)
    ->ignoreDotFiles(false)
    ->ignoreVCS(true)
    ->exclude([
        '.ddev',
        'public',
        'var',
        'vendor',
    ])
    ->name('/\.php$/')
;

// foreach ($finder->sortByName() as $file) {
//    /** @var SplFileInfo $file */
//    dump($file->getRealPath());
// }

$revertedSymfonyRules = [
    'cast_spaces' => [ // revert @Symfony
        'space' => 'none',
    ],
    'concat_space' => [ // revert @Symfony
        'spacing' => 'one',
    ],
    'increment_style' => false, // revert @Symfony
    'no_alias_language_construct_call' => false, // revert @Symfony
    'nullable_type_declaration_for_default_null_value' => [ // revert @Symfony
        'use_nullable_type_declaration' => true,
    ],
    'phpdoc_align' => false, // revert @Symfony
    'phpdoc_no_access' => false, // revert @Symfony
    'phpdoc_no_package' => false, // revert @Symfony
    'phpdoc_to_comment' => false, // revert @Symfony
    'single_line_comment_style' => true, // revert @Symfony
    'single_line_throw' => false, // revert @Symfony
    'yoda_style' => false, // revert @Symfony
];

$revertedPhpCsFixerRules = [
    'no_useless_else' => false,
    'no_superfluous_elseif' => false,
];

$revertedPHP81Rules = [
    'octal_notation' => false,
];

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(false)
    ->setRules(array_merge_recursive([
        '@DoctrineAnnotation' => true,
        '@PHP70Migration' => true,
        '@PHP71Migration' => true,
        '@PHP73Migration' => true,
        '@PHP74Migration' => true,
        '@Symfony' => true,
        'multiline_whitespace_before_semicolons' => [ // @PhpCsFixer
            'strategy' => 'new_line_for_chained_calls',
        ],
        'phpdoc_no_empty_return' => true, // @PhpCsFixer
        'operator_linebreak' => [ // adjust @Symfony
            'only_booleans' => true,
            'position' => 'end',
        ],
    ], $revertedPHP81Rules, $revertedPhpCsFixerRules, $revertedSymfonyRules))
    ->setFinder($finder)
;
