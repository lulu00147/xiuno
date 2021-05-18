<?php

!defined('DEBUG') AND exit('Access Denied.');

$action = param(1);

is_numeric($action) AND $action = '';

// hook user_start.php

if(empty($action)) {

        // hook user_index_start.php

        $_uid = param(1, 0);
        empty($_uid) AND $_uid = $uid;
        $_user = user_read($_uid);

	empty($_user) AND message(-1, lang('user_not_exists'));

        $header['title'] = $_user['username'];
        $header['mobile_title'] = $_user['username'];

        // hook user_index_end.php

	include _include(APP_PATH.'view/htm/user.htm');

} elseif($action == 'thread') {

        // hook user_thread_start.php

        $_uid = param(2, 0);
        empty($_uid) AND $_uid = $uid;
        $_user = user_read($_uid);

        empty($_user) AND message(-1, lang('user_not_exists'));
        $header['title'] = $_user['username'];
        $header['mobile_title'] = $_user['username'];

        $page = param(3, 1);
        $pagesize = 20;
        $totalnum = $_user['threads'];
        $pagination = pagination(url("user-thread-$_uid-{page}"), $totalnum, $page, $pagesize);
        $threadlist = mythread_find_by_uid($_uid, $page, $pagesize);
        thread_list_access_filter($threadlist, $gid);

        // hook user_thread_end.php

	include _include(APP_PATH.'view/htm/user_thread.htm');

} elseif($action == 'login') {

	// hook user_login_get_post.php

	if($method == 'GET') {

		// hook user_login_get_start.php

		$referer = user_http_referer();

		$header['title'] = lang('user_login');

		// hook user_login_get_end.php

		include _include(APP_PATH.'view/htm/user_login.htm');

	} else if($method == 'POST') {

		// hook user_login_post_start.php

		$email = param('email');			// 邮箱或者手机号 / email or mobile
		$password = param('password');
		empty($email) AND message('email', lang('email_is_empty'));
		if(is_email($email, $err)) {
			$_user = user_read_by_email($email);
			empty($_user) AND message('email', lang('email_not_exists'));
		} else {
			$_user = user_read_by_username($email);
			empty($_user) AND message('email', lang('username_not_exists'));
		}

		!is_password($password, $err) AND message('password', $err);
		$check = (md5($password.$_user['salt']) == $_user['password']);
		// hook user_login_post_password_check_after.php
		!$check AND message('password', lang('password_incorrect'));

		// 更新登录时间和次数
		// update login times
		user_update($_user['uid'], array('login_ip'=>$longip, 'login_date' =>$time , 'logins+'=>1));

		// 全局变量 $uid 会在结束后，在函数 register_shutdown_function() 中存入 session (文件: model/session.func.php)
		// global variable $uid will save to session in register_shutdown_function() (file: model/session.func.php)
		$uid = $_user['uid'];

		$_SESSION['uid'] = $uid;

		user_token_set($_user['uid']);

		// hook user_login_post_end.php

		// 设置 token，下次自动登陆。

		message(0, lang('user_login_successfully'));

	}

} elseif($action == 'create') {

	// hook user_create_get_post.php

	empty($conf['user_create_on']) AND message(-1, lang('user_create_not_on'));

	if($method == 'GET') {

		// hook user_create_get_start.php

		$referer = user_http_referer();
		$header['title'] = lang('create_user');

		// hook user_create_get_end.php

		include _include(APP_PATH.'view/htm/user_create.htm');

	} else if($method == 'POST') {

		// hook user_create_post_start.php

		$email = param('email');
		$username = param('username');
		$password = param('password');
		$code = param('code');
		empty($email) AND message('email', lang('please_input_email'));
		empty($username) AND message('username', lang('please_input_username'));
		empty($password) AND message('password', lang('please_input_password'));

		!is_email($email, $err) AND message('email', $err);
		$_user = user_read_by_email($email);
		$_user AND message('email', lang('email_is_in_use'));

		!is_username($username, $err) AND message('username', $err);
		$_user = user_read_by_username($username);
		$_user AND message('username', lang('username_is_in_use'));

		!is_password($password, $err) AND message('password', $err);

		$salt = xn_rand(16);
		$pwd = md5($password.$salt);
		$gid = 101;
		$_user = array (
			'username' => $username,
			'email' => $email,
			'password' => $pwd,
			'salt' => $salt,
			'gid' => $gid,
			'create_ip' => $longip,
			'create_date' => $time,
			'logins' => 1,
			'login_date' => $time,
			'login_ip' => $longip,
		);
		$uid = user_create($_user);
		$uid === FALSE AND message('email', lang('user_create_failed'));
		$user = user_read($uid);

		// 更新 session

		unset($_SESSION['user_create_email']);
		unset($_SESSION['user_create_code']);
		$_SESSION['uid'] = $uid;
		user_token_set($uid);

		$extra = array('token'=>user_token_gen($uid));

		// hook user_create_post_end.php

		message(0, lang('user_create_sucessfully'), $extra);
	}

} elseif($action == 'logout') {

	// hook user_logout_start.php

	$uid = 0;
	$_SESSION['uid'] = $uid;
	user_token_clear();

	// hook user_logout_end.php

	message(0, jump(lang('logout_successfully'), http_referer(), 1));
	//message(0, jump('退出成功', './', 1));

// 简单的同步登陆实现：| sync login implement simply
/*
	将用户信息通过 token 传递给其他系统 | send user information to other system by token
	两边系统将 auth_key 设置为一致，用 xn_encrypt() xn_decrypt() 加密解密。all subsystem set auth_key to correct by xn_encrypt() xn_decrypt()
*/
} elseif($action == 'synlogin') {

	// 检查过来的 token | check token
	$token = param('token');
	$return_url = param('return_url');

	$s = xn_decrypt($token);
	!$s AND message(-1, lang('unauthorized_access'));
	list($_time, $_useragent) = explode("\t", $s);
	$useragent != $_useragent AND message(-1, lang('authorized_get_failed'));

	empty($_SESSION['return_url']) AND $_SESSION['return_url'] = $return_url;
	if(!$uid) {
		http_location(url('user-login'));
	} else {
		$return_url = _SESSION('return_url');

		empty($return_url) AND message(-1, lang('request_synlogin_again'));
		unset($_SESSION['return_url']);

		$arr = array(
			'uid'=>$user['uid'],
			'gid'=>$user['gid'],
			'username'=>$user['username'],
			'avatar_url'=>$user['avatar_url'],
			'email'=>$user['email'],
			'mobile'=>$user['mobile'],
		);
		$s = xn_json_encode($arr);
		$s = xn_encrypt($s);

		// 将 token 附加到 URL，跳转回去 | add token into URL, jump back
		$url = xn_urldecode($return_url).'?token='.$s;
		//$url = xn_url_add_arg($return_url, 'token', $s);
		http_location($url);
	}

} else {

}

// hook user_end.php


?>
