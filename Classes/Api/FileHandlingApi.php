<?php

namespace JambageCom\AddonsEm\Api;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Api for dealing with files and folders
 */

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileHandlingApi
{
    use LoggerAwareTrait;

    /**
     * Create a zip file from an extension
     *
     * @param	string		Extension key
     * @param	string		Path to extension
     * @param	array		Extension information array
     * @param	array		Order record
     * @param	array		Variant piVars
     * @return string Name and path of create zip file
     */
    public function createZipFileFromExtension(
        $extensionKey,
        $extensionPath,
        array $conf,
        array $orderRow,
        array $variantVars
    ) {
        $hookVar = 'file';
        $version = $conf['version'] ?? '';
        if (empty($version)) {
            $version = '0.0.0';
        }

        $temporaryPath = Environment::getVarPath() . '/transient/';
        if (!@is_dir($temporaryPath)) {
            GeneralUtility::mkdir($temporaryPath);
        }
        $fileName = $temporaryPath . $extensionKey . '_' . $version . '_' . date('YmdHi', $GLOBALS['EXEC_TIME']) . '.zip';

        $zip = new \ZipArchive();
        $zip->open($fileName, \ZipArchive::CREATE);

        $excludePattern = $GLOBALS['TYPO3_CONF_VARS']['EXT']['excludeForPackaging'];

        // Get all the files of the extension, but exclude the ones specified in the excludePattern
        $files = GeneralUtility::getAllFilesAndFoldersInPath(
            [], // No files pre-added
            $extensionPath, // Start from here
            '', // Do not filter files by extension
            true, // Include subdirectories
            PHP_INT_MAX, // Recursion level
            $excludePattern        // Files and directories to exclude.
        );

        // Make paths relative to extension root directory.
        $files = GeneralUtility::removePrefixPathFromList($files, $extensionPath);

        // Remove the one empty path that is the extension dir itself.
        $files = array_filter($files);

        $generatedFilenames = [];

        if (
            $hookVar &&
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['addons_em'][$hookVar])
        ) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['addons_em'][$hookVar] as $classRef) {
                $hookObj = GeneralUtility::makeInstance(
                    $classRef
                );

                if (method_exists($hookObj, 'getGeneratedFilenames')) {

                    $result =
                        $hookObj->getGeneratedFilenames(
                            $extensionKey,
                            $extensionPath,
                            $conf,
                            $orderRow,
                            $variantVars,
                            $generatedFilenames
                        );
                }
            }
        }

        $files = array_merge($files, $generatedFilenames);

        foreach ($files as $file) {
            $fullPath = $extensionPath . $file;

            if (
                $hookVar &&
                is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['addons_em'][$hookVar])
            ) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['addons_em'][$hookVar] as $classRef) {
                    $hookObj = GeneralUtility::makeInstance(
                        $classRef
                    );

                    if (method_exists($hookObj, 'modifyFile')) {
                        $fileContent = '';
                        if (is_file($fullPath)) {
                            $readFile = fopen($fullPath, 'r');
                            $fileContent = fread($writeFile, filesize($fullPath));
                            fclose($readFile);
                        }
                        $result =
                            $hookObj->modifyFile(
                                $file,
                                $orderRow,
                                $variantVars,
                                $extKey,
                                $conf,
                                $fileContent
                            );
                        if ($result && is_file($fullPath)) {
                            $writeFile = fopen($fullPath, 'w');
                            fwrite($writeFile, $fileContent);
                        }
                    }
                }
            }

            // Distinguish between files and directories, as creation of the archive
            // fails on Windows when trying to add a directory with "addFile".
            if (is_dir($fullPath)) {
                $zip->addEmptyDir($file);
            } else {
                $zip->addFile($fullPath, $file);
            }
        }

        $zip->close();
        return $fileName;
    }


    /**
     * Sends a zip file to the browser and deletes it afterwards
     *
     * @param string $fileName
     * @param string $downloadName
     */
    public function sendZipFileToBrowserAndDelete($fileName, $downloadName = ''): void
    {
        if ($downloadName === '') {
            $downloadName = PathUtility::basename($fileName);
        }
        header('Content-Type: application/zip');
        header('Content-Length: ' . filesize($fileName));
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        readfile($fileName);
        unlink($fileName);
        die;
    }
}
