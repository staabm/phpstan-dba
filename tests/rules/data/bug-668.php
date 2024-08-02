<?php

namespace Bug668;

use PDO;

function triggerFetchingColumnInformation(PDO $pdo) {
    $pdo->query('SELECT email, adaid FROM ada', PDO::FETCH_ASSOC);
}
