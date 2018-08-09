<?php
namespace app\index\model;
use think\Model;
use think\Db;
class All_Model extends Model
{   //组成日历遍历数组类
    public function for_month(){
        $year = date('Y');
        $month = date('m');
        $Lmonth = [];
        //查询由当日开始的月份,并组成前3后6的数组"Lmonth",并解决跨年问题
        for ($i=-3; $i < 7; $i++) {
            if (($month+$i)<=0) {
                array_push($Lmonth, ($year-1).'-'.($month+$i+12).'-'.'1');
            }else if(($month+$i)>=13){
                array_push($Lmonth, ($year+1).'-'.($month+$i-12).'-'.'1');
            }else{
                array_push($Lmonth, $year.'-'.($month+$i).'-'.'1');
            }
        }
        $Rmonth = [];
        $Weeks = [];
        //'Rmonth'是表示这些月份有多少天,'weeks'是这些月份的1号分别是周几
        foreach ($Lmonth as $key => $value) {
            $days = date('t', strtotime($value));
            $first = date('w', strtotime($value));
            array_push($Rmonth,$days);
            array_push($Weeks,$first);
        }
        //将'Rmonth'与'Lmonth'组合成需要的循环数组'formonth'
        $M = array_combine($Lmonth, $Rmonth);
        $for_month = [
            'weeks'     =>  $Weeks,
            'formonth'  =>  $M
        ];
        return $for_month;
    }
    //根据循环数组算出开始和结束时间戳类
    public function start_and_end($formonth){
        //利用formonth数组查询开始月份的第一天时间戳和结束月份最后一天的时间戳
        $formonth1 = $formonth;
        $formonth = array_keys($formonth);
        $startday = reset($formonth);
        $startday = strtotime($startday);
        $endmonth = end($formonth);
        $endmonth = date('Y-m',strtotime($endmonth));
        $endday = end($formonth1);
        $endday = $endmonth.'-'.$endday;
        $endday = strtotime($endday);
        $array = [$startday,$endday];
        return $array;
    }
    //组合日历数组类
    public function isplan($formonth,$startday,$endday,$openId){
        $i = 0;
        foreach ($formonth as $key => $value) {
            $year = date('Y',strtotime($key));
            $month = date('m',strtotime($key));
            $data[$i] = db('calendar')->where('year',$year)->where('month',$month)->select();
            $i++;
        }
        unset($i);
        $event = getIsPlan($openId,$startday,$endday);
        foreach ($data as $key => $value) {
            foreach ($value as $k => $v) {
                foreach ($event as $k1 => $v1) {
                    if ($v1 == $v['time']) {
                        $data[$key][$k]['isplan'] = 1;
                        unset($event[$k1]);
                        break;
                    }
                }
            }
        }
        return $data;
    }
    //组合事件数组类
    public function allthings($openId,$startDay,$endDay){
        $allthings = [];
        //根据开始和结束时间查询当前用户的所有事件
        $event = getAllThings($openId,$startDay,$endDay);
        if (count($event)!=0) {$event = $this->twoSort($event,'startTime');}
        //根据开始时间和结束时间组成事件数组
        for ($i=$startDay; $i <= $endDay; $i+=86400) { 
            $year = date('Y',$i);
            $month = date('m',$i);
            $day = date('d',$i);
            $things = [];
            $endtime = [];
            foreach ($event as $key => $value) {
                if ($value['startStr'] == $i) {
                    array_push($things,$value);
                }
            }
            if (count($things)>1) {
                foreach ($things as $key => $value) {
                    if (count($endtime) == 0) {
                        $end = $value['endTime'];
                        array_push($endtime, $end);
                    }else{
                        if ($value['startTime'] < end($endtime)) {
                            if (count($endtime)>=5) {
                                $things[$key]['layer'] = 4;
                            } else {
                                $things[$key]['layer'] = count($endtime);
                            }
                            array_push($endtime,$value['endTime']);
                        }else{
                            foreach ($endtime as $k => $v) {
                                if ($value['startTime']>=$v) {
                                    array_pop($endtime);
                                }
                            }
                            if (count($endtime)>=5) {
                                $things[$key]['layer'] = 4;
                            } else {
                                $things[$key]['layer'] = count($endtime);
                            }
                            array_push($endtime,$value['endTime']);
                        }
                    }
                }
            }
            $oneday = [
                'year'  =>  $year,
                'month' =>  $month,
                'day'   =>  $day,
                'things'=>  $things
            ];
            array_push($allthings,$oneday);
        }
        return $allthings;
    }
    //无限滚动数组组成类
    public function postTime($year,$month,$type){
        $Lmonth = [];
        if ($type != 'next') {
            $s = -4;
            $e = 1;
        }else{
            $s = 0;
            $e = 5;
        }
        for ($i=$s; $i < $e; $i++) {
            if (($month+$i)<=0) {
                array_push($Lmonth, ($year-1).'-'.($month+$i+12).'-'.'1');
            }else if(($month+$i)>=13){
                array_push($Lmonth, ($year+1).'-'.($month+$i-12).'-'.'1');
            }else{
                array_push($Lmonth, $year.'-'.($month+$i).'-'.'1');
            }
        }
        $Weeks = [];
        //'Rmonth'是表示这些月份有多少天,'weeks'是这些月份的1号分别是周几
        foreach ($Lmonth as $key => $value) {
            $first = date('w', strtotime($value));
            array_push($Weeks,$first);
        }
        $startDay = strtotime($Lmonth[0]);
        $endDay = end($Lmonth);
        $endDay = date('t',strtotime($endDay))*86400 + strtotime($endDay);
        $a['startDay'] = $startDay;
        $a['endDay'] = $endDay;
        $a['month'] = $Lmonth;
        $a['weeks'] = $Weeks;
        return $a;
    }
    //无限滚动返回类
    public function IS($openId,$postTime){
        $startDay = $postTime['startDay'];
        $endDay = $postTime['endDay'];
        $month = $postTime['month'];
        $weeks = $postTime['weeks'];
        //后面
        foreach ($month as $key => $value) {
            $year = date('Y',strtotime($value));
            $month = date('m',strtotime($value));
            $allmonth[$key] = db('calendar')->where('year',$year)->where('month',$month)->select();
        }
        $event = getIsPlan($openId,$startDay,$endDay);
        foreach ($allmonth as $key => $value) {
            # code...
            foreach ($value as $k => $v) {
                foreach ($event as $k1 => $v1) {
                    if ($v1 == $v['time']) {
                        $allmonth[$key][$k]['isplan'] = 1;
                        unset($event[$k1]);
                        break;
                    }
                }
            }
        }
        $all_month['weeks'] = $weeks;
        $all_month['month'] = $allmonth;
        return $all_month;
    }
    //事件查询类
    public function select($POST){
        $eventId = $POST['eventId'];
        $openId = $POST['openId'];
        $realStart = $POST['realStart'];
        $realEnd = $POST['realEnd'];
        $select = db('event')->where('eventId',$eventId)->where('openId',$openId)->order('startStr esc')->select();
        if (!$select) {return false;}
        $end = end($select);
        $select = $select[0];
        $ifAcross = $select['acrossDay'];
        $title = $select['title'];
        $place = $select['place'];
        $notes = $select['notes'];
        $remind = $select['remind'];
        $loopType = $select['loopType'];
        $remindArray = [
            '无'    =>  '无',
            0       =>  '事件发生时',
            300     =>  '5分钟前',
            900     =>  '15分钟前',
            1600    =>  '30分钟前',
            3200    =>  '1小时前',
            6400    =>  '2小时前',
            86400   =>  '1天前',
            172800  =>  '2天前',
            604800  =>  '1周前'
        ];
        $remind = $remindArray[$remind];
        //修改
        //对开始年月日和结束年月日进行重复逻辑整理
        //是否是重复事件
        $startYear = date('Y',$realStart);
        $startMonth = date('m',$realStart);
        $startDay = date('d',$realStart);
        $endYear = date('Y',$realEnd);
        $endMonth = date('m',$realEnd);
        $endDay = date('d',$realEnd);
        //修改
        $startHour = $select['startHour'];
        $startMinute = $select['startMinute'];
        $endHour = $end['endHour'];
        $endMinute = $end['endMinute'];
        $weekData = ['周日','周一','周二','周三','周四','周五','周六'];
        $loop = [
            'loopType'      => $select['loopType'],
            'UDL_unit'      => $select['UDL_unit'],
            'UDL_length'    => $select['UDL_length'],
            'LE_year'       => $select['LE_year'],
            'LE_month'      => $select['LE_month'],
            'LE_day'        => $select['LE_day']
        ];
        $joinList = db('event as e')->join('user u','u.openId=e.openId')->field('e.openId')->field('avatarUrl')->field('nickName')->where('e.eventId',$eventId)->group('e.openId')->order('joinTime esc')->select();
        $data = compact('eventId','title','place','notes','remind','startYear','startMonth','startDay','startHour','startMinute','endYear','endMonth','endDay','endHour','endMinute','loop','openId','joinList');
        //时间显示字段
        if ($loopType == '永不') {
            $ymd = date('Y年m月d日',$select['realStart']);
            $week = date('w',$select['realStart']);
            $week = $weekData[$week];
            $endYmd = date('Y年m月d日',$select['realEnd']);
            $endWeek = date('w',$select['realEnd']);
            $endWeek = $weekData[$endWeek];
        }else{
            $ymd = date('Y年m月d日',$realStart);
            $week = date('w',$realStart);
            $week = $weekData[$week];
            $endYmd = date('Y年m月d日',$realEnd);
            $endWeek = date('w',$realEnd);
            $endWeek = $weekData[$endWeek];
        }
        if ($ifAcross == 'true') {
            //跨天事件
            $data['timeStr'] = [
                'startYmd'  =>  $ymd,
                'startWeek' =>  $week,
                'startHi'   =>  $startHour.':'.$startMinute,
                'endYmd'    =>  $endYmd,
                'endWeek'   =>  $endWeek,
                'endHi'     =>  $endHour.':'.$endMinute
            ];
        }else{
            //不跨天事件
            $data['timeStr'] = [
                'startYmd'  =>  $ymd,
                'startWeek' =>  $week,
                'startHi'   =>  $startHour.':'.$startMinute,
                'endHi'     =>  $endHour.':'.$endMinute
            ];
        }
        return $data;
    }
    //增加事件
    public function Insert($POST){
        $openId = $POST['openId'];//111;
        $title = $POST['title'];//'我是标题';
        $place = $POST['place'];//'我是地点';
        $notes = $POST['notes'];//'我是备注';
        $loopType = $POST['loopType'];//'永不';
        $UDL_unit = $POST['UDL_unit'];//'天';
        $UDL_length = $POST['UDL_length'];//1;
        $remind = $POST['remind'];//1111;
        $startYear = $POST['startYear'];//2018;
        $startMonth = $POST['startMonth'];//7;
        $startDay = $POST['startDay'];//12;
        $startStr = strtotime($startYear.'-'.$startMonth.'-'.$startDay);
        $startHour = $POST['startHour'];//22;
        $startMinute = $POST['startMinute'];//0;
        if ($remind != '无') {
            $remindTime = strtotime($startYear.'-'.$startMonth.'-'.$startDay.' '.$startHour.':'.$startMinute)-$remind;
        }else{
            $remindTime = '无';
        }
        $startTime = $startHour.'.'.floor(($startMinute/60)*10);
        $endYear = $POST['endYear'];//2018;
        $endMonth = $POST['endMonth'];//7;
        $endDay = $POST['endDay'];//15;
        $endStr = strtotime($endYear.'-'.$endMonth.'-'.$endDay);
        $endHour = $POST['endHour'];//8;
        $endMinute = $POST['endMinute'];//0;
        if (($endHour==0)&&($endMinute==0)) {
            $endStr -=86400;
        }
        $endTime = $endHour.'.'.floor(($endMinute/60)*10);
        $hour = $endTime-$startTime;
        $loopEnd = $POST['loopEnd'];//'false';
        $LE_year = $POST['LE_year'];//2018;
        $LE_month = $POST['LE_month'];//12;
        $LE_day = $POST['LE_day'];//1;
        $LE_time = strtotime($LE_year.'-'.$LE_month.'-'.$LE_day);
        $eventId = microtime();
        $eventId = explode(' ', $eventId);
        $eventIdR = $eventId[0]*1000000;
        $eventIdL = $eventId[1];
        $eventId = $eventIdL.$eventIdR;
        $eventId = $eventId.'_'.rand(1000,9999);
        $realStart = strtotime($startYear.'-'.$startMonth.'-'.$startDay.' '.$startHour.':'.$startMinute);
        $realEnd = strtotime($endYear.'-'.$endMonth.'-'.$endDay.' '.$endHour.':'.$endMinute);
        $admin = $openId;
        $joinTime = time();
        if ($startStr == $endStr || (($startStr-86400) == $endStr)&&$endHour) {
            //不跨天事件
            $data = compact('startStr','eventId','openId','title','place','UDL_unit','notes','loopType','UDL_unit','UDL_length','remind','remindTime','startYear','startMonth','startDay','startHour','startMinute','startTime','endYear','endMonth','endDay','endHour','endMinute','endTime','hour','loopEnd','LE_year','LE_month','LE_day','LE_time','realStart','realEnd','admin','joinTime');
            $bool = db('event')->insert($data);
            if ($bool == 1) {
                $bool = true;
            }else{
                $bool = false;
            }
            $return['bool'] = $bool;
            $return['eventId'] = $eventId;
            $return['realStart'] = $realStart;
            $return['realEnd'] = $realEnd;
            return $return;
        }else{
            //拆分跨天事件
            $acrossDays = ($endStr-$startStr)/86400;
            for ($i=0; $i <= $acrossDays; $i++) {
                //跨天事件的第一天
                if ($i == 0) {
                    //将以上数据插入数据库
                    $data = [
                        'startStr'      =>  $startStr,
                        'eventId'       =>  $eventId,
                        'openId'        =>  $openId,
                        'title'         =>  $title,
                        'place'         =>  $place,
                        'UDL_unit'      =>  $UDL_unit,
                        'notes'         =>  $notes,
                        'loopType'      =>  $loopType,
                        'UDL_unit'      =>  $UDL_unit,
                        'UDL_length'    =>  $UDL_length,
                        'remind'        =>  $remind,
                        'remindTime'    =>  $remindTime,
                        'startYear'     =>  $startYear,
                        'startMonth'    =>  $startMonth,
                        'startDay'      =>  $startDay,
                        'startHour'     =>  $startHour,
                        'startMinute'   =>  $startMinute,
                        'startTime'     =>  $startTime,
                        'endYear'       =>  $startYear,//不同
                        'endMonth'      =>  $startMonth,//不同
                        'endDay'        =>  $startDay,//不同
                        'endHour'       =>  24,//不同
                        'endMinute'     =>  0,//不同
                        'endTime'       =>  24,//不同
                        'hour'          =>  24-$startTime,//不同
                        'loopEnd'       =>  $loopEnd,
                        'LE_year'       =>  $LE_year,
                        'LE_month'      =>  $LE_month,
                        'LE_day'        =>  $LE_day,
                        'LE_time'       =>  $LE_time,
                        'acrossDay'     =>  'true',//不同
                        'realStart'     =>  $realStart,
                        'realEnd'       =>  $realEnd,
                        'admin'         =>  $openId,
                        'joinTime'      =>  time()
                ];
                    $bool = db('event')->insert($data);
                }
                //跨天事件的中间天
                if(($i!=0) && ($i!=$acrossDays)){
                    //仅写与非跨天事件不同的变量
                    $startStr1 = $startStr+(86400*$i);
                    $startYear1 = date('Y',$startStr1);
                    $endYear1 = $startYear1;
                    $startMonth1 = date('m',$startStr1);
                    $endMonth1 = $startMonth1;
                    $startDay1 = date('d',$startStr1);
                    $endDay1 = $startDay1;
                    $LE_year1 = $LE_year;
                    $LE_month1 = $LE_month;
                    $LE_day1 = $LE_day;
                    $LE_time1 = $LE_time;
                    if ($loopEnd == 'true') {
                        $LE_time1 = $LE_time+(86400*$i);
                        $LE_year1 = date('Y',$LE_time1);
                        $LE_month1 = date('m',$LE_time1);
                        $LE_day1 = date('d',$LE_time1);
                    }
                    //将以上数据插入数据库
                    $data = [
                        'startStr'      =>  $startStr1,
                        'eventId'       =>  $eventId,
                        'openId'        =>  $openId,
                        'title'         =>  $title,
                        'place'         =>  $place,
                        'UDL_unit'      =>  $UDL_unit,
                        'notes'         =>  $notes,
                        'loopType'      =>  $loopType,
                        'UDL_unit'      =>  $UDL_unit,
                        'UDL_length'    =>  $UDL_length,
                        'remind'        =>  $remind,
                        'remindTime'    =>  '无',//不同
                        'startYear'     =>  $startYear1,
                        'startMonth'    =>  $startMonth1,
                        'startDay'      =>  $startDay1,
                        'startHour'     =>  0,//不同
                        'startMinute'   =>  0,//不同
                        'startTime'     =>  0,//不同
                        'endYear'       =>  $endYear1,
                        'endMonth'      =>  $endMonth1,
                        'endDay'        =>  $endDay1,
                        'endHour'       =>  24,//不同
                        'endMinute'     =>  0,//不同
                        'endTime'       =>  24,//不同
                        'hour'          =>  24,//不同
                        'loopEnd'       =>  $loopEnd,
                        'LE_year'       =>  $LE_year1,
                        'LE_month'      =>  $LE_month1,
                        'LE_day'        =>  $LE_day1,
                        'LE_time'       =>  $LE_time1,
                        'acrossDay'     =>  'true',//不同
                        'realStart'     =>  $realStart,
                        'realEnd'       =>  $realEnd,
                        'admin'         =>  $openId,
                        'joinTime'      =>  time()
                ];
                    $bool = db('event')->insert($data);
                }
                //跨天事件的最后一天
                if ($i == $acrossDays) {
                    //仅写与非跨天事件不同的变量
                    $LE_year1 = $LE_year;
                    $LE_month1 = $LE_month;
                    $LE_day1 = $LE_day;
                    $LE_time1 = $LE_time;
                    if ($loopEnd == 'true') {
                        $LE_time1 = $LE_time+(86400*($acrossDays));
                        $LE_year1 = date('Y',$LE_time1);
                        $LE_month1 = date('m',$LE_time1);
                        $LE_day1 = date('d',$LE_time1);
                    }
                    //将以上数据插入数据库
                    $data = [
                        'startStr'      =>  $endStr,//不同
                        'eventId'       =>  $eventId,
                        'openId'        =>  $openId,
                        'title'         =>  $title,
                        'place'         =>  $place,
                        'UDL_unit'      =>  $UDL_unit,
                        'notes'         =>  $notes,
                        'loopType'      =>  $loopType,
                        'UDL_unit'      =>  $UDL_unit,
                        'UDL_length'    =>  $UDL_length,
                        'remind'        =>  $remind,
                        'remindTime'    =>  '无',//不同
                        'startYear'     =>  $endYear,//不同
                        'startMonth'    =>  $endMonth,//不同
                        'startDay'      =>  $endDay,//不同
                        'startHour'     =>  0,//不同
                        'startMinute'   =>  0,//不同
                        'startTime'     =>  0,//不同
                        'endYear'       =>  $endYear,
                        'endMonth'      =>  $endMonth,
                        'endDay'        =>  $endDay,
                        'endHour'       =>  $endHour,
                        'endMinute'     =>  $endMinute,
                        'endTime'       =>  $endTime,
                        'hour'          =>  $endTime,//不同
                        'loopEnd'       =>  $loopEnd,
                        'LE_year'       =>  $LE_year1,
                        'LE_month'      =>  $LE_month1,
                        'LE_day'        =>  $LE_day1,
                        'LE_time'       =>  $LE_time1,
                        'acrossDay'     =>  'true',//不同
                        'realStart'     =>  $realStart,
                        'realEnd'       =>  $realEnd,
                        'admin'         =>  $openId,
                        'joinTime'      =>  time()
                ];
                    $bool = db('event')->insert($data);
                }
            }
            if ($bool == 1) {
                $bool = 'true';
            }else{
                $bool = 'false';
            }
            $return['bool'] = $bool;
            $return['eventId'] = $eventId;
            $return['realStart'] = $realStart;
            $return['realEnd'] = $realEnd;
            return $return;
        }
    }
    //修改类
    public function up($POST){
        $openId = $POST['openId'];
        $eventId = $POST['eventId'];
        $realStart = $POST['realStart'];
        $realEnd = $POST['realEnd'];
        $select = db('event')->where('eventId',$eventId)->find();
        $admin = $select['admin'];//创建人
        $ifLoop = $select['loopType'];//是否重复
        $ifAcross = $select['acrossDay'];//是否跨天
        $ifStart = $select['startStr'];//是否是开始天
        $realYmd = date('Y-m-d',$realStart);
        $realYmd = strtotime($realYmd);
        $userList = db('event')->where('eventId',$eventId)->distinct(true)->field('openId')->field('admin')->select();
        //是否是创建人
        if ($openId == $admin) {
            //是创建人
            //是否是重复事件
            if ($ifLoop == '永不') {
                //不是重复事件
                db('event')->where('eventId',$eventId)->delete();
                foreach ($userList as $key => $value) {
                    $bool = $this->upper($POST,$value['openId'],$eventId,$value['admin']);
                }
                $return['bool'] = $openId;
                $return['eventId'] = $eventId;
                $return['realStart'] = $bool['realStart'];
                $return['realEnd'] = $bool['realEnd'];
                return $return;
            }else{
                //是重复事件
                //是否是跨天事件
                if ($ifAcross == 'true') {
                    //是跨天事件
                    //是否是事件开始天
                    if (($ifStart == $realYmd)) {
                        //是事件开始天
                        db('event')->where('eventId',$eventId)->delete();
                        foreach ($userList as $key => $value) {
                            $bool = $this->upper($POST,$value['openId'],$eventId,$value['admin']);
                        }
                        $return['bool'] = $openId;
                        $return['eventId'] = $eventId;
                        $return['realStart'] = $bool['realStart'];
                        $return['realEnd'] = $bool['realEnd'];
                        return $return;
                    }else{
                        //不是事件开始天
                        //判断点击事件距开始天差多少 
                        foreach ($userList as $key => $value) {
                            $openId = $value['openId'];
                            $event = db('event')->where('eventId',$eventId)->where('openId',$openId)->order('startStr desc')->select();
                            $count = count($event);
                            $i = 2-$count;
                            foreach ($event as $k => $v) {
                                $LE_time = $realStart;
                                $loopEnd = 'true';
                                $LE_time -= (86400*$i);
                                $LE_year = date('Y',$LE_time);
                                $LE_month = date('m',$LE_time);
                                $LE_day = date('d',$LE_time);
                                $data = compact('loopEnd','LE_year','LE_month','LE_day','LE_time');
                                $bool = db('event')->where('eventId',$eventId)->where('openId',$openId)->where('startStr',$v['startStr'])->update($data);
                                $i++;
                            }
                        }
                        $eventId = microtime();
                        $eventId = explode(' ', $eventId);
                        $eventIdR = $eventId[0]*1000000;
                        $eventIdL = $eventId[1];
                        $eventId = $eventIdL.$eventIdR;
                        $eventId = $eventId.'_'.rand(1000,9999);
                        foreach ($userList as $key => $value) {
                            $bool = $this->upper($POST,$value['openId'],$eventId,$value['admin']);
                        }
                        $return['bool'] = $openId;
                        $return['eventId'] = $eventId;
                        $return['realStart'] = $bool['realStart'];
                        $return['realEnd'] = $bool['realEnd'];
                        return $return;
                    }
                }else{
                    //不是跨天事件
                    //判断是否是事件开始天
                    if ($ifStart == $realYmd) {
                        //是事件开始天
                        db('event')->where('eventId',$eventId)->delete();
                        foreach ($userList as $key => $value) {
                            $bool = $this->upper($POST,$value['openId'],$eventId,$value['admin']);
                        }
                        $return['bool'] = $openId;
                        $return['eventId'] = $eventId;
                        $return['realStart'] = $bool['realStart'];
                        $return['realEnd'] = $bool['realEnd'];
                        return $return;
                    }else{
                        //不是事件开始天
                        $LE_time = $realStart;
                        $loopEnd = 'true';
                        $LE_time -= 86400;
                        $LE_year = date('Y',$LE_time);
                        $LE_month = date('m',$LE_time);
                        $LE_day = date('d',$LE_time);
                        $data = compact('loopEnd','LE_year','LE_month','LE_day','LE_time');
                        $bool = db('event')->where('eventId',$eventId)->update($data);
                        $eventId = microtime();
                        $eventId = explode(' ', $eventId);
                        $eventIdR = $eventId[0]*1000000;
                        $eventIdL = $eventId[1];
                        $eventId = $eventIdL.$eventIdR;
                        $eventId = $eventId.'_'.rand(1000,9999);
                        foreach ($userList as $key => $value) {
                            $bool = $this->upper($POST,$value['openId'],$eventId,$value['admin']);
                        }
                        $return['bool'] = $openId;
                        $return['eventId'] = $eventId;
                        $return['realStart'] = $bool['realStart'];
                        $return['realEnd'] = $bool['realEnd'];
                        return $return;
                    }
                }
            }
        }else{
            //不是创建人
            return '不是创建人不能修改事件';
        }
    }
    //执行修改类
    public function upper($POST,$openId,$eventId,$admin){
        $title = $POST['title'];//'我是标题';
        $place = $POST['place'];//'我是地点';
        $notes = $POST['notes'];//'我是备注';
        $loopType = $POST['loopType'];//'永不';
        $UDL_unit = $POST['UDL_unit'];//'天';
        $UDL_length = $POST['UDL_length'];//1;
        $remind = $POST['remind'];//1111;
        $startYear = $POST['startYear'];//2018;
        $startMonth = $POST['startMonth'];//7;
        $startDay = $POST['startDay'];//12;
        $startStr = strtotime($startYear.'-'.$startMonth.'-'.$startDay);
        $startHour = $POST['startHour'];//22;
        $startMinute = $POST['startMinute'];//0;
        if ($remind != '无') {
            $remindTime = strtotime($startYear.'-'.$startMonth.'-'.$startDay.' '.$startHour.':'.$startMinute)-$remind;
        }else{
            $remindTime = '无';
        }
        $startTime = $startHour.'.'.floor(($startMinute/60)*10);
        $endYear = $POST['endYear'];//2018;
        $endMonth = $POST['endMonth'];//7;
        $endDay = $POST['endDay'];//15;
        $endStr = strtotime($endYear.'-'.$endMonth.'-'.$endDay);
        $endHour = $POST['endHour'];//8;
        $endMinute = $POST['endMinute'];//0;
        if (($endHour==0)&&($endMinute==0)) {
            $endStr -=86400;
        }
        $endTime = $endHour.'.'.floor(($endMinute/60)*10);
        $hour = $endTime-$startTime;
        $loopEnd = $POST['loopEnd'];//'false';
        $LE_year = $POST['LE_year'];//2018;
        $LE_month = $POST['LE_month'];//12;
        $LE_day = $POST['LE_day'];//1;
        $LE_time = strtotime($LE_year.'-'.$LE_month.'-'.$LE_day);
        $realStart = strtotime($startYear.'-'.$startMonth.'-'.$startDay.' '.$startHour.':'.$startMinute);
        $realEnd = strtotime($endYear.'-'.$endMonth.'-'.$endDay.' '.$endHour.':'.$endMinute);
        $joinTime = time();
        if ($startStr == $endStr || (($startStr-86400) == $endStr)&&$endHour) {
            //不跨天事件
            $data = compact('startStr','eventId','openId','title','place','UDL_unit','notes','loopType','UDL_unit','UDL_length','remind','remindTime','startYear','startMonth','startDay','startHour','startMinute','startTime','endYear','endMonth','endDay','endHour','endMinute','endTime','hour','loopEnd','LE_year','LE_month','LE_day','LE_time','realStart','realEnd','admin','joinTime');
            $bool = db('event')->insert($data);
            if ($bool == 1) {
                $bool = true;
            }else{
                $bool = false;
            }
            $return['bool'] = $bool;
            $return['eventId'] = $eventId;
            $return['realStart'] = $realStart;
            $return['realEnd'] = $realEnd;
            return $return;
        }else{
            //拆分跨天事件
            $acrossDays = ($endStr-$startStr)/86400;
            for ($i=0; $i <= $acrossDays; $i++) {
                //跨天事件的第一天
                if ($i == 0) {
                    //将以上数据插入数据库
                    $data = [
                        'startStr'      =>  $startStr,
                        'eventId'       =>  $eventId,
                        'openId'        =>  $openId,
                        'title'         =>  $title,
                        'place'         =>  $place,
                        'UDL_unit'      =>  $UDL_unit,
                        'notes'         =>  $notes,
                        'loopType'      =>  $loopType,
                        'UDL_unit'      =>  $UDL_unit,
                        'UDL_length'    =>  $UDL_length,
                        'remind'        =>  $remind,
                        'remindTime'    =>  $remindTime,
                        'startYear'     =>  $startYear,
                        'startMonth'    =>  $startMonth,
                        'startDay'      =>  $startDay,
                        'startHour'     =>  $startHour,
                        'startMinute'   =>  $startMinute,
                        'startTime'     =>  $startTime,
                        'endYear'       =>  $startYear,//不同
                        'endMonth'      =>  $startMonth,//不同
                        'endDay'        =>  $startDay,//不同
                        'endHour'       =>  24,//不同
                        'endMinute'     =>  0,//不同
                        'endTime'       =>  24,//不同
                        'hour'          =>  24-$startTime,//不同
                        'loopEnd'       =>  $loopEnd,
                        'LE_year'       =>  $LE_year,
                        'LE_month'      =>  $LE_month,
                        'LE_day'        =>  $LE_day,
                        'LE_time'       =>  $LE_time,
                        'acrossDay'     =>  'true',//不同
                        'realStart'     =>  $realStart,
                        'realEnd'       =>  $realEnd,
                        'admin'         =>  $admin,
                        'joinTime'      =>  time()
                ];
                    $bool = db('event')->insert($data);
                }
                //跨天事件的中间天
                if(($i!=0) && ($i!=$acrossDays)){
                    //仅写与非跨天事件不同的变量
                    $startStr1 = $startStr+(86400*$i);
                    $startYear1 = date('Y',$startStr1);
                    $endYear1 = $startYear1;
                    $startMonth1 = date('m',$startStr1);
                    $endMonth1 = $startMonth1;
                    $startDay1 = date('d',$startStr1);
                    $endDay1 = $startDay1;
                    $LE_year1 = $LE_year;
                    $LE_month1 = $LE_month;
                    $LE_day1 = $LE_day;
                    $LE_time1 = $LE_time;
                    if ($loopEnd == 'true') {
                        $LE_time1 = $LE_time+(86400*$i);
                        $LE_year1 = date('Y',$LE_time1);
                        $LE_month1 = date('m',$LE_time1);
                        $LE_day1 = date('d',$LE_time1);
                    }
                    //将以上数据插入数据库
                    $data = [
                        'startStr'      =>  $startStr1,
                        'eventId'       =>  $eventId,
                        'openId'        =>  $openId,
                        'title'         =>  $title,
                        'place'         =>  $place,
                        'UDL_unit'      =>  $UDL_unit,
                        'notes'         =>  $notes,
                        'loopType'      =>  $loopType,
                        'UDL_unit'      =>  $UDL_unit,
                        'UDL_length'    =>  $UDL_length,
                        'remind'        =>  $remind,
                        'remindTime'    =>  '无',//不同
                        'startYear'     =>  $startYear1,
                        'startMonth'    =>  $startMonth1,
                        'startDay'      =>  $startDay1,
                        'startHour'     =>  0,//不同
                        'startMinute'   =>  0,//不同
                        'startTime'     =>  0,//不同
                        'endYear'       =>  $endYear1,
                        'endMonth'      =>  $endMonth1,
                        'endDay'        =>  $endDay1,
                        'endHour'       =>  24,//不同
                        'endMinute'     =>  0,//不同
                        'endTime'       =>  24,//不同
                        'hour'          =>  24,//不同
                        'loopEnd'       =>  $loopEnd,
                        'LE_year'       =>  $LE_year1,
                        'LE_month'      =>  $LE_month1,
                        'LE_day'        =>  $LE_day1,
                        'LE_time'       =>  $LE_time1,
                        'acrossDay'     =>  'true',//不同
                        'realStart'     =>  $realStart,
                        'realEnd'       =>  $realEnd,
                        'admin'         =>  $admin,
                        'joinTime'      =>  time()
                ];
                    $bool = db('event')->insert($data);
                }
                //跨天事件的最后一天
                if ($i == $acrossDays) {
                    //仅写与非跨天事件不同的变量
                    $LE_year1 = $LE_year;
                    $LE_month1 = $LE_month;
                    $LE_day1 = $LE_day;
                    $LE_time1 = $LE_time;
                    if ($loopEnd == 'true') {
                        $LE_time1 = $LE_time+(86400*($acrossDays));
                        $LE_year1 = date('Y',$LE_time1);
                        $LE_month1 = date('m',$LE_time1);
                        $LE_day1 = date('d',$LE_time1);
                    }
                    //将以上数据插入数据库
                    $data = [
                        'startStr'      =>  $endStr,//不同
                        'eventId'       =>  $eventId,
                        'openId'        =>  $openId,
                        'title'         =>  $title,
                        'place'         =>  $place,
                        'UDL_unit'      =>  $UDL_unit,
                        'notes'         =>  $notes,
                        'loopType'      =>  $loopType,
                        'UDL_unit'      =>  $UDL_unit,
                        'UDL_length'    =>  $UDL_length,
                        'remind'        =>  $remind,
                        'remindTime'    =>  '无',//不同
                        'startYear'     =>  $endYear,//不同
                        'startMonth'    =>  $endMonth,//不同
                        'startDay'      =>  $endDay,//不同
                        'startHour'     =>  0,//不同
                        'startMinute'   =>  0,//不同
                        'startTime'     =>  0,//不同
                        'endYear'       =>  $endYear,
                        'endMonth'      =>  $endMonth,
                        'endDay'        =>  $endDay,
                        'endHour'       =>  $endHour,
                        'endMinute'     =>  $endMinute,
                        'endTime'       =>  $endTime,
                        'hour'          =>  $endTime,//不同
                        'loopEnd'       =>  $loopEnd,
                        'LE_year'       =>  $LE_year1,
                        'LE_month'      =>  $LE_month1,
                        'LE_day'        =>  $LE_day1,
                        'LE_time'       =>  $LE_time1,
                        'acrossDay'     =>  'true',//不同
                        'realStart'     =>  $realStart,
                        'realEnd'       =>  $realEnd,
                        'admin'         =>  $admin,
                        'joinTime'      =>  time()
                ];
                    $bool = db('event')->insert($data);
                }
            }
            if ($bool == 1) {
                $bool = 'true';
            }else{
                $bool = 'false';
            }
            $return['bool'] = $bool;
            $return['eventId'] = $eventId;
            $return['realStart'] = $realStart;
            $return['realEnd'] = $realEnd;
            return $return;
        }
    }
    //删除类
    public function del($POST){
        $openId = $POST['openId'];
        $eventId = $POST['eventId'];
        $realStart = $POST['realStart'];
        $realEnd = $POST['realEnd'];
        $select = db('event')->where('eventId',$eventId)->find();
        $admin = $select['admin'];//创建人
        $ifLoop = $select['loopType'];//是否重复
        $ifAcross = $select['acrossDay'];//是否跨天
        $ifStart = $select['startStr'];//是否是开始天
        $realYmd = date('Y-m-d',$realStart);
        $realYmd = strtotime($realYmd);
        $userList = db('event')->where('eventId',$eventId)->distinct(true)->field('openId')->select();
        //是否是创建人
        if ($openId == $admin) {
            //是创建人
            //是否是重复事件
            if ($ifLoop == '永不') {
                //不是重复事件
                $bool = db('event')->where('eventId',$eventId)->delete();
            }else{
                //是重复事件
                //是否是跨天事件
                if ($ifAcross == 'true') {
                    //是跨天事件
                    //是否是事件开始天
                    if (($ifStart == $realYmd)) {
                        //是事件开始天
                        $bool = db('event')->where('eventId',$eventId)->delete();
                    }else{
                        //不是事件开始天
                        //判断点击事件距开始天差多少
                        foreach ($userList as $key => $value) {
                            $openId = $value['openId'];
                            $event = db('event')->where('eventId',$eventId)->where('openId',$openId)->order('startStr desc')->select();
                            $count = count($event);
                            $i = 2-$count;
                            foreach ($event as $k => $v) {
                                $LE_time = $realStart;
                                $loopEnd = 'true';
                                $LE_time -= (86400*$i);
                                $LE_year = date('Y',$LE_time);
                                $LE_month = date('m',$LE_time);
                                $LE_day = date('d',$LE_time);
                                $data = compact('loopEnd','LE_year','LE_month','LE_day','LE_time');
                                $bool = db('event')->where('eventId',$eventId)->where('openId',$openId)->where('startStr',$v['startStr'])->update($data);
                                $i++;
                            }
                        }
                    }
                }else{
                    //不是跨天事件
                    //判断是否是事件开始天
                    if ($ifStart == $realYmd) {
                        //是事件开始天
                        $bool = db('event')->where('eventId',$eventId)->delete();
                    }else{
                        //不是事件开始天
                        $LE_time = $realStart;
                        $loopEnd = 'true';
                        $LE_time -= 86400;
                        $LE_year = date('Y',$LE_time);
                        $LE_month = date('m',$LE_time);
                        $LE_day = date('d',$LE_time);
                        $data = compact('loopEnd','LE_year','LE_month','LE_day','LE_time');
                        $bool = db('event')->where('eventId',$eventId)->update($data);
                    }
                }
            }
        }else{
            //不是创建人
            $bool = db('event')->where('eventId',$eventId)->where('openId',$openId)->delete();
        }
        if ($bool != 0) {
            $bool = true;
        }else{
            $bool = false;
        }
        return $bool;
    }
    //邀请页面
    public function joinSelect($POST){
        $openId = $POST['openId'];
        $eventId = $POST['eventId'];
        $ifJoin = db('event')->where('eventId',$eventId)->where('openId',$openId)->find();
        $select = db('event')->where('eventId',$eventId)->where('openId=admin')->select();
        if(!$select){return false;}
        $end = end($select);
        $select = $select[0];
        if($ifJoin){
            $remind = $ifJoin['remind'];
            $ifJoin = 'true';
        }else{
            $remind = $select['remind'];
            $ifJoin = 'false';
        }
        $realStart = $POST['realStart'];
        $realEnd = $POST['realEnd'];
        $ifAcross = $select['acrossDay'];
        $loopType = $select['loopType'];
        $title = $select['title'];
        $place = $select['place'];
        $notes = $select['notes'];
        $remindArray = [
            '无'    =>  '无',
            0       =>  '事件发生时',
            300     =>  '5分钟前',
            900     =>  '15分钟前',
            1600    =>  '30分钟前',
            3200    =>  '1小时前',
            6400    =>  '2小时前',
            86400   =>  '1天前',
            172800  =>  '2天前',
            604800  =>  '1周前'
        ];
        $remind = $remindArray[$remind];
        //修改
        //对开始年月日和结束年月日进行重复逻辑整理
        //是否是重复事件
        $startYear = date('Y',$realStart);
        $startMonth = date('m',$realStart);
        $startDay = date('d',$realStart);
        $endYear = date('Y',$realEnd);
        $endMonth = date('m',$realEnd);
        $endDay = date('d',$realEnd);
        //修改
        $startHour = $select['startHour'];
        $startMinute = $select['startMinute'];
        $endHour = $end['endHour'];
        $endMinute = $end['endMinute'];
        $weekData = ['周日','周一','周二','周三','周四','周五','周六'];
        $loop = [
            'loopType'      => $select['loopType'],
            'UDL_unit'      => $select['UDL_unit'],
            'UDL_length'    => $select['UDL_length'],
            'LE_year'       => $select['LE_year'],
            'LE_month'      => $select['LE_month'],
            'LE_day'        => $select['LE_day']
        ];
        $joinList = db('event as e')->join('user u','u.openId=e.openId')->field('e.openId')->field('avatarUrl')->field('nickName')->where('e.eventId',$eventId)->group('e.openId')->order('joinTime esc')->select();
        $data = compact('eventId','title','place','notes','remind','startYear','startMonth','startDay','startHour','startMinute','endYear','endMonth','endDay','endHour','endMinute','loop','ifJoin','joinList');
        $conflict = Db::query("select eventId,title,realStart,realEnd,acrossDay from event where openId='$openId' and loopType='永不' and eventId<>'$eventId' and (realStart<=$realEnd and realStart>=$realStart or realEnd<=$realEnd and realEnd>=$realStart or realStart<=$realStart and realEnd>=$realEnd) group by eventId order by realstart");
        if ($conflict) {
            foreach ($conflict as $key => $value) {
                if ($value['acrossDay']=='true') {
                    $one['title'] = $value['title'];
                    $one['time'] = date('Y年m月d日 H:i',$value['realStart']);
                    $one['across'] = date('Y年m月d日 H:i',$value['realEnd']);
                }else{
                    if(isset($one['across'])){unset($one['across']);}
                    $one['title'] = $value['title'];
                    $one['time'] = date('Y年m月d日 H:i',$value['realStart']).'-'.date('H:i',$value['realEnd']);
                }
                $conf[$key] = $one;
            }
            $data['conflict'] = $conf;
        }else{
            $data['conflict'] = $conflict;
        }
        //时间显示字段
        if ($loopType == '永不') {
            $ymd = date('Y年m月d日',$select['realStart']);
            $week = date('w',$select['realStart']);
            $week = $weekData[$week];
            $endYmd = date('Y年m月d日',$select['realEnd']);
            $endWeek = date('w',$select['realEnd']);
            $endWeek = $weekData[$endWeek];
        }else{
            $ymd = date('Y年m月d日',$realStart);
            $week = date('w',$realStart);
            $week = $weekData[$week];
            $endYmd = date('Y年m月d日',$realEnd);
            $endWeek = date('w',$realEnd);
            $endWeek = $weekData[$endWeek];
        }
        if ($ifAcross == 'true') {
            //跨天事件
            $data['timeStr'] = [
                'startYmd'  =>  $ymd,
                'startWeek' =>  $week,
                'startHi'   =>  $startHour.':'.$startMinute,
                'endYmd'    =>  $endYmd,
                'endWeek'   =>  $endWeek,
                'endHi'     =>  $endHour.':'.$endMinute
            ];
        }else{
            //不跨天事件
            $data['timeStr'] = [
                'startYmd'  =>  $ymd,
                'startWeek' =>  $week,
                'startHi'   =>  $startHour.':'.$startMinute,
                'endHi'     =>  $endHour.':'.$endMinute
            ];
        }
        return $data;
    }
    //加入邀请事件
    public function join($POST){
        $openId = $POST['openId'];
        $eventId = $POST['eventId'];
        $remind = $POST['remind'];
        $select = db('event')->where('eventId',$eventId)->where('openId=admin')->select();
        $startStr = $select[0]['startYear'].'-'.$select[0]['startMonth'].'-'.$select[0]['startDay'].' '.$select[0]['startHour'].':'.$select[0]['startMinute'];
        $startStr = strtotime($startStr);
        if ($remind=='无') {
            $remindTime = '无';
        }else{
            $remindTime = $startStr-$remind;
        }
        foreach ($select as $key => $value) {
            $data = $value;
            $data['openId'] = $openId;
            $data['joinTime'] = time();
            if ($key == 0) {
                $data['remind'] = $remind;
                $data['remindTime'] = $remindTime;
            }
            $bool = db('event')->insert($data);
        }
        return $bool;
    }
    //退出邀请事件
    public function exit($POST){
        $openId = $POST['openId'];
        $eventId = $POST['eventId'];
        $return = db('event')->where('openId',$openId)->where('eventId',$eventId)->delete();
        return $return;
    }
    //修改提醒时间
    public function joinUpdate($POST){
        $openId = $POST['openId'];
        $eventId = $POST['eventId'];
        $remind = $POST['remind'];
        $select = db('event')->where('eventId',$eventId)->find();
        $startStr = $select['startYear'].'-'.$select['startMonth'].'-'.$select['startDay'].' '.$select['startHour'].':'.$select['startMinute'];
        $startStr = strtotime($startStr);
        if ($remind=='无') {
            $remindTime = '无';
        }else{
            $remindTime = $startStr-$remind;
        }
        $data['remind'] = $remind;
        $data['remindTime'] = $remindTime;
        $bool = db('event')->where('eventId',$eventId)->where('openId',$openId)->order('startStr esc')->limit(1)->update($data);
        return $bool;
    }
    //二维数组排序
    public function twoSort($arrays,$sort_key,$sort_order=SORT_ASC,$sort_type=SORT_NUMERIC){
        if(is_array($arrays)){
            foreach ($arrays as $array){
                if(is_array($array)){
                    $key_arrays[] = $array[$sort_key];
                }else{ 
                    return false;
                }
            }
        }else{
            return false;
        }
        array_multisort($key_arrays,$sort_order,$sort_type,$arrays);
        return $arrays;
    }
}