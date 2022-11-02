<?php

namespace NoErrorInQuery;

use PDO;

function rexstanBug165(PDO $pdo, array $files) {
    $where = '';
    $where .= implode(' OR ', $files);
    $query = 'SELECT email, adaid FROM ada WHERE ' . $where;

    // should not query error
    $pdo->query($query);
}
