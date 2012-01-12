<?php

/*
 * This file is part of Doctrine\Spatial.
 *
 * (c) Jan Sorgalla <jsorgalla@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Spatial;

use Gedmo\Mapping\MappedEventSubscriber as GedmoMappedEventSubscriber;
use Doctrine\Common\EventArgs;

/**
 * Mapped event subscriber enabling spatial data support.
 *
 * @author  Jan Sorgalla <jsorgalla@googlemail.com>
 */
class MappedEventSubscriber extends GedmoMappedEventSubscriber
{
    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return array('loadClassMetadata');
    }
    
    public function getClassConfigurationFromEventArgs(EventArgs $args, $class)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();

        return $this->getConfiguration($om, $class);
    }

    /**
     * {@inheritDoc}
     */
    public function loadClassMetadata(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $this->loadMetadataForObjectClass($ea->getObjectManager(), $args->getClassMetadata());
    }
    
    /**
     * {@inheritDoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }
}
