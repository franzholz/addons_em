<?php

########################################################################
# Extension Manager/Repository config file for ext "addons_feusers".
########################################################################

$EM_CONF[$_EXTKEY] = [
    'title' => 'Addons to the Extension Manager',
    'description' => 'Enhancements to the Extension Manager (em].',
    'category' => 'misc',
    'author' => 'Franz Holzinger',
    'author_email' => 'franz@ttproducts.de',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'author_company' => '',
    'version' => '0.8.1',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];

