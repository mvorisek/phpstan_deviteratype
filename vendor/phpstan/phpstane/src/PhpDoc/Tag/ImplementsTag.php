<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Type;
class ImplementsTag
{
    /** @var \PHPStan\Type\Type */
    private $type;
    public function __construct(\PHPStan\Type\Type $type)
    {
        $this->type = $type;

        var_dump($type->describe(\PHPStan\Type\VerbosityLevel::precise()));
    }
    public function getType() : \PHPStan\Type\Type
    {
      //  debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        return $this->type;
    }
    /**
     * @param mixed[] $properties
     * @return self
     */
    public static function __set_state(array $properties) : self
    {
        return new self($properties['type']);
    }
}
