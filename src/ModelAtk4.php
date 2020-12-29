<?php

declare(strict_types=1);

namespace Mvorisek\Iterphpstan;

class ModelAtk4 extends \Atk4\Data\Model
{
    /** @var int */
    public $a;

    public function testPhpStanIter(): void
    {
        $m = new self();
        foreach ($m as $mitem) {
            $m->a = 5;
            $mitem->a = 5;
        }
    }
}
