<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Database;
use GaiaAlpha\Env;

class DbController extends BaseController
{
    public static function connect(): Database
    {
        $dataPath = Env::get('path_data');
        $dsn = defined('GAIA_DB_DSN') ? GAIA_DB_DSN : 'sqlite:' . (defined('GAIA_DB_PATH') ? GAIA_DB_PATH : $dataPath . '/database.sqlite');

        $db = new Database($dsn);
        $db->ensureSchema();

        return $db;
    }
}
