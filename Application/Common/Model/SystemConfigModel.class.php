<?php
// +----------------------------------------------------------------------
// | CoreThink [ Simple Efficient Excellent ]
// +----------------------------------------------------------------------
// | Copyright (c) 2014 http://www.corethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: jry <598821125@qq.com> <http://www.corethink.cn>
// +----------------------------------------------------------------------
namespace Common\Model;
use Think\Model;
/**
 * 配置模型
 * @author jry <598821125@qq.com>
 */
class SystemConfigModel extends Model{
    /**
     * 自动验证规则
     * @author jry <598821125@qq.com>
     */
    protected $_validate = array(
        array('group', 'require', '配置分组不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('type', 'require', '配置类型不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('name', 'require', '配置名称不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('name', '1,32', '配置名称长度为1-32个字符', self::EXISTS_VALIDATE, 'length', self::MODEL_BOTH),
        array('name', '', '配置名称已经存在', self::VALUE_VALIDATE, 'unique', self::MODEL_BOTH),
        array('title','require','配置标题必须填写', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('title', '1,32', '配置标题长度为1-32个字符', self::EXISTS_VALIDATE, 'length', self::MODEL_BOTH),
        array('title', '', '配置标题已经存在', self::VALUE_VALIDATE, 'unique', self::MODEL_BOTH),
    );

    /**
     * 自动完成规则
     * @author jry <598821125@qq.com>
     */
    protected $_auto = array(
        array('ctime', NOW_TIME, self::MODEL_INSERT),
        array('utime', NOW_TIME, self::MODEL_BOTH),
        array('status', '1', self::MODEL_BOTH),
    );

    /**
     * 获取配置列表与ThinkPHP配置合并
     * @return array 配置数组
     * @author jry <598821125@qq.com>
     */
    public function lists(){

        return array(
            'TOGGLE_WEB_SITE' => 1,
            'WEB_SITE_TITLE' => '快修沙漏计时器',
            'WEB_SITE_SLOGAN' => '轻量级WEB产品开发框架',
            'WEB_SITE_LOGO' => '',
            'WEB_SITE_DESCRIPTION' => '',
            'WEB_SITE_KEYWORD' => '奔驰、沙漏、Benz、Mercedes',
            'WEB_SITE_COPYRIGHT' => '版权所有 2014-2015 宜创北京科技有限公司',
            'WEB_SITE_ICP' => '',
            'WEB_STIE_STATISTICS' => '',
            'TOGGLE_USER_REGISTER' => 1,
            'ALLOW_RGE_TYPE' => array('username','email','mobile'),
            'LIMIT_TIME_BY_IP' => 0,
            'ADMIN_THEME' => 'default',
            'URL_MODEL' => 3,
            'SHOW_PAGE_TRACE' => true,
            'DEVELOP_MODE' => 1,
        );

        $map['status']  = array('eq', 1);
        $list = $this->where($map)->field('name,value,type')->select();
        foreach ($list as $key => $val){
            switch($val['type']){
                case 'array': 
                    $config[$val['name']] = parse_attr(stream_get_contents($val['value']));
                    break;
                case 'checkbox': 
                    $config[$val['name']] = explode(',', stream_get_contents($val['value']));
                    break;
                default:
                    $config[$val['name']] = stream_get_contents($val['value']);
                    break;
            }
        }
        return $config;
    }
}
