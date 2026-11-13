<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use staabm\PHPStanDba\MysqliReflection\MysqliResultObjectType;

/** @implements Rule<CallLike> */
final class MysqliFetchRangeRule implements Rule
{
    private ReflectionProvider $reflectionProvider;

    private bool $reportMaybes;

    public function __construct(
        ReflectionProvider $reflectionProvider,
        #[AutowiredParameter]
        bool $reportMaybes
    ) {
        $this->reflectionProvider = $reflectionProvider;
        $this->reportMaybes = $reportMaybes;
    }

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node instanceof FuncCall && ! $node instanceof MethodCall) {
            return [];
        }

        foreach ($node->getArgs() as $arg) {
            if ($arg->unpack) {
                return [];
            }
        }

        if ($node instanceof FuncCall) {
            if (! $node->name instanceof Node\Name
                || ! $this->reflectionProvider->hasFunction($node->name, $scope)
            ) {
                return [];
            }

            $reflection = $this->reflectionProvider->getFunction($node->name, $scope);
            if (strtolower($reflection->getName()) !== 'mysqli_fetch_column') {
                return [];
            }

            $acceptor = ParametersAcceptorSelector::selectFromArgs(
                $scope,
                $node->getArgs(),
                $reflection->getVariants(),
                $reflection->getNamedArgumentsVariants(),
            );
            $call = ArgumentsNormalizer::reorderFuncArguments($acceptor, $node);
            if ($call === null) {
                return [];
            }

            $args = $call->getArgs();
            if (! isset($args[0], $args[1])) {
                return [];
            }

            $resultType = $scope->getType($args[0]->value);
            if (! ($resultType instanceof MysqliResultObjectType)) {
                return [];
            }

            $columnArg = $args[1];
        } else { // MethodCall
            if (! $node->name instanceof Node\Identifier
                || strtolower($node->name->toString()) !== 'fetch_column'
            ) {
                return [];
            }

            $resultType = $scope->getType($node->var);
            if (! $resultType instanceof MysqliResultObjectType) {
                return [];
            }

            $reflection = $scope->getMethodReflection($resultType, $node->name->toString());
            if ($reflection === null) {
                return [];
            }

            $acceptor = ParametersAcceptorSelector::selectFromArgs(
                $scope,
                $node->getArgs(),
                $reflection->getVariants(),
                $reflection->getNamedArgumentsVariants(),
            );
            $call = ArgumentsNormalizer::reorderMethodArguments($acceptor, $node);
            if ($call === null) {
                return [];
            }

            $args = $call->getArgs();
            if (! isset($args[0])) {
                return [];
            }
            $columnArg = $args[0];
        }


        $rowType = $resultType->getRowType();
        $numericRowType = MysqliResultObjectType::getRowTypeForMode($rowType);
        if ($numericRowType === null) {
            return [];
        }

        $columnType = $scope->getType($columnArg->value);

        $hasOffset = $numericRowType->hasOffsetValueType($columnType);
        if (! $this->reportMaybes && $hasOffset->maybe()) {
            return [];
        }
        if (! $hasOffset->yes()) {
            return [
                RuleErrorBuilder::message(sprintf(
                    'Column offset %s %s out of range; valid offsets are %s.',
                    $columnType->describe(VerbosityLevel::value()),
                    $hasOffset->maybe() ? 'may be' : 'is',
                    $numericRowType->getIterableKeyType()->describe(VerbosityLevel::value()),
                ))
                    ->identifier('dba.columnOffset')
                    ->line($columnArg->value->getStartLine())
                    ->build(),
            ];
        }
        return [];
    }
}
