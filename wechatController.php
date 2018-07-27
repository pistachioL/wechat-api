<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use app\indexModel;
class wechatController extends Controller
{
    public function __construct()
    {

    }

    public function checkSignature(Request $request)//在设置url时，微信公众号第一次推送get请求到第三方url地址
    {
        $timestamp = $request->get('timestamp');
        $nonce = $request->get('nonce');
        $signature = $request->get('signature');
        $token = 'hello';
        $array = array($token, $timestamp, $nonce);
        sort($array);
        $tmpstr = implode('', $array);
        $tmpstr = sha1($tmpstr);

        if ($tmpstr == $signature) {
            //第一次接入微信api接口
            echo $request->get('echostr'); //随机加密字符串
            exit;
        } else {
            //不是第一次，直接实现时间推送的回复。实现自己的业务逻辑
            $this->responseMsg($request);/*当客户发送图文等文件时，微信公众平台在第一次验证成功后，
                                           会把消息传递到url那里，会少了一个echostr*/
        }


    }

    //1.接收事件推送 并回复
    public function responseMsg($request)
    {
        //1.获取微信推送过来的POST数据(以xml格式)，要用超全局来接收
        $postArr = $request->getContent();
        //2.处理消息类型，并设置回复类型和内容

        //接收数据的xml数据包<xml>转换成对象
        $postObj = simplexml_load_string($postArr, 'SimpleXMLElement', LIBXML_NOCDATA);//用这个函数把xml格式的内容转换成对象

        //判断该数据包$postObj是否是订阅的事件推送
        if (strtolower($postObj->MsgType) == 'event') {
            if (strtolower($postObj->Event) == 'subscribe') //关注
            {
                //回复用户消息
                $toUser = $postObj->FromUserName;
                $fromUser = $postObj->ToUserName;
                $time = time();
                $msgType = "text";
                $content = "加油" . $postObj->FromUserName . "-" . $postObj->ToUserName;
                $template = "
                 <xml>
                 <ToUserName><![CDATA[%s]]></ToUserName>
                 <FromUserName><![CDATA[%s]]></FromUserName>
                 <CreateTime><![CDATA[%s]]></CreateTime>
                 <MsgType><![CDATA[%s]]></MsgType>
                 <Content><![CDATA[%s]]></Content>
                 </xml>";//解析模板
                $info = sprintf($template, $toUser, $fromUser, $time, $msgType, $content);//sprintf()把变量值传递到%s
                echo $info;


                //发送信息的数据包
//                $indexModel=new indexModel();
//                $indexModel->responseSubscribe($postObj,$content);

            }
            //如果是重扫二维码
            if (strtolower($postObj->Event) == 'scan') {
                if ($postObj->EvenKey == 2000) //临时二维码
                {
                    $tem = "临时二维码";
                }
                if ($postObj->EvenKey == 3000) //永久二维码
                {
                    $tem = "永久二维码";
                }
                $toUser = $postObj->FromUserName;
                $fromUser = $postObj->ToUserName;
                $time = time();
                $msgType = 'text';
                $content = "你好啊，好高兴见到你！~";
                $template = "
                            <xml>
                             <ToUserName><![CDATA[%s]]></ToUserName>
                             <FromUserName><![CDATA[%s]]></FromUserName>
                             <CreateTime>%s</CreateTime>
                             <MsgType><![CDATA[%s]]></MsgType>
                             <Content><![CDATA[%s]]></Content>
                             </xml>";
                echo sprintf($template, $toUser, $fromUser, $time, $msgType, $content);
            }
        }
        if (strtolower($postObj->MsgType) == 'text' && trim($postObj->Content) == "aa") {
            $arr = array(
                array(
                    'title' => 'BBS',
                    'description' => 'Liao',
                    'picUrl' => 'https://avatars3.githubusercontent.com/u/35989937?s=460&v=4',
                    'url' => 'http://xkpiastachio.top/blog/public/loginShow',
                ),
                array(
                    'title' => 'github',
                    'description' => 'Learning',
                    'picUrl' => 'https://avatars0.githubusercontent.com/u/34258355?s=460&v=4',
                    'url' => 'https://github.com/GreenHatHG',
                ),
            );
            $toUser = $postObj->FromUserName;
            $fromUser = $postObj->ToUserName;
            $template = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[%s]]></MsgType>
                    <ArticleCount>" . count($arr) . "</ArticleCount>
                    <Articles>";
            foreach ($arr as $k => $v) {
                $template .= "<item>
                        <Title><![CDATA[" . $v['title'] . "]]></Title> 
                        <Description><![CDATA[" . $v['description'] . "]]></Description>
                        <PicUrl><![CDATA[" . $v['picUrl'] . "]]></PicUrl>
                        <Url><![CDATA[" . $v['url'] . "]]></Url></item>";
            }
            $template .= "</Articles>
                   </xml>";
            $info = sprintf($template, $toUser, $fromUser, time(), 'news');
            echo $info;
//
//                    $indexModel = new indexModel();
//                    $indexModel->responseMsg($postObj,$arr);
        } else if (strtolower($postObj->MsgType) == 'text' && trim($postObj->Content) == "天气") {
            $ch = curl_init();
            $url = 'https://api.seniverse.com/v3/weather/now.json?key=wkklh5t86jq8xzz1&location=beijing&language=zh-Hans&unit=c';
            $header = array(
                'apikey: wkklh5t86jq8xzz1',  //添加apikey到header
            );
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_URL, $url);
            $res = curl_exec($ch);
            $arr = json_decode($res, true);//true则表示返回一个数组,否则只是一个对象
            $content = $arr['results']['0']['now']['text'] . '<br />' . $arr['results']['0']['now']['code'] . '<br />' . $arr['results']['0']['now']['temperature'];
            $toUser = $postObj->FromUserName;
            $fromUser = $postObj->ToUserName;
            $time = time();
            $msgType = 'text';
            $template = "
                              <xml> 
                 <ToUserName><![CDATA[%s]]></ToUserName> 
                 <FromUserName><![CDATA[%s]]></FromUserName> 
                 <CreateTime><![CDATA[%s]]></CreateTime> 
                 <MsgType><![CDATA[%s]]></MsgType> 
                 <Content><![CDATA[%s]]></Content> 
                 </xml>";


            //dd($content);
            echo sprintf($template, $toUser, $fromUser, $time, $msgType, $content);

        } //回复单文本
        else if (strtolower($postObj->MsgType) == 'text') {
            if (strtolower($postObj->Content == 'hi')) {
                $toUser = $postObj->FromUserName;
                $fromUser = $postObj->ToUserName;
                $time = time();
                $msgType = 'text';
                $content = "你好啊，好高兴见到你！~";
                $template = "
                            <xml>
                             <ToUserName><![CDATA[%s]]></ToUserName>
                             <FromUserName><![CDATA[%s]]></FromUserName>
                             <CreateTime>%s</CreateTime>
                             <MsgType><![CDATA[%s]]></MsgType>
                             <Content><![CDATA[%s]]></Content>
                             </xml>";
                echo sprintf($template, $toUser, $fromUser, $time, $msgType, $content);//参数按照模板顺序
//                       $indexModel=new indexModel();
//                       $indexModel->responseText($postObj,$content);
            }
        }
    }

    function http_curl($url, $type = 'get', $res = 'json', $arr = '')
    {
        //1.初始化curl
        $ch = curl_init();//创建一个curl资源

        //2.设置url的参数
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//把采集的东西返回

        if ($type == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);//输出到屏幕上
            curl_setopt($ch, CURLOPT_POSTFIELDS, $arr);
        }
        //3.采集
        $output = curl_exec($ch);
        //4.关闭
        curl_close($ch);
        if ($res == 'json') {
            return json_decode($output, true);
        }

    }

    public function getAccessToken()
    {
//
//            session_start();
//
//
//
//        //将access_Token 存储在session/cookies中(数据库也行)
//        if (SESSION('access_token') && SESSION('expire_time') > time())//存在session并且过期时间大于当前时间戳
//        {
//            //如果access_token在session中没有过期
//            return SESSION('access_token');
//        } else {
            //access_token不存在或已过期，则重新获取access_token(curl调用接口)
            $appid = "wxe81600f23faad0e1";
            $appsecret = "07a06e25a7a140c04f31f58e1a0b0bad";
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appid . "&secret=" . $appsecret;
            $result = $this->http_curl($url, 'get', 'json');
            $access_token = $result['access_token'];
            //将重新获取到的access_token存储到session中
//            SESSION(['access_token' => $access_token, 'expire_time' => time() + 7000]);
            //$_SESSION['expire_time']=time()+7000;
            return $access_token;
//        }

    }

    function getWxAccessToken()//获取access_Token值
    {
        //1.请求url地址
        $appid = "wxe81600f23faad0e1";
        $appsecret = "07a06e25a7a140c04f31f58e1a0b0bad";
        $url = "https://api.weixin.qq.com/cgi-bin/message/mass/preview?access_token=" . $appid . "&secret=" . $appsecret;
        //2.初始化
        $ch = curl_init();
        //3.设置参数
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
        //4.调用接口
        $res = curl_exec($ch);//json数据
        //5.关闭
        curl_close($ch);
        $arr = json_decode($res, true);
        // dd($arr);//通过appid和appsecret调用本接口来获取access_token
    }

    public function test()
    {
        $ch = curl_init();
        $url = 'https://api.seniverse.com/v3/weather/now.json?key=wkklh5t86jq8xzz1&location=beijing&language=zh-Hans&unit=c';
        $header = array(
            'apikey: wkklh5t86jq8xzz1',  //添加apikey到header
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        $res = curl_exec($ch);
        $arr = json_decode($res, true);//true则表示返回一个数组,否则只是一个对象
        //dd($arr);
        $content = $arr['results']['0']['now']['text'] . '<br />' . $arr['results']['0']['now']['code'] . '<br />' . $arr['results']['0']['now']['temperature'];
        dd($content);
    }

    //群发接口
    public function sendMsgAll()
    {
        //1.获取全局的access_token
        echo $access_token = $this->getAccessToken();
        echo "<br />";
        $url = "https://api.weixin.qq.com/cgi-bin/message/mass/preview?access_token=" . $access_token;
        //2.组装群发接口数据array
        $array = array(
            'touser' => 'o1srj01G1GmlAs3UabdqOZ7gZJao',//微信用户的openid
            'text' => array('content' => 'hahahahahha'),
            'msgtype' => 'text'//消息类型
        );
        //3.将数组转为json
        $postJson = json_encode($array);

        //4.调用curl
        $res = $this->http_curl($url, 'post', 'json', $postJson);
        var_dump($res);

    }

    //临时二维码
    function getTempQrCode()
    {
        //1.获取ticket票据
        $access_token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=" . $access_token;
        $postArr = array(
            'expire_seconds' => 604800,
            'action_name' => "QR_SCENE",
            'action_info' => array(
                'scene' => array('scene_id' => 2000),
            ),
        );
        $postJson = json_encode($postArr);
        $res = $this->http_curl($url, 'post', 'json', $postJson);
        $ticket = $res['ticket'];
        //2.使用ticket获取二维码图片
        $url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=" . urlencode($ticket);
        //$res = $this->http_curl($url);   //?????
        //直接显示
        echo "<img src='" . $url . "'/>";
        /*
         * {"expire_seconds": 604800, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": 123}}}
         */

    }

    //永久二维码
    function getForeverQrCode()
    {
        $access_token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=" . $access_token;
        /*
         * {"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": 123}}}
         */
        $postArr = array(
            'action_name' => "QR_LIMIT_SCENE",
            'action_info' => array(
                'scene' => array(
                    'scene_id' => 3000
                ),
            ),
        );
        $postJson = json_encode($postArr);
        $res = $this->http_curl($url, 'post', 'json', $postJson);
        $ticket = $res['ticket'];
        $url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=" . urlencode($ticket);
        echo "<img src='" . $url . "' />";
    }

    public function responseScan($postObj, $arr)
    {
        $toUser = $postObj->FromUserName;
        $fromUser = $postObj->ToUserName;
        $template = "<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[news]]></MsgType>122
                <ArticleCount>1</ArticleCount>
                <Articles><item>
                <Title><![CDATA[" . $arr['title'] . "]]></Title>
              <Description><![CDATA[" . $arr['description'] . "]]></Description>
              <PicUrl><![CDATA[" . $arr['picUrl'] . "]]></PicUrl>
              <Url><![CDATA[" . $arr['url'] . "]]></Url>
              </item></Articles>
                 </xml>";
        $info = sprintf($template, $toUser, $fromUser, time());
        echo $info;
    }

    //模板消息
    function sendTemplateMsg()
    {
        //1.获取到access_token
        $access_token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" . $access_token;
        //2.组装数组
        $array = array(
            'touser' => 'o1srj01G1GmlAs3UabdqOZ7gZJao',
            'template_id' => 'xOkH2CBcc7_lgru5MtGk7mKo8lAJCdVfc22c7quBcKQ',
            'url' => 'https://www.baidu.com',
            'data' => array(
                'name' => array('value' => 'hello', 'color' => '#173177'),
                'money' => array('value' => 100, 'color' => '#173177'),
                'date' => array('value' => date('Y-m-d H:i:s'), 'color' => '#173177'),
            ),
        );
        $postJson = json_encode($array);
        $res = $this->http_curl($url, 'post', 'json', $postJson);
        var_dump($res);
    }

    //网页授权 snsapi_base 获取用户的openid
    function getBaseInfo()
    {
        //1.获取到code
        $appid = "wxe81600f23faad0e1";
        $redirect_uri = urlencode("http://xkpiastachio.top/blog/public/getUserOpenId");
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".$redirect_uri.
            "&response_type=code&scope=snsapi_base&state=123&#wechat_redirect";
        header('location:'.$url);
        exit();
    }

    function getUserOpenId(Request $request)
    {

        //2.获取网页授权的access_token
        $appid = "wxe81600f23faad0e1";
        $appsecret = "07a06e25a7a140c04f31f58e1a0b0bad";
        $code = $request->get('code');

        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid="
            . $appid . "&secret=" . $appsecret . "&code=" . $code . "&grant_type=authorization_code";
        //3.拉取用户的openid
        $res = $this->http_curl($url,'get');
        var_dump($res);
        //$openid = $res['openid'];//知道这个用户的信息，例如时间点，活动次数var_dump($openid);

    }

//snsapi_userinfo为scope发起的网页授权 获取用户基本信息
    function getUserDetail(){
        //1.获取到code
        $appid = "wxe81600f23faad0e1";
        $redirect_uri = urlencode("http://xkpiastachio.top/blog/public/getUserInfo");
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $appid . "&redirect_uri=" . $redirect_uri . "&response_type=code&scope=snsapi_base&state=123#wechat_redirect";
        header('location:' . $url);
        exit();

    }
    function getUserInfo(Request $request)
    {
            //2.获取网页授权的access_token
            $appid = "wxe81600f23faad0e1";
            $appsecret = "07a06e25a7a140c04f31f58e1a0b0bad";
        $code=$request->get('code');
            $url="https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$appsecret."&code=".$code."&grant_type=authorization_code";
            $res=$this->http_curl($url,'get');
            $access_token=$res['access_token'];
            $openid=$res['openid'];
            //3.拉取用户的详细信息
            $url= "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
            $res=$this->http_curl($url,'get');
            var_dump($res);

    }
    //获取jsapiticket全局票据
  function getJsApiTicket()
  { //如果session中保存有效的jsapi_ticket
      if(session('jsapi_ticket_expire_time' ) >time() && session('jsapi_ticket')  )
      {
          $jsapi_ticket=session('jsapi_ticket');
        
      }
      else{
          $access_token=$this->getAccessToken();
          $url="https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$access_token."&type=jsapi";//调用接口的url
          $res=$this->http_curl($url);//获得一个数组
          $jsapi_ticket=$res['ticket'];
          //缓存
          session(['jsapi_ticket'=>$jsapi_ticket,'jsapi_ticket_expire_time'=>time()+7000] );
      }

       return $jsapi_ticket;


      //获取之后缓存

  }
  //获取16位随机码
    function getRandCode()
    {
      $array= array(
          'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T',
          'U','V','W','X','Y','Z','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o',
          'p','q','r','s','t','u','v','w','x','y','z','0','1','2','3','4','5','6','7','8','9'
      );
      $tmpstr= '';
      $max=count($array);//返回数组中元素数目
        for($i=1;$i<=16;$i++)
        {
            $key=rand(0,$max-1);
            $tmpstr .=$array[$key];
        }
        return $tmpstr;  //OLYpPWo64NzSuADJ
    }

    //分享朋友圈
    function shareWx()
    {
        $name=111;
        //1.获取jsapi_ticket票据
        $jsapi_ticket=$this->getJsApiTicket();//取到jsapi_ticket的值
        $timestamp=time();

        $noncestr=$this->getRandCode();
        //2.获取signature
        $url="http://localhost/blog/public/shareWx";
        $signature= "jsapi_ticket=".$jsapi_ticket."&noncestr=".$noncestr."&timestamp=".$timestamp."&url=".$url;
     
        $signature=sha1($signature);



      return view('share',[
          'name'=>$name,
          'timestamp'=>$timestamp,
          'noncestr'=>$noncestr,
          'signature'=>$signature

      ]);

    }



    //新增临时素材
    public function Upload()//通过curl上传图片,获得media_id
    {
        $type = "image";
        $access_token = $this->getAccessToken();
//        echo $access_token;
//        dd($access_token);
       $url= "https://api.weixin.qq.com/cgi-bin/media/upload?access_token=".$access_token."&type=".$type;
       
        $filepath=storage_path()."/app/public/jerry2.jpg";

       // dd($filepath);
        $data = array('media' => new \CURLFile($filepath));//绝对路径 php5.5以上的写法


       // dd($data);
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, TRUE);    //将CURL_SAFE_UPLOAD设置为FALSE
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        $output = curl_exec($ch);
        curl_close($ch);
        //dd($output);
        $res = json_decode($output, true);
        dd($res);

    }


    public function sendImage(Request $request)
    {
	$postArr=$request->getContent();
        $postObj=simplexml_load_string($postArr,'SimpleXMLElement',LIBXML_NOCDATA);
           $toUser='wxe81600f23faad0e1';
            $fromUser='o1srj01G1GmlAs3UabdqOZ7gZJao';
            $time=time();
            $msgType="image";
            $media_id="-bshDBUUidt3DuCxGmNzqAoxZtZpnhfWRK29AL7X7t9WKj4R-rGzDLVdWeOewUGF";
            $template="<xml>
                 <ToUserName><![CDATA[%s]]></ToUserName>
                 <FromUserName><![CDATA[%s]]></FromUserName>
                 <CreateTime><![CDATA[%s]]></CreateTime>
                 <MsgType><![CDATA[%s]]></MsgType>
                  <Image><MediaId><![CDATA[%s]]></MediaId></Image>
              </xml>";
            $info=sprintf($template,$toUser,$fromUser,$time,$msgType,$media_id);
            echo $info;


	
      }
    



}

