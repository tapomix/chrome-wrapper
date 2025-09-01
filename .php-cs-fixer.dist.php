<?php
# @see https://cs.symfony.com/ (official site)

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__.'/src')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,

        'concat_space' => [
            'spacing' => 'one',
        ],
        'phpdoc_summary' => false,
        'phpdoc_to_comment' => [
            'ignored_tags' => [
                'var',
            ],
        ],
    ])
    ->setFinder($finder)
    ->setUsingCache(false)
;

