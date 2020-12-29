<?php

declare (strict_types=1);
namespace PHPStan\Rules\Generics;

use PhpParser\Node\Name;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\VerbosityLevel;
class GenericAncestorsCheck
{
    /** @var \PHPStan\Reflection\ReflectionProvider */
    private $reflectionProvider;
    /** @var \PHPStan\Rules\Generics\GenericObjectTypeCheck */
    private $genericObjectTypeCheck;
    /** @var \PHPStan\Rules\Generics\VarianceCheck */
    private $varianceCheck;
    /** @var bool */
    private $checkGenericClassInNonGenericObjectType;
    public function __construct(\PHPStan\Reflection\ReflectionProvider $reflectionProvider, \PHPStan\Rules\Generics\GenericObjectTypeCheck $genericObjectTypeCheck, \PHPStan\Rules\Generics\VarianceCheck $varianceCheck, bool $checkGenericClassInNonGenericObjectType)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->genericObjectTypeCheck = $genericObjectTypeCheck;
        $this->varianceCheck = $varianceCheck;
        $this->checkGenericClassInNonGenericObjectType = $checkGenericClassInNonGenericObjectType;
    }
    /**
     * @param array<\PhpParser\Node\Name> $nameNodes
     * @param array<\PHPStan\Type\Type> $ancestorTypes
     * @return \PHPStan\Rules\RuleError[]
     */
    public function check(array $nameNodes, array $ancestorTypes, string $incompatibleTypeMessage, string $noNamesMessage, string $noRelatedNameMessage, string $classNotGenericMessage, string $notEnoughTypesMessage, string $extraTypesMessage, string $typeIsNotSubtypeMessage, string $invalidTypeMessage, string $genericClassInNonGenericObjectType, string $invalidVarianceMessage) : array
    {
        $names = \array_fill_keys(\array_map(static function (\PhpParser\Node\Name $nameNode) : string {
            return $nameNode->toString();
        }, $nameNodes), \true);
        $unusedNames = $names;
        $messages = [];
        foreach ($ancestorTypes as $ancestorType) {
            if (!$ancestorType instanceof \PHPStan\Type\Generic\GenericObjectType) {
                $messages[] = \PHPStan\Rules\RuleErrorBuilder::message(\sprintf($incompatibleTypeMessage, $ancestorType->describe(\PHPStan\Type\VerbosityLevel::typeOnly())))->build();
                continue;
            }
            $ancestorTypeClassName = $ancestorType->getClassName();
            if (!isset($names[$ancestorTypeClassName])) {
                if (\count($names) === 0) {
                    $messages[] = \PHPStan\Rules\RuleErrorBuilder::message($noNamesMessage)->build();
                } else {
                    $messages[] = \PHPStan\Rules\RuleErrorBuilder::message(\sprintf($noRelatedNameMessage, $ancestorTypeClassName, \implode(', ', \array_keys($names))))->build();
                }
                continue;
            }
            unset($unusedNames[$ancestorTypeClassName]);
            $genericObjectTypeCheckMessages = $this->genericObjectTypeCheck->check($ancestorType, $classNotGenericMessage, $notEnoughTypesMessage, $extraTypesMessage, $typeIsNotSubtypeMessage);
            $messages = \array_merge($messages, $genericObjectTypeCheckMessages);
            foreach ($ancestorType->getReferencedClasses() as $referencedClass) {
                if ($this->reflectionProvider->hasClass($referencedClass) && !$this->reflectionProvider->getClass($referencedClass)->isTrait()) {
                    continue;
                }
                $messages[] = \PHPStan\Rules\RuleErrorBuilder::message(\sprintf($invalidTypeMessage, $referencedClass))->build();
            }
            $variance = \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant();
            $messageContext = \sprintf($invalidVarianceMessage, $ancestorType->describe(\PHPStan\Type\VerbosityLevel::typeOnly()));
            foreach ($this->varianceCheck->check($variance, $ancestorType, $messageContext) as $message) {
                $messages[] = $message;
            }
        }
        if ($this->checkGenericClassInNonGenericObjectType) {
            foreach (\array_keys($unusedNames) as $unusedName) {
                if (!$this->reflectionProvider->hasClass($unusedName)) {
                    continue;
                }
                $unusedNameClassReflection = $this->reflectionProvider->getClass($unusedName);
                if (!$unusedNameClassReflection->isGeneric()) {
                    continue;
                }
                $messages[] = \PHPStan\Rules\RuleErrorBuilder::message(\sprintf($genericClassInNonGenericObjectType, $unusedName, \implode(', ', \array_keys($unusedNameClassReflection->getTemplateTypeMap()->getTypes()))))->tip(\PHPStan\Rules\MissingTypehintCheck::TURN_OFF_NON_GENERIC_CHECK_TIP)->build();
            }
        }
        return $messages;
    }
}
