<?php
namespace app\index\controller;
use app\index\model\TimeModel;
use think\Controller;
class Time extends Controller{
	public $model;
    public function __construct(){
        parent::__construct();
        $this->model = new TimeModel;
    }
    //更新数据库中的token
    public function getToken(){
    	$token = file_get_contents('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wxbbab4e1293067b14&secret=2940073ed79ad4d15726e27b04f481a8');
    	$token = json_decode($token);
    	$token = $token->access_token;
    	db('token')->where('id',1)->update(['access_token'=>$token]);
    }
    //删除7天前的form_id
    public function sevenLife(){
    	$time = time()-518400;
    	db('form_id')->where('time','<',$time)->delete();
    }
    //发送普通的事件提醒
    public function normalMessage(){
    	$startTime = time()-150;
    	$endTime = time()+150;
    	$event = db('event')->where('remindTime','between',$startTime.','.$endTime)->where('loopType','永不')->select();
    	foreach ($event as $k => $v) {
    		$title = $v['title'];
    		$place = $v['place'];
    		$notes = $v['notes'];
    		$openId = $v['openId'];
    		$eventId = $v['eventId'];
    		$startStr = $v['startStr'];
    		$startTime = $v['startTime'];
    		$time = $v['startYear'].'年'.$v['startMonth'].'月'.$v['startDay'].'日 '.$v['startHour'].':'.$v['startMinute'];
    		if ($v['acrossDay'] == 'true') {
    			$end = db('event')->where('eventId',$eventId)->order('startStr desc')->find();
    			$year = $end['startYear'];
    			$month = $end['startMonth'];
    			$day = $end['startDay'];
    			$hour = $end['endHour'];
    			$minute = $end['endMinute'];
    			$time = $time.'-'.$year.'年'.$month.'月'.$day.'日 '.$hour.':'.$minute;
    		}else{
    			$time = $time.'-'.$v['endHour'].':'.$v['endMinute'];
    		}
    		$this->model->message($title,$time,$place,$notes,$openId,$eventId,$startStr,$startTime);
    	}
    }
    //每天重复事件提醒
    public function dayMessage(){
    	$nowHour = date('H',time());
    	$nowMinute = date('i',time());
    	$startStr = date('Y-m-d',time());
    	$startStr = strtotime($startStr);
    	$event = db('event')->where('loopType','每天')->where('remindTime','<=',time())->select();
    	foreach ($event as $k => $v) {
    		$remindTime = $v['remindTime'];
    		$hour = date('H',$remindTime);
    		$minute = date('i',$remindTime);
    		if ($nowHour==$hour && $nowMinute==$minute) {
    			if ($v['loopEnd'] == 'false') {
    				//如果没有重复结束时间
    				$title = $v['title'];
		    		$place = $v['place'];
		    		$notes = $v['notes'];
		    		$openId = $v['openId'];
		    		$eventId = $v['eventId'];
		    		$startStr = $startStr+$v['remind'];
		    		$startTime = $v['startTime'];
		    		$startYear = date('Y',$startStr);
		    		$startMonth = date('m',$startStr);
		    		$startDay = date('d',$startStr);
		    		$time = $startYear.'年'.$startMonth.'月'.$startDay.'日 '.$v['startHour'].':'.$v['startMinute'];
		    		if ($v['acrossDay'] == 'true') {
		    			$end = db('event')->where('eventId',$eventId)->where('openId',$openId)->order('startStr desc')->find();
		    			$days = db('event')->where('eventId',$eventId)->where('openId',$openId)->field('count(*)')->find();
		    			$days = $days['count(*)']-1;
		    			$endStr = $startStr+(86400*$days);
		    			$year = date('Y',$endStr);
		    			$month = date('m',$endStr);
		    			$day = date('d',$endStr);
		    			$hour = $end['endHour'];
		    			$minute = $end['endMinute'];
		    			$time = $time.'-'.$year.'年'.$month.'月'.$day.'日 '.$hour.':'.$minute;
		    		}else{
		    			$time = $time.'-'.$v['endHour'].':'.$v['endMinute'];
		    		}
		    		$this->model->message($title,$time,$place,$notes,$openId,$eventId,$startStr,$startTime);
    			}
    		}
    	}
    }
    //自定义天重复事件提醒
    public function uDayMessage(){
    	$nowHour = date('H',time());
    	$nowMinute = date('i',time());
    	$startStr = date('Y-m-d',time());
    	$startStr = strtotime($startStr);
    	$event = db('event')->where('loopType','自定义')->where('UDL_unit','天')->where('remindTime','<=',time())->select();
    	foreach ($event as $k => $v) {
    		$remindTime = $v['remindTime'];
    		$remindStr = date('Y-m-d',$remindTime);
    		$remindStr = strtotime($remindStr);
    		$hour = date('H',$remindTime);
    		$minute = date('i',$remindTime);
    		$gap = ($startStr-$remindStr)/86400;
    		$gap %= $v['UDL_length'];
    		if ($gap!=0) {continue;}
    		if ($nowHour==$hour && $nowMinute==$minute) {
    			if ($v['loopEnd'] == 'false') {
    				//如果没有重复结束时间
    				$title = $v['title'];
		    		$place = $v['place'];
		    		$notes = $v['notes'];
		    		$openId = $v['openId'];
		    		$eventId = $v['eventId'];
		    		$startStr = $startStr+$v['remind'];
		    		$startTime = $v['startTime'];
		    		$startYear = date('Y',$startStr);
		    		$startMonth = date('m',$startStr);
		    		$startDay = date('d',$startStr);
		    		$time = $startYear.'年'.$startMonth.'月'.$startDay.'日 '.$v['startHour'].':'.$v['startMinute'];
		    		if ($v['acrossDay'] == 'true') {
		    			$end = db('event')->where('eventId',$eventId)->where('openId',$openId)->order('startStr desc')->find();
		    			$days = db('event')->where('eventId',$eventId)->where('openId',$openId)->field('count(*)')->find();
		    			$days = $days['count(*)']-1;
		    			$endStr = $startStr+(86400*$days);
		    			$year = date('Y',$endStr);
		    			$month = date('m',$endStr);
		    			$day = date('d',$endStr);
		    			$hour = $end['endHour'];
		    			$minute = $end['endMinute'];
		    			$time = $time.'-'.$year.'年'.$month.'月'.$day.'日 '.$hour.':'.$minute;
		    		}else{
		    			$time = $time.'-'.$v['endHour'].':'.$v['endMinute'];
		    		}
		    		$this->model->message($title,$time,$place,$notes,$openId,$eventId,$startStr,$startTime);
    			}
    		}
    	}
    }
    public function workMessage(){
        $nowHour = date('H',time());
        $nowMinute = date('i',time());
        $startStr = date('Y-m-d',time());
        $startStr = strtotime($startStr);
        $event = db('event')->where('loopType','每个工作日')->where('remindTime','<=',time())->select();
        foreach ($event as $k => $v) {
            $remindTime = $v['remindTime'];
            $hour = date('H',$remindTime);
            $minute = date('i',$remindTime);
            $remindStr = date('Y-m-d',$remindTime);
            $remindStr = strtotime($remindStr);
            $week = date('w',$startStr);
            $int = ($v['startStr'] - $remindStr)/86400;
            if ($int == 0) {
                $saturday = 6;
                $sunday = 0;
            }else{
                $saturday-=$int;
                $sunday-=$int;
                if($saturday<0){$saturday+=7;}
                if($sunday<0){$sunday+=7;}
            }
            if (($week==$saturday)||($week==$sunday)) {
                continue;
            }
            if ($nowHour==$hour && $nowMinute==$minute) {
                if ($v['loopEnd'] == 'false') {
                    //如果没有重复结束时间
                    $title = $v['title'];
                    $place = $v['place'];
                    $notes = $v['notes'];
                    $openId = $v['openId'];
                    $eventId = $v['eventId'];
                    $startStr = $startStr+$v['remind'];
                    $startTime = $v['startTime'];
                    $startYear = date('Y',$startStr);
                    $startMonth = date('m',$startStr);
                    $startDay = date('d',$startStr);
                    $time = $startYear.'年'.$startMonth.'月'.$startDay.'日 '.$v['startHour'].':'.$v['startMinute'];
                    if ($v['acrossDay'] == 'true') {
                        $end = db('event')->where('eventId',$eventId)->where('openId',$openId)->order('startStr desc')->find();
                        $days = db('event')->where('eventId',$eventId)->where('openId',$openId)->field('count(*)')->find();
                        $days = $days['count(*)']-1;
                        $endStr = $startStr+(86400*$days);
                        $year = date('Y',$endStr);
                        $month = date('m',$endStr);
                        $day = date('d',$endStr);
                        $hour = $end['endHour'];
                        $minute = $end['endMinute'];
                        $time = $time.'-'.$year.'年'.$month.'月'.$day.'日 '.$hour.':'.$minute;
                    }else{
                        $time = $time.'-'.$v['endHour'].':'.$v['endMinute'];
                    }
                    $this->model->message($title,$time,$place,$notes,$openId,$eventId,$startStr,$startTime);
                }
            }
        }
    }
    //每周重复事件提醒
    public function weekMessage(){
    	$nowHour = date('H',time());
    	$nowMinute = date('i',time());
    	$startStr = date('Y-m-d',time());
    	$startStr = strtotime($startStr);
    	$event = db('event')->where('loopType','每周')->where('remindTime','<=',time())->select();
    	foreach ($event as $k => $v) {
    		$remindTime = $v['remindTime'];
    		$remindStr = date('Y-m-d',$remindTime);
    		$remindStr = strtotime($remindStr);
    		$hour = date('H',$remindTime);
    		$minute = date('i',$remindTime);
    		$gap = ($startStr-$remindStr)/86400;
    		$gap %= 7;
    		if ($gap!=0) {continue;}
    		if ($nowHour==$hour && $nowMinute==$minute) {
    			if ($v['loopEnd'] == 'false') {
    				//如果没有重复结束时间
    				$title = $v['title'];
		    		$place = $v['place'];
		    		$notes = $v['notes'];
		    		$openId = $v['openId'];
		    		$eventId = $v['eventId'];
		    		$startStr = $startStr+$v['remind'];
		    		$startTime = $v['startTime'];
		    		$startYear = date('Y',$startStr);
		    		$startMonth = date('m',$startStr);
		    		$startDay = date('d',$startStr);
		    		$time = $startYear.'年'.$startMonth.'月'.$startDay.'日 '.$v['startHour'].':'.$v['startMinute'];
		    		if ($v['acrossDay'] == 'true') {
		    			$end = db('event')->where('eventId',$eventId)->where('openId',$openId)->order('startStr desc')->find();
		    			$days = db('event')->where('eventId',$eventId)->where('openId',$openId)->field('count(*)')->find();
		    			$days = $days['count(*)']-1;
		    			$endStr = $startStr+(86400*$days);
		    			$year = date('Y',$endStr);
		    			$month = date('m',$endStr);
		    			$day = date('d',$endStr);
		    			$hour = $end['endHour'];
		    			$minute = $end['endMinute'];
		    			$time = $time.'-'.$year.'年'.$month.'月'.$day.'日 '.$hour.':'.$minute;
		    		}else{
		    			$time = $time.'-'.$v['endHour'].':'.$v['endMinute'];
		    		}
		    		$this->model->message($title,$time,$place,$notes,$openId,$eventId,$startStr,$startTime);
    			}
    		}
    	}
    }
    //自定义周重复事件提醒
    public function uweekMessage(){
    	$nowHour = date('H',time());
    	$nowMinute = date('i',time());
    	$startStr = date('Y-m-d',time());
    	$startStr = strtotime($startStr);
    	$event = db('event')->where('loopType','自定义')->where('UDL_unit','周')->where('remindTime','<=',time())->select();
    	foreach ($event as $k => $v) {
    		$remindTime = $v['remindTime'];
    		$remindStr = date('Y-m-d',$remindTime);
    		$remindStr = strtotime($remindStr);
    		$hour = date('H',$remindTime);
    		$minute = date('i',$remindTime);
    		$gap = ($startStr-$remindStr)/86400;
    		$gap %= (7*$v['UDL_length']);
    		if ($gap!=0) {continue;}
    		if ($nowHour==$hour && $nowMinute==$minute) {
    			if ($v['loopEnd'] == 'false') {
    				//如果没有重复结束时间
    				$title = $v['title'];
		    		$place = $v['place'];
		    		$notes = $v['notes'];
		    		$openId = $v['openId'];
		    		$eventId = $v['eventId'];
		    		$startStr = $startStr+$v['remind'];
		    		$startTime = $v['startTime'];
		    		$startYear = date('Y',$startStr);
		    		$startMonth = date('m',$startStr);
		    		$startDay = date('d',$startStr);
		    		$time = $startYear.'年'.$startMonth.'月'.$startDay.'日 '.$v['startHour'].':'.$v['startMinute'];
		    		if ($v['acrossDay'] == 'true') {
		    			$end = db('event')->where('eventId',$eventId)->where('openId',$openId)->order('startStr desc')->find();
		    			$days = db('event')->where('eventId',$eventId)->where('openId',$openId)->field('count(*)')->find();
		    			$days = $days['count(*)']-1;
		    			$endStr = $startStr+(86400*$days);
		    			$year = date('Y',$endStr);
		    			$month = date('m',$endStr);
		    			$day = date('d',$endStr);
		    			$hour = $end['endHour'];
		    			$minute = $end['endMinute'];
		    			$time = $time.'-'.$year.'年'.$month.'月'.$day.'日 '.$hour.':'.$minute;
		    		}else{
		    			$time = $time.'-'.$v['endHour'].':'.$v['endMinute'];
		    		}
		    		$this->model->message($title,$time,$place,$notes,$openId,$eventId,$startStr,$startTime);
    			}
    		}
    	}
    }
    //每月重复事件提醒
    public function monthMessage(){
    	$nowHour = date('H',time());
    	$nowMinute = date('i',time());
    	$startStr = date('Y-m-d',time());
    	$startStr = strtotime($startStr);
    	$event = db('event')->where('loopType','每月')->where('remindTime','<=',time())->select();
    	foreach ($event as $k => $v) {
    		$remindTime = $v['remindTime'];
    		$remindStr = date('Y-m-d',$remindTime);
    		$remindStr = strtotime($remindStr);
    		$hour = date('H',$remindTime);
    		$minute = date('i',$remindTime);
    		$nowDay = date('d',$startStr);
    		$eventDay = date('d',$remindStr);
    		if ($nowDay != $eventDay) {continue;}
    		if ($nowHour==$hour && $nowMinute==$minute) {
    			if ($v['loopEnd'] == 'false') {
    				//如果没有重复结束时间
    				$title = $v['title'];
		    		$place = $v['place'];
		    		$notes = $v['notes'];
		    		$openId = $v['openId'];
		    		$eventId = $v['eventId'];
		    		$startStr = $startStr+$v['remind'];
		    		$startTime = $v['startTime'];
		    		$startYear = date('Y',$startStr);
		    		$startMonth = date('m',$startStr);
		    		$startDay = date('d',$startStr);
		    		$time = $startYear.'年'.$startMonth.'月'.$startDay.'日 '.$v['startHour'].':'.$v['startMinute'];
		    		if ($v['acrossDay'] == 'true') {
		    			$end = db('event')->where('eventId',$eventId)->where('openId',$openId)->order('startStr desc')->find();
		    			$days = db('event')->where('eventId',$eventId)->where('openId',$openId)->field('count(*)')->find();
		    			$days = $days['count(*)']-1;
		    			$endStr = $startStr+(86400*$days);
		    			$year = date('Y',$endStr);
		    			$month = date('m',$endStr);
		    			$day = date('d',$endStr);
		    			$hour = $end['endHour'];
		    			$minute = $end['endMinute'];
		    			$time = $time.'-'.$year.'年'.$month.'月'.$day.'日 '.$hour.':'.$minute;
		    		}else{
		    			$time = $time.'-'.$v['endHour'].':'.$v['endMinute'];
		    		}
		    		$this->model->message($title,$time,$place,$notes,$openId,$eventId,$startStr,$startTime);
    			}
    		}
    	}
    }
    //自定义月重复提醒
    public function umonthMessage(){
    	$nowHour = date('H',time());
    	$nowMinute = date('i',time());
    	$startStr = date('Y-m-d',time());
    	$startStr = strtotime($startStr);
    	$event = db('event')->where('loopType','自定义')->where('UDL_unit','月')->where('remindTime','<=',time())->select();
    	foreach ($event as $k => $v) {
    		$remindTime = $v['remindTime'];
    		$remindStr = date('Y-m-d',$remindTime);
    		$remindStr = strtotime($remindStr);
    		$hour = date('H',$remindTime);
    		$minute = date('i',$remindTime);
    		$nowDay = date('d',$startStr);
    		$eventDay = date('d',$remindStr);
    		$nowYear = date('Y',$startStr);
    		$eventYear = date('Y',$remindStr);
    		$gapYear = $nowYear-$eventYear;
    		$nowMonth = date('m',$startStr)+($gapYear*12);
    		$eventMonth = date('m',$remindStr);
    		$gapMonth = $nowMonth-$eventMonth;
    		$gapMonth %= $v['UDL_length'];
    		if (($nowDay!=$eventDay) || ($gapMonth!=0)) {continue;}
    		if ($nowHour==$hour && $nowMinute==$minute) {
    			if ($v['loopEnd'] == 'false') {
    				//如果没有重复结束时间
    				$title = $v['title'];
		    		$place = $v['place'];
		    		$notes = $v['notes'];
		    		$openId = $v['openId'];
		    		$eventId = $v['eventId'];
		    		$startStr = $startStr+$v['remind'];
		    		$startTime = $v['startTime'];
		    		$startYear = date('Y',$startStr);
		    		$startMonth = date('m',$startStr);
		    		$startDay = date('d',$startStr);
		    		$time = $startYear.'年'.$startMonth.'月'.$startDay.'日 '.$v['startHour'].':'.$v['startMinute'];
		    		if ($v['acrossDay'] == 'true') {
		    			$end = db('event')->where('eventId',$eventId)->where('openId',$openId)->order('startStr desc')->find();
		    			$days = db('event')->where('eventId',$eventId)->where('openId',$openId)->field('count(*)')->find();
		    			$days = $days['count(*)']-1;
		    			$endStr = $startStr+(86400*$days);
		    			$year = date('Y',$endStr);
		    			$month = date('m',$endStr);
		    			$day = date('d',$endStr);
		    			$hour = $end['endHour'];
		    			$minute = $end['endMinute'];
		    			$time = $time.'-'.$year.'年'.$month.'月'.$day.'日 '.$hour.':'.$minute;
		    		}else{
		    			$time = $time.'-'.$v['endHour'].':'.$v['endMinute'];
		    		}
		    		$this->model->message($title,$time,$place,$notes,$openId,$eventId,$startStr,$startTime);
    			}
    		}
    	}
    }
    public function yearMessage(){
    	$nowHour = date('H',time());
    	$nowMinute = date('i',time());
    	$startStr = date('Y-m-d',time());
    	$startStr = strtotime($startStr);
    	$event = db('event')->where('loopType','每年')->where('remindTime','<=',time())->select();
    	foreach ($event as $k => $v) {
    		$remindTime = $v['remindTime'];
    		$remindStr = date('Y-m-d',$remindTime);
    		$remindStr = strtotime($remindStr);
    		$hour = date('H',$remindTime);
    		$minute = date('i',$remindTime);
    		$nowDay = date('d',$startStr);
    		$eventDay = date('d',$remindStr);
    		$nowMonth = date('m',$startStr);
    		$eventMonth = date('m',$remindStr);
    		if (($nowDay!=$eventDay) || ($nowMonth!=$eventMonth)) {continue;}
    		if ($nowHour==$hour && $nowMinute==$minute) {
    			if ($v['loopEnd'] == 'false') {
    				//如果没有重复结束时间
    				$title = $v['title'];
		    		$place = $v['place'];
		    		$notes = $v['notes'];
		    		$openId = $v['openId'];
		    		$eventId = $v['eventId'];
		    		$startStr = $startStr+$v['remind'];
		    		$startTime = $v['startTime'];
		    		$startYear = date('Y',$startStr);
		    		$startMonth = date('m',$startStr);
		    		$startDay = date('d',$startStr);
		    		$time = $startYear.'年'.$startMonth.'月'.$startDay.'日 '.$v['startHour'].':'.$v['startMinute'];
		    		if ($v['acrossDay'] == 'true') {
		    			$end = db('event')->where('eventId',$eventId)->where('openId',$openId)->order('startStr desc')->find();
		    			$days = db('event')->where('eventId',$eventId)->where('openId',$openId)->field('count(*)')->find();
		    			$days = $days['count(*)']-1;
		    			$endStr = $startStr+(86400*$days);
		    			$year = date('Y',$endStr);
		    			$month = date('m',$endStr);
		    			$day = date('d',$endStr);
		    			$hour = $end['endHour'];
		    			$minute = $end['endMinute'];
		    			$time = $time.'-'.$year.'年'.$month.'月'.$day.'日 '.$hour.':'.$minute;
		    		}else{
		    			$time = $time.'-'.$v['endHour'].':'.$v['endMinute'];
		    		}
		    		$this->model->message($title,$time,$place,$notes,$openId,$eventId,$startStr,$startTime);
    			}
    		}
    	}
    }
    public function uyearMessage(){
    	$nowHour = date('H',time());
    	$nowMinute = date('i',time());
    	$startStr = date('Y-m-d',time());
    	$startStr = strtotime($startStr);
    	$event = db('event')->where('loopType','自定义')->where('UDL_unit','年')->where('remindTime','<=',time())->select();
    	foreach ($event as $k => $v) {
    		$remindTime = $v['remindTime'];
    		$remindStr = date('Y-m-d',$remindTime);
    		$remindStr = strtotime($remindStr);
    		$hour = date('H',$remindTime);
    		$minute = date('i',$remindTime);
    		$nowDay = date('d',$startStr);
    		$eventDay = date('d',$remindStr);
    		$nowMonth = date('m',$startStr);
    		$eventMonth = date('m',$remindStr);
    		$nowYear = date('Y',$startStr);
    		$eventYear = date('Y',$remindStr);
    		$gapYear = $nowYear-$eventYear;
    		$gapYear %= $v['UDL_length'];
    		if (($nowDay!=$eventDay) || ($nowMonth!=$eventMonth) || ($gapYear!=0)) {continue;}
    		if ($nowHour==$hour && $nowMinute==$minute) {
    			if ($v['loopEnd'] == 'false') {
    				//如果没有重复结束时间
    				$title = $v['title'];
		    		$place = $v['place'];
		    		$notes = $v['notes'];
		    		$openId = $v['openId'];
		    		$eventId = $v['eventId'];
		    		$startStr = $startStr+$v['remind'];
		    		$startTime = $v['startTime'];
		    		$startYear = date('Y',$startStr);
		    		$startMonth = date('m',$startStr);
		    		$startDay = date('d',$startStr);
		    		$time = $startYear.'年'.$startMonth.'月'.$startDay.'日 '.$v['startHour'].':'.$v['startMinute'];
		    		if ($v['acrossDay'] == 'true') {
		    			$end = db('event')->where('eventId',$eventId)->where('openId',$openId)->order('startStr desc')->find();
		    			$days = db('event')->where('eventId',$eventId)->where('openId',$openId)->field('count(*)')->find();
		    			$days = $days['count(*)']-1;
		    			$endStr = $startStr+(86400*$days);
		    			$year = date('Y',$endStr);
		    			$month = date('m',$endStr);
		    			$day = date('d',$endStr);
		    			$hour = $end['endHour'];
		    			$minute = $end['endMinute'];
		    			$time = $time.'-'.$year.'年'.$month.'月'.$day.'日 '.$hour.':'.$minute;
		    		}else{
		    			$time = $time.'-'.$v['endHour'].':'.$v['endMinute'];
		    		}
		    		$this->model->message($title,$time,$place,$notes,$openId,$eventId,$startStr,$startTime);
    			}
    		}
    	}
    }
}
