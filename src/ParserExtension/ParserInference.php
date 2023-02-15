<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\ParserExtension;

use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use SqlFtw\Parser\Parser;
use SqlFtw\Platform\Platform;
use SqlFtw\Session\Session;
use SqlFtw\Sql\Dml\Query\SelectCommand;
use SqlFtw\Sql\Dml\TableReference\Join;
use SqlFtw\Sql\Dml\TableReference\TableReferenceTable;
use staabm\PHPStanDba\SchemaReflection\SchemaReflection;

final class ParserInference
{
    /**
     * @var list<QueryExpressionReturnTypeExtension<mixed>>
     */
    private $extensions;

    /**
     * @var SchemaReflection
     */
    private $schemaReflection;

    public function __construct(SchemaReflection $schemaReflection)
    {
        $this->schemaReflection = $schemaReflection;

        $this->extensions = [
            new CountReturnTypeExtension(),
            new CoalesceReturnTypeExtension(),
            new IfNullReturnTypeExtension(),
        ];
    }

    public function narrowResultType(string $queryString, ConstantArrayType $resultType): Type
    {
        $platform = Platform::get(Platform::MYSQL, '8.0'); // version defaults to x.x.99 when no patch number is given
        $session = new Session($platform);
        $parser = new Parser($session);

//        $queryString = 'SELECT a.email, b.adaid FROM ada a LEFT JOIN ada b ON a.adaid=b.adaid';

        // returns a Generator. will not parse anything if you don't iterate over it
        $commands = $parser->parse($queryString);

        $fromColumns = null;
        $fromTable = null;
        $joinedTables = [];
        foreach ($commands as [$command]) {
            // Parser does not throw exceptions. this allows to parse partially invalid code and not fail on first error
            if ($command instanceof SelectCommand) {
                if (null === $fromColumns) {
                    $fromColumns = $command->getColumns();
                }
                $from = $command->getFrom();

                if ($from instanceof TableReferenceTable) {
                    $fromName = $from->getTable()->getName();
                    $fromTable = $this->schemaReflection->getTable($fromName);
                } elseif ($from instanceof Join) {
                    $joinName = $from->getRight()->getTable()->getName();
                    $joinedTables[] = $this->schemaReflection->getTable($joinName);
                }
            }
        }

        if (null === $fromTable) {
            // not parsable atm, return un-narrowed type
            return $resultType;
        }
        if (null === $fromColumns) {
            throw new ShouldNotHappenException();
        }

        $queryScope = new QueryScope($fromTable, $joinedTables);

        foreach ($fromColumns as $i => $column) {
            $expression = $column->getExpression();

            $offsetType = new ConstantIntegerType($i);
            $aliasOffsetType = null;
            if (null !== $column->getAlias()) {
                $aliasOffsetType = new ConstantStringType($column->getAlias());
            }

            $valueType = $resultType->getOffsetValueType($offsetType);
            foreach ($this->extensions as $extension) {
                if (!$extension->isExpressionSupported($expression)) {
                    continue;
                }

                $extensionType = $extension->getTypeFromExpression($expression, $queryScope);
                if (null !== $extensionType) {
                    $valueType = $extensionType;
                    break;
                }
            }

            if (null !== $aliasOffsetType && $resultType->hasOffsetValueType($aliasOffsetType)->yes()) {
                $resultType = $resultType->setOffsetValueType(
                    $aliasOffsetType,
                    $valueType
                );
            }
            if ($resultType->hasOffsetValueType($offsetType)->yes()) {
                $resultType = $resultType->setOffsetValueType(
                    $offsetType,
                    $valueType
                );
            }
        }

        return $resultType;
    }
}
