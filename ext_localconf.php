<?php

defined('TYPO3') || die('Access denied.');

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][TYPO3\CMS\Core\Database\Schema\SchemaMigrator::class] = [
   'className' => JambageCom\AddonsEm\Xclass\Schema\SchemaMigrator::class
];
