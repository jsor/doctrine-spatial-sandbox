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

use Doctrine\Spatial\DBAL\SchemaEventSubscriber as DBALSchemaEventSubscriber;
use Doctrine\Spatial\MappedEventSubscriber;
use Doctrine\ORM\Tools\ToolEvents;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;

/**
 * ORM event subscriber enabling spatial data support.
 *
 * @author  Jan Sorgalla <jsorgalla@googlemail.com>
 */
class SchemaEventSubscriber extends DBALSchemaEventSubscriber
{
    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return array_merge(
            parent::getSubscribedEvents(),
            array(
                ToolEvents::postGenerateSchemaTable
            )
        );
    }
    
    /**
     * @param \Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs $args
     */
    public function postGenerateSchemaTable(GenerateSchemaTableEventArgs $args)
    {
        foreach ($args->getClassTable()->getColumns() as $column) {
            if (!$column->getType() instanceof \Doctrine\Spatial\DBAL\Types\Type) {
                continue;
            }

            if (!$column->hasCustomSchemaOption('spatial_srid')) {
                $column->setCustomSchemaOption('spatial_srid', 4326);
            }
            
            if (!$column->hasCustomSchemaOption('spatial_dimension')) {
                $column->setCustomSchemaOption('spatial_dimension', 2);
            }
            
            if (!$column->hasCustomSchemaOption('spatial_index')) {
                $column->setCustomSchemaOption('spatial_index', false);
            }
        }
    }
}
