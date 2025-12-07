<?php

namespace GaiaAlpha\Model;

use GaiaAlpha\Database;
use PDO;

abstract class BaseModel
{
    protected PDO $db;

    public function __construct(Database $database)
    {
        $this->db = $database->getPdo();
    }
}
