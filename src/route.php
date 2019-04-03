<?php

/*
 * This file is part of ibrand/auth-api.
 *
 * (c) iBrand <https://www.ibrand.cc>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

Route::post('oauth/sms', 'AuthController@smsLogin')->name('api.oauth.sms');

Route::get('oauth/getRedirectUrl', 'OfficialAccountAuthController@getRedirectUrl');
Route::post('oauth/quicklogin', 'OfficialAccountAuthController@quickLogin');

Route::get('oauth/getUser', 'OfficialAccountAuthController@getUser');

Route::post('oauth/MiniProgramLogin', 'MiniProgramLoginController@login')->name('api.oauth.miniprogram.login');
Route::post('oauth/MiniProgramMobileLogin', 'MiniProgramLoginController@mobileLogin')->name('api.oauth.miniprogram.mobile.login');
Route::post('oauth/miniprogram/openid', 'MiniProgramLoginController@getOpenIdByCode');
