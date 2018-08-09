<?php
namespace app\index\controller;
use app\index\model\All_Model;
use think\Controller;
use think\Db;
class Index extends Controller
{   public $model;
    public function __construct(){
        parent::__construct();
        $this->model = new All_Model;
    }
    public function index(){
        return '测试用api';
    }
    //日历数组API
    public function isplan(){
    	if (empty($_POST)) {die;}
        $for_month = $this->model->for_month();
        //利用"formonth"数组查询第一天和最后一天的时间戳
        $start_and_end = $this->model->start_and_end($for_month['formonth']);
        $startday = $start_and_end[0];
        $endday = $start_and_end[1];
        $openId = $_POST['openId'];
        $calendar = $this->model->isplan($for_month['formonth'],$startday,$endday,$openId);
        $weeks = $for_month['weeks'];
        $week = ["日","一","二","三","四","五","六"];
        $alldata = [
            'weeks'     =>  $weeks,
            'calendar'  =>  $calendar,
            'week'      =>  $week
        ];
        return json_encode($alldata,JSON_UNESCAPED_UNICODE);
    }
    //首屏事件API
    public function twoweek(){
    	if (empty($_POST)) {die;}
        //用户id
        $openId = $_POST['openId'];
        $week = date('w',time());
        $nextWeek = 13-$week;
        $today = strtotime(date('Y-m-d',time()));
        $startDay = $today-(86400*$week);
        $endDay = $today+(86400*$nextWeek);
        //根据用户id查询这2周的事件数组
        $twoweek = $this->model->allthings($openId,$startDay,$endDay);
        return json_encode($twoweek,JSON_UNESCAPED_UNICODE);
    }
    //事件数组API
    public function allthings(){
    	if (empty($_POST)) {die;}
        //用户id
        $openId = $_POST['openId'];
        //调用formonth数组
        $for_month = $this->model->for_month();
        $formonth = $for_month['formonth'];
        //查询日历的开始时间和结束时间
        $start_and_end = $this->model->start_and_end($formonth);
        $startday = $start_and_end[0];
        $endday = $start_and_end[1];
        //查询所有日期内的事件,组成需要的数组
        $allthings = $this->model->allthings($openId,$startday,$endday);
        return json_encode($allthings,JSON_UNESCAPED_UNICODE);
    }
    //无限滚动API
    public function IS(){
    	if (empty($_POST)) {die;}
        $openId = $_POST['openId'];
        $year = $_POST['year'];
        $month = $_POST['month'];
        $type = $_POST['postType'];
        $postTime = $this->model->postTime($year,$month,$type);
        $DATA = $this->model->IS($openId,$postTime);
        $startDay = $postTime['startDay'];
        $endDay = $postTime['endDay'];
        $omThings = $this->model->allthings($openId,$startDay,$endDay);
        $DATA['things'] = $omThings;
        return json_encode($DATA,JSON_UNESCAPED_UNICODE);
    }
    //事件查
    public function select(){
    	if (empty($_GET)) {die;}
        $select = $this->model->select($_GET);
        return json_encode($select,JSON_UNESCAPED_UNICODE);
    }
    //事件增
    public function insert(){
    	if (empty($_POST)) {die;}
        $insert = $this->model->insert($_POST);
        return json_encode($insert,JSON_UNESCAPED_UNICODE);
    }
    //事件删
    public function delete(){
    	if (empty($_POST)) {die;}
        $delete = $this->model->del($_POST);
        return json_encode($delete,JSON_UNESCAPED_UNICODE);
    }
    //事件改
    public function update(){
    	if (empty($_POST)) {die;}
        $update = $this->model->up($_POST);
        return json_encode($update,JSON_UNESCAPED_UNICODE);
    }
    //返回openId
    public function getOpenId(){
        if (empty($_POST)) {die;}
        $code = $_POST['code'];
        $a = file_get_contents('https://api.weixin.qq.com/sns/jscode2session?appid=wxbbab4e1293067b14&secret=2940073ed79ad4d15726e27b04f481a8&js_code='.$code.'&grant_type=authorization_code');
        return $a;
    }
    //将formId加入数据库
    public function formId(){
    	if (empty($_POST)) {die;}
        $form_id = trim($_POST['formId']);
        if ($form_id == 'the formId is a mock one'){die;}
        $openId = $_POST['openId'];
        if ($openId == '') {die;}
        $time = time();
        $data = compact('form_id','openId','time');
        db('formId')->insert($data);
    }
    //判断是否需要授权
    public function ifLogin(){
        if (empty($_POST)) {die;}
        $openId = $_POST['openId'];
        $bool = db('user')->where('openId',$openId)->find();
        if ($bool) {
            if ($bool['nickName'] == '未授权用户') {
                return 'false';
            }else{
                return 'true';
            }
        }else{
            return 'false';
        }
    }
    //执行授权
    public function login(){
        if (empty($_POST)) {die;}
        $data = $_POST;
        $image = file_get_contents($data['avatarUrl']);
        file_put_contents('public/upload/avatarUrl/'.$data['openId'].'.jpg',$image);
        $data['avatarUrl'] = 'https://'.$_SERVER['SERVER_NAME'].'/public/upload/avatarUrl/'.$data['openId'].'.jpg';
        $select = db('user')->where('openId',$data['openId'])->field('openId')->find();
        if ($select) {
            $insert = db('user')->where('openId',$data['openId'])->update($data);
        }else{
            $insert = db('user')->insert($data);
        }
        return json_encode($_POST,JSON_UNESCAPED_UNICODE);
    }
    //不授权
    public function notAuth(){
        if (empty($_POST)) {die;}
        $select = db('user')->where('openId',$_POST['openId'])->field('openId')->find();
        if ($select) {die;}
        $data['openId'] = $_POST['openId'];
        $data['nickName'] = '未授权用户';
        $data['country'] = 'China';
        $data['province'] = '';
        $data['city'] = '';
        $data['language'] = 'zh_CN';
        $data['gender'] = 0;
        $data['avatarUrl'] = 'https://'.$_SERVER['SERVER_NAME'].'/public/static/images/20180802155441.png';
        $insert = db('user')->insert($data);
        return json_encode($insert,JSON_UNESCAPED_UNICODE);
    }
    //查看参与日程
    public function joinSelect(){
        if (empty($_GET)) {die;}
        $select = $this->model->joinSelect($_GET);
        return json_encode($select,JSON_UNESCAPED_UNICODE);
    }
    //参与日程
    public function join(){
        if (empty($_POST)) {die;}
        $insert = $this->model->join($_POST);
        return json_encode($insert,JSON_UNESCAPED_UNICODE);
    }
    //取消参与
    public function exit(){
        if (empty($_POST)) {die;}
        $delete = $this->model->exit($_POST);
        return json_encode($delete,JSON_UNESCAPED_UNICODE);
    }
    //修改提醒时间
    public function joinUpdate(){
        if (empty($_POST)) {die;}
        $update = $this->model->joinUpdate($_POST);
        return json_encode($update,JSON_UNESCAPED_UNICODE);
    }
    public function test(){
        $joinList = db('event as e')->join('user u','u.openId=e.openId')->field('e.openId')->field('avatarUrl')->field('nickName')->where('e.eventId','1533721268159379_4178')->group('e.openId')->order('joinTime esc')->select();
        $select = db('event')->where('eventId','1533721268159379_4178')->field('openId')->field('admin')->group('openId')->order('joinTime esc')->select();
        dump($select);
        dump($joinList);
    }
}