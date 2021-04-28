<?php
namespace App\Functions;

class Validation
{
    public function email($email) {
        if (!trim($email)) {
            return 'invalid-email';
        }
        return null;
    }

    public function password($password, $passwordConfirm) {
        if ($password !== $passwordConfirm) {
            return 'passwords-different';
        }
        return null;
    }

    function is_base64($str) {
        // Check if there are valid base64 characters
        if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $str)) return false;

        // Decode the string in strict mode and check the results
        $decoded = base64_decode($str, true);

        if (!preg_match('/[a-zA-Z0-9._-]+$/', $decoded)) return false;

        if(false === $decoded) return false;

        // Encode the string again
        if(base64_encode($decoded) != $str) return false;

        return true;
    }
}
