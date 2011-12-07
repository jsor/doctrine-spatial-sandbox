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
use Doctrine\DBAL\Types\Type;

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
                return $this->getPortablePostgresqlTableColumnDefinition($table, $database, $tableColumn, $conn);
            default:
                return false;
        }
    }

    protected function getPortablePostgresqlTableColumnDefinition($table, $database, $tableColumn, Connection $conn)
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

            return new SpatialColumn($tableColumn['field'], Type::getType($type), $options);
        }

        return false;
    }

    public function getColumnFromMetadata($class, array $mapping, $table, AbstractPlatform $platform)
    {
        switch ($platform->getName()) {
            case 'postgresql':
                if ($this->isGeometryColumn($mapping['type'])) {
                    $columnName = $class->getQuotedColumnName($mapping['fieldName'], $platform);

                    $platformOptions = array();
                    $platformOptions['version'] = $class->isVersioned && $class->versionField == $mapping['fieldName'] ? true : false;

                    $options = array(
                        'length'          => null,
                        'notnull'         => isset($mapping['nullable']) ? !$mapping['nullable'] : true,
                        'default'         => isset($mapping['default']) ? $mapping['default'] : null,
                        'primary'         => false,
                        'precision'       => null,
                        'scale'           => null,
                        'fixed'           => null,
                        'unsigned'        => false,
                        'autoincrement'   => false,
                        'comment'         => null,
                        'platformOptions' => $platformOptions,
                        'spatialOptions'  => array(
                            //'srid'            => (int) $row['srid'],
                            //'coord_dimension' => (int) $row['coord_dimension'],
                        )
                    );

                    return new SpatialColumn($columnName, Type::getType($mapping['type']), $options);
                }
                break;
        }

        return false;
    }

    public function getColumnDeclarationSQL($name, array $field, AbstractPlatform $platform)
    {
        switch ($platform->getName()) {
            case 'postgresql':
                if ($this->isGeometryColumn($field['type']->getName())) {
                    return null; // Skip
                }
                break;
        }

        return false;
    }

    public function getCreateTableSQL(Table $table, AbstractPlatform $platform)
    {
        $query = array();

        switch ($platform->getName()) {
            case 'postgresql':
                foreach ($table->getColumns() as $column) {
                    if (!$this->isGeometryColumn($column->getType()->getName())) {
                        continue;
                    }

                    $query = array_merge(
                        $query,
                        $this->getPostgresqlAddColumnSQL(
                            $table->getQuotedName($platform),
                            $column->getQuotedName($platform),
                            $column->getType()->getName(),
                            $column->getNotnull()
                        )
                    );
                }
                break;
        }

        return $query;
    }

    public function getDropTableSQL(Table $table, AbstractPlatform $platform)
    {
        $query = array();

        switch ($platform->getName()) {
            case 'postgresql':
                foreach ($table->getColumns() as $column) {
                    if (!$this->isGeometryColumn($column->getType()->getName())) {
                        continue;
                    }

                    $query = array_merge(
                        $query,
                        $this->getPostgresqlDropColumnSQL(
                            $table->getQuotedName($platform),
                            $column->getQuotedName($platform),
                            $column->getType()->getName(),
                            $column->getNotnull()
                        )
                    );
                }
                break;
        }

        return $query;
    }

    public function getAddedColumnAlterTableSQL(TableDiff $diff, Column $column, AbstractPlatform $platform)
    {
        switch ($platform->getName()) {
            case 'postgresql':
                if ($this->isGeometryColumn($column->getType()->getName())) {
                    return null; // Columns will added later in getAlterTableSQL() after the table is renamed
                }
                break;
        }

        return false;
    }

    public function getRemovedColumnAlterTableSQL(TableDiff $diff, Column $column, AbstractPlatform $platform)
    {
        switch ($platform->getName()) {
            case 'postgresql':
                if ($this->isGeometryColumn($column->getType()->getName())) {
                    return $this->getPostgresqlDropColumnSQL(
                        $diff->name,
                        $column->getQuotedName($platform),
                        $column->getType()->getName(),
                        $column->getNotnull()
                    );
                }
                break;
        }

        return false;
    }

    public function getChangedColumnAlterTableSQL(TableDiff $diff, ColumnDiff $columnDiff, AbstractPlatform $platform)
    {
        // @TODO: Check how changing is possible

        $column = $columnDiff->column;

        switch ($platform->getName()) {
            case 'postgresql':
                if ($this->isGeometryColumn($column->getType()->getName())) {
                    // Columns can't be changed, they always have to be dropped and added
                    return $this->getPostgresqlDropColumnSQL(
                        $diff->name,
                        $column->getQuotedName($platform),
                        $column->getType()->getName(),
                        $column->getNotnull()
                    );
                }
                break;
        }

        return false;
    }

    public function getRenamedColumnAlterTableSQL(TableDiff $diff, $oldColumnName, Column $column, AbstractPlatform $platform)
    {
        switch ($platform->getName()) {
            case 'postgresql':
                if ($this->isGeometryColumn($column->getType()->getName())) {
                    // Columns can't be renamed, they always have to be dropped and added
                    return $this->getPostgresqlDropColumnSQL(
                        $diff->name,
                        $oldColumnName,
                        $column->getType()->getName(),
                        $column->getNotnull()
                    );
                }
                break;
        }

        return false;
    }

    public function getAlterTableSQL(TableDiff $diff, AbstractPlatform $platform)
    {
        $query = array();

        $tableName = $diff->newName !== false ? $diff->newName : $diff->name;

        foreach ($diff->addedColumns as $column) {
            switch ($platform->getName()) {
                case 'postgresql':
                    if ($this->isGeometryColumn($column->getType()->getName())) {
                        $query = array_merge(
                            $query,
                            $this->getPostgresqlAddColumnSQL(
                                $tableName,
                                $column->getQuotedName($platform),
                                $column->getType()->getName(),
                                $column->getNotnull()
                            )
                        );
                    }
                    break;
            }
        }

        foreach ($diff->changedColumns as $columnDiff) {
            switch ($platform->getName()) {
                case 'postgresql':
                    $column = $columnDiff->column;
                    if ($this->isGeometryColumn($column->getType()->getName())) {
                        $query = array_merge(
                            $query,
                            $this->getPostgresqlAddColumnSQL(
                                $tableName,
                                $column->getQuotedName($platform),
                                $column->getType()->getName(),
                                $column->getNotnull()
                            )
                        );
                    }
                    break;
            }
        }

        foreach ($diff->renamedColumns as $oldColumnName => $column) {
            switch ($platform->getName()) {
                case 'postgresql':
                    if ($this->isGeometryColumn($column->getType()->getName())) {
                        $query = array_merge(
                            $query,
                            $this->getPostgresqlAddColumnSQL(
                                $tableName,
                                $column->getQuotedName($platform),
                                $column->getType()->getName(),
                                $column->getNotnull()
                            )
                        );
                    }
                    break;
            }
        }

        return $query;
    }

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

    protected function getPostgresqlAddColumnSQL($tableName, $columnName, $type, $notnull)
    {
        $query = array();

        // Geometry columns are created by AddGeometryColumn stored procedure
        $query[] = sprintf(
            "SELECT AddGeometryColumn('%s', '%s', %d, '%s', %d)",
            $tableName, // Table name
            $columnName, // Column name
            -1, // SRID
            strtoupper($type), // Geometry type
            2 // Dimension
        );

        if ($notnull) {
            // Add a NOT NULL constraint to the field
            $query[] = sprintf(
                "ALTER TABLE %s ALTER %s SET NOT NULL",
                $tableName, // Table name
                $columnName // Column name
            );
        }

        return $query;
    }

    protected function getPostgresqlDropColumnSQL($tableName, $columnName, $type, $notnull)
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
