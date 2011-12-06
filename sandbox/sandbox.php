<?php

require __DIR__.'/bootstrap.php';

// ---

use Doctrine\Spatial\Geometry\GeometryCollection;
use Doctrine\Spatial\Geometry\LineString;
use Doctrine\Spatial\Geometry\LinearRing;
use Doctrine\Spatial\Geometry\MultiLineString;
use Doctrine\Spatial\Geometry\MultiPoint;
use Doctrine\Spatial\Geometry\MultiPolygon;
use Doctrine\Spatial\Geometry\Point;
use Doctrine\Spatial\Geometry\Polygon;

$geo = new \Entities\Geo();

$geo->setPoint('POINT(15 20)');

$geo->setLineString('LINESTRING(0 0, 10 10, 20 25, 50 60)');

$geo->setPolygon('POLYGON((0 0,10 0,10 10,0 10,0 0),(5 5,7 5,7 7,5 7, 5 5))');

$geo->setMultiPoint('MULTIPOINT(0 0, 20 20, 60 60)');

$geo->setMultiLineString('MULTILINESTRING((10 10, 20 20), (15 15, 30 15))');

$geo->setMultiPolygon('MULTIPOLYGON(((0 0,10 0,10 10,0 10,0 0)),((5 5,7 5,7 7,5 7, 5 5)))');

$geo->setGeometryCollection('GEOMETRYCOLLECTION(POINT(10 10), POINT(30 30), LINESTRING(15 15, 20 20))');

$em->persist($geo);
$em->flush();

// ---

$geo = $em->find('\Entities\Geo', 1);

var_dump($geo);
