<?php

class GT {
    private $PHPSESSID;

    function __construct($PHPSESSID) {
        $this->PHPSESSID = $PHPSESSID;
    }

    function query_rt($cmd) {
        $rt = shell_exec($cmd);
        $arr = json_decode($rt, true);
        return $arr;
    }

    function get_timetableId($day) {
        $cmd = "curl -s -H 'Host: gtweixin.nlc.cn' -H 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8' -H 'Cookie: PHPSESSID={$this->PHPSESSID}' -H 'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 15_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 MicroMessenger/8.0.26(0x18001a31) NetType/WIFI Language/zh_CN' -H 'Accept-Language: zh-CN,zh-Hans;q=0.9' -H 'Referer: https://gtweixin.nlc.cn/subscribe' --compressed 'https://gtweixin.nlc.cn/subscribe/order.html'|grep -Eo 'data-day=\"\S+\" data-id=\"\d+\" data-indx=\"(\d+)\"'";
        $rt = shell_exec($cmd);
        preg_match_all('/data-day="(\S+)" data-id="(\d+)" data-indx="(\d+)"/', $rt, $match);
        $index = -1;
        if (isset($match[1]) && isset($match[2]) && isset($match[3])) {
            foreach ($match[1] as $k=>$v) {
                if ($v == $day) {
                    $index = $k;
                    break;
                }
            }
        }
        if ($index >= 0) {
            return array(
                'timetableId'   =>  $match[2][$index],
                'indx'         =>  $match[3][$index],
            );
        }
        return array();
    }

    function get_timeslot($timetableId, $venue) {
        $cmd = "curl -s -H 'Host: gtweixin.nlc.cn' -H 'Cookie: PHPSESSID={$this->PHPSESSID}' -H 'Accept: */*' -H 'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 15_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 MicroMessenger/8.0.26(0x18001a31) NetType/WIFI Language/zh_CN' -H 'Referer: https://gtweixin.nlc.cn/subscribe/order.html' -H 'Accept-Language: zh-CN,zh-Hans;q=0.9' -H 'X-Requested-With: XMLHttpRequest' --compressed 'https://gtweixin.nlc.cn/subscribe/order/timeslot.html?timetableId={$timetableId}&venue={$venue}'";
        return $this->query_rt($cmd);
    }

    function get_venue($day, $timetableId, $indx, $venue_name = '总馆馆区') {
        $cmd = "curl -s 'https://gtweixin.nlc.cn/subscribe/order/subscribe.html?day={$day}&timetableId={$timetableId}&indx={$indx}'    -X 'POST'    -H 'Accept: application/json, text/javascript, */*; q=0.01'    -H 'Accept-Language: zh-CN,zh;q=0.9'    -H 'Cache-Control: no-cache'    -H 'Connection: keep-alive'    -H 'Content-Length: 0'    -H 'Cookie: PHPSESSID={$this->PHPSESSID}'    -H 'Origin: https://gtweixin.nlc.cn'    -H 'Pragma: no-cache'    -H 'Referer: https://gtweixin.nlc.cn/subscribe/order.html'    -H 'Sec-Fetch-Dest: empty'    -H 'Sec-Fetch-Mode: cors'    -H 'Sec-Fetch-Site: same-origin'    -H 'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1'    -H 'X-Requested-With: XMLHttpRequest'";
        $arr = $this->query_rt($cmd);
        $venue = 0;
        if (isset($arr['data']['list'])) {
            foreach ($arr['data']['list'] as $item) {
                if ($item[1] == $venue_name) {
                    $venue = $item[0];
                }
            }
        }
        return $venue;
    }

    function order($day, $venue, $timeslot, $timetableId) {
        $cmd = "curl -s -H 'Host: gtweixin.nlc.cn' -H 'Accept: application/json, text/javascript, */*; q=0.01' -H 'X-Requested-With: XMLHttpRequest' -H 'Accept-Language: zh-CN,zh-Hans;q=0.9' -H 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8' -H 'Origin: https://gtweixin.nlc.cn' -H 'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 15_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 MicroMessenger/8.0.26(0x18001a31) NetType/WIFI Language/zh_CN' -H 'Referer: https://gtweixin.nlc.cn/subscribe/order.html' -H 'Cookie: PHPSESSID={$this->PHPSESSID}' --data-binary \"action=create&resubmit=1&day={$day}&venue={$venue}&timeslot={$timeslot}&order_type=10&timetableId={$timetableId}&is_carray=0\" --compressed 'https://gtweixin.nlc.cn/subscribe/order.html'";
        return $this->query_rt($cmd);
    }

    function check_order($orderId) {
        $cmd = "curl -s -H 'Host: gtweixin.nlc.cn' -H 'Cookie: PHPSESSID={$this->PHPSESSID}' -H 'Accept: application/json, text/javascript, */*; q=0.01' -H 'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 15_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 MicroMessenger/8.0.26(0x18001a31) NetType/WIFI Language/zh_CN' -H 'Referer: https://gtweixin.nlc.cn/subscribe/order.html' -H 'Accept-Language: zh-CN,zh-Hans;q=0.9' -H 'X-Requested-With: XMLHttpRequest' --compressed 'https://gtweixin.nlc.cn/subscribe/order/chk.html?orderId={$orderId}'";
        return $this->query_rt($cmd);
    }

    function gt_order($day) {
        $order_status = false;
        $table = $this->get_timetableId($day);
        $timetableId = $table['timetableId'];
        $indx = $table['indx'];
        $venue = $this->get_venue($day, $timetableId, $indx);

        echo "day={$day}\ttimetableId={$timetableId}\tvenue={$venue}\n";
        do {
            $arr = $this->get_timeslot($timetableId, $venue);
            echo date('Y-m-d H:i:s') . "\n";
            if (isset($arr['data'][0][1]) && strpos($arr['data'][0][1], '(剩余0)') === false) {
                $timeslot = $arr['data'][0][0];
                //var_dump($day, $venue, $timeslot, $timetableId);
                $arr = $this->order($day, $venue, $timeslot, $timetableId);
                $arr = $this->check_order($arr['data']['orderId']);
                if ($arr['data']['result'] != 'fail') {
                    $order_status = true;
                }
            }
            print_r($arr['data']);
            sleep(3);
        } while ($order_status != true);
    }
}

$PHPSESSID_list = array(
    'xxxx',
    'xxxxxx',
);
$day = '2022-08-23';

foreach ($PHPSESSID_list as $PHPSESSID) {
    $g = new GT($PHPSESSID);
    $g->gt_order($day);
}


