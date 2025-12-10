<?php

namespace GaiaAlpha\Model;

class MapMarker
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create($userId, $label, $lat, $lng)
    {
        $pdo = $this->db->getPdo();
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

    public function findAllByUserId($userId)
    {
        $pdo = $this->db->getPdo();
        $sql = "SELECT * FROM map_markers WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }
}
