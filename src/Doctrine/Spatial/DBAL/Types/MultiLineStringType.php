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
 * @author Jan Sorgalla <jsorgalla@googlemail.com>
 */
class MultiLineStringType extends Type
{
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return Type::MULTILINESTRING;
    }
}
