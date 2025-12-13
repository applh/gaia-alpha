<?php
namespace GaiaAlpha\Model;

class Menu extends BaseModel
{
    protected static $table = 'menus';
    protected static $fillable = ['title', 'location', 'items'];

    public static function findByLocation(string $location)
    {
        $db = \GaiaAlpha\Controller\DbController::getPdo();
        $stmt = $db->prepare("SELECT * FROM menus WHERE location = ? LIMIT 1");
        $stmt->execute([$location]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}
