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

use Doctrine\DBAL\Event\SchemaCreateTableColumnEventArgs;
use Doctrine\DBAL\Event\SchemaDropTableEventArgs;
use Doctrine\DBAL\Event\SchemaIndexDefinitionEventArgs;
use Doctrine\DBAL\Event\SchemaColumnDefinitionEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableAddColumnEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableRemoveColumnEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableChangeColumnEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableRenameColumnEventArgs;

/**
 * @author Jan Sorgalla <jsorgalla@googlemail.com>
 */
interface HandlerInterface
{
    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaCreateTableColumnEventArgs $args
     */
    function onSchemaCreateTableColumn(SchemaCreateTableColumnEventArgs $args);

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaDropTableEventArgs $args
     */
    function onSchemaDropTable(SchemaDropTableEventArgs $args);

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaAlterTableAddColumnEventArgs $args
     */
    function onSchemaAlterTableAddColumn(SchemaAlterTableAddColumnEventArgs $args);

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaAlterTableRemoveColumnEventArgs $args
     */
    function onSchemaAlterTableRemoveColumn(SchemaAlterTableRemoveColumnEventArgs $args);

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaAlterTableChangeColumnEventArgs $args
     */
    function onSchemaAlterTableChangeColumn(SchemaAlterTableChangeColumnEventArgs $args);

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaAlterTableRenameColumnEventArgs $args
     */
    function onSchemaAlterTableRenameColumn(SchemaAlterTableRenameColumnEventArgs $args);

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaIndexDefinitionEventArgs $args
     */
    function onSchemaIndexDefinition(SchemaIndexDefinitionEventArgs $args);

    /**
     * @param \Doctrine\ORM\Tools\Event\SchemaColumnDefinitionEventArgs $args
     */
    function onSchemaColumnDefinition(SchemaColumnDefinitionEventArgs $args);
}
