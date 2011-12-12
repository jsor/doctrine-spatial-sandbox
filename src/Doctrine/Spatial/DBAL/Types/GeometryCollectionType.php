<?php

/*
 * This file is part of Doctrine\Spatial.
 *
 * (c) Jan Sorgalla <jsorgalla@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Spatial\DBAL\Types;

/**
 * Type that maps a SQL Spatial GeometryCollection data type to a Geometry GeometryCollection object.
 *
 * @author  Jan Sorgalla <jsorgalla@googlemail.com>
 * @version @package_version@
 */
class GeometryCollectionType extends Type
{
    public function getName()
    {
        return Type::GEOMETRYCOLLECTION;
    }
}
