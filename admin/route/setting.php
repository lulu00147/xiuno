<?php

!defined('DEBUG') AND exit('Access Denied.');

$action = param(1);

// hook admin_setting_start.php

if($action == 'base') {
	
	// hook admin_setting_base_get_post.php
	
	if($method == 'GET') {
		
		// hook admin_setting_base_get_start.php
		
		$input = array();
		$input['sitename'] = form_text('sitename', $conf['sitename']);
		$input['sitebrief'] = form_textarea('sitebrief', $conf['sitebrief'], '100%', 100);
		$input['runlevel'] = form_radio('runlevel', array(0=>lang('runlevel_0'), 1=>lang('runlevel_1'), 2=>lang('runlevel_2'), 3=>lang('runlevel_3'), 4=>lang('runlevel_4'), 5=>lang('runlevel_5')), $conf['runlevel']);
		$input['user_create_on'] = form_radio_yes_no('user_create_on', $conf['user_create_on']);
		$input['lang'] = form_select('lang', array('zh-cn'=>lang('lang_zh_cn'), 'en-us'=>lang('lang_en_us')), $conf['lang']);
		$input['url_rewrite_on'] = form_radio_yes_no('url_rewrite_on', $conf['url_rewrite_on']);
		$input['cdn_on'] = form_radio_yes_no('cdn_on', $conf['cdn_on']);
		$input['pagesize'] = form_text('pagesize', $conf['pagesize'], 100);
		$input['postlist_pagesize'] = form_text('postlist_pagesize', $conf['postlist_pagesize'], 100);
		
		$header['title'] = lang('admin_site_setting');
		$header['mobile_title'] =lang('admin_site_setting');
		
		// hook admin_setting_base_get_end.php
		
		include _include(ADMIN_PATH.'view/htm/setting_base.htm');
		
	} else {
		
		$sitebrief = param('sitebrief', '', FALSE);
		$sitename = param('sitename', '', FALSE);
		$runlevel = param('runlevel', 0);
		$user_create_on = param('user_create_on', 0);
		$url_rewrite_on = param('url_rewrite_on', 0);
		$cdn_on = param('cdn_on', 0);
		$pagesize = param('pagesize', 0);
		$postlist_pagesize = param('postlist_pagesize', 0);
		
		$_lang = param('lang');
		
		// hook admin_setting_base_post_start.php
		
		$replace = array();
		$replace['sitename'] = $sitename;
		$replace['sitebrief'] = $sitebrief;
		$replace['runlevel'] = $runlevel;
		$replace['user_create_on'] = $user_create_on;
		$replace['lang'] = $_lang;
		$replace['url_rewrite_on'] = $url_rewrite_on;
		$replace['cdn_on'] = $cdn_on;
		$replace['pagesize'] = $pagesize;
		$replace['postlist_pagesize'] = $postlist_pagesize;
			
		file_replace_var(APP_PATH.'conf/conf.php', $replace);
	
		// hook admin_setting_base_post_end.php
		
		message(0, lang('modify_successfully'));
	}

}

// hook admin_setting_end.php

?>