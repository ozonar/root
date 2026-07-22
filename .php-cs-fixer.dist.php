<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/config',
        __DIR__ . '/migrations',
    ])
    ->append([
        __DIR__ . '/bin/console',
        __DIR__ . '/public/index.php',
    ])
;

$config = new PhpCsFixer\Config();

return $config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PhpCsFixer' => true,
        '@Symfony' => true,
        'strict_param' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'not_operator_with_successor_space' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],
        'phpdoc_align' => ['align' => 'vertical'],
        'blank_line_before_statement' => [
            'statements' => ['break', 'continue', 'declare', 'return', 'throw', 'try'],
        ],
        'single_line_comment_style' => false,
        'php_unit_method_casing' => ['case' => 'snake_case'],
        'php_unit_test_class_requires_covers' => false,
        'php_unit_internal_class' => false,
        'general_phpdoc_annotation_remove' => [
            'annotations' => ['author', 'package'],
        ],
    ])
    ->setFinder($finder)
;