<?php
header("Access-Control-Allow-Origin:*");
header("Access-Control-Allow-Methods:GET");
header("Access-Control-Allow-Headers:x-requested-with,content-type");
header("Content-Type:text/html,application/json; charset=utf-8");
if ($_GET['domain']) {
    $domain = getTopHost($_GET['domain']);
} else {
    exit("缺少参数");
}
//  domain 1;app 6;小程序 7;快应用 8; 查什么，就把下面的换成哪一个数字，也可以加一个请求参数，用get获取查询类型。
$serviceType = 1;

$timeStamp = time();
$authKey = md5("testtest" . $timeStamp);
$token = json_decode(curl_post("auth", "authKey=$authKey&timeStamp=$timeStamp", "application/x-www-form-urlencoded;charset=UTF-8", "0"));
$token = $token->params->bussiness;
$query = json_decode(curl_post("icpAbbreviateInfo/queryByCondition", '{"pageNum":"","pageSize":"","serviceType":"'. $serviceType .'","unitName":"' . $domain . '"}', "application/json;charset=UTF-8", $token));
$query = json_encode($query->params->list);
$query = str_replace("[", "", $query);
$query = json_decode(str_replace("]", "", $query));
$icp = $query->serviceLicence;
$unitName = $query->unitName;
$natureName = $query->natureName;
if (!$token) {
    $icp = "服务器请求频率过高，请稍后再试";
    $msg = "查询失败";
    $code = "0";
} elseif (!$icp) {
    $icp = "未备案";
    $msg = "查询成功";
    $code = "1";
} else {
    $msg = "查询成功";
    $code = "1";
}
$json = array(
    'icp' => $icp,
    'unitName' => $unitName,
    'natureName' => $natureName,
    'msg' => $msg,
    'result' => $code
);
print_r(json_encode($json, JSON_UNESCAPED_UNICODE));

function curl_post($url, $data, $Content, $token) {
    $ip = "101.".mt_rand(1,255).".".mt_rand(1,255).".".mt_rand(1,255);
    $ch = curl_init();
    $headers = array(
        "Content-Type: $Content",
        "Origin: https://beian.miit.gov.cn/",
        "Referer: https://beian.miit.gov.cn/",
        "token: $token",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.87 Safari/537.36",
        "CLIENT-IP: $ip",
        "X-FORWARDED-FOR: $ip"
    );
    curl_setopt($ch, CURLOPT_URL, "https://hlwicpfwc.miit.gov.cn/icpproject_query/api/" . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $content = curl_exec($ch);
    curl_close($ch);
    return $content;
}
function getTopHost($url) {
    if (stristr($url, "http") === false) {
        $url = "http://" . $url;
    }
    $url = strtolower($url);
    $hosts = parse_url($url);
    $host = $hosts['host'];
    $data = explode('.', $host);
    $n = count($data);
    $preg = '/[\w].+\.(com|net|org|gov|edu)\.cn$/';
    $pregip = '/((2(5[0-5]|[0-4]\d))|[0-1]?\d{1,2})(\.((2(5[0-5]|[0-4]\d))|[0-1]?\d{1,2})){3}/';
    if (($n > 2) && preg_match($preg, $host)) {
        $host = $data[$n - 3] . '.' . $data[$n - 2] . '.' . $data[$n - 1];
    } elseif (preg_match($pregip, $host)) {
        $host = $host;
    } else {
        $host = $data[$n - 2] . '.' . $data[$n - 1];
    }
    return $host;
}
