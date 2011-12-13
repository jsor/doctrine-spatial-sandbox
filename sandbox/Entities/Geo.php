<?php

namespace Entities;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Spatial\Mapping\Annotation as Spatial;

/**
 * @ORM\Entity
 * @ORM\Table(name="geo")
 */
class Geo
{
    /**
     * @ORM\Id 
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="point", nullable=true)
     * @Spatial\Column(srid=4326, dimension=3, index=true)
     */
    private $point;

    /**
     * @ORM\Column(type="linestring", nullable=true)
     */
    private $linestring;

    /**
     * @ORM\Column(type="polygon", nullable=true)
     */
    private $polygon;

    /**
     * @ORM\Column(type="multipoint", nullable=true)
     */
    private $multipoint;

    /**
     * @ORM\Column(type="multilinestring", nullable=true)
     */
    private $multilinestring;

    /**
     * @ORM\Column(type="multipolygon", nullable=true)
     */
    private $multipolygon;

    /**
     * @ORM\Column(type="geometrycollection", nullable=true)
     */
    private $geometrycollection;

    public function getId()
    {
        return $this->id;
    }

    public function setPoint($point)
    {
        $this->point = $point;
    }

    public function getPoint()
    {
        return $this->point;
    }

    public function setLineString($linestring)
    {
        $this->linestring = $linestring;
    }

    public function getLineString()
    {
        return $this->linestring;
    }

    public function setPolygon($polygon)
    {
        $this->polygon = $polygon;
    }

    public function getPolygon()
    {
        return $this->polygon;
    }

    public function setMultiPoint($multipoint)
    {
        $this->multipoint = $multipoint;
    }

    public function getMultiPoint()
    {
        return $this->multipoint;
    }

    public function setMultiLineString($multilinestring)
    {
        $this->multilinestring = $multilinestring;
    }

    public function getMultiLineString()
    {
        return $this->multilinestring;
    }

    public function setMultiPolygon($multipolygon)
    {
        $this->multipolygon = $multipolygon;
    }

    public function geMultiPolygon()
    {
        return $this->multipolygon;
    }

    public function setGeometryCollection($geometrycollection)
    {
        $this->geometrycollection = $geometrycollection;
    }

    public function getGeometryCollection()
    {
        return $this->geometrycollection;
    }
}