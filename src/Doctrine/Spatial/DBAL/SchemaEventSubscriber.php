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
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * DBAL event subscriber enabling spatial data support.
 *
 * @author  Jan Sorgalla <jsorgalla@googlemail.com>
 */
class SchemaEventSubscriber implements EventSubscriber
{
    protected $handlerClasses = array(
        'postgresql' => '\Doctrine\Spatial\DBAL\Handler\PostgreSqlHandler',
        'mysql'      => '\Doctrine\Spatial\DBAL\Handler\MySqlHandler',
        'sqlite'     => '\Doctrine\Spatial\DBAL\Handler\SqliteHandler'
    );

    /**
     * @var array 
     */
    protected $handlerMap = array();

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
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
     * @return \Doctrine\Spatial\DBAL\HandlerInterface 
     */
    public function getHandler(AbstractPlatform $platform)
    {
        $name = $platform->getName();

        if (isset($this->handlerMap[$name])) {
            return $this->handlerMap[$name];
        }

        if (!isset($this->handlerClasses[$name])) {
            throw new \RuntimeException('The database platform ' . $name . ' is not supported');
        }

        return $this->handlerMap[$name] = new $this->handlerClasses[$name]();
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaCreateTableColumnEventArgs $args
     */
    public function onSchemaCreateTableColumn(SchemaCreateTableColumnEventArgs $args)
    {
        $this->getHandler($args->getPlatform())->onSchemaCreateTableColumn($args);
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaDropTableEventArgs $args
     */
    public function onSchemaDropTable(SchemaDropTableEventArgs $args)
    {
        $this->getHandler($args->getPlatform())->onSchemaDropTable($args);
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaAlterTableAddColumnEventArgs $args
     */
    public function onSchemaAlterTableAddColumn(SchemaAlterTableAddColumnEventArgs $args)
    {
        $this->getHandler($args->getPlatform())->onSchemaAlterTableAddColumn($args);
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaAlterTableRemoveColumnEventArgs $args
     */
    public function onSchemaAlterTableRemoveColumn(SchemaAlterTableRemoveColumnEventArgs $args)
    {
        $this->getHandler($args->getPlatform())->onSchemaAlterTableRemoveColumn($args);
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaAlterTableChangeColumnEventArgs $args
     */
    public function onSchemaAlterTableChangeColumn(SchemaAlterTableChangeColumnEventArgs $args)
    {
        $this->getHandler($args->getPlatform())->onSchemaAlterTableChangeColumn($args);
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaAlterTableRenameColumnEventArgs $args
     */
    public function onSchemaAlterTableRenameColumn(SchemaAlterTableRenameColumnEventArgs $args)
    {
        $this->getHandler($args->getPlatform())->onSchemaAlterTableRenameColumn($args);
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaIndexDefinitionEventArgs $args
     */
    public function onSchemaIndexDefinition(SchemaIndexDefinitionEventArgs $args)
    {
        $this->getHandler($args->getDatabasePlatform())->onSchemaIndexDefinition($args);
    }

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaColumnDefinitionEventArgs $args
     */
    public function onSchemaColumnDefinition(SchemaColumnDefinitionEventArgs $args)
    {
        $this->getHandler($args->getDatabasePlatform())->onSchemaColumnDefinition($args);
    }
}
