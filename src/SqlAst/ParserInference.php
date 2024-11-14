<?php declare(strict_types=1);

namespace staabm\PHPStanDba\SqlAst;

use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Type;
use SqlFtw\Parser\Parser;
use SqlFtw\Parser\ParserConfig;
use SqlFtw\Platform\Platform;
use SqlFtw\Session\Session;
use SqlFtw\Sql\Dml\Query\SelectCommand;
use SqlFtw\Sql\Dml\Query\SelectExpression;
use SqlFtw\Sql\Dml\TableReference\Join;
use SqlFtw\Sql\Dml\TableReference\TableReferenceTable;
use SqlFtw\Sql\Expression\Identifier;
use SqlFtw\Sql\SqlSerializable;
use staabm\PHPStanDba\SchemaReflection\Table;

final class ParserInference
{
    public function narrowResultType(string $queryString, ConstantArrayType $resultType): Type
    {
        $platform = Platform::get(Platform::MYSQL, '8.0'); // version defaults to x.x.99 when no patch number is given
        $config = new ParserConfig($platform);
        $session = new Session($platform);
        $parser = new Parser($config, $session);

        //        $queryString = 'SELECT a.email, b.adaid FROM ada a LEFT JOIN ada b ON a.adaid=b.adaid';

        // returns a Generator. will not parse anything if you don't iterate over it
        $commands = $parser->parse($queryString);

        $selectColumns = null;
        $fromTable = null;
        foreach ($commands as $command) {
            // Parser does not throw exceptions. this allows to parse partially invalid code and not fail on first error
            if ($command instanceof SelectCommand) {
                if (null === $selectColumns) {
                    $selectColumns = $command->getColumns();
                }
                $from = $command->getFrom();

                if (null === $from) {
                    // no FROM clause, use an empty Table to signify this
                    $fromTable = new Table('', []);
                } elseif ($from instanceof TableReferenceTable) {
                    $fromName = $from->getTable()->getName();
                } elseif ($from instanceof Join) {
                    while (1) {
                        if ($from->getCondition() === null) {
                            return $resultType;
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
        throw new ShouldNotHappenException();
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
