<?php
namespace app\index\model;
use think\Model;
class TimeModel extends Model
{
	public function message($title,$time,$place,$notes,$openId,$eventId,$startStr,$startTime){
        $formId = db('form_id')->where('openId',$openId)->find();
        if ($formId == null) {die;}
        $access_token = db('token')->where('id',1)->find();
        $access_token = $access_token['access_token'];
        $request_url='https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='.$access_token;
        $formId = $formId['form_id'];
        db('form_id')->where('form_id',$formId)->delete();
        $request_data=[
            'touser' => $openId,  
            'template_id' => 'qGE0675T5oJyMLAblXs9KM7oSq31xIk9NvktRl6ffEE',
            //todo
            'page'  =>  'pages/view_event/view_event?openId='.$openId.'&eventId='.$eventId.'&startStr='.$startStr.'&startTime='.$startTime,
            "form_id"   =>  $formId,
            'data'  =>  [
                'keyword1'  =>  [
                    'value' =>  $title
                ],
                'keyword2'  =>  [
                    'value' =>  $time
                ],
                'keyword3'  =>  [
                    'value' =>  $place
                ],
                'keyword4'  =>  [
                    'value' =>  $notes
                ],
                'keyword5'  =>  [
                    'value' =>  '就你一个'
                ]
            ],
            'emphasis_keyword'  =>  "keyword1.DATA"
        ];
        $return=json_decode(https_request($request_url,$request_data,'json'),true);
        return $return;
	}
}