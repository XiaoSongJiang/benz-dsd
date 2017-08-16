<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.workingchat.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: gaoyu <540846283@qq.com> 
// +----------------------------------------------------------------------
namespace Weixin\Controller;
use Common\Controller\CommonController;

/**
 * 微信默认控制器
 * @author gaoyu <540846283@qq.com>
 */
class IndexController extends CommonController{

    //初始化
    public function __construct() {
      parent::__construct();
    }

    public function index() {
    	$this->display();
    }
}
