<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2016 Franz Holzinger (franz@ttproducts.de)
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
 * download functions for extensions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


class tx_addonsem_file_div {

	/**
	 * Include a locallang file and return the $LOCAL_LANG array serialized.
	 *
	 * @param	string		Absolute path to locallang file to include.
	 * @param	string		Old content of a locallang file (keeping the header content)
	 * @return	array		Array with header/content as key 0/1
	 * @see makeUploadarray()
	 */
	static public function getSerializedLocalLang ($file, $content)
	{
		$LOCAL_LANG = null;
		$returnParts = explode('$LOCAL_LANG', $content, 2);

		include($file);
		if (is_array($LOCAL_LANG)) {
			$returnParts[1] = serialize($LOCAL_LANG);
			return $returnParts;
		} else {
			return array();
		}
	}

	/**
	 * Encodes extension upload array
	 *
	 * @param	array		Array containing extension
	 * @return	string		Content stream
	 */
	static public function makeUploadDataFromarray ($uploadArray)
	{
		$content = '';
		if (is_array($uploadArray)) {
			$serialized = serialize($uploadArray);
			$md5 = md5($serialized);

			$content = $md5 . ':';
			$content .= 'gzcompress:';
			$content .= gzcompress($serialized);
		}
		return $content;
	}

	/**
	 * Make upload array out of extension
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	mixed		Returns array with extension upload array on success, otherwise an error string.
	 */
	static function makeUploadarray ($extKey, $extPath, $conf, $orderRow, $variantVars)
	{
		$result = false;
		$hookVar = 'file';
		$callingClassName = '\\TYPO3\\CMS\\Core\\Utility\\GeneralUtility';

		if (
			!class_exists($callingClassName) ||
			!method_exists($callingClassName, 'getAllFilesAndFoldersInPath')
		) {
			$callingClassName = 't3lib_div';
		}

		if ($extKey != '' && $extPath != '' && is_array($conf)) {

			// Get files for extension:
			$fileArray = array();
			$fileArray =
				call_user_func(
					$callingClassName . '::getAllFilesAndFoldersInPath',
					$fileArray,
					$extPath,
					'',
					0,
					99,
					$GLOBALS['TYPO3_CONF_VARS']['EXT']['excludeForPackaging']
				);

			$generatedFilenames = array();
			$generatedFilenames[] = 'ext_emconf.php';

			if (
				$hookVar &&
				is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['addons_em'][$hookVar])
			) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['addons_em'][$hookVar] as $classRef) {
					$hookObj= call_user_func(
						$callingClassName . '::getUserObj',
						$classRef
					);

					if (method_exists($hookObj, 'getGeneratedFilenames')) {

						$result =
							$hookObj->getGeneratedFilenames(
								$extKey,
								$extPath,
								$conf,
								$orderRow,
								$variantVars,
								$generatedFilenames
						);
					}
				}
			}

			// Initialize output array:
			$uploadArray = array();
			$uploadArray['extKey'] = $extKey;
			$uploadArray['EM_CONF'] = $conf;
			$uploadArray['misc']['codelines'] = 0;
			$uploadArray['misc']['codebytes'] = 0;

			// Read all files:
			foreach ($fileArray as $file) {
				$relFileName = substr($file, strlen($extPath));
				$fI = pathinfo($relFileName);

				if (!in_array($relFileName, $generatedFilenames)) { // This file should be dynamically written...

					$uploadArray['FILES'][$relFileName] = array(
						'name' => $relFileName,
// 						'size' => filesize($file),
						'mtime' => filemtime($file),
						'is_executable' => (TYPO3_OS == 'WIN' ? 0 : is_executable($file)),
						'content' => call_user_func(
								$callingClassName . '::getUrl',
								$file
							)
					);

					if (
						$hookVar &&
						is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['addons_em'][$hookVar])
					) {
						foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['addons_em'][$hookVar] as $classRef) {
							$hookObj = call_user_func(
								$callingClassName . '::getUserObj',
								$classRef
							);
							if (method_exists($hookObj, 'modifyFile')) {
								$result =
									$hookObj->modifyFile(
										$file,
										$orderRow,
										$variantVars,
										$extKey,
										$conf,
										$uploadArray['FILES'][$relFileName]
									);
							}
						}
					}

					if (call_user_func($callingClassName . '::inList', 'php,inc', strtolower($fI['extension']))) {
						$uploadArray['FILES'][$relFileName]['codelines'] = count(explode(LF, $uploadArray['FILES'][$relFileName]['content']));

						$uploadArray['FILES'][$relFileName]['size'] = strlen($uploadArray['FILES'][$relFileName]['content']);
						$uploadArray['misc']['codelines'] += $uploadArray['FILES'][$relFileName]['codelines'];
						$uploadArray['misc']['codebytes'] += $uploadArray['FILES'][$relFileName]['size'];

						// locallang*.php files:
						if (
							substr($fI['basename'], 0, 9) == 'locallang' &&
							strstr($uploadArray['FILES'][$relFileName]['content'], '$LOCAL_LANG')
						) {
							$uploadArray['FILES'][$relFileName]['LOCAL_LANG'] =
								self::getSerializedLocalLang(
									$file,
									$uploadArray['FILES'][$relFileName]['content']
								);
						}
					}

					$uploadArray['FILES'][$relFileName]['content_md5'] = md5($uploadArray['FILES'][$relFileName]['content']);
				}
			}

			if (
				$hookVar &&
				is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['addons_em'][$hookVar])
			) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['addons_em'][$hookVar] as $classRef) {
					$hookObj = call_user_func(
						$callingClassName . '::getUserObj',
						$classRef
					);

					if (method_exists($hookObj, 'addFile')) {
						$result =
							$hookObj->addFile(
								$extKey,
								$extPath,
								$conf,
								$fileArray,
								$orderRow,
								$variantVars,
								$uploadArray
						);
					}
				}
			}

			// Return upload-array:
			$result = $uploadArray;
		} else {
			$LANG = $GLOBALS['LANG'];
			if (!is_object($LANG)) {
				$LANG = call_user_func(
						$callingClassName . '::makeInstance',
						'language'
					);
				$LANG->init($GLOBALS['TSFE']->tmpl->setup['config.']['language']);
			}
			$result = sprintf($LANG->getLL('makeUploadArray_error_path'),
				$extKey);
		}
		return $result;
	}

	/**
	 * Download extension as file / make backup
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	void		EXIT from PHP
	 */
	static public function extBackup ($extKey, $path, $extInfo, $orderRow, $variantVars)
	{
		$result = false;

		$callingClassName = '\\TYPO3\\CMS\\Core\\Utility\\GeneralUtility';

		if (
			!class_exists($callingClassName) ||
			!method_exists($callingClassName, 'makeInstance')
		) {
			$callingClassName = 't3lib_div';
		}

		$uArr =
			self::makeUploadarray(
				$extKey,
				$path,
				$extInfo,
				$orderRow,
				$variantVars
			);

		if (is_array($uArr)) {
			$backUpData = self::makeUploadDataFromarray($uArr);
			$time = filemtime($path);
			$filename = 'T3X_' . $extKey . '-' . str_replace('.', '_', $extInfo['version']) . '-z-' . date('YmdHi', $time) . '.t3x';

			ob_end_clean();
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename=' . $filename);
			echo $backUpData;
			exit;
		} else {
			$LANG = $GLOBALS['LANG'];
			if (!is_object($LANG)) {
				$LANG = call_user_func(
						$callingClassName . '::makeInstance',
						'language'
					);

				$LANG->init($GLOBALS['TSFE']->tmpl->setup['config.']['language']);
			}
			throw new RuntimeException(
				'TYPO3 Fatal Error: ' . $LANG->getLL('extBackup_unexpected_error'),
				1270853981
			);
		}
	}

	/**
	 * Writes the extension list to "localconf.php" file
	 * Removes the temp_CACHED* files before return.
	 *
	 * @param	string		List of extensions
	 * @return	void
	 */
	static public function writeLocalconfValue ($extKey, $newValue, $updateIdentity)
	{
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return false;
	}
}

