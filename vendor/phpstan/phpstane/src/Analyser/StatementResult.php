<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node\Stmt;
class StatementResult
{
    /** @var MutatingScope */
    private $scope;
    /** @var bool */
    private $hasYield;
    /** @var bool */
    private $isAlwaysTerminating;
    /** @var StatementExitPoint[] */
    private $exitPoints;
    /**
     * @param MutatingScope $scope
     * @param bool $hasYield
     * @param bool $isAlwaysTerminating
     * @param StatementExitPoint[] $exitPoints
     */
    public function __construct(\PHPStan\Analyser\MutatingScope $scope, bool $hasYield, bool $isAlwaysTerminating, array $exitPoints)
    {
        $this->scope = $scope;
        $this->hasYield = $hasYield;
        $this->isAlwaysTerminating = $isAlwaysTerminating;
        $this->exitPoints = $exitPoints;
    }
    public function getScope() : \PHPStan\Analyser\MutatingScope
    {
        return $this->scope;
    }
    public function hasYield() : bool
    {
        return $this->hasYield;
    }
    public function isAlwaysTerminating() : bool
    {
        return $this->isAlwaysTerminating;
    }
    public function filterOutLoopExitPoints() : self
    {
        if (!$this->isAlwaysTerminating) {
            return $this;
        }
        foreach ($this->exitPoints as $exitPoint) {
            $statement = $exitPoint->getStatement();
            if ($statement instanceof \PhpParser\Node\Stmt\Break_ || $statement instanceof \PhpParser\Node\Stmt\Continue_) {
                return new self($this->scope, $this->hasYield, \false, $this->exitPoints);
            }
        }
        return $this;
    }
    /**
     * @return StatementExitPoint[]
     */
    public function getExitPoints() : array
    {
        return $this->exitPoints;
    }
    /**
     * @param string $stmtClass
     * @return StatementExitPoint[]
     */
    public function getExitPointsByType(string $stmtClass) : array
    {
        $exitPoints = [];
        foreach ($this->exitPoints as $exitPoint) {
            if (!$exitPoint->getStatement() instanceof $stmtClass) {
                continue;
            }
            $exitPoints[] = $exitPoint;
        }
        return $exitPoints;
    }
}
