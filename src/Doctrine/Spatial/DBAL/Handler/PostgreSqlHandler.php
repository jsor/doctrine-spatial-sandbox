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
use Doctrine\DBAL\Event\SchemaColumnDefinitionEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableAddColumnEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableRemoveColumnEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableChangeColumnEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableRenameColumnEventArgs;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

/**
 * @author Jan Sorgalla <jsorgalla@googlemail.com>
 */
class PostgreSqlHandler extends AbstractHandler
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

        $platform = $args->getPlatform();

        $args
            ->preventDefault()
            ->addSql(
                $this->getAddColumnSQL(
                    $args->getTable()->getQuotedName($platform),
                    $column->getQuotedName($platform),
                    $column
                )
            );
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaDropTableEventArgs $args
     */
    public function onSchemaDropTable(SchemaDropTableEventArgs $args)
    {
        $table = $args->getTable();

        if ($table instanceof Table) {
            foreach ($table->getColumns() as $column) {
                if (!$column->getType() instanceof \Doctrine\Spatial\DBAL\Types\Type) {
                    continue;
                }

                $args
                    ->preventDefault()
                    ->setSql("SELECT DropGeometryTable('" . $table->getQuotedName($args->getPlatform()) . "')");
                break;
            }
        } else {
            // We should check here if the table contains geometry columns but we
            // don't have a connection availabe to query the geometry_columns table.
        }
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaAlterTableAddColumnEventArgs $args
     */
    public function onSchemaAlterTableAddColumn(SchemaAlterTableAddColumnEventArgs $args)
    {
        $column = $args->getColumn();

        if (!$column->getType() instanceof \Doctrine\Spatial\DBAL\Types\Type) {
            return;
        }

        $platform = $args->getPlatform();

        $diff = $args->getTableDiff();
        $tableName = $diff->newName !== false ? $diff->newName : $diff->name;
        $args
            ->preventDefault()
            ->addSql(
                $this->getAddColumnSQL(
                    $tableName,
                    $column->getQuotedName($platform),
                    $column
                )
            );
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaAlterTableRemoveColumnEventArgs $args
     */
    public function onSchemaAlterTableRemoveColumn(SchemaAlterTableRemoveColumnEventArgs $args)
    {
        $column = $args->getColumn();

        if (!$column->getType() instanceof \Doctrine\Spatial\DBAL\Types\Type) {
            return;
        }

        $platform = $args->getPlatform();

        $diff = $args->getTableDiff();
        $tableName = $diff->newName !== false ? $diff->newName : $diff->name;;
        $args
            ->preventDefault()
            ->addSql(
                $this->getDropColumnSQL(
                    $tableName,
                    $column->getQuotedName($platform),
                    $column->getNotnull()
                )
            );
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

        $platform = $args->getPlatform();

        $diff = $args->getTableDiff();
        $tableName = $diff->newName !== false ? $diff->newName : $diff->name;

        $args->preventDefault();

        if ($columnDiff->hasChanged('notnull')) {
            $query = 'ALTER ' . $column->getQuotedName($platform) . ' ' . ($column->getNotNull() ? 'SET' : 'DROP') . ' NOT NULL';
            $args->addSql('ALTER TABLE ' . $tableName . ' ' . $query);
        }

        if ($columnDiff->hasChanged('spatial_srid')) {
            $args->addSql(sprintf(
                "SELECT UpdateGeometrySRID('%s', '%s', %d)",
                $tableName, // Table name
                $column->getQuotedName($platform),
                $column->getCustomSchemaOption('spatial_srid')
            ));
        }

        if ($columnDiff->hasChanged('spatial_dimension')) {
            throw new \RuntimeException('The dimension of a spatial column cannot be changed (Requested changing dimension to "' . $column->getCustomSchemaOption('spatial_dimension') . '" for column "' . $column->getName() . '" in table "' . $diff->name . '")');
        }

        if ($columnDiff->hasChanged('spatial_index')) {
            $indexName = $this->generateIndexName($tableName, $column->getName());

            if ($column->getCustomSchemaOption('spatial_index')) {
                $args->addSql(sprintf(
                    "CREATE INDEX %s ON %s USING GIST (%s)",
                    $indexName,
                    $tableName, // Table name
                    $column->getQuotedName($platform) // Column name
                ));
            } else {
                $args->addSql($platform->getDropIndexSQL($indexName, $tableName));
            }
        }
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaAlterTableRenameColumnEventArgs $args
     */
    public function onSchemaAlterTableRenameColumn(SchemaAlterTableRenameColumnEventArgs $args)
    {
        $column = $args->getColumn();

        if (!$column->getType() instanceof \Doctrine\Spatial\DBAL\Types\Type) {
            return;
        }

        throw new \RuntimeException('Spatial columns cannot be renamed (Requested renaming column "' . $args->getOldColumnName() . '" to "' . $column->getName() . '" in table "' . $args->getTableDiff()->name . '")');
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

        if ($tableColumn['type'] !== 'geometry') {
            return;
        }

        $sql = "SELECT COUNT(*) as index_exists
                FROM pg_class, pg_index
                WHERE oid IN (
                    SELECT indexrelid
                    FROM pg_index si, pg_class sc, pg_namespace sn
                    WHERE sc.relname = ? AND sc.oid = si.indrelid AND sc.relnamespace = sn.oid
                 ) AND pg_index.indexrelid = oid AND relname = ?";

        $stmt = $conn->prepare($sql);
        $stmt->execute(array($table, $this->generateIndexName($table, $tableColumn['field'])));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $indexExists = $row['index_exists'] > 0;

        $sql = 'SELECT coord_dimension, srid, type FROM geometry_columns WHERE f_table_name = ? AND f_geometry_column = ?';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array($table, $tableColumn['field']));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $type = strtolower($row['type']);

        $options = array(
            'length'          => null,
            'notnull'         => (bool) $tableColumn['isnotnull'],
            'default'         => isset($tableColumn['default']) ? $tableColumn['default'] : null,
            'primary'         => (bool) ($tableColumn['pri'] == 't'),
            'precision'       => null,
            'scale'           => null,
            'fixed'           => null,
            'unsigned'        => false,
            'autoincrement'   => false,
            'comment'         => (isset($tableColumn['comment'])) ? $tableColumn['comment'] : null
        );

        $column = new Column($tableColumn['field'], Type::getType($type), $options);

        $column
            ->setCustomSchemaOption('spatial_srid',      (int)  $row['srid'])
            ->setCustomSchemaOption('spatial_dimension', (int)  $row['coord_dimension'])
            ->setCustomSchemaOption('spatial_index',     (bool) $indexExists);

        $args
            ->preventDefault()
            ->setColumn($column);
    }

    /**
     * @param string $tableName
     * @param string $columnName
     * @param \Doctrine\DBAL\Schema\Column $column
     * @return array 
     */
    protected function getAddColumnSQL($tableName, $columnName, Column $column)
    {
        $query = array();

        $spatial = array(
            'srid'      => -1,
            'dimension' => 2,
            'index'     => true
        );
        
        foreach ($spatial as $key => &$val) {
            if ($column->hasCustomSchemaOption('spatial_' . $key)) {
                $val = $column->getCustomSchemaOption('spatial_' . $key);
            }
        }

        // Geometry columns are created by AddGeometryColumn stored procedure
        $query[] = sprintf(
            "SELECT AddGeometryColumn('%s', '%s', %d, '%s', %d)",
            $tableName, // Table name
            $columnName, // Column name
            $spatial['srid'], // SRID
            strtoupper($column->getType()->getName()), // Geometry type
            $spatial['dimension'] // Dimension
        );

        if ($spatial['index']) {
            // Add a spatial index to the field
            $indexName = $this->generateIndexName($tableName, $columnName);

            $query[] = sprintf(
                "CREATE INDEX %s ON %s USING GIST (%s)",
                $indexName,
                $tableName, // Table name
                $columnName // Column name
            );
        }

        if ($column->getNotnull()) {
            // Add a NOT NULL constraint to the field
            $query[] = sprintf(
                "ALTER TABLE %s ALTER %s SET NOT NULL",
                $tableName, // Table name
                $columnName // Column name
            );
        }

        return $query;
    }

    /**
     * @param string $tableName
     * @param string $columnName
     * @param boolean $notnull
     * @return array 
     */
    protected function getDropColumnSQL($tableName, $columnName, $notnull)
    {
        $query = array();
        
        if ($notnull) {
            // Remove NOT NULL constraint from the field
            $query[] = sprintf(
                "ALTER TABLE %s ALTER %s SET DEFAULT NULL",
                $tableName, // Table name
                $columnName // Column name
            );
        }

        // We use DropGeometryColumn() to also drop entries from the geometry_columns table
        $query[] = sprintf(
            "SELECT DropGeometryColumn ('%s', '%s')",
            $tableName, // Table name
            $columnName // Column name
        );

        return $query;
    }
}
