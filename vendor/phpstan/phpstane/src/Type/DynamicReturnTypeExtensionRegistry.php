<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Broker\Broker;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\ReflectionProvider;
class DynamicReturnTypeExtensionRegistry
{
    /** @var \PHPStan\Reflection\ReflectionProvider */
    private $reflectionProvider;
    /** @var \PHPStan\Type\DynamicMethodReturnTypeExtension[] */
    private $dynamicMethodReturnTypeExtensions;
    /** @var \PHPStan\Type\DynamicStaticMethodReturnTypeExtension[] */
    private $dynamicStaticMethodReturnTypeExtensions;
    /** @var \PHPStan\Type\DynamicFunctionReturnTypeExtension[] */
    private $dynamicFunctionReturnTypeExtensions;
    /** @var \PHPStan\Type\DynamicMethodReturnTypeExtension[][]|null */
    private $dynamicMethodReturnTypeExtensionsByClass = null;
    /** @var \PHPStan\Type\DynamicStaticMethodReturnTypeExtension[][]|null */
    private $dynamicStaticMethodReturnTypeExtensionsByClass = null;
    /**
     * @param \PHPStan\Broker\Broker $broker
     * @param ReflectionProvider $reflectionProvider
     * @param \PHPStan\Type\DynamicMethodReturnTypeExtension[] $dynamicMethodReturnTypeExtensions
     * @param \PHPStan\Type\DynamicStaticMethodReturnTypeExtension[] $dynamicStaticMethodReturnTypeExtensions
     * @param \PHPStan\Type\DynamicFunctionReturnTypeExtension[] $dynamicFunctionReturnTypeExtensions
     */
    public function __construct(\PHPStan\Broker\Broker $broker, \PHPStan\Reflection\ReflectionProvider $reflectionProvider, array $dynamicMethodReturnTypeExtensions, array $dynamicStaticMethodReturnTypeExtensions, array $dynamicFunctionReturnTypeExtensions)
    {
        foreach (\array_merge($dynamicMethodReturnTypeExtensions, $dynamicStaticMethodReturnTypeExtensions, $dynamicFunctionReturnTypeExtensions) as $extension) {
            if (!$extension instanceof \PHPStan\Reflection\BrokerAwareExtension) {
                continue;
            }
            $extension->setBroker($broker);
        }
        $this->reflectionProvider = $reflectionProvider;
        $this->dynamicMethodReturnTypeExtensions = $dynamicMethodReturnTypeExtensions;
        $this->dynamicStaticMethodReturnTypeExtensions = $dynamicStaticMethodReturnTypeExtensions;
        $this->dynamicFunctionReturnTypeExtensions = $dynamicFunctionReturnTypeExtensions;
    }
    /**
     * @param string $className
     * @return \PHPStan\Type\DynamicMethodReturnTypeExtension[]
     */
    public function getDynamicMethodReturnTypeExtensionsForClass(string $className) : array
    {
        if ($this->dynamicMethodReturnTypeExtensionsByClass === null) {
            $byClass = [];
            foreach ($this->dynamicMethodReturnTypeExtensions as $extension) {
                $byClass[$extension->getClass()][] = $extension;
            }
            $this->dynamicMethodReturnTypeExtensionsByClass = $byClass;
        }
        return $this->getDynamicExtensionsForType($this->dynamicMethodReturnTypeExtensionsByClass, $className);
    }
    /**
     * @param string $className
     * @return \PHPStan\Type\DynamicStaticMethodReturnTypeExtension[]
     */
    public function getDynamicStaticMethodReturnTypeExtensionsForClass(string $className) : array
    {
        if ($this->dynamicStaticMethodReturnTypeExtensionsByClass === null) {
            $byClass = [];
            foreach ($this->dynamicStaticMethodReturnTypeExtensions as $extension) {
                $byClass[$extension->getClass()][] = $extension;
            }
            $this->dynamicStaticMethodReturnTypeExtensionsByClass = $byClass;
        }
        return $this->getDynamicExtensionsForType($this->dynamicStaticMethodReturnTypeExtensionsByClass, $className);
    }
    /**
     * @param \PHPStan\Type\DynamicMethodReturnTypeExtension[][]|\PHPStan\Type\DynamicStaticMethodReturnTypeExtension[][] $extensions
     * @param string $className
     * @return mixed[]
     */
    private function getDynamicExtensionsForType(array $extensions, string $className) : array
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return [];
        }
        $extensionsForClass = [[]];
        $class = $this->reflectionProvider->getClass($className);
        foreach (\array_merge([$className], $class->getParentClassesNames(), $class->getNativeReflection()->getInterfaceNames()) as $extensionClassName) {
            if (!isset($extensions[$extensionClassName])) {
                continue;
            }
            $extensionsForClass[] = $extensions[$extensionClassName];
        }
        return \array_merge(...$extensionsForClass);
    }
    /**
     * @return \PHPStan\Type\DynamicFunctionReturnTypeExtension[]
     */
    public function getDynamicFunctionReturnTypeExtensions() : array
    {
        return $this->dynamicFunctionReturnTypeExtensions;
    }
}
