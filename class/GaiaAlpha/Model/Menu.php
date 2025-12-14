<?php
namespace GaiaAlpha\Model;

class Menu extends BaseModel
{
    protected static $table = 'menus';
    protected static $fillable = ['title', 'location', 'items'];

    public static function findByLocation(string $location)
    {
        return BaseModel::fetch("SELECT * FROM menus WHERE location = ? LIMIT 1", [$location]);
    }
}
