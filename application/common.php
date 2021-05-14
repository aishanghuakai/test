<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
use think\Db;
use app\util\Strs;
use lib\Redist;
use think\Session;
use \app\admin\controller\Email;

//生成唯一uuid
function getuuid($length)
{
    //$uuid = Strs::keyGen($length);
    $uuid = Strs::randString(8, 1);
    $r = Db::name('user')->field("id")->where(["uuid" => $uuid])->find();
    if (!empty($r) && count($r) > 0) {
        return getuuid($length);
    }
    return $uuid;
}

//字符串截取
function substr_text($str, $start = 0, $length, $charset = "utf-8", $suffix = "")
{
    if (function_exists("mb_substr")) {

        return mb_substr($str, $start, $length, $charset) . $suffix;

    } elseif (function_exists('iconv_substr')) {

        return iconv_substr($str, $start, $length, $charset) . $suffix;
    }
    $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
    $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
    $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
    $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
    preg_match_all($re[$charset], $str, $match);
    $slice = join("", array_slice($match[0], $start, $length));
    return $slice . $suffix;
}

//获取域名地址
function getdomainname()
{
    if (!empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'off'))
        $http = 'https';
    else
        $http = 'http';
    if (isset($_SERVER['HTTP_HOST']))
        $hostInfo = $http . '://' . $_SERVER['HTTP_HOST'];
    else {
        $hostInfo = $http . '://' . $_SERVER['SERVER_NAME'];
        $port = isset($_SERVER['SERVER_PORT']) ? (int)$_SERVER['SERVER_PORT'] : 80;
        if ($port !== 80)
            $hostInfo .= ':' . $port;
    }
    return $hostInfo;
}

function get_broswer()
{
    $brower = $_SERVER['HTTP_USER_AGENT'];
    if (preg_match('/360SE/', $brower)) {
        $brower = "360se";
    } elseif (preg_match('/Maxthon/', $brower)) {
        $brower = "Maxthon";
    } elseif (preg_match('/Tencent/', $brower)) {
        $brower = "Tencent Brower";
    } elseif (preg_match('/Green/', $brower)) {
        $brower = "Green Brower";
    } elseif (preg_match('/baidu/', $brower)) {
        $brower = "baidu";
    } elseif (preg_match('/TheWorld/', $brower)) {
        $brower = "The World";
    } elseif (preg_match('/MetaSr/', $brower)) {
        $brower = "Sogou Brower";
    } elseif (preg_match('/Firefox/', $brower)) {
        $brower = "Firefox";
    } elseif (preg_match('/MSIE\s6\.0/', $brower)) {
        $brower = "IE6.0";
    } elseif (preg_match('/MSIE\s7\.0/', $brower)) {
        $brower = "IE7.0";
    } elseif (preg_match('/MSIE\s8\.0/', $brower)) {
        $brower = "IE8.0";
    } elseif (preg_match('/MSIE\s9\.0/', $brower)) {
        $brower = "IE9.0";
    } elseif (preg_match('/Netscape/', $brower)) {
        $brower = "Netscape";
    } elseif (preg_match('/Opera/', $brower)) {
        $brower = "Opera";
    } elseif (preg_match('/Chrome/', $brower)) {
        $brower = "Chrome";
    } elseif (preg_match('/Gecko/', $brower)) {
        $brower = "Gecko";
    } elseif (preg_match('/Safari/', $brower)) {
        $brower = "Safari";
    } else$brower = "Unknow browser";
    return $brower;
}

function get_os()
{
    $agent = $_SERVER['HTTP_USER_AGENT'];
    $os = false;

    if (preg_match('/win/i', $agent) && strpos($agent, '95')) {
        $os = 'Windows 95';
    } else if (preg_match('/win 9x/i', $agent) && strpos($agent, '4.90')) {
        $os = 'Windows ME';
    } else if (preg_match('/win/i', $agent) && preg_match('/98/i', $agent)) {
        $os = 'Windows 98';
    } else if (preg_match('/win/i', $agent) && preg_match('/nt 6.0/i', $agent)) {
        $os = 'Windows Vista';
    } else if (preg_match('/win/i', $agent) && preg_match('/nt 6.1/i', $agent)) {
        $os = 'Windows 7';
    } else if (preg_match('/win/i', $agent) && preg_match('/nt 6.2/i', $agent)) {
        $os = 'Windows 8';
    } else if (preg_match('/win/i', $agent) && preg_match('/nt 10.0/i', $agent)) {
        $os = 'Windows 10';#添加win10判断
    } else if (preg_match('/win/i', $agent) && preg_match('/nt 5.1/i', $agent)) {
        $os = 'Windows XP';
    } else if (preg_match('/win/i', $agent) && preg_match('/nt 5/i', $agent)) {
        $os = 'Windows 2000';
    } else if (preg_match('/win/i', $agent) && preg_match('/nt/i', $agent)) {
        $os = 'Windows NT';
    } else if (preg_match('/win/i', $agent) && preg_match('/32/i', $agent)) {
        $os = 'Windows 32';
    } else if (preg_match('/linux/i', $agent)) {
        $os = 'Linux';
    } else if (preg_match('/unix/i', $agent)) {
        $os = 'Unix';
    } else if (preg_match('/iphone/i', $agent)) {
        $os = 'iphone';
    } else if (preg_match('/ipad/i', $agent)) {
        $os = 'ipad';
    } else if (preg_match('/android/i', $agent)) {
        $os = 'android';
    } else {
        $os = "未知设备";
    }
    return $os;
}

//加密
function mdd5($str)
{
    $salt = "gamebrain@#%74gf";
    return md5(md5($salt . $str));
}

//后台操作日志记录
function admin_log($content)
{
    $truename = Session::get('truename');
    if ($truename == "李雄飞" || !$truename) return;
    $remark = get_broswer() . " " . get_os();
    $id = Session::get('admin_userid');
    return Db::name('admin_log')->insert(["operate_name" => $truename, "operate_content" => $content, "userid" => $id, "remark" => $remark]);
}

function getadmobname($str)
{
    $allow_appname = ["tankr", "hexland", "helixrush", "airplane", "colorballs"];
    foreach ($allow_appname as $v) {
        if (preg_match("/$v/", $str)) {
            return $v;
        }
    }
    return "";
}

function getappidbycampaign($campaign_id, $platform, $type = "2")
{
    $r = Db::name('related_app')->field("related_appid")->where(["app_id" => $campaign_id, "platform" => $platform, "type" => $type])->find();
    return isset($r["related_appid"]) ? $r["related_appid"] : "";
}

function getgooglecountry($id)
{
    $countrys = array(
        "2158" => "TW",
        "2840" => "US",
        "2156" => "CN",
        "2392" => "JP",
        "2410" => "KR",
        "2276" => "DE",
        "2250" => "FR",
        "2643" => "RU",
        "2124" => "CA",
        "2826" => "GB",
        "2764" => "TH",
        "2076" => "BR",
        "2360" => "ID",
        "2458" => "MY",
        "2756" => "CH",
        "2704" => "VN",
        "2792" => "TR",
        "2702" => "SG",
        "2344" => "HK",
        "2752" => "SE",
        "2566" => "NG",
        "2608" => "PH",
        "2446" => "MO",
        "2356" => "IN",
        "2554" => "NZ",
        "2040" => "AT",
        "2528" => "NL",
        "2208" => "DK",
        "2246" => "FI",
        "2036" => "AU",
		"2616" => "PL",
        // 2020-09-15 update
        "2380" => "IT",
		"2372" => "IE",
		"2818" => "EG",
		"2682" => "SA",
        "2578" => "NO",
        "2710" => "ZA",
		"2203" => "CR",
		"2376" => "IL",
		"2440" => "LT",
		"2484" => "MX",
		"2032" => "AR",
		"2056" => "BE",
		"2104" => "MM",
		"2116" => "KH",
		"2152" => "CL",
		"2268" => "GE",
		"2170" => "CO",
		"2642" => "RO",
		"2604" => "PE",
		"2620" => "PT",
		"2804" => "UA",
		"2300" => "GR",
		"2100" => "BG",
		"2348" => "HU",
        "2724" => "ES"
        // 2020-09-15 update

    );
    if (isset($countrys[$id])) {
        return $countrys[$id];
    }
    return $id;
}

function getplatform($tag)
{

    $platforms = array(
        "all" => "全部",
        "1" => "Mobvista",
        "2" => "Unity",
        "3" => "Applovin",
        "4" => "Vungle",
        "5" => "Google",
        "6" => "Facebook",
        "7" => "ironSource",
        "8" => "Chartboost",
        "9" => "Tapjoy",
        "30" => "Upltv",
        "31" => "Adcolony",
        "32" => "Toutiao/CSJ",
        "33" => "Yomob",
        "34" => "Sigmob",
        "35" => "GDT",
        "36" => "TikTok",
        "37" => "Mopub",
        "38" => "Snapchat",
        "39" => "ASM",
        "40" => "Inmobi",
		"41" => "Fyber",
		"42"=> "KuaiShou"
    );
    if ($tag) {
        return isset($platforms[$tag]) ? $platforms[$tag] : "no";
    }
    return $platforms;
}

function getadtype($platform, $id)
{
    $name = "";
    $res = [];
    if ($platform == "1") {
        $name = "mv";
        $row = Db::name('ads_id')->field("adtype")->where("platform=1 and unit_id='{$id}'")->find();
        if (!empty($row)) {
            return $row["adtype"];
        }
    } elseif ($platform == "2") {
        $name = "un";
        $row = Db::name('ads_id')->field("adtype")->where("platform=2 and unit_id='{$id}'")->find();
        if (!empty($row)) {
            return $row["adtype"];
        }
        if (preg_match("/INT/", $id)) {
            return "int";
        }
        if (preg_match("/int/", $id)) {
            return "int";
        }
        if (preg_match("/RV/", $id)) {
            return "rew";
        }
        if (preg_match("/rv/", $id)) {
            return "rew";
        }

    } elseif ($platform == "4") {
        $name = "vu";
        if (preg_match("/RV/", $id)) {
            return "rew";
        }
        if (preg_match("/INT/", $id)) {
            return "int";
        }
        $row = Db::name('ads_id')->field("adtype")->where("platform=4 and unit_id='{$id}'")->find();
        if (!empty($row)) {
            return $row["adtype"];
        }
    } elseif ($platform == "3") {
        $adtyps = array(
            "GRAPHIC" => "int",
            "PLAY" => "int",
            "VIDEO" => "int",
            "REWARD" => "rew",
            "MRAID" => "int"
        );
        if (isset($adtyps[$id])) {
            return $adtyps[$id];
        }
        $row = Db::name('ads_id')->field("adtype")->where("platform=3 and unit_id='{$id}'")->find();
        if (!empty($row)) {
            return $row["adtype"];
        }
    } elseif ($platform == "5") {
        $name = "am";
        $row = Db::name('ads_id')->field("adtype")->where("platform=5 and unit_id='{$id}'")->find();
        if (!empty($row)) {
            return $row["adtype"];
        }
    } elseif ($platform == "6") {
        $name = "fb";
        $row = Db::name('ads_id')->field("adtype")->where("platform=6 and unit_id='{$id}'")->find();
        if (!empty($row)) {
            return $row["adtype"];
        }
    } elseif ($platform == "7") {
        if (preg_match("/RV/i", $id)) {
            return "rew";
        }
        if (preg_match("/INT/i", $id)) {
            return "int";
        }
    }
    return "no";
}

// 获取应用根目录
function getbaseurl()
{
    return getdomainname() . "/uploads/";
}

//加密
function hotgame_encrypt($txt)
{
    $key = "SAD%#$%#";
    return encrypt($txt, 'E', $key);
}

//解密
function hotgame_decrypt($txt)
{
    $key = "SAD%#$%#";
    return encrypt($txt, 'D', $key);
}

function encrypt($string, $operation, $key = '')
{
    $key = md5($key);
    $key_length = strlen($key);
    $string = $operation == 'D' ? base64_decode($string) : substr(md5($string . $key), 0, 8) . $string;
    $string_length = strlen($string);
    $rndkey = $box = array();
    $result = '';
    for ($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($key[$i % $key_length]);
        $box[$i] = $i;
    }
    for ($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }
    for ($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    if ($operation == 'D') {
        if (substr($result, 0, 8) == substr(md5(substr($result, 8) . $key), 0, 8)) {
            return substr($result, 8);
        } else {
            return '';
        }
    } else {
        return str_replace('=', '', base64_encode($result));
    }
}

function curl($url, $data = null, $method = null)
{

    $header = array("Content-Type:application/x-www-form-urlencoded;charset=UTF-8");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if ($method == 'post') {
        curl_setopt($ch, CURLOPT_POST, 1);
    }
    curl_setopt($ch, CURLOPT_HEADER, 0);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //不验证证书 https访问的时候
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //不验证证书 https访问的时候
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//传递参数
    }
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

//根据ID获取名称
function getappname($id)
{
    $res = Db::name("adcash_appname")->where(["app_id" => $id])->find();
    return $res["app_name"];
}

//获取redis实例化
function getredis()
{
    static $_redis;
    if (is_null($_redis)) {
        $_redis = Redist::getInstance(["host" => "127.0.0.1", "port" => 6379, "auth" => "hellowdshow"]);
    }
    return $_redis;
}

//获取年龄
function birthday($birthday)
{
    list($d1, $m1, $y1) = explode("/", $birthday);
    $now = strtotime("now");
    list($y2, $m2, $d2) = explode("-", date("Y-m-d", $now));
    $age = $y2 - $y1;
    if ((int)($m2 . $d2) < (int)($m1 . $d1))
        $age -= 1;
    return $age < 0 ? 0 : $age;
}

function getdeveloperapp($url = "")
{
    $out_data = [];
    if (preg_match("/itunes.apple.com/", $url)) {
        $platform = "ios";
    } else {
        $platform = "android";
    }
    if ($url) {
        import('phpQuery.phpQuery', EXTEND_PATH);
        if ($platform == "ios") {
            //实例化PHPExcel
            $detail = \phpQuery::newDocumentFile($url);
            $card = pq($detail)->find("div.l-row")->find('a');
            foreach ($card as $k => $vv) {
                $out_data[$k]["title"] = pq($vv)->find("div.we-lockup__title ")->text();
                $out_data[$k]["subtitle"] = "";
                $out_data[$k]["img"] = pq($vv)->find("img")->attr("src");
                $shop_url = pq($vv)->attr("href");
                $rt = explode("/", $shop_url);
                $s_url = explode("id", end($rt));
                $s_url = explode("?", end($s_url));
                $out_data[$k]["packages"] = $s_url[0];
                $out_data[$k]["shop_url"] = "http://console.gamebrain.io/admin_compet/results?kw=" . $out_data[$k]["packages"];
            }
        } else {
            //实例化PHPExcel
            $detail = \phpQuery::newDocumentFile($url . "&num=100&start=0");
            if (preg_match("/developer/i", $url)) {
                $card = pq($detail)->find('.IFTL7')->find('.Vpfmgd');
                foreach ($card as $k => $vv) {
                    $out_data[$k]["title"] = pq($vv)->find('div.nnK0zc')->text();
                    $out_data[$k]["subtitle"] = pq($vv)->find('a.mnKHRc')->text();
                    $d = pq($vv)->find("a.JC71ub")->attr("href");
                    $arr = explode("id=", $d);
                    $out_data[$k]["packages"] = $arr[1];
                    $img = pq($vv)->find("img")->eq(0)->attr("data-src");
                    $out_data[$k]["img"] = "http://cnf.mideoshow.com/adgastatic/getgoogleimg?url=" . str_replace("https:", "", $img);
                    $out_data[$k]["shop_url"] = "http://console.gamebrain.io/admin_compet/results?kw=" . $out_data[$k]["packages"];
                }

            } else {
                $r = pq($detail)->find("div.W9yFB")->find("a")->attr('href');
                $detail1 = \phpQuery::newDocumentFile($r);
                $card = pq($detail1)->find('.ZmHEEd')->find('.WHE7ib');

                foreach ($card as $k => $vv) {
                    $out_data[$k]["title"] = pq($vv)->find('div.RZEgze .nnK0zc')->text();
                    $out_data[$k]["subtitle"] = pq($vv)->find('div.RZEgze a.mnKHRc')->text();
                    $packages = pq($vv)->find('a.poRVub')->attr("href");
                    $ddd = explode("=", $packages);
                    $out_data[$k]["packages"] = $ddd[1];
                    $out_data[$k]["img"] = "http://cnf.mideoshow.com/adgastatic/getgoogleimg?url=" . pq($vv)->find("span.yNWQ8e img")->attr("src");
                    $out_data[$k]["shop_url"] = "http://console.gamebrain.io/admin_compet/results?kw=" . $out_data[$k]["packages"];
                }
            }
        }
    }
    return ["platform" => $platform, "out_data" => $out_data];
}

//发送邮箱
function send_mail($tomail, $name, $subject = '', $body = '', $myname = "", $attachment = null)
{
    $mail = new \PHPMailer();           //实例化PHPMailer对象
    $mail->CharSet = 'UTF-8';           //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
    $mail->IsSMTP();                    // 设定使用SMTP服务
    $mail->SMTPDebug = 0;               // SMTP调试功能 0=关闭 1 = 错误和消息 2 = 消息
    $mail->SMTPAuth = true;             // 启用 SMTP 验证功能
    $mail->SMTPSecure = 'ssl';          // 使用安全协议
    $mail->Host = "smtp.exmail.qq.com"; // SMTP 服务器
    $mail->Port = 465;                  // SMTP服务器的端口号
    $mail->Username = "game@hello-games.net";    // SMTP服务器用户名
    $mail->Password = "&U#Qywu0";     // SMTP服务器密码
    $mail->SetFrom('game@hello-games.net', $myname);
    $replyEmail = '';                   //留空则为发件人EMAIL
    $replyName = '';                    //回复名称（留空则为发件人名称）
    $mail->AddReplyTo($replyEmail, $replyName);
    $mail->Subject = $subject;
    $mail->MsgHTML($body);
    $mail->AddAddress($tomail, $name);
    if (is_array($attachment)) { // 添加附件
        foreach ($attachment as $file) {
            is_file($file) && $mail->AddAttachment($file);
        }
    }
    return $mail->Send() ? true : $mail->ErrorInfo;
}

//收件箱
function receive_mail()
{
    set_time_limit(0);

    $mailserver = "imap.exmail.qq.com"; //IMAP主机
    $username = 'game@hello-games.net'; //邮箱用户名
    $password = '&U#Qywu0'; //邮箱密码
    $mailBody = array();
    $mail = new Email();
    $connect = $mail->mailConnect($mailserver, '143', $username, $password);
    if ($connect) {
        $totalCount = $mail->mailTotalCount();
        for ($i = $totalCount; $i > 0; $i--) {
            $mailHeader = $mail->mailHeader($i);
            //只处理未读邮件，将未读设置成已读
            //if ($mailHeader['seen'] == "U") {
            $mailHeader["body"] = $mail->getBody($i);
            //$status = $mail->mailRead($i);
            //}
            $mailBody[] = $mailHeader;
        }
        $mail->closeMail();
    }
    return $mailBody;
}
