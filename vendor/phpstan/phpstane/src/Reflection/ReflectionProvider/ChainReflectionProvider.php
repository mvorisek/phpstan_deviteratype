<?php

declare (strict_types=1);
namespace PHPStan\Reflection\ReflectionProvider;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\GlobalConstantReflection;
use PHPStan\Reflection\ReflectionProvider;
class ChainReflectionProvider implements \PHPStan\Reflection\ReflectionProvider
{
    /** @var \PHPStan\Reflection\ReflectionProvider[] */
    private $providers;
    /**
     * @param \PHPStan\Reflection\ReflectionProvider[] $providers
     */
    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }
    public function hasClass(string $className) : bool
    {
        foreach ($this->providers as $provider) {
            if (!$provider->hasClass($className)) {
                continue;
            }
            return \true;
        }
        return \false;
    }
    public function getClass(string $className) : \PHPStan\Reflection\ClassReflection
    {
        foreach ($this->providers as $provider) {
            if (!$provider->hasClass($className)) {
                continue;
            }
            return $provider->getClass($className);
        }
        throw new \PHPStan\Broker\ClassNotFoundException($className);
    }
    public function getClassName(string $className) : string
    {
        foreach ($this->providers as $provider) {
            if (!$provider->hasClass($className)) {
                continue;
            }
            return $provider->getClassName($className);
        }
        throw new \PHPStan\Broker\ClassNotFoundException($className);
    }
    public function supportsAnonymousClasses() : bool
    {
        foreach ($this->providers as $provider) {
            if (!$provider->supportsAnonymousClasses()) {
                continue;
            }
            return \true;
        }
        return \false;
    }
    public function getAnonymousClassReflection(\PhpParser\Node\Stmt\Class_ $classNode, \PHPStan\Analyser\Scope $scope) : \PHPStan\Reflection\ClassReflection
    {
        foreach ($this->providers as $provider) {
            if (!$provider->supportsAnonymousClasses()) {
                continue;
            }
            return $provider->getAnonymousClassReflection($classNode, $scope);
        }
        throw new \PHPStan\ShouldNotHappenException();
    }
    public function hasFunction(\PhpParser\Node\Name $nameNode, ?\PHPStan\Analyser\Scope $scope) : bool
    {
        foreach ($this->providers as $provider) {
            if (!$provider->hasFunction($nameNode, $scope)) {
                continue;
            }
            return \true;
        }
        return \false;
    }
    public function getFunction(\PhpParser\Node\Name $nameNode, ?\PHPStan\Analyser\Scope $scope) : \PHPStan\Reflection\FunctionReflection
    {
        foreach ($this->providers as $provider) {
            if (!$provider->hasFunction($nameNode, $scope)) {
                continue;
            }
            return $provider->getFunction($nameNode, $scope);
        }
        throw new \PHPStan\Broker\FunctionNotFoundException((string) $nameNode);
    }
    public function resolveFunctionName(\PhpParser\Node\Name $nameNode, ?\PHPStan\Analyser\Scope $scope) : ?string
    {
        foreach ($this->providers as $provider) {
            $resolvedName = $provider->resolveFunctionName($nameNode, $scope);
            if ($resolvedName === null) {
                continue;
            }
            return $resolvedName;
        }
        return null;
    }
    public function hasConstant(\PhpParser\Node\Name $nameNode, ?\PHPStan\Analyser\Scope $scope) : bool
    {
        foreach ($this->providers as $provider) {
            if (!$provider->hasConstant($nameNode, $scope)) {
                continue;
            }
            return \true;
        }
        return \false;
    }
    public function getConstant(\PhpParser\Node\Name $nameNode, ?\PHPStan\Analyser\Scope $scope) : \PHPStan\Reflection\GlobalConstantReflection
    {
        foreach ($this->providers as $provider) {
            if (!$provider->hasConstant($nameNode, $scope)) {
                continue;
            }
            return $provider->getConstant($nameNode, $scope);
        }
        throw new \PHPStan\Broker\ConstantNotFoundException((string) $nameNode);
    }
    public function resolveConstantName(\PhpParser\Node\Name $nameNode, ?\PHPStan\Analyser\Scope $scope) : ?string
    {
        foreach ($this->providers as $provider) {
            $resolvedName = $provider->resolveConstantName($nameNode, $scope);
            if ($resolvedName === null) {
                continue;
            }
            return $resolvedName;
        }
        return null;
    }
}
