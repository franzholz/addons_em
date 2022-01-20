<?php
defined('TYPO3_MODE') || die('Access denied.');

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][TYPO3\CMS\Core\Database\Schema\SchemaMigrator::class] = [
   'className' => JambageCom\AddonsEm\XClass\Schema\SchemaMigrator::class
];


