<?php

declare (strict_types=1);
namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ErrorType;
use PHPStan\Type\NeverType;
use PHPStan\Type\VerbosityLevel;
/**
 * @implements \PHPStan\Rules\Rule<\PHPStan\Node\ClassPropertyNode>
 */
class IncompatiblePropertyPhpDocTypeRule implements \PHPStan\Rules\Rule
{
    /** @var \PHPStan\Rules\Generics\GenericObjectTypeCheck */
    private $genericObjectTypeCheck;
    public function __construct(\PHPStan\Rules\Generics\GenericObjectTypeCheck $genericObjectTypeCheck)
    {
        $this->genericObjectTypeCheck = $genericObjectTypeCheck;
    }
    public function getNodeType() : string
    {
        return \PHPStan\Node\ClassPropertyNode::class;
    }
    public function processNode(\PhpParser\Node $node, \PHPStan\Analyser\Scope $scope) : array
    {
        if (!$scope->isInClass()) {
            throw new \PHPStan\ShouldNotHappenException();
        }
        $propertyName = $node->getName();
        $propertyReflection = $scope->getClassReflection()->getNativeProperty($propertyName);
        if (!$propertyReflection->hasPhpDoc()) {
            return [];
        }
        $phpDocType = $propertyReflection->getPhpDocType();
        $description = 'PHPDoc tag @var';
        if ($propertyReflection->isPromoted()) {
            $description = 'PHPDoc type';
        }
        $messages = [];
        if ($phpDocType instanceof \PHPStan\Type\ErrorType || $phpDocType instanceof \PHPStan\Type\NeverType && !$phpDocType->isExplicit()) {
            $messages[] = \PHPStan\Rules\RuleErrorBuilder::message(\sprintf('%s for property %s::$%s contains unresolvable type.', $description, $propertyReflection->getDeclaringClass()->getName(), $propertyName))->build();
        }
        $nativeType = $propertyReflection->getNativeType();
        $isSuperType = $nativeType->isSuperTypeOf($phpDocType);
        if ($isSuperType->no()) {
            $messages[] = \PHPStan\Rules\RuleErrorBuilder::message(\sprintf('%s for property %s::$%s with type %s is incompatible with native type %s.', $description, $propertyReflection->getDeclaringClass()->getDisplayName(), $propertyName, $phpDocType->describe(\PHPStan\Type\VerbosityLevel::typeOnly()), $nativeType->describe(\PHPStan\Type\VerbosityLevel::typeOnly())))->build();
        } elseif ($isSuperType->maybe()) {
            $messages[] = \PHPStan\Rules\RuleErrorBuilder::message(\sprintf('%s for property %s::$%s with type %s is not subtype of native type %s.', $description, $propertyReflection->getDeclaringClass()->getDisplayName(), $propertyName, $phpDocType->describe(\PHPStan\Type\VerbosityLevel::typeOnly()), $nativeType->describe(\PHPStan\Type\VerbosityLevel::typeOnly())))->build();
        }
        $messages = \array_merge($messages, $this->genericObjectTypeCheck->check($phpDocType, \sprintf('%s for property %s::$%s contains generic type %%s but class %%s is not generic.', $description, $propertyReflection->getDeclaringClass()->getDisplayName(), $propertyName), \sprintf('Generic type %%s in %s for property %s::$%s does not specify all template types of class %%s: %%s', $description, $propertyReflection->getDeclaringClass()->getDisplayName(), $propertyName), \sprintf('Generic type %%s in %s for property %s::$%s specifies %%d template types, but class %%s supports only %%d: %%s', $description, $propertyReflection->getDeclaringClass()->getDisplayName(), $propertyName), \sprintf('Type %%s in generic type %%s in %s for property %s::$%s is not subtype of template type %%s of class %%s.', $description, $propertyReflection->getDeclaringClass()->getDisplayName(), $propertyName)));
        return $messages;
    }
}
