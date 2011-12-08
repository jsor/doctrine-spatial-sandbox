<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
*/

namespace Doctrine\Spatial;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Events;
use Doctrine\DBAL\Event\SchemaCreateTableColumnEventArgs;
use Doctrine\DBAL\Event\SchemaDropTableEventArgs;
use Doctrine\DBAL\Event\SchemaColumnDefinitionEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableAddedColumnEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableRemovedColumnEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableChangedColumnEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableRenamedColumnEventArgs;
use Doctrine\DBAL\Types\Type;
use Doctrine\Spatial\Schema\SpatialColumn;

/**
 * Event subscriber enabling spatial data support.
 *
 * @author  Jan Sorgalla <jsorgalla@googlemail.com>
 * @version @package_version@>
 */
class SpatialEventSubscriber implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return array(
            Events::onSchemaCreateTableColumn,
            Events::onSchemaDropTable,
            Events::onSchemaColumnDefinition,
            Events::onSchemaAlterTableAddedColumn,
            Events::onSchemaAlterTableRemovedColumn,
            Events::onSchemaAlterTableChangedColumn,
            Events::onSchemaAlterTableRenamedColumn
        );
    }

    /**
     * @param SchemaCreateTableColumnEventArgs $args
     * @return void
     */
    public function onSchemaCreateTableColumn(SchemaCreateTableColumnEventArgs $args)
    {
        $column = $args->getColumn();

        if (!$this->isGeometryColumn($column->getType()->getName())) {
            return;
        }

        $platform = $args->getPlatform();

        switch ($platform->getName()) {
            case 'postgresql':
                $args
                    ->preventDefault()
                    ->addSql(
                        $this->getPostgresqlAddColumnSQL(
                            $args->getTable()->getQuotedName($platform),
                            $column->getQuotedName($platform),
                            $column->getType()->getName(),
                            $column->getNotnull()
                        )
                    );
                break;
        }
    }

    /**
     * @param SchemaCreateTableColumnEventArgs $args
     * @return void
     */
    public function onSchemaDropTable(SchemaDropTableEventArgs $args)
    {
        $table    = $args->getTable();
        $platform = $args->getPlatform();

        switch ($platform->getName()) {
            case 'postgresql':
                // We should check here if the table contains geometry columns
                // but we must ensure that we either always get a Table instance 
                // or have a connection availabe to query the geometry_columns table.
                if ($table instanceof Table) {
                    $table = $table->getQuotedName($platform);
                }

                $args
                    ->preventDefault()
                    ->setSql("SELECT DropGeometryTable('" . $table . "')");
                break;
        }
    }

    /**
     * @param SchemaAlterTableAddedColumnEventArgs $args
     * @return void
     */
    public function onSchemaAlterTableAddedColumn(SchemaAlterTableAddedColumnEventArgs $args)
    {
        $column = $args->getColumn();

        if (!$this->isGeometryColumn($column->getType()->getName())) {
            return;
        }

        $platform = $args->getPlatform();

        switch ($platform->getName()) {
            case 'postgresql':
                $diff = $args->getTableDiff();
                $tableName = $diff->newName !== false ? $diff->newName : $diff->name;;
                $args
                    ->preventDefault()
                    ->addSql(
                        $this->getPostgresqlAddColumnSQL(
                            $tableName,
                            $column->getQuotedName($platform),
                            $column->getType()->getName(),
                            $column->getNotnull()
                        )
                    );
                break;
        }
    }

    /**
     * @param SchemaAlterTableRemovedColumnEventArgs $args
     * @return void
     */
    public function onSchemaAlterTableRemovedColumn(SchemaAlterTableRemovedColumnEventArgs $args)
    {
        $column = $args->getColumn();

        if (!$this->isGeometryColumn($column->getType()->getName())) {
            return;
        }

        $platform = $args->getPlatform();

        switch ($platform->getName()) {
            case 'postgresql':
                $diff = $args->getTableDiff();
                $tableName = $diff->newName !== false ? $diff->newName : $diff->name;;
                $args
                    ->preventDefault()
                    ->addSql(
                        $this->getPostgresqlDropColumnSQL(
                            $tableName,
                            $column->getQuotedName($platform),
                            $column->getNotnull()
                        )
                    );
                break;
        }
    }

    /**
     * @param SchemaAlterTableChangedColumnEventArgs $args
     * @return void
     */
    public function onSchemaAlterTableChangedColumn(SchemaAlterTableChangedColumnEventArgs $args)
    {
        // @TODO: Make granular change detection (eg. if only SRID has changed)

        $columnDiff = $args->getColumnDiff();
        $column = $columnDiff->column;

        if (!$this->isGeometryColumn($column->getType()->getName())) {
            return;
        }

        $platform = $args->getPlatform();

        switch ($platform->getName()) {
            case 'postgresql':
                $diff = $args->getTableDiff();
                $tableName = $diff->newName !== false ? $diff->newName : $diff->name;;
                $args
                    ->preventDefault()
                    ->addSql(
                        $this->getPostgresqlDropColumnSQL(
                            $tableName,
                            $columnDiff->oldColumnName,
                            $column->getNotnull()
                        )
                    )
                    ->addSql(
                        $this->getPostgresqlAddColumnSQL(
                            $tableName,
                            $column->getQuotedName($platform),
                            $column->getType()->getName(),
                            $column->getNotnull()
                        )
                    );
                break;
        }
    }

    /**
     * @param SchemaAlterTableRenamedColumnEventArgs $args
     * @return void
     */
    public function onSchemaAlterTableRenamedColumn(SchemaAlterTableRenamedColumnEventArgs $args)
    {
        $column = $args->getColumn();

        if (!$this->isGeometryColumn($column->getType()->getName())) {
            return;
        }

        $platform = $args->getPlatform();

        switch ($platform->getName()) {
            case 'postgresql':
                $diff = $args->getTableDiff();
                $tableName = $diff->newName !== false ? $diff->newName : $diff->name;;
                $args
                    ->preventDefault()
                    ->addSql(
                        $this->getPostgresqlDropColumnSQL(
                            $tableName,
                            $args->getOldColumnName(),
                            $column->getNotnull()
                        )
                    )
                    ->addSql(
                        $this->getPostgresqlAddColumnSQL(
                            $tableName,
                            $column->getQuotedName($platform),
                            $column->getType()->getName(),
                            $column->getNotnull()
                        )
                    );
                break;
        }
    }

    /**
     * @param SchemaColumnDefinitionEventArgs $args
     * @return void
     */
    public function onSchemaColumnDefinition(SchemaColumnDefinitionEventArgs $args)
    {
        switch ($args->getDatabasePlatform()->getName()) {
            case 'postgresql':
                $this->setPostgresqlSchemaColumnDefinition($args);
                break;
        }
    }

    protected function setPostgresqlSchemaColumnDefinition(SchemaColumnDefinitionEventArgs $args)
    {
        $table  = $args->getTable();
        $column = $args->getColumn();
        $conn   = $args->getConnection();

        $column = array_change_key_case($column, CASE_LOWER);

        if ($column['type'] !== 'geometry') {
            return;
        }

        $sql = 'SELECT coord_dimension, srid, type FROM geometry_columns WHERE f_table_name = ? AND f_geometry_column = ?';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array($table, $column['field']));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $type = strtolower($row['type']);

        $options = array(
            'length'          => null,
            'notnull'         => (bool) $column['isnotnull'],
            'default'         => $column['default'],
            'primary'         => (bool) ($column['pri'] == 't'),
            'precision'       => null,
            'scale'           => null,
            'fixed'           => null,
            'unsigned'        => false,
            'autoincrement'   => false,
            'comment'         => $column['comment'],
            'platformOptions' => isset($column['platformoptions']) ? (array) $column['platformoptions'] : array(),
            'spatialOptions'  => array(
                'srid'            => (int) $row['srid'],
                'coord_dimension' => (int) $row['coord_dimension'],
            )
        );

        $args
            ->preventDefault()
            ->setColumnDefinition(new SpatialColumn($column['field'], Type::getType($type), $options));
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

    protected function getPostgresqlDropColumnSQL($tableName, $columnName, $notnull)
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
