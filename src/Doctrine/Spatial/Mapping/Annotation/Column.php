<?php

/*
 * This file is part of Doctrine\Spatial.
 *
 * (c) Jan Sorgalla <jsorgalla@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Spatial\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Column extends Annotation
{
    /** @var integer */
    public $srid = 4326;
    /** @var integer */
    public $dimension = 2;
    /** @var boolean */
    public $index = false; // Set to false because MySQL InnoDB doesn't support spatial indexes
}
