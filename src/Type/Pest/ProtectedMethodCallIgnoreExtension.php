<?php

declare(strict_types=1);

namespace Pest\PHPStan\Type\Pest;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPUnit\Framework\TestCase;

final class ProtectedMethodCallIgnoreExtension implements IgnoreErrorExtension
{
    private const array INSTANCE_IDENTIFIERS = ['method.protected', 'method.private'];

    private const array STATIC_IDENTIFIERS = ['staticMethod.protected', 'staticMethod.private'];

    public function shouldIgnore(Error $error, Node $node, Scope $scope): bool
    {
        $identifier = $error->getIdentifier();

        $receiver = match (true) {
            $node instanceof MethodCall && in_array($identifier, self::INSTANCE_IDENTIFIERS, true) => $node->var,
            $node instanceof StaticCall && in_array($identifier, self::STATIC_IDENTIFIERS, true) => $node->class,
            default => null,
        };

        if (! $receiver instanceof Variable || $receiver->name !== 'this') {
            return false;
        }

        if (! $scope->isInAnonymousFunction()) {
            return false;
        }

        if (! $scope->hasVariableType('this')->yes()) {
            return false;
        }

        $thisType = $scope->getVariableType('this');

        if (! new ObjectType(TestCase::class)->isSuperTypeOf($thisType)->yes()) {
            return false;
        }

        // @note: the closure is bound to a subclass of the test case, so its protected members are reachable.
        if ($identifier === 'method.protected' || $identifier === 'staticMethod.protected') {
            return true;
        }

        // @note: a private member is reachable only when it comes from a trait Pest flattens onto the test case.
        return $this->isBoundTraitMethod($node, $thisType, $scope);
    }

    private function isBoundTraitMethod(MethodCall|StaticCall $node, Type $thisType, Scope $scope): bool
    {
        if (! $node->name instanceof Identifier) {
            return false;
        }

        $methodName = $node->name->toString();

        if (! $thisType->hasMethod($methodName)->yes()) {
            return false;
        }

        return $thisType->getMethod($methodName, $scope)->getDeclaringClass()->isTrait();
    }
}
