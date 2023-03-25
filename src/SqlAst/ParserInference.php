<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\SqlAst;

use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use SqlFtw\Parser\Parser;
use SqlFtw\Platform\Platform;
use SqlFtw\Session\Session;
use SqlFtw\Sql\Dml\Query\SelectCommand;
use SqlFtw\Sql\Dml\Query\SelectExpression;
use SqlFtw\Sql\Dml\TableReference\InnerJoin;
use SqlFtw\Sql\Dml\TableReference\Join;
use SqlFtw\Sql\Dml\TableReference\TableReferenceSubquery;
use SqlFtw\Sql\Dml\TableReference\TableReferenceTable;
use SqlFtw\Sql\Expression\Identifier;
use SqlFtw\Sql\SqlSerializable;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\SchemaReflection\Join as SchemaJoin;
use staabm\PHPStanDba\SchemaReflection\SchemaReflection;
use staabm\PHPStanDba\SchemaReflection\Table;
use staabm\PHPStanDba\UnresolvableAstInQueryException;

final class ParserInference
{
    /**
     * @var SchemaReflection
     */
    private $schemaReflection;

    public function __construct(SchemaReflection $schemaReflection)
    {
        $this->schemaReflection = $schemaReflection;
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
        $joins = [];
        foreach ($commands as [$command]) {
            // Parser does not throw exceptions. this allows to parse partially invalid code and not fail on first error
            if ($command instanceof SelectCommand) {
                if (null === $fromColumns) {
                    $fromColumns = $command->getColumns();
                }
                $from = $command->getFrom();

                if (null === $from) {
                    // no FROM clause, use an empty Table to signify this
                    $fromTable = new Table('', []);
                } elseif ($from instanceof TableReferenceTable) {
                    $fromName = $from->getTable()->getName();
                    $fromTable = $this->schemaReflection->getTable($fromName);
                } elseif ($from instanceof Join) {
                    while (1) {
                        if ($from->getCondition() === null) {
                            if (QueryReflection::getRuntimeConfiguration()->isDebugEnabled()) {
                                throw new UnresolvableAstInQueryException('Cannot narrow down types null join conditions: ' . $queryString);
                            }

                            return $resultType;
                        }

                        if ($from->getRight() instanceof TableReferenceSubquery || $from->getLeft() instanceof TableReferenceSubquery) {
                            if (QueryReflection::getRuntimeConfiguration()->isDebugEnabled()) {
                                throw new UnresolvableAstInQueryException('Cannot narrow down types for SQLs with subqueries: ' . $queryString);
                            }

                            return $resultType;
                        }

                        if ($from instanceof InnerJoin && $from->isCrossJoin()) {
                            if (QueryReflection::getRuntimeConfiguration()->isDebugEnabled()) {
                                throw new UnresolvableAstInQueryException('Cannot narrow down types for cross joins: ' . $queryString);
                            }

                            return $resultType;
                        }

                        $joinType = SchemaJoin::TYPE_OUTER;

                        if ($from instanceof InnerJoin) {
                            $joinType = SchemaJoin::TYPE_INNER;
                        }

                        $joinedTable = $this->schemaReflection->getTable($from->getRight()->getTable()->getName());

                        if ($joinedTable !== null) {
                            $joins[] = new SchemaJoin(
                                $joinType,
                                $joinedTable,
                                $from->getCondition()
                            );
                        }

                        if ($from->getLeft() instanceof TableReferenceTable) {
                            $from = $from->getLeft();

                            $fromName = $from->getTable()->getName();
                            $fromTable = $this->schemaReflection->getTable($fromName);

                            break;
                        }

                        $from = $from->getLeft();
                    }
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

        $queryScope = new QueryScope($fromTable, $joins);

        foreach ($fromColumns as $i => $column) {
            $expression = $column->getExpression();

            $offsetType = new ConstantIntegerType($i);

            $nameType = null;
            $exprName = self::getIdentifierName($column);
            if ($exprName !== null) {
                $nameType = new ConstantStringType($exprName);
            }
            $rawExpressionType = null;
            if ($column->getRawExpression() !== null) {
                $rawExpressionType = new ConstantStringType($column->getRawExpression());
            }
            $aliasOffsetType = null;
            if (null !== $column->getAlias()) {
                $aliasOffsetType = new ConstantStringType($column->getAlias());
            }

            $valueType = $resultType->getOffsetValueType($offsetType);

            $type = $queryScope->getType($expression);
            if (! $type instanceof MixedType) {
                $valueType = $type;
            }

            if (null !== $rawExpressionType && $resultType->hasOffsetValueType($rawExpressionType)->yes()) {
                $resultType = $resultType->setOffsetValueType(
                    $rawExpressionType,
                    $valueType
                );
            }
            if (null !== $aliasOffsetType && $resultType->hasOffsetValueType($aliasOffsetType)->yes()) {
                $resultType = $resultType->setOffsetValueType(
                    $aliasOffsetType,
                    $valueType
                );
            }
            if (null !== $nameType && $resultType->hasOffsetValueType($nameType)->yes()) {
                $resultType = $resultType->setOffsetValueType(
                    $nameType,
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

    /**
     * @return null|string
     */
    public static function getIdentifierName(SqlSerializable $expression)
    {
        if ($expression instanceof SelectExpression) {
            $expression = $expression->getExpression();
        }

        if ($expression instanceof Identifier) {
            return $expression->getName();
        }

        return null;
    }
}
