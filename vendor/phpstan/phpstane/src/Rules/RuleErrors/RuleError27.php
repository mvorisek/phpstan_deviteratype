<?php

declare (strict_types=1);
namespace PHPStan\Rules\RuleErrors;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
class RuleError27 implements \PHPStan\Rules\RuleError, \PHPStan\Rules\LineRuleError, \PHPStan\Rules\TipRuleError, \PHPStan\Rules\IdentifierRuleError
{
    /** @var string */
    public $message;
    /** @var int */
    public $line;
    /** @var string */
    public $tip;
    /** @var string */
    public $identifier;
    public function getMessage() : string
    {
        return $this->message;
    }
    public function getLine() : int
    {
        return $this->line;
    }
    public function getTip() : string
    {
        return $this->tip;
    }
    public function getIdentifier() : string
    {
        return $this->identifier;
    }
}
