<?php

namespace GaiaAlpha\Model;

class MapMarker
{


    public static function create($userId, $label, $lat, $lng)
    {
        $pdo = \GaiaAlpha\Controller\DbController::getPdo();
        $sql = "INSERT INTO map_markers (user_id, label, lat, lng) VALUES (:user_id, :label, :lat, :lng)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':label' => $label,
            ':lat' => $lat,
            ':lng' => $lng
        ]);
        return $pdo->lastInsertId();
    }

    public static function findAllByUserId($userId)
    {
        $pdo = \GaiaAlpha\Controller\DbController::getPdo();
        $sql = "SELECT * FROM map_markers WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function updatePosition($id, $userId, $lat, $lng)
    {
        $pdo = \GaiaAlpha\Controller\DbController::getPdo();
        $sql = "UPDATE map_markers SET lat = :lat, lng = :lng WHERE id = :id AND user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':lat' => $lat,
            ':lng' => $lng,
            ':id' => $id,
            ':user_id' => $userId
        ]);
    }
}
