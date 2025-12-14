<?php

namespace GaiaAlpha\Database;

use PDO;
use GaiaAlpha\Debug;

class LoggedPDO extends PDO
{
    public function __construct($dsn, $username = null, $password = null, $options = [])
    {
        parent::__construct($dsn, $username, $password, $options);
        // Set statement class to our custom one
        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, [LoggedPDOStatement::class, [$this]]);
    }

    #[\ReturnTypeWillChange]
    public function query($statement, $fetchMode = null, ...$fetchModeArgs)
    {
        $start = microtime(true);
        if ($fetchMode !== null) {
            $result = parent::query($statement, $fetchMode, ...$fetchModeArgs);
        } else {
            $result = parent::query($statement);
        }
        $duration = microtime(true) - $start;
        Debug::logQuery($statement, [], $duration);
        return $result;
    }

    public function exec(string $statement): int|false
    {
        $start = microtime(true);
        $result = parent::exec($statement);
        $duration = microtime(true) - $start;
        Debug::logQuery($statement, [], $duration);
        return $result;
    }
}
