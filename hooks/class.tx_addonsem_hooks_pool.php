<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Franz Holzinger (franz@ttproducts.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Part of the addons_em (Addons to the Extension Manager) extension.
 *
 * hook functions for the pool extension
 *
 * $Id: class.tx_addonsem_hooks_pool.php 323 2015-12-03 11:27:01Z franzholz $
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */



class tx_addonsem_hooks_pool extends tx_pool_hooks_base {
	public $extKey = 'addons_em';
	public $prefixId = 'tx_addonsem_hooks_pool';	// Same as class name
	public $LLFileArray = array ('hooks/locallang_pool.xml');
	public $modMenu = array('function' => array('generate'));
	public $headerText = 'header_generate';

// Todo:  typo3/sysext/t3skin/images/icons/actions/system-extension-download.png

	public function getViewData (&$content, &$header, &$docHeaderButtons, &$markerArray, &$pOb) {
		$errorText = FALSE;
		$postVar = 'CMD';
		$cmdData = t3lib_div::_GP($postVar);
		$config = array();
		$data = t3lib_div::_GP($this->prefixId);
		$config['extKey'] = 'tt_products';
		$config['version'] = '2.7.2';
		$config['patchlevel'] = '7';

		$confArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);

		if (
			isset($confArray) &&
			is_array($confArray)
		) {
			if (isset($confArray['version'])) {
				$config['version'] = $confArray['version'];
			}
			if (isset($confArray['patchlevel'])) {
				$config['patchlevel'] = $confArray['patchlevel'];
			}
		}

		$pathSource = 'fileadmin/extension/original/' .
			$config['extKey'] . '-' . $config['version'] . '/' .
			$config['extKey'] . '_' . $config['version'] . '.' . $config['patchlevel'] . '/' .
			$config['extKey'] . '/';

		if (
			isset($cmdData) &&
			is_array($cmdData)
		) {
			if ($cmdData['save'] != '') {
				if (
					isset($data) &&
					is_array($data)
				) {
					$config = array_merge($config, $data);
				}
				if (isset($confArray) && is_array($confArray)) {
					$confArray = array_merge($confArray, $config);
				} else {
					$confArray = $config;
				}

				tx_addonsem_file_div::writeLocalconfValue($this->extKey, serialize($confArray), 'extension addons_em');
			}

			if ($cmdData['download'] != '') {
				t3lib_div::requireOnce(PATH_BE_div2007 . 'class.tx_div2007_alpha.php');

				$path = PATH_site . $pathSource;
				$extInfo = tx_div2007_alpha::getExtensionInfo_fh002($config['extKey'], $path);

				if (is_array($extInfo)) {

						// Ausführen des Kommandos und EXIT
					tx_addonsem_file_div::extBackup($config['extKey'], $path, $extInfo);
				} else {
					$errorText = $extInfo;
				}
			}
		}

		$url = 'www.meinedomaene.de';

		if ($errorText) {
			$content = '<b>' . $errorText . '</b>';
		} else {
			$content = '<b>Es kann eine Lizenzdatei generiert und in ext_emconf.php eingetragen werden.</b><br/>';
			$pathDestination = 'fileadmin/extension/licence/tt_products-' . $config['version'] . '/';

			$content .= 'Es wird die Extension "' . $config['extKey'] . '" aus dem Verzeichnis "' . $pathSource . '" generiert<br/>';
			$content .= 'Die Domäne für die Lizenz ist "' . $url . '"<br/>';
			$content .= 'Das Zielverzeichnis ist "' . $pathDestination . '"<br/>';
		}
		$content .= '<br/>';
		$content .= '<p>Version: </p><input type="text" title="Version" name="' . $this->prefixId . '[version]" value="' . $config['version'] . '" />"';
		$content .= '<br/>';
		$content .= '<p>Patch Level: </p><input type="text" title="Patch" name="' . $this->prefixId . '[patchlevel]" value="' . $config['patchlevel'] . '" />"';
		$content .= '<br/>';
		$content .= '<input type="submit" name="' . $postVar . '[save]" title="Save" value="&Auml;ndern" >&nbsp;&nbsp;&nbsp;';

		$directLink = 'index.php?M=txpoolM1';

		$content .=
			'<a href="' .
				htmlspecialchars(
					$directLink . '&' . $postVar . '[download]=1'
				) .
			'" title="' .
			$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:download') . '">' .
			t3lib_iconWorks::getSpriteIcon('actions-system-extension-download') .
			'</a>';
	}
}

