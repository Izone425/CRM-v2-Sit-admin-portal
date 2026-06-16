<?php

namespace App\Services;

class PasswordGeneratorService
{
    public function generate(int $length = 12): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*';
        $password = '';
        $password .= $characters[rand(26, 51)];
        $password .= $characters[rand(0, 25)];
        $password .= $characters[rand(52, 61)];
        $password .= $characters[rand(62, strlen($characters) - 1)];

        for ($i = 4; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }

        return str_shuffle($password);
    }
}
