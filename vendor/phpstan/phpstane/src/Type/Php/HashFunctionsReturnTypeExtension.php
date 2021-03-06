<?php

declare (strict_types=1);
namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
final class HashFunctionsReturnTypeExtension implements \PHPStan\Type\DynamicFunctionReturnTypeExtension
{
    public function isFunctionSupported(\PHPStan\Reflection\FunctionReflection $functionReflection) : bool
    {
        return $functionReflection->getName() === 'hash';
    }
    public function getTypeFromFunctionCall(\PHPStan\Reflection\FunctionReflection $functionReflection, \PhpParser\Node\Expr\FuncCall $functionCall, \PHPStan\Analyser\Scope $scope) : \PHPStan\Type\Type
    {
        $defaultReturnType = \PHPStan\Reflection\ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
        if (!isset($functionCall->args[0])) {
            return $defaultReturnType;
        }
        $argType = $scope->getType($functionCall->args[0]->value);
        if ($argType instanceof \PHPStan\Type\MixedType) {
            return \PHPStan\Type\TypeUtils::toBenevolentUnion($defaultReturnType);
        }
        $values = \PHPStan\Type\TypeUtils::getConstantStrings($argType);
        if (\count($values) !== 1) {
            return \PHPStan\Type\TypeUtils::toBenevolentUnion($defaultReturnType);
        }
        $string = $values[0];
        return \in_array($string->getValue(), \hash_algos(), \true) ? new \PHPStan\Type\StringType() : new \PHPStan\Type\Constant\ConstantBooleanType(\false);
    }
}
