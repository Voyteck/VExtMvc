<?php
namespace Voyteck\VExtMvc\Factory;

use Zend\ServiceManager\Factory\AbstractFactoryInterface;
use Interop\Container\ContainerInterface;

class BasicLazyFactory extends AbstractLazyFactory implements AbstractFactoryInterface
{

    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null){
        return $this->createObject($requestedName, $serviceLocator);
    }

    public function canCreate(ContainerInterface $serviceLocator, $requestedName){
        return class_exists($requestedName);
    }
}

