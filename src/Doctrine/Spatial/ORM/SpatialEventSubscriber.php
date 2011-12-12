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

namespace Doctrine\Spatial\ORM;

use Doctrine\Spatial\DBAL\SpatialEventSubscriber as SpatialDBALEventSubscriber;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Schema\Column;
use Doctrine\ORM\Tools\ToolEvents;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;

/**
 * ORM event subscriber enabling spatial data support.
 *
 * @author  Jan Sorgalla <jsorgalla@googlemail.com>
 * @version @package_version@>
 */
class SpatialEventSubscriber extends SpatialDBALEventSubscriber
{
    public function getSubscribedEvents()
    {
        return array_merge(
            parent::getSubscribedEvents(),
            array(ToolEvents::postGenerateSchemaTable)
        );
    }

    /**
     * @param GenerateSchemaTableEventArgs $args
     * @return void
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
                'default'          => $column->getDefault(),
                'notnull'          => $column->getNotnull(),
                'length'           => $column->getLength(),
                'precision'        => $column->getPrecision(),
                'scale'            => $column->getScale(),
                'fixed'            => $column->getFixed(),
                'unsigned'         => $column->getUnsigned(),
                'autoincrement'    => $column->getAutoincrement(),
                'columnDefinition' => $column->getColumnDefinition(),
                'comment'          => $column->getComment(),
                'platformOptions'  => array_merge(
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
