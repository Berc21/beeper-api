<?php

namespace BeeperApi\Services;

use BeeperApi\Exceptions\ApiException;
use BeeperApi\Repositories\Users\UserRepository;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;

class AuthService
{
    private $key = "qd2UtNBu0fVyW4Z2tARCEiLV4je4lclu";
    private $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function createTokenForUser($user)
    {
        $token = [
            "iss" => "beeper",
            "iat" => time(), //time issued
            "exp" => time() * 60 * 240, //keep token valid for 4 hours,
            "user_id" => $user['id']
        ];

        return JWT::encode($token, $this->key);
    }

    public function getCurrentUser()
    {
        $expMessage = 'Looks like your session expired or you weren\'t expired, please log in again.';
        $errMessage = "There was an issue while attempting to authorize your request, please login again.";

        $token = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : null;

        if (!$token)
            throw new ApiException(422, [$expMessage]);

        $token = explode(" ", $token)[1];

        try {
            $decoded = JWT::decode($token, $this->key, ['HS256']);
        }
        catch (ExpiredException $e)
        {
            throw new ApiException(422, [$expMessage]);
        }
        catch (\Exception $e)
        {
            throw new ApiException(422, [$e->getMessage()]);
        }

        $user = $this->users->first([
            'id' => $decoded->user_id
        ]);

        if (!$user)
            throw new ApiException(422, [$errMessage]);

        return $user;
    }
}