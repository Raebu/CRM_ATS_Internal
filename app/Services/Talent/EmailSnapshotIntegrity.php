<?php
namespace App\Services\Talent;

use App\Models\Talent\EmailDelivery;

final class EmailSnapshotIntegrity
{
    public static function valid(EmailDelivery $delivery): bool
    {
        if (!$delivery->template_version || !$delivery->subject_snapshot || !$delivery->body_snapshot
            || !is_string($delivery->template_checksum) || strlen($delivery->template_checksum) !== 64) {
            return false;
        }
        $actual = hash('sha256', $delivery->subject_snapshot."\n".$delivery->body_snapshot);
        return hash_equals($delivery->template_checksum, $actual);
    }
}
