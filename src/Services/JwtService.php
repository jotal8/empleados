<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Services\GlobalContainer;
use exception;
use Throwable;
use stdClass;


class JwtService
{
    /**
     * @var string
     */
    private static string $encrypt = 'HS256';

    public static function create(array $data, int $duration): string
    {
        $time = time();
        $token = [
            'iat'  => $time,
            'exp'  => $time + $duration,
            'data' => $data
        ];

        return JWT::encode($token, GlobalContainer::getContainer()->getParameter('app_secret_key'), self::$encrypt);
    }

    public static function check(string $token): bool
    {
        return true;
    }

    public static function getUserId(string $token): int
    {
        $json = self::getDataToken($token);

        if(!$json){
            throw new Exception('El token no es valido!');
        }

        if(!$json->data->id){
            throw new Exception('El token es incorrecto!');
        }

        return $json->data->id;
    }

    /**
     * Obtiene la informacion del token
     *
     * @param string $token
     * @param bool   $exception
     * @return stdClass|null
     * @throws Throwable
     */
    public static function getDataToken(string $token, $exception = false): ?stdClass
    {
        if (empty($token)) {
            return null;
        }
        try {
            return JWT::decode($token, new Key(GlobalContainer::getContainer()->getParameter('app_secret_key'), self::$encrypt));
        } catch (Throwable $th) {
            if (!$exception) {
                return null;
            }
            throw $th;
        }
    }

}