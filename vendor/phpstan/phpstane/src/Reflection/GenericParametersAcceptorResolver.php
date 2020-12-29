<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\Reflection\Generic\ResolvedFunctionVariant;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;
class GenericParametersAcceptorResolver
{
    /**
     * Resolve template types
     *
     * @param \PHPStan\Type\Type[] $argTypes Unpacked arguments
     */
    public static function resolve(array $argTypes, \PHPStan\Reflection\ParametersAcceptor $parametersAcceptor) : \PHPStan\Reflection\ParametersAcceptor
    {
        $typeMap = \PHPStan\Type\Generic\TemplateTypeMap::createEmpty();
        foreach ($parametersAcceptor->getParameters() as $i => $param) {
            if (isset($argTypes[$i])) {
                $argType = $argTypes[$i];
            } elseif ($param->getDefaultValue() !== null) {
                $argType = $param->getDefaultValue();
            } else {
                break;
            }
            $paramType = $param->getType();
            $typeMap = $typeMap->union($paramType->inferTemplateTypes($argType));
        }
        return new \PHPStan\Reflection\Generic\ResolvedFunctionVariant($parametersAcceptor, new \PHPStan\Type\Generic\TemplateTypeMap(\array_merge($parametersAcceptor->getTemplateTypeMap()->map(static function (string $name, \PHPStan\Type\Type $type) : Type {
            return new \PHPStan\Type\ErrorType();
        })->getTypes(), $typeMap->getTypes())));
    }
}
