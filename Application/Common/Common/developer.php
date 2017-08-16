<?php
// +----------------------------------------------------------------------
// | CoreThink [ Simple Efficient Excellent ]
// +----------------------------------------------------------------------
// | Copyright (c) 2014 http://www.corethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: jry <598821125@qq.com> <http://www.corethink.cn>
// +----------------------------------------------------------------------

//开发者二次开发公共函数统一写入此文件，不要修改function.php以便于系统升级。
// function Config($key) {
// 	$map = array(
// 		'OABLOGIN' 	=> "https://oab.mercedes-benz.com.cn/oab/useritfserver/loginFrOtherSys",
// 		'OABSYNC'  	=> "https://oab.mercedes-benz.com.cn/oab/useritfserver/synUserAndUR",
// 		'WECHAT' 		=> 'gh_47ef703180df',
// 		'APPURL' 		=> 'http://53.99.68.61',
// 		'DOMAIN'	  => 'https://dos.mercedes-benz.com.cn/benz/',
// 		'AUTHURL' 	=> 'https://helm.mercedes-benz.com.cn/WechatService/oauth?sn=BEBA3423-683E-463A-A3A7-C54C76AF963D',
// 		'SENDURL' 	=> 'https://helm.mercedes-benz.com.cn/HelmPublicService/SendWechatMessage?accessToken=',
// 		'TOKEN' 		=> 'https://helm.mercedes-benz.com.cn/HelmPublicService/getToken?dataSource=DOS&accountId=dos1&securityKey=1qaz2wsx3edc$',
// 	);
// 	return $map[$key];
// }

function Config($key) {
	$map = array(
		'OABLOGIN' 	=> "https://oabint1.mercedes-benz.com.cn/oab/useritfserver/loginFrOtherSys",
		'OABSYNC'  	=> "https://oabint1.mercedes-benz.com.cn/oab/useritfserver/synUserAndUR",
		'WECHAT' 		=> 'gh_74b878be8bfc',
		'APPURL' 		=> 'ttp://53.99.68.62',
		'DOMAIN' 		=> 'https://dosint1.mercedes-benz.com.cn/benz/',
		'AUTHURL' 	=> 'https://helm-uat.mercedes-benz.com.cn/WechatService/oauth?sn=833B9613-0178-4BE3-BE53-2EE76328C580',
		'SENDURL'		=> 'https://helm-uat.mercedes-benz.com.cn/SendWechatMessage?accessToken=',
		'TOKEN'			=> 'https://helm-uat.mercedes-benz.com.cn/getToken?dataSource=DOS&accountId=dos1&securityKey=1qaz2wsx'
	);
	return $map[$key];
}
