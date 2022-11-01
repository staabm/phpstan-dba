<?php

namespace SyntaxErrorInPreparedStatementMethodRuleTest;

use staabm\PHPStanDba\Tests\Fixture\Connection;
use staabm\PHPStanDba\Tests\Fixture\PreparedStatement;

class Foo
{
    public function syntaxError(Connection $connection)
    {
        $connection->preparedQuery('SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada', []);
    }

    public function syntaxErrorInConstruct()
    {
        $stmt = new PreparedStatement('SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada', []);
    }

    public function syntaxErrorOnKnownParamType(Connection $connection, int $i, bool $bool)
    {
        $connection->preparedQuery('
            SELECT email adaid
            WHERE gesperrt = ? AND email LIKE ?
            FROM ada
            LIMIT        1
        ', [$i, '%@example.com']);

        $connection->preparedQuery('
            SELECT email adaid
            WHERE gesperrt = ? AND email LIKE ?
            FROM ada
            LIMIT        1
        ', [$bool, '%@example.com']);
    }

    public function noErrorOnMixedParams(Connection $connection, $unknownType)
    {
        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = ? AND email LIKE ?
            LIMIT        1
        ', [1, $unknownType]);
    }

    public function noErrorOnPlaceholderInLimit(Connection $connection, int $limit)
    {
        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = ?
            LIMIT        ?
        ', [1, $limit]);

        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = :gesperrt
            LIMIT        :limit
        ', [':gesperrt' => 1, ':limit' => $limit]);
    }

    public function noErrorOnPlaceholderInOffsetAndLimit(Connection $connection, int $offset, int $limit)
    {
        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = ?
            LIMIT        ?,  ?
        ', [1, $offset, $limit]);

        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = :gesperrt
            LIMIT   :offset,     :limit
        ', [':gesperrt' => 1, ':offset' => $offset, ':limit' => $limit]);
    }

    public function preparedParams(Connection $connection)
    {
        $connection->preparedQuery('SELECT email, adaid FROM ada WHERE gesperrt = ?', [1]);

        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = ? AND email LIKE ?
            LIMIT        1
        ', [1, '%@example%']);
    }

    public function preparedNamedParams(Connection $connection)
    {
        $connection->preparedQuery('SELECT email, adaid FROM ada WHERE gesperrt = :gesperrt', ['gesperrt' => 1]);
    }

    public function camelCase(Connection $connection)
    {
        $connection->preparedQuery('SELECT email, adaid FROM ada WHERE gesperrt = :myGesperrt', ['myGesperrt' => 1]);
    }

    public function syntaxErrorInDoctrineDbal(\Doctrine\DBAL\Connection $conn, $types, \Doctrine\DBAL\Cache\QueryCacheProfile $qcp)
    {
        $conn->executeQuery('SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada', []);
        $conn->executeCacheQuery('SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada', [], $types, $qcp);
        $conn->executeStatement('SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada', []);
    }

    public function conditionalSyntaxError(Connection $connection)
    {
        $query = 'SELECT email, adaid, gesperrt, freigabe1u1 FROM ada';

        if (rand(0, 1)) {
            // valid condition
            $query .= ' WHERE gesperrt=?';
        } else {
            // unknown column
            $query .= ' WHERE asdsa=?';
        }

        $connection->preparedQuery($query, [1]);
    }

    public function placeholderValidation(Connection $connection)
    {
        $query = 'SELECT email, adaid, gesperrt, freigabe1u1 FROM ada';

        if (rand(0, 1)) {
            // valid condition
            $query .= ' WHERE gesperrt=?';
        } else {
            // unknown column
            $query .= ' WHERE asdsa=?';
        }

        $connection->preparedQuery($query, [':gesperrt' => 1]);
    }

    public function samePlaceholderMultipleTimes(Connection $connection)
    {
        $query = 'SELECT email, adaid, gesperrt, freigabe1u1 FROM ada
            WHERE (gesperrt=:gesperrt AND freigabe1u1=1) OR (gesperrt=:gesperrt AND freigabe1u1=0)';
        $connection->preparedQuery($query, [':gesperrt' => 1]);
    }

    public function noErrorOnTraillingSemicolon(Connection $connection)
    {
        $query = 'SELECT email, adaid, gesperrt, freigabe1u1 FROM ada;';
        $connection->preparedQuery($query, []);
    }

    public function noErrorOnPlaceholderInData(Connection $connection)
    {
        $query = "SELECT adaid FROM ada WHERE email LIKE 'hello?%'";
        $connection->preparedQuery($query, []);

        $query = "SELECT adaid FROM ada WHERE email LIKE '%questions ?%'";
        $connection->preparedQuery($query, []);

        $query = 'SELECT adaid FROM ada WHERE email LIKE ":gesperrt%"';
        $connection->preparedQuery($query, []);

        $query = "SELECT adaid FROM ada WHERE email LIKE ':gesperrt%'";
        $connection->preparedQuery($query, []);

        $query = "SELECT adaid FROM ada WHERE email LIKE 'some strange string - :gesperrt it is'";
        $connection->preparedQuery($query, []);
    }

    public function arrayParam(Connection $connection)
    {
        $query = 'SELECT email FROM ada WHERE adaid IN (:adaids)';
        $connection->preparedQuery($query, ['adaids' => [1, 2, 3]]);
    }

    public function noErrorInBug156(Connection $connection, array $idsToUpdate, string $time)
    {
        $query = 'UPDATE package SET indexedAt=:indexed WHERE id IN (:ids) AND (indexedAt IS NULL OR indexedAt <= crawledAt)';
        $connection->preparedQuery($query, [
            'ids' => $idsToUpdate,
            'indexed' => $time,
        ]);
    }

    public function noErrorInBug94(Connection $connection)
    {
        // XXX with proper sql parsing, we should better detect the placeholders and therefore could validate this query
        $sql = "
            INSERT IGNORE INTO `s_articles_supplier` (`id`, `name`, `img`, `link`, `changed`) VALUES (:supplierId, 'TestSupplier', '', '', '2019-12-09 10:42:10');

            INSERT INTO `s_articles` (`id`, `supplierID`, `name`, `datum`, `taxID`, `changetime`, `pricegroupID`, `pricegroupActive`, `filtergroupID`, `laststock`, `crossbundlelook`, `notification`, `template`, `mode`) VALUES
            (:productId, :supplierId, 'SwagTest', '2020-03-20', '1', '2020-03-20 10:42:10', NULL, '0', NULL, '0', '0', '0', '', '0');

            INSERT IGNORE INTO `s_order` (`id`, `ordernumber`, `userID`, `invoice_amount`, `invoice_amount_net`, `invoice_shipping`, `invoice_shipping_net`, `ordertime`, `status`, `cleared`, `paymentID`, `transactionID`, `comment`, `customercomment`, `internalcomment`, `net`, `taxfree`, `partnerID`, `temporaryID`, `referer`, `cleareddate`, `trackingcode`, `language`, `dispatchID`, `currency`, `currencyFactor`, `subshopID`, `remote_addr`) VALUES
            (:orderId, '29996', 1, 126.82, 106.57, 3.9, 3.28, '2013-07-10 08:17:20', 0, 17, 5, '', '', '', '', 0, 0, '', '', '', NULL, '', '1', 9, 'EUR', 1, 1, '172.16.10.71');

            INSERT IGNORE INTO `s_order_details` (`id`, `orderID`, `ordernumber`, `articleID`, `articleordernumber`, `price`, `quantity`, `name`, `status`, `shipped`, `shippedgroup`, `releasedate`, `modus`, `esdarticle`, `taxID`, `tax_rate`, `config`) VALUES
            (15315352, :orderId, '20003', :productId, 'SW10178', 19.95, 1, 'Strandtuch Ibiza', 0, 0, 0, '0000-00-00', 0, 0, 1, 19, ''),
            (15315353, :orderId, '20003', 177, 'SW10177', 34.99, 1, 'Strandtuch Stripes für Kinder', 0, 0, 0, '0000-00-00', 0, 0, 1, 19, ''),
            (15315354, :orderId, '20003', 173, 'SW10173', 39.99, 1, 'Strandkleid Flower Power', 0, 0, 0, '0000-00-00', 0, 0, 1, 19, ''),
            (15315355, :orderId, '20003', 160, 'SW10160.1', 29.99, 1, 'Sommer Sandale Ocean Blue 36', 0, 0, 0, '0000-00-00', 0, 0, 1, 19, ''),
            (15315356, :orderId, '20003', 0, 'SHIPPINGDISCOUNT', -2, 1, 'Warenkorbrabatt', 0, 0, 0, '0000-00-00', 4, 0, 0, 19, '');
        ";

        $supplierId = '81729';
        $productId = 91829002;
        $orderId = 15315351;
        $connection->preparedQuery($sql, ['orderId' => $orderId, 'productId' => $productId, 'supplierId' => $supplierId]);
    }

    public function noErrorOnBug175(Connection $connection, int $limit, int $offset)
    {
        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = ?
            LIMIT        ?
            OFFSET '.$offset.'
        ', [1, $limit]);

        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = ?
            LIMIT        ?
            OFFSET '.((int) $offset).'
        ', [1, $limit]);
    }

    public function noErrorOnOffsetAfterLimit(Connection $connection, int $limit, int $offset)
    {
        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = ?
            LIMIT        ?
            OFFSET ?
        ', [1, $limit, $offset]);

        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = :gesperrt
            LIMIT        :limit
            OFFSET :offset
        ', [':gesperrt' => 1, ':limit' => $limit, ':offset' => $offset]);
    }

    public function noErrorOnLockedRead(Connection $connection, int $limit, int $offset)
    {
        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = ?
            FOR UPDATE
        ', [1]);

        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = ?
            LIMIT        ?
            FOR UPDATE
        ', [1, $limit]);

        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = ?
            LIMIT        ?
            OFFSET ?
            FOR UPDATE
        ', [1, $limit, $offset]);

        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = :gesperrt
            LIMIT        :limit
            OFFSET :offset
            FOR SHARE
        ', [':gesperrt' => 1, ':limit' => $limit, ':offset' => $offset]);
    }

    public function noErrorInBug174(Connection $connection, string $name, ?int $gesperrt = null, ?int $adaid = null)
    {
        $sql = 'SELECT adaid FROM ada WHERE email = :name';
        $args = ['name' => $name];
        if (null !== $gesperrt) {
            $sql .= ' AND gesperrt = :gesperrt';
            $args['gesperrt'] = $gesperrt;
        }

        $connection->preparedQuery($sql, $args);
    }

    public function conditionalNumberOfPlaceholders(Connection $connection, string $name, ?int $gesperrt = null, ?int $adaid = null)
    {
        $sql = 'SELECT adaid FROM ada WHERE email = :name';
        $args = [];
        if (null !== $gesperrt) {
            $sql .= ' AND gesperrt = :gesperrt';
            $args['gesperrt'] = $gesperrt;
        }

        $connection->preparedQuery($sql, $args);
    }

    public function noErrorOnTraillingSemicolonAndWhitespace(Connection $connection)
    {
        $query = 'SELECT email, adaid, gesperrt, freigabe1u1 FROM ada;  ';
        $connection->preparedQuery($query, []);
    }

    public function errorOnQueryWithoutArgs(\Doctrine\DBAL\Connection $connection)
    {
        $query = 'SELECT email adaid gesperrt freigabe1u1 FROM ada';
        $connection->executeQuery($query);
    }

    public function preparedNamedParamsSubstitution(Connection $connection)
    {
        $connection->preparedQuery('SELECT email FROM ada WHERE email = :param OR email = :parameter', ['param' => 'abc', 'parameter' => 'def']);
    }

    public function unknownConstant(Connection $connection)
    {
        $connection->preparedQuery('SELECT email FROM ada WHERE email = :param OR email = :parameter', ['param' => CONSTANT_DOES_NOT_EXIST, 'parameter' => 'def']);
    }
}
