<?php

declare(strict_types=1);

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

namespace JambageCom\AddonsEm\Xclass\Schema;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\Table;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\Exception\StatementException;
use TYPO3\CMS\Core\Database\Schema\Parser\Parser;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Helper methods to handle SQL files and transform them into individual statements
 * for further processing.
 *
 * @internal
 */
class SchemaMigrator extends \TYPO3\CMS\Core\Database\Schema\SchemaMigrator
{
    /**
     * Import static data (UPDATE and INSERT statements)
     *
     * @param array $statements
     * @param bool $truncate
     * @return array
     */
    public function importStaticData(array $statements, bool $truncate = false): array
    {
        $result = [];
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $updateStatements = [];

        foreach ($statements as $statement) {
            // Only handle insert statements and extract the table at the same time. Extracting
            // the table name is required to perform the inserts on the right connection.
            if (preg_match('/^UPDATE\s+`?(\w+)`?(.*)/i', $statement, $matches)) {
                [, $tableName, $sqlFragment] = $matches;
                $updateStatements[$tableName][] = sprintf(
                    'UPDATE %s %s',
                    $connectionPool->getConnectionForTable($tableName)->quoteIdentifier($tableName),
                    rtrim($sqlFragment, ';')
                );
            }
        }

        foreach ($updateStatements as $tableName => $perTableStatements) {
            $connection = $connectionPool->getConnectionForTable($tableName);

            foreach ((array)$perTableStatements as $statement) {
                try {
                    $connection->executeUpdate($statement);
                    $result[$statement] = '';
                } catch (DBALException $e) {
                    $result[$statement] = $e->getPrevious()->getMessage();
                }
            }
        }

        if (empty($result)) {
            $result = parent::importStaticData($statements, $truncate);
        }
        return $result;
    }
}
