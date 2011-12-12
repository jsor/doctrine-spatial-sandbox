<?php

namespace Doctrine\Spatial\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\Spatial\Mapping\Event\SpatialAdapter;

/**
 * @author  Jan Sorgalla <jsorgalla@googlemail.com>
 */
final class ORM extends BaseAdapterORM implements SpatialAdapter
{
}
