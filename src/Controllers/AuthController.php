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

use iBrand\Component\User\Models\User;
use iBrand\Component\User\Repository\UserBindRepository;
use iBrand\Component\User\Repository\UserRepository;
use iBrand\Component\User\UserService;
use iBrand\Sms\Facade as Sms;

/**
 * Class AuthController
 * @package iBrand\Auth\Api\Controllers
 */
class AuthController extends Controller
{
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
     * AuthController constructor.
     * @param UserRepository $userRepository
     * @param UserBindRepository $userBindRepository
     * @param UserService $userService
     */
    public function __construct(UserRepository $userRepository, UserBindRepository $userBindRepository, UserService $userService)
    {
        $this->userRepository = $userRepository;
        $this->userBindRepository = $userBindRepository;
        $this->userService = $userService;
    }

    /**
     * @return \Illuminate\Http\Response|mixed
     *
     * @throws \Exception
     */
    public function smsLogin()
    {
        $mobile = request('mobile');
        $code = request('code');
        $environment = request('environment') ?? 'official_account';
        $app = request('app') ?? 'default';

        if (!Sms::checkCode($mobile, $code)) {
            return $this->failed('验证码错误');
        }

        $is_new = false;

        if (!$user = $this->userRepository->getUserByCredentials(['mobile' => $mobile])) {
            $data = ['mobile' => $mobile];
            $user = $this->userRepository->create($data);
            $is_new = true;
        }

        if (User::STATUS_FORBIDDEN == $user->status) {
            return $this->failed('您的账号已被禁用，联系网站管理员或客服！');
        }

        //1. create user token.
        $token = $user->createToken($mobile)->accessToken;

        if (!empty(request('open_id'))) {

            //bind user bind data to user.
            $userBind = $this->userBindRepository->getByOpenId(request('open_id'));

            $this->userService->bindPlatform($user->id, request('open_id'), get_wechat_config($app, $environment)['app_id'], $userBind->type);

            //if wechat bind user data.
            if ('wechat' == $userBind->type) {

                //get wechat data.
                $wechatUser = platform_application()->getUser(get_wechat_config($app, $environment)['app_id'], request('open_id'));

                //update user bind data.
                $this->userBindRepository->update(
                    ['nick_name' => $wechatUser['nickname']
                        , 'sex' => $wechatUser['sex']
                        , 'avatar' => $wechatUser['headimgurl']
                        , 'city' => $wechatUser['city']],
                    $userBind->id
                );

                //update user.
                $this->userRepository->update(
                    [
                        'nick_name' => $wechatUser['nickname']
                        , 'sex' => 1 == $wechatUser['sex'] ? '男' : '女'
                        , 'avatar' => $wechatUser['headimgurl']
                        , 'city' => $wechatUser['city'],
                    ],
                    $user->id);

                //除非更新了微信头像的事件，方便把图片保存到本地，避免导致用户更新头像后，导致头像不显示的问题
                event('user.update.wechat.avatar', [$user, $wechatUser['headimgurl']]);
            }
        }

        event('user.login', [$user,$is_new]);

        return $this->success(['token_type' => 'Bearer', 'access_token' => $token, 'is_new_user' => $is_new]);
    }
}
