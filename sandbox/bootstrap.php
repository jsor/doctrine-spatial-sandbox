<?php

if (file_exists($file = __DIR__.'/../autoload.php')) {
    require_once $file;
} elseif (file_exists($file = __DIR__.'/../autoload.php.dist')) {
    require_once $file;
}

$classLoader = new \Doctrine\Common\ClassLoader('Entities', __DIR__);
$classLoader->register();
$classLoader = new \Doctrine\Common\ClassLoader('Proxies', __DIR__);
$classLoader->register();

// ---

$config = new \Doctrine\ORM\Configuration();
$config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);

$annotationReader = new \Doctrine\Common\Annotations\AnnotationReader();
$driverImpl = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($annotationReader, array(
    __DIR__."/Entities",
));

$config->setMetadataDriverImpl($driverImpl);

$config->setProxyDir(__DIR__ . '/Proxies');
$config->setProxyNamespace('Proxies');

$connectionOptions = array(
    'driver' => 'pdo_pgsql',
    'driver' => 'pdo_mysql',
    'dbname' => 'doctrine_spatial',
    'user' => 'root',
    'password' => 'local',
    'host' => 'localhost',
);

$em = \Doctrine\ORM\EntityManager::create($connectionOptions, $config);

// ---

\Doctrine\DBAL\Types\Type::addType('point',              'Doctrine\Spatial\DBAL\Types\PointType');
\Doctrine\DBAL\Types\Type::addType('linestring',         'Doctrine\Spatial\DBAL\Types\LineStringType');
\Doctrine\DBAL\Types\Type::addType('polygon',            'Doctrine\Spatial\DBAL\Types\PolygonType');
\Doctrine\DBAL\Types\Type::addType('multipoint',         'Doctrine\Spatial\DBAL\Types\MultiPointType');
\Doctrine\DBAL\Types\Type::addType('multilinestring',    'Doctrine\Spatial\DBAL\Types\MultiLineStringType');
\Doctrine\DBAL\Types\Type::addType('multipolygon',       'Doctrine\Spatial\DBAL\Types\MultiPolygonType');
\Doctrine\DBAL\Types\Type::addType('geometrycollection', 'Doctrine\Spatial\DBAL\Types\GeometryCollectionType');

$conn = $em->getConnection();
$conn->getDatabasePlatform()->registerDoctrineTypeMapping('point',              'point');
$conn->getDatabasePlatform()->registerDoctrineTypeMapping('linestring',         'linestring');
$conn->getDatabasePlatform()->registerDoctrineTypeMapping('polygon',            'polygon');
$conn->getDatabasePlatform()->registerDoctrineTypeMapping('multipoint',         'multipoint');
$conn->getDatabasePlatform()->registerDoctrineTypeMapping('multilinestring',    'multilinestring');
$conn->getDatabasePlatform()->registerDoctrineTypeMapping('multipolygon',       'multipolygon');
$conn->getDatabasePlatform()->registerDoctrineTypeMapping('geometrycollection', 'geometrycollection');

$mappedSubscriber = new \Doctrine\Spatial\MappedEventSubscriber();
$schemaSubscriber = new \Doctrine\Spatial\ORM\SchemaEventSubscriber($mappedSubscriber);

$conn->getEventManager()->addEventSubscriber($schemaSubscriber);
