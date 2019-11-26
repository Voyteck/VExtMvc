<?php
namespace Voyteck\VExtMvc\Factory;

use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Abstract lazy factory delivering number of features
 *
 * @author wojte
 *
 */
abstract class AbstractLazyFactory {

    /**
     * Configuration of addObjectProperties() functionality
     *
     * @return array
     */
    protected function getPropertiesConfig() {
        return array(
            'LAZYFACTORY_INCLUDE_CONFIG'                => 'Config',
            'LAZYFACTORY_INCLUDE_SERVICELOCATOR'        => function($serviceLocator) { return $serviceLocator; },
        );
    }

    protected $aliases = [
        'Zend\Form\FormElementManager' => 'FormElementManager',
        'Zend\Validator\ValidatorPluginManager' => 'ValidatorManager',
        'Zend\Mvc\I18n\Translator' => 'translator',
    ];

    /**
     * Adds properties to the object basing on LAZYFACTORY_INCLUDE_* constants
     * If the properties are to be available during object's __construct()
     * they need to be set after object will be created without calling constructor
     * (and construtor can be called later)
     *
     * @param unknown $object                           Object to which the properties should be added (and against which the constant checks will be performed)
     * @param ServiceLocatorInterface $serviceLocator   ServiceLocator
     */
    protected function addObjectProperties($object, ServiceLocatorInterface $serviceLocator) {
        foreach($this->getPropertiesConfig() as $constant => $property) {
            if (defined(get_class($object) . '::' . $constant))
                if (constant(get_class($object) . '::' . $constant) !== null) {
                    $propertyName = constant(get_class($object) . '::' . $constant);
                    if (is_string($property)) // setting the property for string values (retrieves from serviceLocator)
                        $object->$propertyName = $serviceLocator->get($property);
                    if (is_callable($property)) // setting the property for callback values
                        $object->$propertyName = $property($serviceLocator);
                }
        }
    }

    protected function createConstructorParameters($requestedName, ServiceLocatorInterface $serviceLocator) {
//     	$serviceLocator->get('Log')->debug('Creating parameters for ' . $requestedName);
        $class = new \ReflectionClass($requestedName);
        $constructor = $class->getConstructor();
        $parameter_instances = [];
        if($constructor) {
            $params = $constructor->getParameters();
            if($params) {
                foreach($params as $p)
                    if($p->getClass()) {
                        $cn = $p->getClass()->getName();
                        if (is_array($this->aliases))
                            if (array_key_exists($cn, $this->aliases))
                                $cn = $this->aliases[$cn];

                        try {
                            $parameter_instances[] = $serviceLocator->get($cn);
                        }
                        catch (\Exception $x) {
                            $serviceLocator->get('Log')->err(get_class($this) . " couldn't create an instance of $cn to satisfy the constructor for $requestedName - " . $x->getMessage());
                            exit;
                        }
                    }
            }
        }

        if ($parameter_instances === null)
            return array();
        else
            return $parameter_instances;
    }

    /**
     * Creates actual object.
     * Adds properties if appropriate constants are set.
     * Adds EventManager log functionality if class implements EventManagerLogCapableInterface
     * and has Log attached via LAZYFACTORY_INCLUDE_LOG constant
     *
     * @param unknown $requestedName                    Name for the class
     * @param ServiceLocatorInterface $serviceLocator   ServiceLocator
     * @param array $constructParams                    Constructor (__construct()) parameters in array
     * @return object                                   Created object
     */
    protected function createObject($requestedName, ServiceLocatorInterface $serviceLocator, array $constructParams = array()) {

        $reflectedClass = new \ReflectionClass($requestedName);
        $objectInstance = $reflectedClass->newInstanceWithoutConstructor();

        // Adding properties defined by constants
        $this->addObjectProperties($objectInstance, $serviceLocator);



        // Running constructor
        if ($reflectedClass->hasMethod('__construct'))
            \call_user_func_array(array($objectInstance, '__construct'), $constructParams);

        return $objectInstance;
    }

}

