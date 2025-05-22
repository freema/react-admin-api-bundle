<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/dev'
    ])
    ->exclude('var')
    ->exclude('vendor')
    ->exclude('cache')
;

$config = new PhpCsFixer\Config();
return $config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PHP82Migration' => true,
        '@PhpCsFixer' => true,
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'declare_strict_types' => true,
        'strict_param' => true,
        'strict_comparison' => true,
        'void_return' => true,
        'fully_qualified_strict_types' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => false,
            'import_functions' => false,
        ],
        'php_unit_strict' => true,
        'php_unit_test_class_requires_covers' => false,
        'phpdoc_align' => [
            'align' => 'left',
        ],
        'multiline_whitespace_before_semicolons' => [
            'strategy' => 'no_multi_line',
        ],
        'class_attributes_separation' => [
            'elements' => [
                'const' => 'one',
                'method' => 'one',
                'property' => 'one',
                'trait_import' => 'none',
            ],
        ],
        'blank_line_before_statement' => [
            'statements' => [
                'break',
                'continue',
                'declare',
                'return',
                'throw',
                'try',
                'if',
            ],
        ],
        'binary_operator_spaces' => [
            'default' => 'single_space',
        ],
        'increment_style' => ['style' => 'post'],
        'concat_space' => ['spacing' => 'one'],
        'yield_from_array_to_yields' => true,
        'types_spaces' => ['space' => 'none'],
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters'],
        ],
    ])
    ->setFinder($finder)
    ;