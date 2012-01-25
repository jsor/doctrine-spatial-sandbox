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

use Doctrine\DBAL\Schema\Column;

/**
 * @author Jan Sorgalla <jsorgalla@googlemail.com>
 */
class SqliteHandler extends PostgreSqlHandler
{
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
            'srid'      => 4326,
            'dimension' => 2,
            'index'     => false
        );
        
        foreach ($spatial as $key => &$val) {
            if ($column->hasCustomSchemaOption('spatial_' . $key)) {
                $val = $column->getCustomSchemaOption('spatial_' . $key);
            }
        }

        // Geometry columns are created by AddGeometryColumn stored procedure
        $query[] = sprintf(
            "SELECT AddGeometryColumn('%s', '%s', %d, '%s', %d)",
            $tableName,
            $columnName,
            $spatial['srid'],
            strtoupper($column->getType()->getName()),
            $spatial['dimension']
        );

        if ($spatial['index']) {
            // Add a spatial index to the field
            $query[] = sprintf(
                "Select CreateSpatialIndex('%s', '%s')",
                $tableName,
                $columnName
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

        // We use DropGeometryColumn() to also drop entries from the geometry_columns table
        $query[] = sprintf(
            "SELECT DiscardGeometryColumn('%s', '%s')",
            $tableName, // Table name
            $columnName // Column name
        );

        return $query;
    }
}
