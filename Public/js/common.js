function formatDate(fmt, timestamp) {
    Date.prototype.Format = function (fmt) { //author: meizz 
        var o = {
            "M+": this.getMonth() + 1, //月份 
            "d+": this.getDate(), //日 
            "h+": this.getHours(), //小时 
            "m+": this.getMinutes(), //分 
            "s+": this.getSeconds(), //秒 
            "q+": Math.floor((this.getMonth() + 3) / 3), //季度 
            "S": this.getMilliseconds() //毫秒 
        };
        if (/(y+)/.test(fmt)) fmt = fmt.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length));
        for (var k in o)
            if (new RegExp("(" + k + ")").test(fmt)) fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)));
        return fmt;
    }
    if (timestamp) {
        return new Date(timestamp).Format(fmt)
    }
    return new Date().Format(fmt)
}

function get_datetime(date) {
  if(date) {
    date = date.replace(/-/g, '/')
    return new Date(date).getTime()
  }
  return 0
}

function check_time(data, start, end) {
  var start = get_datetime(data[start])
  var end   = get_datetime(data[end])
  if(start && end) {
    return start < end;
  }
  return true
}

function checkTimeLogic(log) {

  // 判断SA派单给调度时间
  if(!check_time(log, 'handover_time', 'dispatch_time')) {
    return {
      status: false,
      message: 'SA交单给调度时间必须早于派单时间'
    }
  }

  if(!check_time(log, 'dispatch_time', 'complete_time')) {
    return {
      status: false,
      message: '派单时间必须早于交单时间'
    }
  }
  if(!check_time(log, 'final_start_time', 'final_end_time')) {
    return {
      'status': false,
      'message': '终检开始时间必须早于终检结束时间'
    }
  }
  if(!check_time(log, 'wash_start_time', 'wash_end_time')) {
    return {
      'status': false,
      'message': '洗车开始时间必须早于洗车结束时间'
    }   
  }
  if(!check_time(log, 'dispatch_time', 'b_time')) {
    return {
      status: false,
      message: '调度派单时间不能大于开钟时间'
    }
  }
  var tips = {
    'dispatch_time': '派单时间',
    'complete_time': '交单时间',
    'final_start_time': '终检开始时间',
    'final_end_time': '终检结束时间',
    'wash_start_time': '洗车开始时间',
    'wash_end_time': '洗车结束时间',
    'handover': 'SA派单给调度时间'
  }

  var final_times = ['final_start_time', 'final_end_time', 'wash_start_time', 'wash_end_time'];
  for(var i = 0; i < 4; i++) {
    var time = final_times[i];
    if(!check_time(log, 'complete_time', time)) {
      return {
        status: false,
        message: '交单时间不能大于' + tips[time]
      }
    }
  }

  if(log['normal_status'] >= 'G') {
    var times = ['dispatch_time', 'complete_time', 'final_start_time', 'final_end_time', 'wash_start_time', 'wash_end_time'];
    for(var i = 0; i < 6; i++) {
      var time = times[i]
      if(!check_time(log, time, 'g_time')) {
        return {
          status: false,
          message: tips[time] + '不能大于准备交车时间'
        }
      }
    }
  }
  return {
    status: true
  }
}

// 订单状态判断
function getTemporaryStatus(value) {
  console.log('value', value)
  var normal_status = value['normal_status'];
  var status = normal_status;
  if(status < 'B') {
    if(value['handover_time'] != 0) {
      status = 'A0';
    }
    if(value['dispatch_time'] != 0) {
      status = 'A1';
    }
  }
  // 判断已交单状态
  if(status < 'F') {
    if(value['complete_time'] != 0) {
      status = 'A2';
    }
  }
  return status
}

/**
 * 获取N保服务状态
 */
function getNormalStatus(status) {
    
    var value = "等待进厂";
    var map = {
      'A': '登记进厂',
      'A0': '未派单',
      'A1': '已派单',
      'D1': '已交单',
      'B': '已开钟',
      'D': '已关钟',
      'F': '终检开始',
      'G': '准备交车',
      'I': '已结账'
    }
    return status ? map[status] : value;
}

/**
 * 获取N保服务状态的ICON
 */
function getNormalStatusImg(status) {
    
    var value = 'wait.png';
    var map = {
      'A': 'checkin.png',
      'A0': 'handover.png',
      'A1': 'dispatch.png',
      'D1': 'complete.png',
      'B': 'clock.png',
      'D': 'clock.png',
      'F': 'final.png',
      'G': 'deliver.png',
      'I': 'success.png'
    }
    var image = status ? map[status] : value;
    return '/benz/Public/images/' + image
}

/**
 * 获取N保服务状态的样式
 */
function getNormalStatusColor(status) {
    
    value = '#DF474A';
    map = {
      'A': '#FFAB28',
      'A0': '#FFAB28',
      'A1': '#FFAB28',
      'D1': '#FFAB28',
      'B': '#FFAB28',
      'D': '#FFAB28',
      'F': '#FFAB28',
      'G': '#4AB366',
      'I': '#4AB366'
    }
    return status ? map[status] : value;
}








