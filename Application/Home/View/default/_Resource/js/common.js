
// namespace 
benz = {};

// function
benz.fn = {
	isMobile : function () {
		var userAgentInfo = navigator.userAgent;  
		var Agents = new Array("Android", "iPhone", "SymbianOS", "Windows Phone", "iPad", "iPod");  
		var flag = false;
		for (var v = 0; v < Agents.length; v++) {  
		    if (userAgentInfo.indexOf(Agents[v]) > 0) { flag = true; break; }  
		}  
		return flag;
	},
	createMobileMeta : function () {
		var ele = document.createElement('meta');
		ele.setAttribute("content","initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no");
		ele.setAttribute("name","viewport");
		document.getElementsByTagName('head')[0].appendChild(ele, null);
		$('body').removeClass('none');
	},
	isIE : function (version) {
		if (typeof version == "Numeber" &&　window.attachEvent) {
			var userAgent = navigator.userAgent.split(";")[1].split(" ")[2];
			return parseInt(userAgent) <= version;
		}
		return window.addEventListener ? false : true;
	},
};

// countdown
benz.countdown = {

	start: function() {
		
	}
}

// UI
benz.ui = {
	alert: function(message,action,param,button) {
		var alert = $('.alert');
		$(alert).children('.message').html(message);
		var shadow = $(alert).parent('.shadow').show();
		var max_height = $('body').height() > $(window).height() ? $('body').height() : $(window).height();
		$(shadow).height(max_height);
		if(button) {
			$(shadow).find('.button-success').val(button);
		}
		var ok = $('.button-success').click(function() {
			if(action=='submit') {
				$(param).submit();
			}
			if(button) {
				$(shadow).find('.button-success').val('确定');
			}
			$(shadow).hide();
		});
	},
	confirm: function(message,icon,action,param) {
		var confirm = $('.confirm');
		$('.confirm-icon').attr('src',icon);
		$('.confirm-content').html(message);
		var shadow = $(confirm).parent('.shadow').show();
		var max_height = $('body').height() > $(window).height() ? $('body').height() : $(window).height();
		if(max_height > $(shadow).height()) {
			$(shadow).height(max_height);
		}
		var ok = $(".button-success").click(function() {
			if(action == 'goto') {
				window.location.href = param;
			}else if(action == 'submit') {
				$(param).submit();
			}
			
		});
		var cancle = $(".button-cancle").click(function() {
			$(shadow).hide();
		});
	},
	dealer: function() {
		var confirm = $('.confirm-dealer');
		var shadow = $(confirm).parent('.shadow').show();
		var max_height = $('body').height() > $(window).height() ? $('body').height() : $(window).height();
		$(shadow).height(max_height);
		$(".button-success").click(function() {
			$('#form').submit();
		})
		$(".button-cancle").click(function() {
			$(shadow).hide();
		});
	}
}

// ajax method
benz.ajax = {

}

$(function(){
	// 禁止页面分享
	function onBridgeReady(){
	    WeixinJSBridge.call('hideOptionMenu');
	}
	if (typeof WeixinJSBridge == "undefined"){
	    if( document.addEventListener ){
	        document.addEventListener('WeixinJSBridgeReady', onBridgeReady, false);
	    }else if (document.attachEvent){
	        document.attachEvent('WeixinJSBridgeReady', onBridgeReady); 
	        document.attachEvent('onWeixinJSBridgeReady', onBridgeReady);
	    }
	}else{
	    onBridgeReady();
	}
})