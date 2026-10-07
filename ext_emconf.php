<?php

global $_EXTKEY;

$EM_CONF[$_EXTKEY] = [
    'title' => 'Container Cleanup',
    'description' => 'Detects and removes unused container children in tt_content',
    'category' => 'backend',
    'state' => 'alpha',
    'author' => 'MFD SEGGER Team',
    'author_email' => 'segger@marketing-factory.de',
    'author_company' => 'Marketing Factory Digital GmbH',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.26-14.3.99',
            'scheduler' => '13.4.26-14.3.99',
            'container' => '3.1.10-4.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => [
            'Mfd\\ContainerCleanup\\' => 'Classes',
        ],
    ],
];
