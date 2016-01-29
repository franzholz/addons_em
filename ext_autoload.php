<?php
$emClass = '\\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility';

if (
	class_exists($emClass) &&
	method_exists($emClass, 'extPath')
) {
	// nothing
} else {
	$emClass = 't3lib_extMgm';
}

$key = 'addons_em';
$extensionPath = call_user_func($emClass . '::extPath', $key, $script);

return array(
	'tx_addonsem_hooks_pool' => $extensionPath . 'hooks/class.tx_addonsem_hooks_pool.php',
	'tx_addonsem_file_div' => $extensionPath . 'lib/class.tx_addonsem_file_div.php',
	'tx_addonsem_tca_div' => $extensionPath . 'lib/class.tx_addonsem_tca_div.php',
);
