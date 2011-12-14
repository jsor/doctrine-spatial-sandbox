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
     * @var \Doctrine\Spatial\MappedEventSubscriber 
     */
    protected $mappedSubscriber;
    
    /**
     * @var array
     */
    protected $tableClassMap = array();

    /**
     * @param \Doctrine\Spatial\MappedEventSubscriber $mappedSubscriber 
     */
    public function __construct(MappedEventSubscriber $mappedSubscriber)
    {
        $this->mappedSubscriber = $mappedSubscriber;
    }

    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return array_merge(
            parent::getSubscribedEvents(),
            array(
                ToolEvents::postGenerateSchemaTable,
                ToolEvents::postGenerateSchema
            )
        );
    }
    
    /**
     * @param \Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs $args
     */
    public function postGenerateSchemaTable(GenerateSchemaTableEventArgs $args)
    {
        $meta = $args->getClassMetadata();
        $this->tableClassMap[$args->getClassTable()->getName()] = $meta->name;
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs $args
     */
    public function postGenerateSchema(GenerateSchemaEventArgs $args)
    {
        $schema = $args->getSchema();

        foreach ($schema->getTables() as $table) {
            $tableName = $table->getName();

            if (!isset($this->tableClassMap[$tableName])) {
                continue;
            }

            $config = $this->mappedSubscriber->getClassConfigurationFromEventArgs($args, $this->tableClassMap[$tableName]);

            if (!$config) {
                continue;
            }

            foreach ($table->getColumns() as $column) {
                if (!$this->isGeometryColumn($column->getType()->getName())) {
                    continue;
                }

                $columnName = $column->getName();

                $spatial = array(
                    'srid'      => -1,
                    'dimension' => 2,
                    'index'     => true
                );

                if (isset($config['spatial']['column'][$columnName])) {
                    $spatial = array_merge($spatial, $config['spatial']['column'][$columnName]);
                }

                $table->getColumn($column->getName())->setCustomSchemaOption('spatial', $spatial);
            }
        }
    }
}
