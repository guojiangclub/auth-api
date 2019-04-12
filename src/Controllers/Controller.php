<?php

/*
 * This file is part of ibrand/auth-api.
 *
 * (c) iBrand <https://www.ibrand.cc>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace iBrand\Auth\Api\Controllers;

use iBrand\Component\User\Repository\UserBindRepository;
use iBrand\Component\User\Repository\UserRepository;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;

/**
 * Class Controller
 * @package iBrand\Auth\Api\Controllers
 */
abstract class Controller extends BaseController
{
    /**
     * @param array $data
     * @param int $code
     * @param bool $status
     *
     * @return Response
     */
    public function success($data = [], $code = Response::HTTP_OK, $status = true)
    {
        return new Response(['status' => $status, 'code' => $code, 'data' => empty($data) ? null : $data]);
    }

    /**
     * @param      $message
     * @param int $code
     * @param bool $status
     *
     * @return mixed
     */
    public function failed($message, $code = Response::HTTP_BAD_REQUEST, $status = false)
    {
        return new Response(['status' => $status, 'code' => $code, 'message' => $message]
        );
    }


    /**
     * get user model by unionid.
     * @param $unionid
     * @return |null
     */
    protected function getUserByUnionid($unionid)
    {
        $binds = app(UserBindRepository::class)->getByUnionId($unionid);

        $user = null;

        $binds->each(function ($item, $key) use ($user) {
            if (!$item->user_id) {
                $user = app(UserRepository::class)->find($item->user_id);
            }
        });

        return $user;

    }
}
