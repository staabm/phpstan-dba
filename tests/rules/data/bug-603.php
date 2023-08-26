<?php

namespace Bug603;

use PDO;

function preparedStatementWithBindOnExecute(PDO $pdo)
{
    $statement = $pdo->prepare('SELECT * FROM invalid_table WHERE id = ?');
    $statement->execute([123]);
    $statement->fetch();
}
