<?php

namespace GaiaAlpha\Database;

use PDO;
use GaiaAlpha\Debug;

/**
 * LoggedPDO extends the native PDO class to provide query logging.
 *
 * DESIGN CHOICE: Inheritance vs Composition
 * We extend PDO directly to maintain full type-compatibility with the existing codebase
 * which heavily relies on `PDO` type hints. While composition would offer better
 * separation of concerns, it would break type contracts (`Database::getPdo(): PDO`)
 * and require significant refactoring. Detailed rationale is documented in 
 * `docs/architect/architecture.md`.
 */
class LoggedPDO extends PDO
{
    public function __construct($dsn, $username = null, $password = null, $options = [])
    {
        parent::__construct($dsn, $username, $password, $options);
        // Set statement class to our custom one
        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, [LoggedPDOStatement::class, [$this]]);
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): \PDOStatement|false
    {
        $start = microtime(true);
        $result = parent::query(...func_get_args());
        $duration = microtime(true) - $start;
        Debug::logQuery($query, [], $duration);
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
