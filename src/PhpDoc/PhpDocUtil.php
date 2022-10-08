<?php

namespace staabm\PHPStanDba\PhpDoc;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;

final class PhpDocUtil
{
    /**
     * @api
     *
     * @param CallLike|MethodReflection $callLike
     */
    public static function matchTaintEscape($callLike, Scope $scope): ?string
    {
        if ($callLike instanceof CallLike) {
            $methodReflection = self::getMethodReflection($callLike, $scope);
        } else {
            $methodReflection = $callLike;
        }

        // XXX does not yet support conditional escaping
        // https://psalm.dev/docs/security_analysis/avoiding_false_positives/#conditional-escaping-tainted-input
        if (null !== $methodReflection) {
            // atm no resolved phpdoc for methods
            // see https://github.com/phpstan/phpstan/discussions/7657
            $phpDocString = $methodReflection->getDocComment();
            if (null !== $phpDocString && preg_match('/@psalm-taint-escape\s+(\S+)$/m', $phpDocString, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * @api
     *
     * @param CallLike|MethodReflection $callLike
     */
    public static function matchInferencePlaceholder($callLike, Scope $scope): ?string
    {
        return self::matchStringAnnotation('@phpstandba-inference-placeholder', $callLike, $scope);
    }

    /**
     * @api
     *
     * @param CallLike|MethodReflection $callLike
     */
    public static function commentContains(string $text, $callLike, Scope $scope): bool
    {
        if ($callLike instanceof CallLike) {
            $methodReflection = self::getMethodReflection($callLike, $scope);
        } else {
            $methodReflection = $callLike;
        }

        if (null !== $methodReflection) {
            // atm no resolved phpdoc for methods
            // see https://github.com/phpstan/phpstan/discussions/7657
            $phpDocString = $methodReflection->getDocComment();
            if (null !== $phpDocString && false !== strpos($phpDocString, $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns a unquoted plain string following a annotation.
     *
     * @param string                    $annotation e.g. '@phpstandba-inference-placeholder'
     * @param CallLike|MethodReflection $callLike
     */
    private static function matchStringAnnotation(string $annotation, $callLike, Scope $scope): ?string
    {
        if ($callLike instanceof CallLike) {
            $methodReflection = self::getMethodReflection($callLike, $scope);
        } else {
            $methodReflection = $callLike;
        }

        if (null !== $methodReflection) {
            // atm no resolved phpdoc for methods
            // see https://github.com/phpstan/phpstan/discussions/7657
            $phpDocString = $methodReflection->getDocComment();
            if (null !== $phpDocString && preg_match('/'.$annotation.'\s+(.+)$/m', $phpDocString, $matches)) {
                $placeholder = $matches[1];

                if (\in_array($placeholder[0], ['"', "'"], true)) {
                    $placeholder = trim($placeholder, $placeholder[0]);
                }

                return $placeholder;
            }
        }

        return null;
    }

    private static function getMethodReflection(CallLike $callLike, Scope $scope): ?MethodReflection
    {
        $methodReflection = null;
        if ($callLike instanceof Expr\StaticCall) {
            if ($callLike->class instanceof Name && $callLike->name instanceof Identifier) {
                $classType = $scope->resolveTypeByName($callLike->class);
                $methodReflection = $scope->getMethodReflection($classType, $callLike->name->name);
            }
        } elseif ($callLike instanceof Expr\MethodCall && $callLike->name instanceof Identifier) {
            $classReflection = $scope->getClassReflection();
            if (null !== $classReflection && $classReflection->hasMethod($callLike->name->name)) {
                $methodReflection = $classReflection->getMethod($callLike->name->name, $scope);
            } else {
                $callerType = $scope->getType($callLike->var);
                $methodReflection = $scope->getMethodReflection($callerType, $callLike->name->name);
            }
        }

        return $methodReflection;
    }
}
