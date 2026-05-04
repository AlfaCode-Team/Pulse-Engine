<?php
namespace AlfacodeTeam\PulseEngine\Security;

class Validator {
    public static function generateSignature(int $cid, int $votes, string $secret): string {
        return hash_hmac("sha256", $cid . "|" . $votes, $secret);
    }
}