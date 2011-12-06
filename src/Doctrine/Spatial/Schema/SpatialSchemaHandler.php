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
use Doctrine\DBAL\Schema\Table;

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
                    $type = $column->getType()->getName();
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
                                strtolower($table->getQuotedName($platform)), // Table name
                                $column->getQuotedName($platform), // Column name
                                -1, // SRID
                                strtoupper($type), // Geometry type
                                2 // Dimension
                            );
                            
                            if ($column->getNotnull()) {
                                // Add a NOT NULL constraint to the field
                                $query[] = sprintf(
                                    "ALTER TABLE %s ALTER %s SET NOT NULL",
                                    strtolower($table->getQuotedName($platform)), // Table name
                                    $column->getQuotedName($platform) // Column name
                                );
                            }
                    }
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
                    $type = $column->getType()->getName();
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
                                strtolower($table->getQuotedName($platform)), // Table name
                                $column->getQuotedName($platform) // Column name
                            );
                            break;
                    }
                }
                break;
            default:
                break;
        }
        
        return $query;
    }
}
