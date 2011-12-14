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
use Doctrine\DBAL\Event\SchemaColumnDefinitionEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableChangeColumnEventArgs;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;

/**
 * @author Jan Sorgalla <jsorgalla@googlemail.com>
 */
class MySqlHandler extends AbstractHandler
{
    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaCreateTableColumnEventArgs $args
     */
    public function onSchemaCreateTableColumn(SchemaCreateTableColumnEventArgs $args)
    {
        $column = $args->getColumn();

        if (!$column->getType() instanceof \Doctrine\Spatial\DBAL\Types\Type) {
            return;
        }

        if ($column->hasCustomSchemaOption('spatial_index') && $column->getCustomSchemaOption('spatial_index')) {
            $platform = $args->getPlatform();
            $indexName = $this->generateIndexName($args->getTable()->getName(), $column->getName());

            $args->addSql(sprintf(
                "CREATE SPATIAL INDEX %s ON %s (%s)",
                $indexName,
                $args->getTable()->getQuotedName($platform), // Table name
                $column->getQuotedName($platform) // Column name
            ));
        }
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaAlterTableChangeColumnEventArgs $args
     */
    public function onSchemaAlterTableChangeColumn(SchemaAlterTableChangeColumnEventArgs $args)
    {
        $columnDiff = $args->getColumnDiff();
        $column = $columnDiff->column;

        if (!$column->getType() instanceof \Doctrine\Spatial\DBAL\Types\Type) {
            return;
        }

        if ($columnDiff->hasChanged('spatial_index')) {
            $diff      = $args->getTableDiff();
            $tableName = $diff->newName !== false ? $diff->newName : $diff->name;
            $indexName = $this->generateIndexName($tableName, $column->getName());

            if ($column->getCustomSchemaOption('spatial_index')) {
                $args->addSql(sprintf(
                    "CREATE SPATIAL INDEX %s ON %s (%s)",
                    $indexName,
                    $tableName, // Table name
                    $column->getQuotedName($platform) // Column name
                ));
            } else {
                $args->addSql($args->getPlatform()->getDropIndexSQL($indexName, $tableName));
            }
        }

        // Check if only spatial properties were changed
        $found = false;
        foreach ($columnDiff->changedProperties as $property) {
            if (strpos($property, 'spatial_') === 0) {
                continue;
            }
            
            $found = true;
            break;
        }

        if (!$found) {
            $args->preventDefault();
        }
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaColumnDefinitionEventArgs $args
     */
    public function onSchemaColumnDefinition(SchemaColumnDefinitionEventArgs $args)
    {
        $tableColumn = $args->getTableColumn();
        $table       = $args->getTable();
        $conn        = $args->getConnection();

        $tableColumn = array_change_key_case($tableColumn, CASE_LOWER);

        switch (strtolower($tableColumn['type'])) {
            case 'point':
            case 'linestring':
            case 'polygon':
            case 'multipoint':
            case 'multilinestring':
            case 'multipolygon':
            case 'geometrycollection':
                break;
            default:
                return;
        }

        $sql = "SHOW INDEX FROM " .  $table . " WHERE Column_name = ?";

        $stmt = $conn->prepare($sql);
        $stmt->execute(array($this->generateIndexName($table, $tableColumn['field'])));
        $indexExists = (bool) $stmt->fetch(\PDO::FETCH_ASSOC);

        $options = array(
            'length'        => null,
            'unsigned'      => false,
            'fixed'         => null,
            'default'       => isset($tableColumn['default']) ? $tableColumn['default'] : null,
            'notnull'       => (bool) ($tableColumn['null'] != 'YES'),
            'scale'         => null,
            'precision'     => null,
            'autoincrement' => false,
            'comment'       => (isset($tableColumn['comment'])) ? $tableColumn['comment'] : null
        );

        $column = new Column($tableColumn['field'], Type::getType($tableColumn['type']), $options);

        $column
            ->setCustomSchemaOption('spatial_srid',      -1)
            ->setCustomSchemaOption('spatial_dimension', 2)
            ->setCustomSchemaOption('spatial_index',     $indexExists);

        $args
            ->preventDefault()
            ->setColumn($column);
    }
}
