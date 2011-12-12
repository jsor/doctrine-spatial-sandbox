<?php

namespace Doctrine\Spatial\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterODM;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\Spatial\Mapping\Event\SpatialAdapter;

/**
 * @author  Jan Sorgalla <jsorgalla@googlemail.com>
 */
final class ODM extends BaseAdapterODM implements SpatialAdapter
{
}
