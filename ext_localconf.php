<?php
defined('TYPO3_MODE') || die('Access denied.');

if (
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('pool')
) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/pool/mod_main/index.php']['addClass'][] = 'EXT:addons_em/hooks/class.tx_addonsem_hooks_pool.php:&tx_addonsem_hooks_pool';
}

