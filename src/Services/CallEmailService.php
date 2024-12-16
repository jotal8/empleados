<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;


class CallEmailService{

    /**
     * Llama el servicio que se encuentra con Python / Flask para envio de email
     * con su respectiva autorizacion mediante token valido
     * 
     * @param string $name,
     * @param string $password,
     * @param string $token
     */
    public static function request($name, $password, $correo, $token): string
    {
        $Client = new Client();

        try {
            $headers = [
                'Authorization' => $token
            ];

            $params = [
                'name' => $name,
                'password' => $password,
                'correo'  => $correo
            ];

            $clientRequest = $Client->request('POST', 'email-service:5000/sendEmail', [
                'verify'      => false,
                'headers'     => $headers,
                'json'        => json_encode($params)
            ]);

            $response = $clientRequest->getBody();
            $responseData = json_decode($response);
        }catch(throwable $th){
            return false;
        }

        return $responseData->message;
    }
}