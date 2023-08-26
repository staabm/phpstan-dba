<?php

namespace Bug603;

use PDO;

function taintEscapedAndInferencePlaceholder(PDO $pdo, string $s, int $start, int $max)
{
    $statement = $pdo->prepare('SELECT * FROM tasks_invalid WHERE id = ?');
    $statement->execute([123]);
    $statement->fetch();
}

class X {
}
