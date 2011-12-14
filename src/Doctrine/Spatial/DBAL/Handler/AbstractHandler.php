<?php

/*
 * This file is part of Doctrine\Spatial.
 *
 * (c) Jan Sorgalla <jsorgalla@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Spatial\DBAL\Handler;

use Doctrine\DBAL\Event\SchemaCreateTableColumnEventArgs;
use Doctrine\DBAL\Event\SchemaDropTableEventArgs;
use Doctrine\DBAL\Event\SchemaIndexDefinitionEventArgs;
use Doctrine\DBAL\Event\SchemaColumnDefinitionEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableAddColumnEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableRemoveColumnEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableChangeColumnEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableRenameColumnEventArgs;

/**
 * @author Jan Sorgalla <jsorgalla@googlemail.com>
 */
abstract class AbstractHandler
{
    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaCreateTableColumnEventArgs $args
     */
    public function onSchemaCreateTableColumn(SchemaCreateTableColumnEventArgs $args)
    {
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaDropTableEventArgs $args
     */
    public function onSchemaDropTable(SchemaDropTableEventArgs $args)
    {
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaAlterTableAddColumnEventArgs $args
     */
    public function onSchemaAlterTableAddColumn(SchemaAlterTableAddColumnEventArgs $args)
    {
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaAlterTableRemoveColumnEventArgs $args
     */
    public function onSchemaAlterTableRemoveColumn(SchemaAlterTableRemoveColumnEventArgs $args)
    {
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaAlterTableChangeColumnEventArgs $args
     */
    public function onSchemaAlterTableChangeColumn(SchemaAlterTableChangeColumnEventArgs $args)
    {
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaAlterTableRenameColumnEventArgs $args
     */
    public function onSchemaAlterTableRenameColumn(SchemaAlterTableRenameColumnEventArgs $args)
    {
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaIndexDefinitionEventArgs $args
     */
    public function onSchemaIndexDefinition(SchemaIndexDefinitionEventArgs $args)
    {
        $index = $args->getTableIndex();

        if (0 === stripos($index['name'], 'spatialidx_')) {
            $args->preventDefault();
        }
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaColumnDefinitionEventArgs $args
     */
    public function onSchemaColumnDefinition(SchemaColumnDefinitionEventArgs $args)
    {
    }

    /**
     * @param  array $columnNames
     * @return string
     */
    protected function generateIndexName($table, $column)
    {
        $table  = preg_replace('/[^a-zA-Z0-9_]+/', '', $table);
        $column = preg_replace('/[^a-zA-Z0-9_]+/', '', $column);

        return substr(strtolower('spatialidx_' . $table . $column), 0, 30);
    }
}
