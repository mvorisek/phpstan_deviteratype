<?php

declare (strict_types=1);
namespace PHPStan;

abstract class AnalysedCodeException extends \Exception
{
    public abstract function getTip() : ?string;
}
