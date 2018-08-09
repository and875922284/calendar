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
function https_request($url,$data,$type){
    if($type=='json'){
        //json$_POST=json_decode(file_get_contents('php://input'),TRUE);
        $headers=array("Content-type:application/json;charset=UTF-8","Accept:application/json","Cache-Control:no-cache","Pragma:no-cache");
        $data=json_encode($data);
    }
    $curl=curl_init();
    curl_setopt($curl,CURLOPT_URL,$url);
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,FALSE);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,FALSE);
    if(!empty($data)){
    curl_setopt($curl,CURLOPT_POST,1);
    curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
    }
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($curl,CURLOPT_HTTPHEADER,$headers);
    $output=curl_exec($curl);
    curl_close($curl);
    return $output;
}
function getIsPlan($openId,$startTime,$endTime){
    //获取全部事件
    $all = [];
    //处理永不事件
    $normalThing = db('event')->where('openId',$openId)->where('loopType','永不')->distinct(true)->field('startStr')->select();
    foreach ($normalThing as $key => $value) {
        $startStr = $value['startStr'];
        array_push($all,$startStr);
    }
    //处理每天重复事件
    $dayThing = db('event')->where('openId',$openId)->where('loopType','每天')->select();
    foreach ($dayThing as $key => $value) {
        $startStr = $value['startStr'];
        $loopEnd = $value['loopEnd'];
        $LE_time = $value['LE_time'];
        $endTime1 = $endTime;
        if ($loopEnd == 'true' && $LE_time < $endTime) {
            $endTime1 = $LE_time;
        }
        $array = [];
        for ($i=$startStr; $i <= $endTime1; $i+=86400) {
            if ($i>=$startTime && $i<=$endTime1) {
                array_push($array,$i);
            }
        }
        $all = array_merge($all,$array);
        $all = array_unique($all);
    }
    //处理每个工作日重复事件
    $workThing = db('event')->where('openId',$openId)->where('loopType','每个工作日')->select();
    foreach ($workThing as $key => $value) {
        $startStr = $value['startStr'];
        $realStart = $value['realStart'];
        $loopEnd = $value['loopEnd'];
        $LE_time = $value['LE_time'];
        $endTime1 = $endTime;
        if ($loopEnd == 'true' && $LE_time < $endTime) {
            $endTime1 = $LE_time;
        }
        $array = [];
        for ($i=$startStr; $i <= $endTime1; $i+=86400) {
            if ($i>=$startTime && $i<=$endTime1) {
                $SD_time = $i+$realStart-$startStr;
                $week = date('w',$SD_time);
                if ($week!=0 && $week!=6) {
                    array_push($array,$i);
                }
            }
        }
        $all = array_merge($all,$array);
        $all = array_unique($all);
    }
    //处理每周重复事件
    $weekThing = db('event')->where('openId',$openId)->where('loopType','每周')->select();
    foreach ($weekThing as $key => $value) {
        $startStr = $value['startStr'];
        $loopEnd = $value['loopEnd'];
        $LE_time = $value['LE_time'];
        $endTime1 = $endTime;
        if ($loopEnd == 'true' && $LE_time < $endTime) {
            $endTime1 = $LE_time;
        }
        $array = [];
        for ($i=$startStr; $i <= $endTime1; $i+=604800) {
            $time = date('Y-m-d',$i);
            if ($i>=$startTime && $i<=$endTime1) {
                array_push($array,$i);
            }
        }
        $all = array_merge($all,$array);
        $all = array_unique($all);
    }
    //处理每月重复事件
    $monthThing = db('event')->where('openId',$openId)->where('loopType','每月')->select();
    foreach ($monthThing as $key => $value) {
        $year = $value['startYear'];
        $month = $value['startMonth'];
        $day = $value['startDay'];
        $startStr = $value['startStr'];
        $loopEnd = $value['loopEnd'];
        $LE_time = $value['LE_time'];
        $endTime1 = $endTime;
        if ($loopEnd == 'true' && $LE_time < $endTime) {
            $endTime1 = $LE_time;
        }
        $array = [];
        for ($i=0; $i < $startStr; $i++) {
            $bool = db('calendar')->where('year',$year)->where('month',$month)->where('day',$day)->find();
            if ($bool != null) {
                $time = strtotime($year.'-'.$month.'-'.$day);
                if ($time>=$startTime && $time<=$endTime1) {
                    array_push($array,$time);
                }else if($time>$endTime){
                    break;
                }
            }
            $month++;
            if ($month > 12) {
                $year++;
                $month -= 12;
            }
        }
        $all = array_merge($all,$array);
        $all = array_unique($all);
    }
    //处理每年重复事件
    $yearThing = db('event')->where('openId',$openId)->where('loopType','每年')->select();
    foreach ($yearThing as $key => $value) {
        $year = $value['startYear'];
        $month = $value['startMonth'];
        $day = $value['startDay'];
        $startStr = $value['startStr'];
        $loopEnd = $value['loopEnd'];
        $endTime1 = $endTime;
        $LE_time = $value['LE_time'];
        if ($loopEnd == 'true' && $LE_time < $endTime) {
            $endTime1 = $LE_time;
        }
        $array = [];
        for ($i=0; $i < $startStr; $i++) {
            $bool = db('calendar')->where('year',$year)->where('month',$month)->where('day',$day)->find();
            if ($bool != null) {
                $time = strtotime($year.'-'.$month.'-'.$day);
                if ($time>=$startTime && $time<=$endTime1) {
                    array_push($array,$time);
                }else if($time>$endTime){
                    break;
                }
            }
            $year++;
        }
        $all = array_merge($all,$array);
        $all = array_unique($all);
    }
    //处理自定义重复天
    $dayThing = db('event')->where('openId',$openId)->where('loopType','自定义')->where('UDL_unit','天')->select();
    foreach ($dayThing as $key => $value) {
        $startStr = $value['startStr'];
        $loopEnd = $value['loopEnd'];
        $LE_time = $value['LE_time'];
        $UDL_length = $value['UDL_length'];
        $endTime1 = $endTime;
        if ($loopEnd == 'true' && $LE_time < $endTime) {
            $endTime1 = $LE_time;
        }
        $array = [];
        for ($i=$startStr; $i <= $endTime1; $i+=(86400*$UDL_length)) {
            if ($i>=$startTime && $i<=$endTime1) {
                array_push($array,$i);
            }
        }
        $all = array_merge($all,$array);
        $all = array_unique($all);
    }
    //处理自定义周重复事件
    $weekThing = db('event')->where('openId',$openId)->where('loopType','自定义')->where('UDL_unit','周')->select();
    foreach ($weekThing as $key => $value) {
        $startStr = $value['startStr'];
        $realStart = $value['realStart'];
        $loopEnd = $value['loopEnd'];
        $LE_time = $value['LE_time'];
        $UDL_length = $value['UDL_length'];
        $endTime1 = $endTime;
        if ($loopEnd == 'true' && $LE_time < $endTime) {
            $endTime1 = $LE_time;
        }
        $array = [];
        for ($i=$startStr; $i <= $endTime1; $i+=(604800*$UDL_length)) {
            $time = date('Y-m-d',$i);
            if ($i>=$startTime && $i<=$endTime1) {
                array_push($array,$i);
            }
        }
        $all = array_merge($all,$array);
        $all = array_unique($all);
    }
    //处理自定义重复月
    $monthThing = db('event')->where('openId',$openId)->where('loopType','自定义')->where('UDL_unit','月')->select();
    foreach ($monthThing as $key => $value) {
        $year = $value['startYear'];
        $month = $value['startMonth'];
        $day = $value['startDay'];
        $startStr = $value['startStr'];
        $UDL_length = $value['UDL_length'];
        $LE_time = $value['LE_time'];
        $loopEnd = $value['loopEnd'];
        $endTime1 = $endTime;
        if ($loopEnd == 'true' && $LE_time < $endTime) {
            $endTime1 = $LE_time;
        }
        $array = [];
        for ($i=0; $i < $startStr; $i++) {
            $bool = db('calendar')->where('year',$year)->where('month',$month)->where('day',$day)->find();
            if ($bool != null) {
                $time = strtotime($year.'-'.$month.'-'.$day);
                if ($time>=$startTime && $time<=$endTime1) {
                    array_push($array,$time);
                }else if($time>$endTime){
                    break;
                }
            }
            $month+=$UDL_length;
            if ($month > 12) {
                $year++;
                $month -= 12;
            }
        }
        $all = array_merge($all,$array);
        $all = array_unique($all);
    }
    //自定义处理年
    $yearThing = db('event')->where('openId',$openId)->where('loopType','自定义')->where('UDL_unit','年')->select();
    foreach ($yearThing as $key => $value) {
        $year = $value['startYear'];
        $month = $value['startMonth'];
        $day = $value['startDay'];
        $startStr = $value['startStr'];
        $UDL_length = $value['UDL_length'];
        $endTime1 = $endTime;
        $loopEnd = $value['loopEnd'];
        $LE_time = $value['LE_time'];
        if ($loopEnd == 'true' && $LE_time < $endTime) {
            $endTime1 = $LE_time;
        }
        $array = [];
        for ($i=0; $i < $startStr; $i++) {
            $bool = db('calendar')->where('year',$year)->where('month',$month)->where('day',$day)->find();
            if ($bool != null) {
                $time = strtotime($year.'-'.$month.'-'.$day);
                if ($time>=$startTime && $time<=$endTime1) {
                    array_push($array,$time);
                }else if($time>$endTime){
                    break;
                }
            }
            $year+=$UDL_length;
        }
        $all = array_merge($all,$array);
        $all = array_unique($all);
    }
    return $all;
}
function getAllThings($openId,$startTime,$endTime){
    //获取全部事件
    $all = [];
    //永不重复事件
    $normalThing = db('event')->where('openId',$openId)->where('loopType','永不')->order('startStr esc,startTime esc')->select();
    $array = [];
    foreach ($normalThing as $key => $value) {
        $one = [
            'startStr'  =>  $value['startStr'],
            'startTime' =>  $value['startTime'],
            'endTime'   =>  $value['endTime'],
            'openId'    =>  $value['admin'],
            'title'     =>  $value['title'],
            'hour'      =>  $value['hour'],
            'eventId'   =>  $value['eventId'],
            'layer'     =>  0,
            'realStart' =>  $value['realStart'],
            'realEnd'   =>  $value['realEnd']
        ];
        array_push($array,$one);
    }
    $all = array_merge($all,$array);
    //每天重复事件
    $dayThing = db('event')->where('openId',$openId)->where('loopType','每天')->order('startStr esc,startTime esc')->select();
    foreach ($dayThing as $key => $value) {
        $startStr = $value['startStr'];
        $loopEnd = $value['loopEnd'];
        $LE_time = $value['LE_time'];
        $realStart = $value['realStart'];
        $realEnd = $value['realEnd'];
        $endTime1 = $endTime;
        if ($loopEnd == 'true' && $LE_time < $endTime) {
            $endTime1 = $LE_time;
        }
        $array = [];
        for ($i=$startStr; $i <= $endTime1; $i+=86400) {
            if ($i>=$startTime && $i<=$endTime1) {
                $SD_time = $i+$realStart-$startStr;
                $ED_time = $i+$realEnd-$startStr;
                $one = [
                    'startStr'  =>  $i,
                    'startTime' =>  $value['startTime'],
                    'endTime'   =>  $value['endTime'],
                    'openId'    =>  $value['admin'],
                    'title'     =>  $value['title'],
                    'hour'      =>  $value['hour'],
                    'eventId'   =>  $value['eventId'],
                    'layer'     =>  0,
                    'realStart' =>  $SD_time,
                    'realEnd'   =>  $ED_time
                ];
                array_push($array,$one);
            }
        }
        $all = array_merge($all,$array);
    }
    //处理自定义重复天
    $dayThing = db('event')->where('openId',$openId)->where('loopType','自定义')->where('UDL_unit','天')->order('startStr esc,startTime esc')->select();
    foreach ($dayThing as $key => $value) {
        $startStr = $value['startStr'];
        $loopEnd = $value['loopEnd'];
        $LE_time = $value['LE_time'];
        $UDL_length = $value['UDL_length'];
        $realStart = $value['realStart'];
        $realEnd = $value['realEnd'];
        $endTime1 = $endTime;
        if ($loopEnd == 'true' && $LE_time < $endTime) {
            $endTime1 = $LE_time;
        }
        $array = [];
        for ($i=$startStr; $i <= $endTime1; $i+=(86400*$UDL_length)) {
            if ($i>=$startTime && $i<=$endTime1) {
                $SD_time = $i+$realStart-$startStr;
                $ED_time = $i+$realEnd-$startStr;
                $one = [
                    'startStr'  =>  $i,
                    'startTime' =>  $value['startTime'],
                    'endTime'   =>  $value['endTime'],
                    'openId'    =>  $value['admin'],
                    'title'     =>  $value['title'],
                    'hour'      =>  $value['hour'],
                    'eventId'   =>  $value['eventId'],
                    'layer'     =>  0,
                    'realStart' =>  $SD_time,
                    'realEnd'   =>  $ED_time
                ];
                array_push($array,$one);
            }
        }
        $all = array_merge($all,$array);
    }
    //处理每个工作日重复事件
    $workThing = db('event')->where('openId',$openId)->where('loopType','每个工作日')->select();
    foreach ($workThing as $key => $value) {
        $startStr = $value['startStr'];
        $loopEnd = $value['loopEnd'];
        $LE_time = $value['LE_time'];
        $realStart = $value['realStart'];
        $realEnd = $value['realEnd'];
        $endTime1 = $endTime;
        if ($loopEnd == 'true' && $LE_time < $endTime) {
            $endTime1 = $LE_time;
        }
        $array = [];
        for ($i=$startStr; $i <= $endTime1; $i+=86400) {
            if ($i>=$startTime && $i<=$endTime1) {
                $SD_time = $i+$realStart-$startStr;
                $ED_time = $i+$realEnd-$startStr;
                $week = date('w',$SD_time);
                if ($week!=0 && $week!=6) {
                    $one = [
                        'startStr'  =>  $i,
                        'startTime' =>  $value['startTime'],
                        'endTime'   =>  $value['endTime'],
                        'openId'    =>  $value['admin'],
                        'title'     =>  $value['title'],
                        'hour'      =>  $value['hour'],
                        'eventId'   =>  $value['eventId'],
                        'layer'     =>  0,
                        'realStart' =>  $SD_time,
                        'realEnd'   =>  $ED_time
                    ];
                    array_push($array,$one);
                }
            }
        }
        $all = array_merge($all,$array);
    }
    //处理每周重复事件
    $weekThing = db('event')->where('openId',$openId)->where('loopType','每周')->select();
    foreach ($weekThing as $key => $value) {
        $startStr = $value['startStr'];
        $week = date('w',$startStr);
        $loopEnd = $value['loopEnd'];
        $LE_time = $value['LE_time'];
        $realStart = $value['realStart'];
        $realEnd = $value['realEnd'];
        $endTime1 = $endTime;
        if ($loopEnd == 'true' && $LE_time < $endTime) {
            $endTime1 = $LE_time;
        }
        $array = [];
        for ($i=$startStr; $i <= $endTime1; $i+=604800) {
            if ($i>=$startTime && $i<=$endTime1) {
                $SD_time = $i+$realStart-$startStr;
                $ED_time = $i+$realEnd-$startStr;
                $one = [
                    'startStr'  =>  $i,
                    'startTime' =>  $value['startTime'],
                    'endTime'   =>  $value['endTime'],
                    'openId'    =>  $value['admin'],
                    'title'     =>  $value['title'],
                    'hour'      =>  $value['hour'],
                    'eventId'   =>  $value['eventId'],
                    'layer'     =>  0,
                    'realStart' =>  $SD_time,
                    'realEnd'   =>  $ED_time
                ];
                array_push($array,$one);
            }
        }
        $all = array_merge($all,$array);
    }
    //处理自定义周重复事件
    $weekThing = db('event')->where('openId',$openId)->where('loopType','自定义')->where('UDL_unit','周')->select();
    foreach ($weekThing as $key => $value) {
        $startStr = $value['startStr'];
        $week = date('w',$startStr);
        $loopEnd = $value['loopEnd'];
        $LE_time = $value['LE_time'];
        $UDL_length = $value['UDL_length'];
        $realStart = $value['realStart'];
        $realEnd = $value['realEnd'];
        $endTime1 = $endTime;
        if ($loopEnd == 'true' && $LE_time < $endTime) {
            $endTime1 = $LE_time;
        }
        $array = [];
        for ($i=$startStr; $i <= $endTime1; $i+=(604800*$UDL_length)) {
            $SD_time = $i+$realStart-$startStr;
            $ED_time = $i+$realEnd-$startStr;
            if ($i>=$startTime && $i<=$endTime1) {
                $one = [
                    'startStr'  =>  $i,
                    'startTime' =>  $value['startTime'],
                    'endTime'   =>  $value['endTime'],
                    'openId'    =>  $value['admin'],
                    'title'     =>  $value['title'],
                    'hour'      =>  $value['hour'],
                    'eventId'   =>  $value['eventId'],
                    'layer'     =>  0,
                    'realStart' =>  $SD_time,
                    'realEnd'   =>  $ED_time
                ];
                array_push($array,$one);
            }
        }
        $all = array_merge($all,$array);
    }
    //处理每月重复事件
    $monthThing = db('event')->where('openId',$openId)->where('loopType','每月')->select();
    foreach ($monthThing as $key => $value) {
        $year = $value['startYear'];
        $month = $value['startMonth'];
        $loopEnd = $value['loopEnd'];
        $day = $value['startDay'];
        $startStr = $value['startStr'];
        $LE_time = $value['LE_time'];
        $realStart = $value['realStart'];
        $realEnd = $value['realEnd'];
        $endTime1 = $endTime;
        if ($loopEnd == 'true' && $LE_time < $endTime) {
            $endTime1 = $LE_time;
        }
        $array = [];
        for ($i=0; $i < $startStr; $i++) {
            $bool = db('calendar')->where('year',$year)->where('month',$month)->where('day',$day)->find();
            if ($bool != null) {
                $time = strtotime($year.'-'.$month.'-'.$day);
                if ($time>=$startTime && $time<=$endTime1) {
                    $SD_time = $time+$realStart-$startStr;
                    $ED_time = $time+$realEnd-$startStr;
                    $one = [
                        'startStr'  =>  $time,
                        'startTime' =>  $value['startTime'],
                        'endTime'   =>  $value['endTime'],
                        'openId'    =>  $value['admin'],
                        'title'     =>  $value['title'],
                        'hour'      =>  $value['hour'],
                        'eventId'   =>  $value['eventId'],
                        'layer'     =>  0,
                        'realStart' =>  $SD_time,
                        'realEnd'   =>  $ED_time
                    ];
                    array_push($array,$one);
                }else if($time>$endTime){
                    break;
                }
            }
            $month++;
            if ($month > 12) {
                $year++;
                $month -= 12;
            }
        }
        $all = array_merge($all,$array);
    }
    //处理自定义重复月
    $monthThing = db('event')->where('openId',$openId)->where('loopType','自定义')->where('UDL_unit','月')->select();
    foreach ($monthThing as $key => $value) {
        $year = $value['startYear'];
        $month = $value['startMonth'];
        $day = $value['startDay'];
        $startStr = $value['startStr'];
        $UDL_length = $value['UDL_length'];
        $loopEnd = $value['loopEnd'];
        $realStart = $value['realStart'];
        $realEnd = $value['realEnd'];
        $LE_time = $value['LE_time'];
        $endTime1 = $endTime;
        if ($loopEnd == 'true' && $LE_time < $endTime) {
            $endTime1 = $LE_time;
        }
        $array = [];
        for ($i=0; $i < $startStr; $i++) {
            $bool = db('calendar')->where('year',$year)->where('month',$month)->where('day',$day)->find();
            if ($bool != null) {
                $time = strtotime($year.'-'.$month.'-'.$day);
                if ($time>=$startTime && $time<=$endTime1) {
                    $SD_time = $time+$realStart-$startStr;
                    $ED_time = $time+$realEnd-$startStr;
                    $one = [
                        'startStr'  =>  $time,
                        'startTime' =>  $value['startTime'],
                        'endTime'   =>  $value['endTime'],
                        'openId'    =>  $value['admin'],
                        'title'     =>  $value['title'],
                        'hour'      =>  $value['hour'],
                        'eventId'   =>  $value['eventId'],
                        'layer'     =>  0,
                        'realStart' =>  $SD_time,
                        'realEnd'   =>  $ED_time
                    ];
                    array_push($array,$one);
                }else if($time>$endTime){
                    break;
                }
            }
            $month+=$UDL_length;
            if ($month > 12) {
                $year++;
                $month -= 12;
            }
        }
        $all = array_merge($all,$array);
    }
    //处理每年重复事件
    $yearThing = db('event')->where('openId',$openId)->where('loopType','每年')->select();
    foreach ($yearThing as $key => $value) {
        $year = $value['startYear'];
        $month = $value['startMonth'];
        $day = $value['startDay'];
        $startStr = $value['startStr'];
        $loopEnd = $value['loopEnd'];
        $realStart = $value['realStart'];
        $realEnd = $value['realEnd'];
        $endTime1 = $endTime;
        $LE_time = $value['LE_time'];
        if ($loopEnd == 'true' && $LE_time < $endTime) {
            $endTime1 = $LE_time;
        }
        $array = [];
        for ($i=0; $i < $startStr; $i++) {
            $bool = db('calendar')->where('year',$year)->where('month',$month)->where('day',$day)->find();
            if ($bool != null) {
                $time = strtotime($year.'-'.$month.'-'.$day);
                if ($time>=$startTime && $time<=$endTime1) {
                    $SD_time = $time+$realStart-$startStr;
                    $ED_time = $time+$realEnd-$startStr;
                    $one = [
                        'startStr'  =>  $time,
                        'startTime' =>  $value['startTime'],
                        'endTime'   =>  $value['endTime'],
                        'openId'    =>  $value['admin'],
                        'title'     =>  $value['title'],
                        'hour'      =>  $value['hour'],
                        'eventId'   =>  $value['eventId'],
                        'layer'     =>  0,
                        'realStart' =>  $SD_time,
                        'realEnd'   =>  $ED_time
                    ];
                    array_push($array,$one);
                }else if($time>$endTime){
                    break;
                }
            }
            $year++;
        }
        $all = array_merge($all,$array);
    }
    //自定义处理年
    $yearThing = db('event')->where('openId',$openId)->where('loopType','自定义')->where('UDL_unit','年')->select();
    foreach ($yearThing as $key => $value) {
        $year = $value['startYear'];
        $month = $value['startMonth'];
        $day = $value['startDay'];
        $startStr = $value['startStr'];
        $UDL_length = $value['UDL_length'];
        $realStart = $value['realStart'];
        $realEnd = $value['realEnd'];
        $endTime1 = $endTime;
        $LE_time = $value['LE_time'];
        $loopEnd = $value['loopEnd'];
        if ($loopEnd == 'true' && $LE_time < $endTime) {
            $endTime1 = $LE_time;
        }
        $array = [];
        for ($i=0; $i < $startStr; $i++) {
            $bool = db('calendar')->where('year',$year)->where('month',$month)->where('day',$day)->find();
            if ($bool != null) {
                $time = strtotime($year.'-'.$month.'-'.$day);
                if ($time>=$startTime && $time<=$endTime1) {
                    $SD_time = $time+$realStart-$startStr;
                    $ED_time = $time+$realEnd-$startStr;
                    $one = [
                        'startStr'  =>  $time,
                        'startTime' =>  $value['startTime'],
                        'endTime'   =>  $value['endTime'],
                        'openId'    =>  $value['admin'],
                        'title'     =>  $value['title'],
                        'hour'      =>  $value['hour'],
                        'eventId'   =>  $value['eventId'],
                        'layer'     =>  0,
                        'realStart' =>  $SD_time,
                        'realEnd'   =>  $ED_time
                    ];
                    array_push($array,$one);
                }else if($time>$endTime){
                    break;
                }
            }
            $year+=$UDL_length;
        }
        $all = array_merge($all,$array);
    }
    return $all;
}
function lunar_calendar ($month, $year)
        {
            $lnlunarcalendar = array(
             'tiangan' => array("未知", "甲", "乙", "丙", "丁", "戊", "己", "庚", "辛", "壬", "癸"),
             'dizhi' => array("未知", "子年（鼠）", "丑年（牛）", "寅年（虎）", "卯年（兔）", "辰年（龙）",
             "巳年（蛇）", "午年（马）", "未年（羊）", "申年（猴）", "酉年（鸡）", "戌年（狗）", "亥年（猪）"),
             'month' => array("闰", "正", "二", "三", "四", "五", "六",
             "七", "八", "九", "十", "十一", "十二", "月"),
             'day' => array("未知", "初一", "初二", "初三", "初四", "初五", "初六", "初七", "初八", "初九", "初十",
             "十一", "十二", "十三", "十四", "十五", "十六", "十七", "十八", "十九", "二十",
             "廿一", "廿二", "廿三", "廿四", "廿五", "廿六", "廿七", "廿八", "廿九", "三十")
            );
             /**
             * Lunar calendar 博大精深的农历
             * 原始数据和算法思路来自 S&S
             */
             /*
             农历每月的天数。
             每个元素为一年。每个元素中的数据为：
             [0]是闰月在哪个月，0为无闰月；
             [1]到[13]是每年12或13个月的每月天数；
             [14]是当年的天干次序，
             [15]是当年的地支次序
             */
             $everymonth = array(
             0 => array(8, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 29, 30, 7, 1),
             1 => array(0, 29, 30, 29, 29, 30, 29, 30, 29, 30, 30, 30, 29, 0, 8, 2),
             2 => array(0, 30, 29, 30, 29, 29, 30, 29, 30, 29, 30, 30, 30, 0, 9, 3),
             3 => array(5, 29, 30, 29, 30, 29, 29, 30, 29, 29, 30, 30, 29, 30, 10, 4),
             4 => array(0, 30, 30, 29, 30, 29, 29, 30, 29, 29, 30, 30, 29, 0, 1, 5),
             5 => array(0, 30, 30, 29, 30, 30, 29, 29, 30, 29, 30, 29, 30, 0, 2, 6),
             6 => array(4, 29, 30, 30, 29, 30, 29, 30, 29, 30, 29, 30, 29, 30, 3, 7),
             7 => array(0, 29, 30, 29, 30, 29, 30, 30, 29, 30, 29, 30, 29, 0, 4, 8),
             8 => array(0, 30, 29, 29, 30, 30, 29, 30, 29, 30, 30, 29, 30, 0, 5, 9),
             9 => array(2, 29, 30, 29, 29, 30, 29, 30, 29, 30, 30, 30, 29, 30, 6, 10),
             10 => array(0, 29, 30, 29, 29, 30, 29, 30, 29, 30, 30, 30, 29, 0, 7, 11),
             11 => array(6, 30, 29, 30, 29, 29, 30, 29, 29, 30, 30, 29, 30, 30, 8, 12),
             12 => array(0, 30, 29, 30, 29, 29, 30, 29, 29, 30, 30, 29, 30, 0, 9, 1),
             13 => array(0, 30, 30, 29, 30, 29, 29, 30, 29, 29, 30, 29, 30, 0, 10, 2),
             14 => array(5, 30, 30, 29, 30, 29, 30, 29, 30, 29, 30, 29, 29, 30, 1, 3),
             15 => array(0, 30, 29, 30, 30, 29, 30, 29, 30, 29, 30, 29, 30, 0, 2, 4),
             16 => array(0, 29, 30, 29, 30, 29, 30, 30, 29, 30, 29, 30, 29, 0, 3, 5),
             17 => array(2, 30, 29, 29, 30, 29, 30, 30, 29, 30, 30, 29, 30, 29, 4, 6),
             18 => array(0, 30, 29, 29, 30, 29, 30, 29, 30, 30, 29, 30, 30, 0, 5, 7),
             19 => array(7, 29, 30, 29, 29, 30, 29, 29, 30, 30, 29, 30, 30, 30, 6, 8),
             20 => array(0, 29, 30, 29, 29, 30, 29, 29, 30, 30, 29, 30, 30, 0, 7, 9),
             21 => array(0, 30, 29, 30, 29, 29, 30, 29, 29, 30, 29, 30, 30, 0, 8, 10),
             22 => array(5, 30, 29, 30, 30, 29, 29, 30, 29, 29, 30, 29, 30, 30, 9, 11),
             23 => array(0, 29, 30, 30, 29, 30, 29, 30, 29, 29, 30, 29, 30, 0, 10, 12),
             24 => array(0, 29, 30, 30, 29, 30, 30, 29, 30, 29, 30, 29, 29, 0, 1, 1),
             25 => array(4, 30, 29, 30, 29, 30, 30, 29, 30, 30, 29, 30, 29, 30, 2, 2),
             26 => array(0, 29, 29, 30, 29, 30, 29, 30, 30, 29, 30, 30, 29, 0, 3, 3),
             27 => array(0, 30, 29, 29, 30, 29, 30, 29, 30, 29, 30, 30, 30, 0, 4, 4),
             28 => array(2, 29, 30, 29, 29, 30, 29, 29, 30, 29, 30, 30, 30, 30, 5, 5),
             29 => array(0, 29, 30, 29, 29, 30, 29, 29, 30, 29, 30, 30, 30, 0, 6, 6),
             30 => array(6, 29, 30, 30, 29, 29, 30, 29, 29, 30, 29, 30, 30, 29, 7, 7),
             31 => array(0, 30, 30, 29, 30, 29, 30, 29, 29, 30, 29, 30, 29, 0, 8, 8),
             32 => array(0, 30, 30, 30, 29, 30, 29, 30, 29, 29, 30, 29, 30, 0, 9, 9),
             33 => array(5, 29, 30, 30, 29, 30, 30, 29, 30, 29, 30, 29, 29, 30, 10, 10),
             34 => array(0, 29, 30, 29, 30, 30, 29, 30, 29, 30, 30, 29, 30, 0, 1, 11),
             35 => array(0, 29, 29, 30, 29, 30, 29, 30, 30, 29, 30, 30, 29, 0, 2, 12),
             36 => array(3, 30, 29, 29, 30, 29, 29, 30, 30, 29, 30, 30, 30, 29, 3, 1),
             37 => array(0, 30, 29, 29, 30, 29, 29, 30, 29, 30, 30, 30, 29, 0, 4, 2),
             38 => array(7, 30, 30, 29, 29, 30, 29, 29, 30, 29, 30, 30, 29, 30, 5, 3),
             39 => array(0, 30, 30, 29, 29, 30, 29, 29, 30, 29, 30, 29, 30, 0, 6, 4),
             40 => array(0, 30, 30, 29, 30, 29, 30, 29, 29, 30, 29, 30, 29, 0, 7, 5),
             41 => array(6, 30, 30, 29, 30, 30, 29, 30, 29, 29, 30, 29, 30, 29, 8, 6),
             42 => array(0, 30, 29, 30, 30, 29, 30, 29, 30, 29, 30, 29, 30, 0, 9, 7),
             43 => array(0, 29, 30, 29, 30, 29, 30, 30, 29, 30, 29, 30, 29, 0, 10, 8),
             44 => array(4, 30, 29, 30, 29, 30, 29, 30, 29, 30, 30, 29, 30, 30, 1, 9),
             45 => array(0, 29, 29, 30, 29, 29, 30, 29, 30, 30, 30, 29, 30, 0, 2, 10),
             46 => array(0, 30, 29, 29, 30, 29, 29, 30, 29, 30, 30, 29, 30, 0, 3, 11),
             47 => array(2, 30, 30, 29, 29, 30, 29, 29, 30, 29, 30, 29, 30, 30, 4, 12),
             48 => array(0, 30, 29, 30, 29, 30, 29, 29, 30, 29, 30, 29, 30, 0, 5, 1),
             49 => array(7, 30, 29, 30, 30, 29, 30, 29, 29, 30, 29, 30, 29, 30, 6, 2),
             50 => array(0, 29, 30, 30, 29, 30, 30, 29, 29, 30, 29, 30, 29, 0, 7, 3),
             51 => array(0, 30, 29, 30, 30, 29, 30, 29, 30, 29, 30, 29, 30, 0, 8, 4),
             52 => array(5, 29, 30, 29, 30, 29, 30, 29, 30, 30, 29, 30, 29, 30, 9, 5),
             53 => array(0, 29, 30, 29, 29, 30, 30, 29, 30, 30, 29, 30, 29, 0, 10, 6),
             54 => array(0, 30, 29, 30, 29, 29, 30, 29, 30, 30, 29, 30, 30, 0, 1, 7),
             55 => array(3, 29, 30, 29, 30, 29, 29, 30, 29, 30, 29, 30, 30, 30, 2, 8),
             56 => array(0, 29, 30, 29, 30, 29, 29, 30, 29, 30, 29, 30, 30, 0, 3, 9),
             57 => array(8, 30, 29, 30, 29, 30, 29, 29, 30, 29, 30, 29, 30, 29, 4, 10),
             58 => array(0, 30, 30, 30, 29, 30, 29, 29, 30, 29, 30, 29, 30, 0, 5, 11),
             59 => array(0, 29, 30, 30, 29, 30, 29, 30, 29, 30, 29, 30, 29, 0, 6, 12),
             60 => array(6, 30, 29, 30, 29, 30, 30, 29, 30, 29, 30, 29, 30, 29, 7, 1),
             61 => array(0, 30, 29, 30, 29, 30, 29, 30, 30, 29, 30, 29, 30, 0, 8, 2),
             62 => array(0, 29, 30, 29, 29, 30, 29, 30, 30, 29, 30, 30, 29, 0, 9, 3),
             63 => array(4, 30, 29, 30, 29, 29, 30, 29, 30, 29, 30, 30, 30, 29, 10, 4),
             64 => array(0, 30, 29, 30, 29, 29, 30, 29, 30, 29, 30, 30, 30, 0, 1, 5),
             65 => array(0, 29, 30, 29, 30, 29, 29, 30, 29, 29, 30, 30, 29, 0, 2, 6),
             66 => array(3, 30, 30, 30, 29, 30, 29, 29, 30, 29, 29, 30, 30, 29, 3, 7),
             67 => array(0, 30, 30, 29, 30, 30, 29, 29, 30, 29, 30, 29, 30, 0, 4, 8),
             68 => array(7, 29, 30, 29, 30, 30, 29, 30, 29, 30, 29, 30, 29, 30, 5, 9),
             69 => array(0, 29, 30, 29, 30, 29, 30, 30, 29, 30, 29, 30, 29, 0, 6, 10),
             70 => array(0, 30, 29, 29, 30, 29, 30, 30, 29, 30, 30, 29, 30, 0, 7, 11),
             71 => array(5, 29, 30, 29, 29, 30, 29, 30, 29, 30, 30, 30, 29, 30, 8, 12),
             72 => array(0, 29, 30, 29, 29, 30, 29, 30, 29, 30, 30, 29, 30, 0, 9, 1),
             73 => array(0, 30, 29, 30, 29, 29, 30, 29, 29, 30, 30, 29, 30, 0, 10, 2),
             74 => array(4, 30, 30, 29, 30, 29, 29, 30, 29, 29, 30, 30, 29, 30, 1, 3),
             75 => array(0, 30, 30, 29, 30, 29, 29, 30, 29, 29, 30, 29, 30, 0, 2, 4),
             76 => array(8, 30, 30, 29, 30, 29, 30, 29, 30, 29, 29, 30, 29, 30, 3, 5),
             77 => array(0, 30, 29, 30, 30, 29, 30, 29, 30, 29, 30, 29, 29, 0, 4, 6),
             78 => array(0, 30, 29, 30, 30, 29, 30, 30, 29, 30, 29, 30, 29, 0, 5, 7),
             79 => array(6, 30, 29, 29, 30, 29, 30, 30, 29, 30, 30, 29, 30, 29, 6, 8),
             80 => array(0, 30, 29, 29, 30, 29, 30, 29, 30, 30, 29, 30, 30, 0, 7, 9),
             81 => array(0, 29, 30, 29, 29, 30, 29, 29, 30, 30, 29, 30, 30, 0, 8, 10),
             82 => array(4, 30, 29, 30, 29, 29, 30, 29, 29, 30, 29, 30, 30, 30, 9, 11),
             83 => array(0, 30, 29, 30, 29, 29, 30, 29, 29, 30, 29, 30, 30, 0, 10, 12),
             84 => array(10, 30, 29, 30, 30, 29, 29, 30, 29, 29, 30, 29, 30, 30, 1, 1),
             85 => array(0, 29, 30, 30, 29, 30, 29, 30, 29, 29, 30, 29, 30, 0, 2, 2),
             86 => array(0, 29, 30, 30, 29, 30, 30, 29, 30, 29, 30, 29, 29, 0, 3, 3),
             87 => array(6, 30, 29, 30, 29, 30, 30, 29, 30, 30, 29, 30, 29, 29, 4, 4),
             88 => array(0, 30, 29, 30, 29, 30, 29, 30, 30, 29, 30, 30, 29, 0, 5, 5),
             89 => array(0, 30, 29, 29, 30, 29, 29, 30, 30, 29, 30, 30, 30, 0, 6, 6),
             90 => array(5, 29, 30, 29, 29, 30, 29, 29, 30, 29, 30, 30, 30, 30, 7, 7),
             91 => array(0, 29, 30, 29, 29, 30, 29, 29, 30, 29, 30, 30, 30, 0, 8, 8),
             92 => array(0, 29, 30, 30, 29, 29, 30, 29, 29, 30, 29, 30, 30, 0, 9, 9),
             93 => array(3, 29, 30, 30, 29, 30, 29, 30, 29, 29, 30, 29, 30, 29, 10, 10),
             94 => array(0, 30, 30, 30, 29, 30, 29, 30, 29, 29, 30, 29, 30, 0, 1, 11),
             95 => array(8, 29, 30, 30, 29, 30, 29, 30, 30, 29, 29, 30, 29, 30, 2, 12),
             96 => array(0, 29, 30, 29, 30, 30, 29, 30, 29, 30, 30, 29, 29, 0, 3, 1),
             97 => array(0, 30, 29, 30, 29, 30, 29, 30, 30, 29, 30, 30, 29, 0, 4, 2),
             98 => array(5, 30, 29, 29, 30, 29, 29, 30, 30, 29, 30, 30, 29, 30, 5, 3),
             99 => array(0, 30, 29, 29, 30, 29, 29, 30, 29, 30, 30, 30, 29, 0, 6, 4),
             100 => array(0, 30, 30, 29, 29, 30, 29, 29, 30, 29, 30, 30, 29, 0, 7, 5),
             101 => array(4, 30, 30, 29, 30, 29, 30, 29, 29, 30, 29, 30, 29, 30, 8, 6),
             102 => array(0, 30, 30, 29, 30, 29, 30, 29, 29, 30, 29, 30, 29, 0, 9, 7),
             103 => array(0, 30, 30, 29, 30, 30, 29, 30, 29, 29, 30, 29, 30, 0, 10, 8),
             104 => array(2, 29, 30, 29, 30, 30, 29, 30, 29, 30, 29, 30, 29, 30, 1, 9),
             105 => array(0, 29, 30, 29, 30, 29, 30, 30, 29, 30, 29, 30, 29, 0, 2, 10),
             106 => array(7, 30, 29, 30, 29, 30, 29, 30, 29, 30, 30, 29, 30, 30, 3, 11),
             107 => array(0, 29, 29, 30, 29, 29, 30, 29, 30, 30, 30, 29, 30, 0, 4, 12),
             108 => array(0, 30, 29, 29, 30, 29, 29, 30, 29, 30, 30, 29, 30, 0, 5, 1),
             109 => array(5, 30, 30, 29, 29, 30, 29, 29, 30, 29, 30, 29, 30, 30, 6, 2),
             110 => array(0, 30, 29, 30, 29, 30, 29, 29, 30, 29, 30, 29, 30, 0, 7, 3),
             111 => array(0, 30, 29, 30, 30, 29, 30, 29, 29, 30, 29, 30, 29, 0, 8, 4),
             112 => array(4, 30, 29, 30, 30, 29, 30, 29, 30, 29, 30, 29, 30, 29, 9, 5),
             113 => array(0, 30, 29, 30, 29, 30, 30, 29, 30, 29, 30, 29, 30, 0, 10, 6),
             114 => array(9, 29, 30, 29, 30, 29, 30, 29, 30, 30, 29, 30, 29, 30, 1, 7),
             115 => array(0, 29, 30, 29, 29, 30, 29, 30, 30, 30, 29, 30, 29, 0, 2, 8),
             116 => array(0, 30, 29, 30, 29, 29, 30, 29, 30, 30, 29, 30, 30, 0, 3, 9),
             117 => array(6, 29, 30, 29, 30, 29, 29, 30, 29, 30, 29, 30, 30, 30, 4, 10),
             118 => array(0, 29, 30, 29, 30, 29, 29, 30, 29, 30, 29, 30, 30, 0, 5, 11),
             119 => array(0, 30, 29, 30, 29, 30, 29, 29, 30, 29, 29, 30, 30, 0, 6, 12),
             120 => array(4, 29, 30, 30, 30, 29, 30, 29, 29, 30, 29, 30, 29, 30, 7, 1)
             );
             $mten = $lnlunarcalendar['tiangan'];// 农历天干
             $mtwelve = $lnlunarcalendar['dizhi'];// 农历地支
             $mmonth = $lnlunarcalendar['month'];// 农历月份
             $mday = $lnlunarcalendar['day'];// 农历日
             // 阳历总天数 至1900年12月21日
             $total = 69 * 365 + 17 + 11;
             //1970年1月1日前的就不算了
             if ($year == "" || $month == "" || ($year < 1970 or $year > 2020)) return ''; //超出这个范围不计算
             // 计算到所求日期阳历的总天数-自1900年12月21日始
             for ($y = 1970; $y < $year;$y++) {// 先算年的和
             $total += 365;
             if ($y % 4 == 0) $total ++;
             }
             // 再加当年的几个月
             $total += gmdate("z", gmmktime(0, 0, 0, $month, 1, $year));
             // 用农历的天数累加来判断是否超过阳历的天数
             $flag1 = 0; //判断跳出循环的条件
             $lcj = 0;
             while ($lcj <= 120) {
             $lci = 1;
             while ($lci <= 13) {
              @$mtotal += $everymonth[$lcj][$lci];
              if ($mtotal >= $total) {
              $flag1 = 1;
              break;
              }
              $lci++;
             }
             if ($flag1 == 1) break;
             $lcj++;
             }
             // 由上，得到的 $lci 为当前农历月， $lcj 为当前农历年
             // 计算所求月份1号的农历日期
             $fisrtdaylunar = $everymonth[$lcj][$lci] - ($mtotal - $total);
             $results['year'] = $mten[$everymonth[$lcj][14]] . $mtwelve[$everymonth[$lcj][15]]; //当前是什么年
             $daysthismonth = gmdate("t", gmmktime(0, 0, 0, $month, 1, $year)); //当前月共几天
             $op = 1;
             for ($i = 1; $i <= $daysthismonth; $i++) {
             $possiblelunarday = $fisrtdaylunar + $op-1; //理论上叠加后的农历日
             if ($possiblelunarday <= $everymonth[$lcj][$lci]) { // 在本月的天数范畴内
              $results[$i] = $mday[$possiblelunarday];
              $op += 1;
             }
             else { // 不在本月的天数范畴内
              $results[$i] = $mday[1]; //退回到1日
              $fisrtdaylunar = 1;
              $op = 2;
              $curmonthnum = ($everymonth[$lcj][0] != 0) ? 13 : 12; //当年有几个月
              if ($lci + 1 > $curmonthnum) { // 第13/14个月了，转到下一年
              $lci = 1;
              $lcj = $lcj + 1;
              // 换年头了，把新一年的天干地支也写上
              $results['year'] .= '/' . $mten[$everymonth[$lcj][14]] . $mtwelve[$everymonth[$lcj][15]];
              }
              else { // 还在这年里
              $lci = $lci + 1;
              $lcj = $lcj;
              }
             }
             if ($results[$i] == $mday[1]) { // 每月的初一应该显示当月是什么月
              if ($everymonth[$lcj][0] != 0) { // 有闰月的年
              $monthss = ($lci > $everymonth[$lcj][0]) ? ($lci-1) : $lci; //闰月后的月数-1
              if ($lci == $everymonth[$lcj][0] + 1) { // 这个月正好是闰月
               $monthssshow = $mmonth[0] . $mmonth[$monthss]; //前面加个闰字
               $runyue = 1;
              }
              else {
               $monthssshow = $mmonth[$monthss];
              }
              }
              else {
              $monthss = $lci;
              $monthssshow = $mmonth[$monthss];
              }
              if ($monthss <= 10 && @$runyue != 1){ //只有1个字的月加上‘月'字
              $monthssshow .= $mmonth[13];
              }
              $results[$i] = $monthssshow;
             }
             }
             return $results;
        }
/**
 * Created by PhpStorm.
 * project： wordpress-blog
 * User: BrainWang
 * Author_URL: http://wangbaiyuan.cn
 * Date: 2015/12/25
 * Time: 11:45
 */
