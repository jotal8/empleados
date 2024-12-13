<?php

namespace App\Services;

use Firebase\JWT\JWT;


class JwtService
{
    /**
     * @var string
     */
    public static function create(array $data, int $duration): string
    {
        $time = time();
        $token = [
            'iat'  => $time,
            'exp'  => $time + $duration,
            'data' => $data
        ];

        return JWT::encode($token, 'el_secreto', 'HS256');
    }
}