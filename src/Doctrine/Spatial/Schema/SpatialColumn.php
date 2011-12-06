<?php

/*
 * This file is part of Doctrine\Spatial.
 *
 * (c) Jan Sorgalla <jsorgalla@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Spatial\Schema;

use Doctrine\DBAL\Schema\Column;

/**
 * Object representation of a spatial column.
 *
 * @author  Jan Sorgalla <jsorgalla@googlemail.com>
 * @version @package_version@
 */
class SpatialColumn extends Column
{
    /**
     * @var array
     */
    protected $_spatialOptions = array();

    /**
     * @param array $spatialOptions
     * @return Column
     */
    public function setSpatialOptions(array $spatialOptions)
    {
        $this->_spatialOptions = $spatialOptions;
        return $this;
    }

    /**
     * @param  string $name
     * @param  mixed $value
     * @return Column
     */
    public function setSpatialOption($name, $value)
    {
        $this->_spatialOptions[$name] = $value;
        return $this;
    }

    public function getSpatialOptions()
    {
        return $this->_spatialOptions;
    }

    public function hasSpatialOption($name)
    {
        return isset($this->_spatialOptions[$name]);
    }

    public function getSpatialOption($name)
    {
        return $this->_spatialOptions[$name];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array_merge(parent::toArray(), $this->_spatialOptions);
    }
}