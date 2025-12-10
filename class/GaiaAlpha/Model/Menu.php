<?php
namespace GaiaAlpha\Model;

class Menu extends BaseModel
{
    protected static $table = 'menus';
    protected static $fillable = ['title', 'location', 'items'];
}
