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
     */
    public static function commentContains(string $text, CallLike $callike, Scope $scope): bool
    {
        $methodReflection = self::getMethodReflection($callike, $scope);

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
     * @param string $annotation e.g. '@phpstandba-inference-placeholder'
     */
    public static function matchStringAnnotation(string $annotation, CallLike $callike, Scope $scope): ?string
    {
        $methodReflection = self::getMethodReflection($callike, $scope);

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

    private static function getMethodReflection(CallLike $callike, Scope $scope): ?MethodReflection
    {
        $methodReflection = null;
        if ($callike instanceof Expr\StaticCall) {
            if ($callike->class instanceof Name && $callike->name instanceof Identifier) {
                $classType = $scope->resolveTypeByName($callike->class);
                $methodReflection = $scope->getMethodReflection($classType, $callike->name->name);
            }
        } elseif ($callike instanceof Expr\MethodCall && $callike->name instanceof Identifier) {
            $classReflection = $scope->getClassReflection();
            if (null !== $classReflection && $classReflection->hasMethod($callike->name->name)) {
                $methodReflection = $classReflection->getMethod($callike->name->name, $scope);
            }
        }

        return $methodReflection;
    }
}
