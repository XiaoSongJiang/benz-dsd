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
class ConfigModel extends Model{

    /**
     * @author ning
     */
    public function getConfig($data){
        //检查ld_code
        if(!$data['ld_code'] || !$data['type']){
            $this->error('请检查经销商代码');
        }else{
            //获取该经销商配置信息
            $where['ld_code'] = $data['ld_code'];
            $where['type'] = $data['type'];
            $res = M('config')->where($where)->find();

            //判断结果
            if($res){
                $config = $res;
                $config['c_value'] = explode(",", $res['c_value']);
            }else{
                $config = array();
            }
            return $config;
        }
    }

    /**
     * @author ning
     */
    public function getStatusList($ld_code){
        $where['ld_code'] = $ld_code;
        $where['type'] = 'page';
        $config = M('config')->where($where)->find();
        $c_value = $config['c_value'];
        if(strlen($c_value) >= 1){
            $str = 'A,B,G,'.$c_value;
            $str = explode(",",$str);
            sort($str);
        }else{
            $str = array(
                'A',
                'B',
                'G'
                );
        }

        return $str;
    }

    /**
     * @author ning
     */
    public function checkPushAuth($log,$status){
        //传入工单。给SA推送微信/短信消息
        // $status = 'A';
        // $wip = "AN66667";

        $log = M('log')->where(array('log_id'=>$log))->find();
        if(!$log){
            //不存在的工单直接返回
            $res = '工单不存在';
        }
        //如果不存在SA，直接返回
        if($log['sa_uid'] > 0){
            //SA信息
            $sa = M('user')->where(array('id'=>$log['sa_uid']))->find();
            
            $data['type'] = 'push';
            $data['ld_code'] = $sa['ld_code'];
            //经销商推送配置
            $config = $this->getConfig($data);
            if($config['c_status'] == 0){
                //经销商关闭推送功能。直接返回
                $res = '推送功能已关闭';
                return $res;
            }

            //检查当前状态是否在经销商推送配置中
            if(in_array($status, $config['c_value'])){
                //判断推送类型，1发短信，2发微信
                if($config['c_type'] == 1){
                    //获取状态
                    $wip_status = $this->getWipStatus($status);
                    //发送短信s
                    $arr = array(
                        'type' => 'sms',
                        'senderName' => 'mercedes-benz',
                        'recipientMobile' => $sa['mobile'],
                        'smsText' => "亲爱的维修顾问，工单{$log['wip']}，{$wip_status}，请密切关注此工单进度。",
                        'smsPriority' => '5',
                    );
                    $this->pushSms($arr);
                    return;

                }else if($config['c_type'] == 2){
                    //获取状态
                    $wip_status = $this->getWipStatus($status);
                    // $wip_time = $this->getWipTime($status);

                    // 给SA发送消息
                    if($sa && $sa['openid']) {

                        $uniqueID = md5($sa['username'].time());

                        $sa_params = array(
                            'msgId' => $uniqueID,
                            'sendTime' => time(),
                            'wechatAccountId' => Config('WECHAT'),
                            'openId' => $sa['openid'],
                            'templateId' => 'ServiceProgressNotification',
                            'data' => array(
                                'first' => '亲爱的维修顾问：工单'.$log['wip'].$wip_status.'，请及时关注',
                                'progress' => $wip_status,
                                'date' => date('Y年m月d日 H时i分', time()),
                                'remark' => '请密切关注此工单进度!'
                            )
                        );
                        $res = A('Home/System')->curl_xml($sa_params);

                        return;

                    }else{
                        return;
                    }

                    $arr = array(
                        'type' => 'sms',
                        'senderName' => 'mercedes-benz',
                        'recipientMobile' => $sa['mobile'],
                        'smsText' => "亲爱的维修顾问，工单{$log['wip']}，{$wip_status}，请密切关注此工单进度。",
                        'smsPriority' => '5',
                    );

                    return;
                }else{
                    //没有配置推送类型的。不发推送
                    $res = '本状态不发推送';
                }
            }else{
                //经销商管理员没有配置，不发推送
                $res = '没有配置推送状态';
            }
        }else{
            //没有绑定SA不发推送
            $res = '没有绑定SA';
        }

        return $res;

    }

    /**
     * @author ning
     */
    public function pushSms($data){
        
        if($data['type']= 'sms'){

            unset($data['type']);

            $res = A('Home/System')->sendSms($data);

            return;
            
        }else if($data['type'] = 'wechat'){

            dump($data);die;

            return;
        }else{
            return false;
        }

    }

    /**
     * @author 获取工单状态描述
     */
    public function getWipStatus($status){
        
        $array = array(
            'A' => '已经登记进厂',
            'A1' => '调度已经派单',
            'A2' => '调度已经交单',
            'B' => '已经开钟',
            'D' => '已经关钟',
            'F1' => '已经开始终检',
            'F2' => '已经完成终检',
            'F3' => '已经开始洗车',
            'F4' => '已经完成洗车',
            'G' => '已经准备交车',
            );
        return $array[$status]?:false;
    }

    // /**
    //  * @author 获取工单状态时间
    //  */
    // public function getWipTime($status){
        
    //     $array = array(
    //         'A' => 'check_in',
    //         'A1' => 'dispatch_time',
    //         'A2' => 'complete_time',
    //         'B' => '已经开钟',
    //         'D' => '已经关钟',
    //         'F1' => 'final_start_time',
    //         'F2' => 'final_end_time',
    //         'F3' => 'wash_start_time',
    //         'F4' => 'wash_end_time',
    //         'G' => '已经准备交车',
    //         );
    //     return $array[$status]?:false;
    // }

}
