<?php

/*
 * This file is part of Doctrine\Spatial.
 *
 * (c) Jan Sorgalla <jsorgalla@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Spatial\Schema;

use Doctrine\DBAL\Schema\CustomSchemaHandler;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;

/**
 * @author  Jan Sorgalla <jsorgalla@googlemail.com>
 * @version @package_version@
 */
class SpatialSchemaHandler implements CustomSchemaHandler
{
    public function getPortableTableColumnDefinition($table, $database, $tableColumn, Connection $conn)
    {
        switch ($conn->getDatabasePlatform()->getName()) {
            case 'postgresql':
                return $this->getPortablePostgresTableColumnDefinition($table, $database, $tableColumn, $conn);
            default:
                return false;
        }
    }

    protected function getPortablePostgresTableColumnDefinition($table, $database, $tableColumn, Connection $conn)
    {
        $tableColumn = array_change_key_case($tableColumn, CASE_LOWER);

        if ($tableColumn['type'] === 'geometry') {
            $sql = "SELECT coord_dimension, srid, type FROM geometry_columns WHERE f_table_name=? AND f_geometry_column=?";
            $stmt = $conn->prepare($sql);
            $stmt->execute(array($table, $tableColumn['field']));
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            $type = strtolower($row['type']);

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
                'platformOptions' => isset($tableColumn['platformoptions']) ? (array) $tableColumn['platformoptions'] : array(),
                'spatialOptions'  => array(
                    'srid'            => (int) $row['srid'],
                    'coord_dimension' => (int) $row['coord_dimension'],
                )
            );

            return new SpatialColumn($tableColumn['field'], \Doctrine\DBAL\Types\Type::getType($type), $options);
        }

        return false;
    }
    
    public function getColumnDeclarationSQL($name, array $field, AbstractPlatform $platform)
    {
        switch ($platform->getName()) {
            case 'postgresql':
                switch (strtolower($field['type']->getName())) {
                    case 'point':
                    case 'linestring':
                    case 'polygon':
                    case 'multipoint':
                    case 'multilinestring':
                    case 'multipolygon':
                    case 'geometrycollection':
                        return null; // Skip
                }
                return false;
            default:
                return false;
        }
    }

    public function getCreateTableSQL(Table $table, AbstractPlatform $platform)
    {
        $query = array();
        switch ($platform->getName()) {
            case 'postgresql':
                foreach ($table->getColumns() as $column) {
                    $query = array_merge($query, $this->getPostgresAddColumnSQL($table->getQuotedName($platform), $column->getQuotedName($platform), $column->getType()->getName()));
                }
                break;
            default:
                break;
        }
        
        return $query;
    }

    public function getDropTableSQL(Table $table, AbstractPlatform $platform)
    {
        $query = array();
        switch ($platform->getName()) {
            // We us DropGeometryColumn() to also drop entries from the geometry_columns table
            case 'postgresql':
                foreach ($table->getColumns() as $column) {
                    $query = array_merge($query, $this->getPostgresDropColumnSQL($table->getQuotedName($platform), $column->getQuotedName($platform), $column->getType()->getName()));
                }
                break;
            default:
                break;
        }
        
        return $query;
    }

    public function getAddedColumnAlterTableSQL(TableDiff $diff, Column $column, AbstractPlatform $platform)
    {
        $type = $column->getType()->getName();
        switch (strtolower($type)) {
            case 'point':
            case 'linestring':
            case 'polygon':
            case 'multipoint':
            case 'multilinestring':
            case 'multipolygon':
            case 'geometrycollection':
                return null; // Columns will added later in getAlterTableSQL() after the table is renamed
            default:
                return false;
        }
    }

    public function getRemovedColumnAlterTableSQL(TableDiff $diff, Column $column, AbstractPlatform $platform)
    {
        return $this->getPostgresDropColumnSQL($table->getQuotedName($platform), $column->getQuotedName($platform), $column->getType()->getName());
    }

    public function getChangedColumnAlterTableSQL(TableDiff $diff, ColumnDiff $columnDiff, AbstractPlatform $platform)
    {
        // @TODO: Check how changing is possible

        // Columns can't be changed, they always have to be dropped and added
        $column = $columnDiff->column;
        return $this->getPostgresDropColumnSQL($table->getQuotedName($platform), $column->getQuotedName($platform), $column->getType()->getName());
    }
    
    public function getRenamedColumnAlterTableSQL(TableDiff $diff, $oldColumnNamem, Column $column, AbstractPlatform $platform)
    {
        // Columns can't be rename, they always have to be dropped and added
        return $this->getPostgresDropColumnSQL($table->getQuotedName($platform), $column->getQuotedName($platform), $column->getType()->getName());
    }

    public function getAlterTableSQL(TableDiff $diff, AbstractPlatform $platform)
    {
        $query = array();

        foreach ($diff->changedColumns as $oldColumnName => $column) {
            // @TODO
        }

        foreach ($diff->renamedColumns as $oldColumnName => $column) {
            // @TODO
        }
        
        return $query;
    }
    
    protected function getPostgresAddColumnSQL($tableName, $columnName, $type)
    {
        switch (strtolower($type)) {
            case 'point':
            case 'linestring':
            case 'polygon':
            case 'multipoint':
            case 'multilinestring':
            case 'multipolygon':
            case 'geometrycollection':
                // Geometry columns are created by AddGeometryColumn stored procedure
                $query[] = sprintf(
                    "SELECT AddGeometryColumn('%s', '%s', %d, '%s', %d)",
                    $tableName, // Table name
                    $columnName, // Column name
                    -1, // SRID
                    strtoupper($type), // Geometry type
                    2 // Dimension
                );

                if ($column->getNotnull()) {
                    // Add a NOT NULL constraint to the field
                    $query[] = sprintf(
                        "ALTER TABLE %s ALTER %s SET NOT NULL",
                        $tableName, // Table name
                        $columnName // Column name
                    );
                }
        }
    }
    
    protected function getPostgresDropColumnSQL($tableName, $columnName, $type)
    {
        switch (strtolower($type)) {
            case 'point':
            case 'linestring':
            case 'polygon':
            case 'multipoint':
            case 'multilinestring':
            case 'multipolygon':
            case 'geometrycollection':
                $query[] = sprintf(
                    "SELECT DropGeometryColumn ('%s', '%s')",
                    $tableName, // Table name
                    $columnName // Column name
                );
        }
    }
}
