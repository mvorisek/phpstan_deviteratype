<?php

declare (strict_types=1);
namespace _HumbugBox221ad6f1b81f\Roave\BetterReflection\SourceLocator\Type;

use InvalidArgumentException;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use _HumbugBox221ad6f1b81f\Roave\BetterReflection\BetterReflection;
use _HumbugBox221ad6f1b81f\Roave\BetterReflection\Identifier\Identifier;
use _HumbugBox221ad6f1b81f\Roave\BetterReflection\Reflection\Exception\InvalidConstantNode;
use _HumbugBox221ad6f1b81f\Roave\BetterReflection\SourceLocator\Ast\Locator as AstLocator;
use _HumbugBox221ad6f1b81f\Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use _HumbugBox221ad6f1b81f\Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use _HumbugBox221ad6f1b81f\Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator\FileReadTrapStreamWrapper;
use _HumbugBox221ad6f1b81f\Roave\BetterReflection\Util\ConstantNodeChecker;
use function array_key_exists;
use function array_reverse;
use function assert;
use function class_exists;
use function defined;
use function file_exists;
use function file_get_contents;
use function function_exists;
use function get_defined_constants;
use function get_included_files;
use function interface_exists;
use function is_string;
use function restore_error_handler;
use function set_error_handler;
use function spl_autoload_functions;
use function trait_exists;
/**
 * Use PHP's built in autoloader to locate a class, without actually loading.
 *
 * There are some prerequisites...
 *   - we expect the autoloader to load classes from a file (i.e. using require/include)
 *   - your autoloader of choice does not replace stream wrappers
 */
class AutoloadSourceLocator extends \_HumbugBox221ad6f1b81f\Roave\BetterReflection\SourceLocator\Type\AbstractSourceLocator
{
    /** @var Parser */
    private $phpParser;
    /** @var NodeTraverser */
    private $nodeTraverser;
    /** @var NodeVisitorAbstract */
    private $constantVisitor;
    public function __construct(?\_HumbugBox221ad6f1b81f\Roave\BetterReflection\SourceLocator\Ast\Locator $astLocator = null, ?\PhpParser\Parser $phpParser = null)
    {
        $betterReflection = new \_HumbugBox221ad6f1b81f\Roave\BetterReflection\BetterReflection();
        parent::__construct($astLocator ?? $betterReflection->astLocator());
        $this->phpParser = $phpParser ?? $betterReflection->phpParser();
        $this->constantVisitor = $this->createConstantVisitor();
        $this->nodeTraverser = new \PhpParser\NodeTraverser();
        $this->nodeTraverser->addVisitor(new \PhpParser\NodeVisitor\NameResolver());
        $this->nodeTraverser->addVisitor($this->constantVisitor);
    }
    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     * @throws InvalidFileLocation
     */
    protected function createLocatedSource(\_HumbugBox221ad6f1b81f\Roave\BetterReflection\Identifier\Identifier $identifier) : ?\_HumbugBox221ad6f1b81f\Roave\BetterReflection\SourceLocator\Located\LocatedSource
    {
        $potentiallyLocatedFile = $this->attemptAutoloadForIdentifier($identifier);
        if (!($potentiallyLocatedFile && \file_exists($potentiallyLocatedFile))) {
            return null;
        }
        return new \_HumbugBox221ad6f1b81f\Roave\BetterReflection\SourceLocator\Located\LocatedSource(\file_get_contents($potentiallyLocatedFile), $potentiallyLocatedFile);
    }
    /**
     * Attempts to locate the specified identifier.
     *
     * @throws ReflectionException
     */
    private function attemptAutoloadForIdentifier(\_HumbugBox221ad6f1b81f\Roave\BetterReflection\Identifier\Identifier $identifier) : ?string
    {
        if ($identifier->isClass()) {
            return $this->locateClassByName($identifier->getName());
        }
        if ($identifier->isFunction()) {
            return $this->locateFunctionByName($identifier->getName());
        }
        if ($identifier->isConstant()) {
            return $this->locateConstantByName($identifier->getName());
        }
        return null;
    }
    /**
     * Attempt to locate a class by name.
     *
     * If class already exists, simply use internal reflection API to get the
     * filename and store it.
     *
     * If class does not exist, we make an assumption that whatever autoloaders
     * that are registered will be loading a file. We then override the file://
     * protocol stream wrapper to "capture" the filename we expect the class to
     * be in, and then restore it. Note that class_exists will cause an error
     * that it cannot find the file, so we squelch the errors by overriding the
     * error handler temporarily.
     *
     * Note: the following code is designed so that the first hit on an actual
     *       **file** leads to a path being resolved. No actual autoloading nor
     *       file reading should happen, and most certainly no other classes
     *       should exist after execution. The only filesystem access is to
     *       check whether the file exists.
     *
     * @throws ReflectionException
     */
    private function locateClassByName(string $className) : ?string
    {
        if (\class_exists($className, \false) || \interface_exists($className, \false) || \trait_exists($className, \false)) {
            $filename = (new \ReflectionClass($className))->getFileName();
            if (!\is_string($filename)) {
                return null;
            }
            return $filename;
        }
        $this->silenceErrors();
        try {
            return \_HumbugBox221ad6f1b81f\Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator\FileReadTrapStreamWrapper::withStreamWrapperOverride(static function () use($className) : ?string {
                foreach (\spl_autoload_functions() as $preExistingAutoloader) {
                    $preExistingAutoloader($className);
                    /**
                     * This static variable is populated by the side-effect of the stream wrapper
                     * trying to read the file path when `include()` is used by an autoloader.
                     *
                     * This will not be `null` when the autoloader tried to read a file.
                     */
                    if (\_HumbugBox221ad6f1b81f\Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator\FileReadTrapStreamWrapper::$autoloadLocatedFile !== null) {
                        return \_HumbugBox221ad6f1b81f\Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator\FileReadTrapStreamWrapper::$autoloadLocatedFile;
                    }
                }
                return null;
            });
        } finally {
            \restore_error_handler();
        }
    }
    private function silenceErrors() : void
    {
        \set_error_handler(static function () : bool {
            return \true;
        });
    }
    /**
     * We can only load functions if they already exist, because PHP does not
     * have function autoloading. Therefore if it exists, we simply use the
     * internal reflection API to find the filename. If it doesn't we can do
     * nothing so throw an exception.
     *
     * @throws ReflectionException
     */
    private function locateFunctionByName(string $functionName) : ?string
    {
        if (!\function_exists($functionName)) {
            return null;
        }
        $reflectionFileName = (new \ReflectionFunction($functionName))->getFileName();
        if (!\is_string($reflectionFileName)) {
            return null;
        }
        return $reflectionFileName;
    }
    /**
     * We can only load constants if they already exist, because PHP does not
     * have constant autoloading. Therefore if it exists, we simply use brute force
     * to search throught all included files to find the right filename.
     */
    private function locateConstantByName(string $constantName) : ?string
    {
        if (!\defined($constantName)) {
            return null;
        }
        /** @var array<string, array<string, int|string|float|bool|array|resource|null>> $constants */
        $constants = \get_defined_constants(\true);
        if (!\array_key_exists($constantName, $constants['user'])) {
            return null;
        }
        /** @psalm-suppress UndefinedMethod */
        $this->constantVisitor->setConstantName($constantName);
        $constantFileName = null;
        // Note: looking at files in reverse order, since newer files are more likely to have
        //       defined a constant that is being looked up. Earlier files are possibly related
        //       to libraries/frameworks that we rely upon.
        foreach (\array_reverse(\get_included_files()) as $includedFileName) {
            $ast = $this->phpParser->parse(\file_get_contents($includedFileName));
            $this->nodeTraverser->traverse($ast);
            /** @psalm-suppress UndefinedMethod */
            if ($this->constantVisitor->getNode() !== null) {
                $constantFileName = $includedFileName;
                break;
            }
        }
        return $constantFileName;
    }
    private function createConstantVisitor() : \PhpParser\NodeVisitorAbstract
    {
        return new class extends \PhpParser\NodeVisitorAbstract
        {
            /** @var string|null */
            private $constantName;
            /** @var Node\Stmt\Const_|Node\Expr\FuncCall|null */
            private $node;
            public function enterNode(\PhpParser\Node $node) : ?int
            {
                if ($node instanceof \PhpParser\Node\Stmt\Const_) {
                    foreach ($node->consts as $constNode) {
                        if ($constNode->namespacedName->toString() === $this->constantName) {
                            $this->node = $node;
                            return \PhpParser\NodeTraverser::STOP_TRAVERSAL;
                        }
                    }
                    return \PhpParser\NodeTraverser::DONT_TRAVERSE_CHILDREN;
                }
                if ($node instanceof \PhpParser\Node\Expr\FuncCall) {
                    try {
                        \_HumbugBox221ad6f1b81f\Roave\BetterReflection\Util\ConstantNodeChecker::assertValidDefineFunctionCall($node);
                    } catch (\_HumbugBox221ad6f1b81f\Roave\BetterReflection\Reflection\Exception\InvalidConstantNode $e) {
                        return null;
                    }
                    $nameNode = $node->args[0]->value;
                    \assert($nameNode instanceof \PhpParser\Node\Scalar\String_);
                    if ($nameNode->value === $this->constantName) {
                        $this->node = $node;
                        return \PhpParser\NodeTraverser::STOP_TRAVERSAL;
                    }
                }
                if ($node instanceof \PhpParser\Node\Stmt\Class_) {
                    return \PhpParser\NodeTraverser::DONT_TRAVERSE_CHILDREN;
                }
                return null;
            }
            public function setConstantName(string $constantName) : void
            {
                $this->constantName = $constantName;
            }
            /**
             * @return Node\Stmt\Const_|Node\Expr\FuncCall|null
             */
            public function getNode() : ?\PhpParser\Node
            {
                return $this->node;
            }
        };
    }
}
