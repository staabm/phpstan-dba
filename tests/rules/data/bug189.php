<?php

namespace Bug189;

use Doctrine\DBAL\Connection;

class Foo {
    /**
     * @param string[] $packageNames
     */
    public function searchSecurityAdvisories(Connection $conn, array $packageNames, int $updatedSince): array
    {
        $sql = 'SELECT s.packagistAdvisoryId as advisoryId, s.packageName, s.remoteId, s.title, s.link, s.cve, s.affectedVersions, s.source, s.reportedAt, s.composerRepository
            FROM security_advisory s
            WHERE s.updatedAt >= :updatedSince ' .
            (count($packageNames) > 0 ? ' AND s.packageName IN (:packageNames)' : '')
            .' ORDER BY s.id DESC';

        return $conn
            ->fetchAllAssociative(
                $sql,
                [
                    'packageNames' => $packageNames,
                    'updatedSince' => date('Y-m-d H:i:s', $updatedSince),
                ],
                ['packageNames' => Connection::PARAM_STR_ARRAY]
            );
    }
}
