<?php

namespace GaiaAlpha\Database;

use PDOStatement;
use GaiaAlpha\Debug;

class LoggedPDOStatement extends PDOStatement
{
    private $pdo;

    protected function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function execute(?array $params = null): bool
    {
        $start = microtime(true);
        $result = parent::execute($params);
        $duration = microtime(true) - $start;

        // Capture SQL info
        // queryString is a property of PDOStatement
        Debug::logQuery($this->queryString, $params ?? [], $duration);

        return $result;
    }
}
