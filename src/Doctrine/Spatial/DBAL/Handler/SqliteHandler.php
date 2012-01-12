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

/**
 * @author Jan Sorgalla <jsorgalla@googlemail.com>
 */
class SqliteHandler extends PostgreSqlHandler
{
    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaDropTableEventArgs $args
     */
    public function onSchemaCreateTable(SchemaDropTableEventArgs $args)
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
}
