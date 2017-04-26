<?php
namespace app\index\controller;
use think\Controller;
use think\Request;
use think\Session;
class Wechat extends Controller
{
	private $redirect_uri;
	private $url;
	public $request;
	public function _initialize(){
		parent::_initialize();
    	$this->request = request::instance();

	}
    public function index()
    {
    	$this->redirect_uri = urlencode($this->request ->domain().url('get_access_token'));

    	$state = mt_rand(100,999);
    	Session::set('wxstate',$state);
    	$this->url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.config('appID').'&redirect_uri='.$this->redirect_uri.'&response_type=code&scope=snsapi_userinfo&state='.$state.'#wechat_redirect';
    	// var_dump($this->url);exit();
    	$this->stepone();
    }


    /**
	*第一步：用户同意授权，获取code
	*在确保微信公众账号拥有授权作用域（scope参数）的权限的前提下（服务号获得高级接口后，默认拥有scope参数中的snsapi_base和snsapi_userinfo），引导关注者打开如下页面：
	https://open.weixin.qq.com/connect/oauth2/authorize?appid=APPID&redirect_uri=REDIRECT_URI&response_type=code&scope=SCOPE&state=STATE#wechat_redirect
	若提示“该链接无法访问”，请检查参数是否填写错误，是否拥有scope参数对应的授权作用域权限。
    */
    private  function stepone(){
    	$this->redirect($this->url);
    }



    /**
	第二步：通过code换取网页授权access_token
	首先请注意，这里通过code换取的是一个特殊的网页授权access_token,与基础支持中的access_token（该access_token用于调用其他接口）不同。公众号可通过下述接口来获取网页授权access_token。如果网页授权的作用域为snsapi_base，则本步骤中获取到网页授权access_token的同时，也获取到了openid，snsapi_base式的网页授权流程即到此为止。
	尤其注意：由于公众号的secret和获取到的access_token安全级别都非常高，必须只保存在服务器，不允许传给客户端。后续刷新access_token、通过access_token获取用户信息等步骤，也必须从服务器发起。
	请求方法
	获取code后，请求以下链接获取access_token： 
	https://api.weixin.qq.com/sns/oauth2/access_token?appid=APPID&secret=SECRET&code=CODE&grant_type=authorization_code


	返回说明

	正确时返回的JSON数据包如下：

	{
	   "access_token":"ACCESS_TOKEN",
	   "expires_in":7200,
	   "refresh_token":"REFRESH_TOKEN",
	   "openid":"OPENID",
	   "scope":"SCOPE"
	}
    */

	public function get_access_token(){
		$code = $this->request->param('code');
		$state = $this->request->param('state');

		if (is_null($code)) die();
		if (Session::get('wxstate')!=$state) die();

		$url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.config('appID').'&secret='.config('appsecret').'&code='.$code.'&grant_type=authorization_code';
		$r = curl($url);
		$r = json_decode($r,true);
		if (isset($r['errcode'])) {
			echo json_encode($r);
		}else{
			$this->get_snsapi_userinfo($r['access_token'],$r['openid']);
		}
	}

	/**
	第四步：拉取用户信息(需scope为 snsapi_userinfo)
	如果网页授权作用域为snsapi_userinfo，则此时开发者可以通过access_token和openid拉取用户信息了。

	请求方法

	http：GET（请使用https协议）
	https://api.weixin.qq.com/sns/userinfo?access_token=ACCESS_TOKEN&openid=OPENID&lang=zh_CN


	返回说明

	正确时返回的JSON数据包如下：

	{
	   "openid":" OPENID",
	   " nickname": NICKNAME,
	   "sex":"1",
	   "province":"PROVINCE"
	   "city":"CITY",
	   "country":"COUNTRY",
	    "headimgurl":    "http://wx.qlogo.cn/mmopen/g3MonUZtNHkdmzicIlibx6iaFqAc56vxLSUfpb6n5WKSYVY0ChQKkiaJSgQ1dZuTOgvLLrhJbERQQ4eMsv84eavHiaiceqxibJxCfHe/46", 
		"privilege":[
		"PRIVILEGE1"
		"PRIVILEGE2"
	    ],
	    "unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"
	}
	*/
	public function get_snsapi_userinfo($access_token,$openid){
		$url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
		$userinfo = curl($url);
		$userinfo = json_decode($userinfo,true);
		/**此处可以将得到的数据存入数据库*/

		/** 将openid存入session*/
		Session::set('openid',$userinfo['openid']);
	}


}