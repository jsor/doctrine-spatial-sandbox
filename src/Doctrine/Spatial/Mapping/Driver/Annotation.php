<?php

/*
 * This file is part of Doctrine\Spatial.
 *
 * (c) Jan Sorgalla <jsorgalla@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Spatial\Mapping\Driver;

use Gedmo\Mapping\Driver\AnnotationDriverInterface,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Gedmo\Exception\InvalidMappingException;

class Annotation implements AnnotationDriverInterface
{
    /**
     * Annotation to identify field as one which holds the spatial options
     */
    const COLUMN = 'Doctrine\\Spatial\\Mapping\\Annotation\\Column';

    /**
     * Annotation reader instance
     *
     * @var object
     */
    private $reader;

    /**
     * Original driver if it is available
     */
    protected $_originalDriver = null;

    /**
     * {@inheritDoc}
     */
    public function setAnnotationReader($reader)
    {
        $this->reader = $reader;
    }

    /**
     * Passes in the mapping read by original driver
     *
     * @param $driver
     * @return void
     */
    public function setOriginalDriver($driver)
    {
        $this->_originalDriver = $driver;
    }

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata(ClassMetadata $meta, array &$config)
    {
        $class = $meta->getReflectionClass();

        foreach ($class->getProperties() as $property) {
            if ($meta->isMappedSuperclass && !$property->isPrivate() ||
                $meta->isInheritedField($property->name) ||
                isset($meta->associationMappings[$property->name]['inherited'])
            ) {
                continue;
            }

            $column = $this->reader->getPropertyAnnotation($property, self::COLUMN);

            if ($column) {
                $field = $property->getName();

                if (!$meta->hasField($field)) {
                    throw new InvalidMappingException("Unable to find spatial [{$field}] as mapped property in entity - {$meta->name}");
                }

                $config['spatial']['column'][$field] = array(
                    'srid'      => $column->srid,
                    'dimension' => $column->dimension,
                    'index'     => $column->index
                );
            }
        }
    }
}
