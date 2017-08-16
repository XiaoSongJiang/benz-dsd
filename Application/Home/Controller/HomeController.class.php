<?php
// +----------------------------------------------------------------------
// | CoreThink [ Simple Efficient Excellent ]
// +----------------------------------------------------------------------
// | Copyright (c) 2014 http://www.corethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: jry <598821125@qq.com> <http://www.corethink.cn>
// +----------------------------------------------------------------------
namespace Home\Controller;
use Common\Controller\CommonController;
/**
 * 前台公共控制器
 * 为防止多分组Controller名称冲突，公共Controller名称统一使用分组名称
 * @author jry <598821125@qq.com>
 */
class HomeController extends CommonController{
    /**
     * 初始化方法
     * @author jry <598821125@qq.com>
     */
    protected function _initialize(){

        //系统开关
        // if(!C('TOGGLE_WEB_SITE')){
        //     $this->error('站点已经关闭，请稍后访问~');
        // }
        $this->assign('__CONTROLLER_NAME__', strtolower(CONTROLLER_NAME)); //当前控制器名称
        $this->assign('__ACTION_NAME__', strtolower(ACTION_NAME)); //当前方法名称
    }

    /**
     * 用户登录检测
     * @author jry <598821125@qq.com>
     */
    protected function is_login(){
        
    }

    /**
     * 模板显示 调用内置的模板引擎显示方法
     * @access protected
     * @param string $templateFile 指定要调用的模板文件
     * 默认为空 由系统自动定位模板文件
     * @param string $charset 输出编码
     * @param string $contentType 输出类型
     * @param string $content 输出内容
     * @param string $prefix 模板缓存前缀
     * @return void
     * @author jry <598821125@qq.com>
     */
    protected function display($templateFile='', $charset='utf-8', $contentType='', $content='', $prefix='') {

        $controller_name = explode('/', CONTROLLER_NAME); //获取ThinkPHP控制器分级时控制器名称
        if($controller_name[0] === 'Home'){
            
            $templateFile = $controller_name[1].'/'.ACTION_NAME;
        }
        $this->view->display($templateFile, $charset, $contentType, $content, $prefix);
    }

}
