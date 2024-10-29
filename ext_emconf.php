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
    'author_company' => '',
    'version' => '0.9.1',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-13.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
