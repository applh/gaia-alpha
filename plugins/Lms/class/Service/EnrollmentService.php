<?php

namespace Lms\Service;

use GaiaAlpha\Model\DB;

class EnrollmentService
{

    public static function isEnrolled($userId, $courseId)
    {
        $enrollment = DB::fetch("SELECT * FROM lms_enrollments WHERE user_id = ? AND course_id = ? AND status = 'active'", [$userId, $courseId]);
        return !empty($enrollment);
    }

    public static function enroll($userId, $courseId)
    {
        if (self::isEnrolled($userId, $courseId)) {
            return; // Already enrolled
        }

        DB::query("INSERT INTO lms_enrollments (user_id, course_id, status) VALUES (?, ?, 'active')", [
            $userId,
            $courseId
        ]);

        // Log or trigger other events if needed
    }

    public static function revoke($userId, $courseId)
    {
        DB::query("UPDATE lms_enrollments SET status = 'expired' WHERE user_id = ? AND course_id = ?", [$userId, $courseId]);
    }
}
