<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->exclude([
        'data/',
    ])
    ->append([
        __FILE__,
        __DIR__.'/bootstrap.php',
    ])
;

return (new PhpCsFixer\Config())
    ->setUsingCache(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'strict_param' => true,
        'array_syntax' => ['syntax' => 'short'],
        'fopen_flags' => false,
        'array_indentation' => true,
        'ordered_imports' => true,
        'protected_to_private' => false,
        'list_syntax' => ['syntax' => 'short'],
        'psr_autoloading' => ['dir' => 'src/'],
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
