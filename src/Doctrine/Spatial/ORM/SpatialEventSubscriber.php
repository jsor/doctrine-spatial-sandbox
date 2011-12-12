<?php

/*
 * This file is part of Doctrine\Spatial.
 *
 * (c) Jan Sorgalla <jsorgalla@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Spatial\ORM;

use Doctrine\Spatial\DBAL\SpatialEventSubscriber as SpatialDBALEventSubscriber;
use Doctrine\ORM\Tools\ToolEvents;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;

/**
 * ORM event subscriber enabling spatial data support.
 *
 * @author  Jan Sorgalla <jsorgalla@googlemail.com>
 */
class SpatialEventSubscriber extends SpatialDBALEventSubscriber
{
    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return array_merge(
            parent::getSubscribedEvents(),
            array(ToolEvents::postGenerateSchemaTable)
        );
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs $args
     */
    public function postGenerateSchemaTable(GenerateSchemaTableEventArgs $args)
    {
        $table = $args->getClassTable();

        foreach ($table->getColumns() as $column) {
            if (!$this->isGeometryColumn($column->getType()->getName())) {
                continue;
            }

            // @TODO: Fetch from metadata
            $srid      = null;
            $dimension = null;

            $options = array(
                'platformOptions' => array_merge(
                    $column->getPlatformOptions(),
                    array(
                        'spatial_srid'      => $srid,
                        'spatial_dimension' => $dimension
                    )
                )
            );

            $table->changeColumn($column->getName(), $options);
        }
    }
}
