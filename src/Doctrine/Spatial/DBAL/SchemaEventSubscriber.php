<?php

/*
 * This file is part of Doctrine\Spatial.
 *
 * (c) Jan Sorgalla <jsorgalla@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Spatial\DBAL;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Events;
use Doctrine\DBAL\Event\SchemaCreateTableColumnEventArgs;
use Doctrine\DBAL\Event\SchemaDropTableEventArgs;
use Doctrine\DBAL\Event\SchemaIndexDefinitionEventArgs;
use Doctrine\DBAL\Event\SchemaColumnDefinitionEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableAddColumnEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableRemoveColumnEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableChangeColumnEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableRenameColumnEventArgs;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Schema\Column;

/**
 * DBAL event subscriber enabling spatial data support.
 *
 * @author  Jan Sorgalla <jsorgalla@googlemail.com>
 */
class SchemaEventSubscriber implements EventSubscriber
{
    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::onSchemaCreateTableColumn,
            Events::onSchemaDropTable,
            Events::onSchemaColumnDefinition,
            Events::onSchemaIndexDefinition,
            Events::onSchemaAlterTableAddColumn,
            Events::onSchemaAlterTableRemoveColumn,
            Events::onSchemaAlterTableChangeColumn,
            Events::onSchemaAlterTableRenameColumn
        );
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaCreateTableColumnEventArgs $args
     */
    public function onSchemaCreateTableColumn(SchemaCreateTableColumnEventArgs $args)
    {
        $column = $args->getColumn();

        if (!$this->isGeometryColumn($column->getType()->getName())) {
            return;
        }

        $platform = $args->getPlatform();

        switch ($platform->getName()) {
            case 'postgresql':
                $args
                    ->preventDefault()
                    ->addSql(
                        $this->getPostgresqlAddColumnSQL(
                            $args->getTable()->getQuotedName($platform),
                            $column->getQuotedName($platform),
                            $column
                        )
                    );
                break;
        }
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaDropTableEventArgs $args
     */
    public function onSchemaDropTable(SchemaDropTableEventArgs $args)
    {
        $table    = $args->getTable();
        $platform = $args->getPlatform();

        switch ($platform->getName()) {
            case 'postgresql':
                // We should check here if the table contains geometry columns
                // but we must ensure that we either always get a Table instance 
                // or have a connection availabe to query the geometry_columns table.
                if ($table instanceof Table) {
                    $table = $table->getQuotedName($platform);
                }

                $args
                    ->preventDefault()
                    ->setSql("SELECT DropGeometryTable('" . $table . "')");
                break;
        }
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaAlterTableAddColumnEventArgs $args
     */
    public function onSchemaAlterTableAddColumn(SchemaAlterTableAddColumnEventArgs $args)
    {
        $column = $args->getColumn();

        if (!$this->isGeometryColumn($column->getType()->getName())) {
            return;
        }

        $platform = $args->getPlatform();

        switch ($platform->getName()) {
            case 'postgresql':
                $diff = $args->getTableDiff();
                $tableName = $diff->newName !== false ? $diff->newName : $diff->name;;
                $args
                    ->preventDefault()
                    ->addSql(
                        $this->getPostgresqlAddColumnSQL(
                            $tableName,
                            $column->getQuotedName($platform),
                            $column
                        )
                    );
                break;
        }
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaAlterTableRemoveColumnEventArgs $args
     */
    public function onSchemaAlterTableRemoveColumn(SchemaAlterTableRemoveColumnEventArgs $args)
    {
        $column = $args->getColumn();

        if (!$this->isGeometryColumn($column->getType()->getName())) {
            return;
        }

        $platform = $args->getPlatform();

        switch ($platform->getName()) {
            case 'postgresql':
                $diff = $args->getTableDiff();
                $tableName = $diff->newName !== false ? $diff->newName : $diff->name;;
                $args
                    ->preventDefault()
                    ->addSql(
                        $this->getPostgresqlDropColumnSQL(
                            $tableName,
                            $column->getQuotedName($platform),
                            $column->getNotnull()
                        )
                    );
                break;
        }
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaAlterTableChangeColumnEventArgs $args
     */
    public function onSchemaAlterTableChangeColumn(SchemaAlterTableChangeColumnEventArgs $args)
    {
        // @TODO: Make granular change detection (eg. if only SRID has changed)

        $columnDiff = $args->getColumnDiff();
        $column = $columnDiff->column;

        if (!$this->isGeometryColumn($column->getType()->getName())) {
            return;
        }

        $platform = $args->getPlatform();

        switch ($platform->getName()) {
            case 'postgresql':
                $diff = $args->getTableDiff();
                $tableName = $diff->newName !== false ? $diff->newName : $diff->name;;
                $args
                    ->preventDefault()
                    ->addSql(
                        $this->getPostgresqlDropColumnSQL(
                            $tableName,
                            $columnDiff->oldColumnName,
                            $column->getNotnull()
                        )
                    )
                    ->addSql(
                        $this->getPostgresqlAddColumnSQL(
                            $tableName,
                            $column->getQuotedName($platform),
                            $column->getType()->getName(),
                            $column->getNotnull()
                        )
                    );
                break;
        }
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaAlterTableRenameColumnEventArgs $args
     */
    public function onSchemaAlterTableRenameColumn(SchemaAlterTableRenameColumnEventArgs $args)
    {
        $column = $args->getColumn();

        if (!$this->isGeometryColumn($column->getType()->getName())) {
            return;
        }

        $platform = $args->getPlatform();

        switch ($platform->getName()) {
            case 'postgresql':
                $diff = $args->getTableDiff();
                $tableName = $diff->newName !== false ? $diff->newName : $diff->name;;
                $args
                    ->preventDefault()
                    ->addSql(
                        $this->getPostgresqlDropColumnSQL(
                            $tableName,
                            $args->getOldColumnName(),
                            $column->getNotnull()
                        )
                    )
                    ->addSql(
                        $this->getPostgresqlAddColumnSQL(
                            $tableName,
                            $column->getQuotedName($platform),
                            $column
                        )
                    );
                break;
        }
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaIndexDefinitionEventArgs $args
     */
    public function onSchemaIndexDefinition(SchemaIndexDefinitionEventArgs $args)
    {
        $index = $args->getTableIndex();
        if (0 === stripos($index['name'], 'spatialidx')) {
            $args->preventDefault();
        }
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaColumnDefinitionEventArgs $args
     */
    public function onSchemaColumnDefinition(SchemaColumnDefinitionEventArgs $args)
    {
        switch ($args->getDatabasePlatform()->getName()) {
            case 'postgresql':
                $this->setPostgresqlSchemaColumnDefinition($args);
                break;
        }
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaColumnDefinitionEventArgs $args
     */
    protected function setPostgresqlSchemaColumnDefinition(SchemaColumnDefinitionEventArgs $args)
    {
        $tableColumn = $args->getTableColumn();
        $table       = $args->getTable();
        $conn        = $args->getConnection();

        $tableColumn = array_change_key_case($tableColumn, CASE_LOWER);

        if ($tableColumn['type'] !== 'geometry') {
            return;
        }

        $indexes = $conn->getSchemaManager()->listTableIndexes($table);

        $sql = 'SELECT coord_dimension, srid, type FROM geometry_columns WHERE f_table_name = ? AND f_geometry_column = ?';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array($table, $tableColumn['field']));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $type = strtolower($row['type']);

        if (!isset($tableColumn['platformoptions'])) {
            $tableColumn['platformoptions'] = array();
        }

        $tableColumn['platformoptions']['spatial'] = array(
            'srid'      => (int) $row['srid'],
            'dimension' => (int) $row['coord_dimension'],
            'index'     => false
        );

        foreach ($indexes as $index) {
            $indexName = $index->getName();

            if (0 === stripos($indexName, 'spatialidx')) {
                if ($index->getColumns() === array($tableColumn['field'])) {
                    $tableColumn['platformoptions']['spatial']['index'] = true;
                }
            }
        }

        $options = array(
            'length'          => null,
            'notnull'         => (bool) $tableColumn['isnotnull'],
            'default'         => $tableColumn['default'],
            'primary'         => (bool) ($tableColumn['pri'] == 't'),
            'precision'       => null,
            'scale'           => null,
            'fixed'           => null,
            'unsigned'        => false,
            'autoincrement'   => false,
            'comment'         => $tableColumn['comment'],
            'platformOptions' => $tableColumn['platformoptions']
        );

        $args
            ->preventDefault()
            ->setColumn(new Column($tableColumn['field'], Type::getType($type), $options));
    }

    /**
     * @param string $type
     * @return boolean 
     */
    protected function isGeometryColumn($type)
    {
        switch (strtolower($type)) {
            case 'point':
            case 'linestring':
            case 'polygon':
            case 'multipoint':
            case 'multilinestring':
            case 'multipolygon':
            case 'geometrycollection':
                return true;
            default:
                return false;
        }
    }

    /**
     * @param string $tableName
     * @param string $columnName
     * @param \Doctrine\DBAL\Schema\Column $column
     * @return array 
     */
    protected function getPostgresqlAddColumnSQL($tableName, $columnName, Column $column)
    {
        $query = array();

        $spatial = array(
            'srid'      => -1,
            'dimension' => 2,
            'index'     => true
        );

        if ($column->hasPlatformOption('spatial')) {
            $spatial = array_merge($spatial, $column->getPlatformOption('spatial'));
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
            $indexName = $this->generateIndexName(
                array($tableName, $columnName), "spatialidx"
            );

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
    protected function getPostgresqlDropColumnSQL($tableName, $columnName, $notnull)
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

    /**
     * @param  array $columnNames
     * @return string
     */
    protected function generateIndexName($columnNames)
    {
        $hash = implode('', array_map(function($column) {
            return preg_replace('/[^a-zA-Z0-9_]+/', '', $column);
        }, (array) $columnNames));

        return substr(strtoupper('SPATIALIDX_' . $hash), 0, 30);
    }
}
