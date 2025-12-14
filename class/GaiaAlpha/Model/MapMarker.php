<?php

namespace GaiaAlpha\Model;

class MapMarker
{


    public static function create($userId, $label, $lat, $lng)
    {
        $sql = "INSERT INTO map_markers (user_id, label, lat, lng) VALUES (:user_id, :label, :lat, :lng)";
        BaseModel::query($sql, [
            ':user_id' => $userId,
            ':label' => $label,
            ':lat' => $lat,
            ':lng' => $lng
        ]);
        return \GaiaAlpha\Controller\DbController::getPdo()->lastInsertId();
    }

    public static function findAllByUserId($userId)
    {
        $sql = "SELECT * FROM map_markers WHERE user_id = :user_id ORDER BY created_at DESC";
        return BaseModel::fetchAll($sql, [':user_id' => $userId]);
    }

    public static function updatePosition($id, $userId, $lat, $lng)
    {
        $sql = "UPDATE map_markers SET lat = :lat, lng = :lng WHERE id = :id AND user_id = :user_id";
        return BaseModel::execute($sql, [
            ':lat' => $lat,
            ':lng' => $lng,
            ':id' => $id,
            ':user_id' => $userId
        ]) > 0;
    }
}
