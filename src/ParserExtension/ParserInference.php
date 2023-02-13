<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\ParserExtension;

use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use SqlFtw\Parser\Parser;
use SqlFtw\Platform\Platform;
use SqlFtw\Session\Session;
use SqlFtw\Sql\Dml\Query\SelectCommand;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\FunctionCall;

final class ParserInference
{
    public function narrowResultType(string $queryString, ConstantArrayType $resultType): Type
    {
        $keyTypes = $resultType->getKeyTypes();

        $platform = Platform::get(Platform::MYSQL, '8.0'); // version defaults to x.x.99 when no patch number is given
        $session = new Session($platform);
        $parser = new Parser($session);

        // returns a Generator. will not parse anything if you don't iterate over it
        $commands = $parser->parse($queryString);
        foreach ($commands as list($command)) {
            // Parser does not throw exceptions. this allows to parse partially invalid code and not fail on first error
            if ($command instanceof SelectCommand) {
                $columns = $command->getColumns();
                foreach ($columns as $i => $column) {
                    $expression = $column->getExpression();
                    if ($expression instanceof FunctionCall && BuiltInFunction::COUNT == $expression->getFunction()->getName()) {
                        if (null !== $column->getAlias()) {
                            $offsetType = new ConstantStringType($column->getAlias());
                        } else {
                            $offsetType = $keyTypes[$i];
                        }

                        $valueType = TypeCombinator::intersect(
                            $resultType->getOffsetValueType($offsetType),
                            IntegerRangeType::fromInterval(0, null)
                        );

                        $resultType = $resultType->setOffsetValueType(
                            $offsetType,
                            $valueType
                        );
                    }
                }
            }
        }

        return $resultType;
    }
}
