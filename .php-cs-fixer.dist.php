<?php

$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/scripts',
    ])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_line_empty_body' => true,
        'nullable_type_declaration' => false,
        'modifier_keywords' => ['elements' => ['const', 'method']],
        'braces_position' => false,
        'statement_indentation' => false,
        'function_declaration' => ['closure_fn_spacing' => 'one'],
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(false)
;
