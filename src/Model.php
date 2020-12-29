<?php

declare(strict_types=1);

namespace Mvorisek\Iterphpstan;

/**
 * @phpstan-implements \IteratorAggregate<static>
 */
class Model implements \IteratorAggregate
{
    /**
     * @return \Traversable<static>
     */
    public function getIterator(): \Traversable
    {
        yield $this;
    }
}

class Model2 extends Model
{
    /** var int */
    public $a;
}

$m = new Model2();
foreach ($m as $mitem) {
    $m->a = 5;
    $mitem->a = 5;
}
