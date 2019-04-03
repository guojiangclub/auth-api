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
use iBrand\Component\User\UserService;

/**
 * Class OfficialAccountAuthController
 * @package iBrand\Auth\Api\Controllers
 */
class OfficialAccountAuthController extends Controller
{
    /**
     * @var \Illuminate\Config\Repository|mixed
     */
    protected $appid;

    /**
     * @var UserRepository
     */
    protected $userRepository;
    /**
     * @var UserBindRepository
     */
    protected $userBindRepository;
    /**
     * @var UserService
     */
    protected $userService;
    /**
     * @var
     */
    protected $miniProgramService;

    /**
     * OfficialAccountAuthController constructor.
     * @param UserRepository $userRepository
     * @param UserBindRepository $userBindRepository
     * @param UserService $userService
     */
    public function __construct(UserRepository $userRepository, UserBindRepository $userBindRepository, UserService $userService)
    {
        $this->userRepository = $userRepository;
        $this->userBindRepository = $userBindRepository;
        $this->userService = $userService;
        $this->appid = config('ibrand.wechat.official_account.default.app_id');
    }

    /**
     * get wechat oauth url.
     *
     * @return \Illuminate\Http\Response|mixed
     */
    public function getRedirectUrl()
    {
        if (empty(request('redirect_url'))) {
            return $this->failed('Missing redirect_url parameters.');
        }

        $url = platform_application()->getOauthUrl(request('redirect_url'), $this->appid);

        return $this->success(['url' => $url]);
    }

    /**
     * use openid quick to login.
     *
     * @return \Illuminate\Http\Response|mixed
     */
    public function quickLogin()
    {
        $openid = request('open_id');
        $type = request('open_type') ?? 'wechat';

        if (empty($openid)) {
            return $this->failed('Missing openid parameters.');
        }

        //1. openid 不存在相关用户和记录，直接返回 openid
        if (!$userBind = $this->userBindRepository->getByOpenId($openid)) {
            $userBind = $this->userBindRepository->create(['open_id' => $openid, 'type' => $type,
                'app_id' => $this->appid, ]);
        }

        //2. openid 不存在相关用户，直接返回 openid
        if (!$userBind->user_id) {
            return $this->success(['open_id' => $openid]);
        }

        //3. 绑定了用户,直接返回 token
        $user = $this->userRepository->find($userBind->user_id);

        $token = $user->createToken($user->id)->accessToken;

        return $this->success(['token_type' => 'Bearer', 'access_token' => $token]);
    }
}
