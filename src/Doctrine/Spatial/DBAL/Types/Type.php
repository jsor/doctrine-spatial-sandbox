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

use Doctrine\DBAL\Types\Type as BaseType;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Abstract type for representing a SQL Spatial data type.
 *
 * @author Jan Sorgalla <jsorgalla@googlemail.com>
 */
abstract class Type extends BaseType
{
    const POINT              = 'point';
    const LINESTRING         = 'linestring';
    const POLYGON            = 'polygon';
    const MULTIPOINT         = 'multipoint';
    const MULTILINESTRING    = 'multilinestring';
    const MULTIPOLYGON       = 'multipolygon';
    const GEOMETRYCOLLECTION = 'geometrycollection';

    /**
     * {@inheritDoc}
     */
    public function canRequireSQLConversion()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function convertToPHPValueSQL($sqlExpr, $platform)
    {
        return sprintf('AsText(%s)', $sqlExpr);
    }

    /**
     * {@inheritDoc}
     */
    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform)
    {
        return sprintf('GeomFromText(%s)', $sqlExpr);
    }

    /**
     * {@inheritDoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        return strtoupper($this->getName());
    }
}
