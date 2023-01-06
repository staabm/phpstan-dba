<?php

namespace Bug94;

use staabm\PHPStanDba\Tests\Fixture\Connection;

function noErrorInBug94(Connection $connection)
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
