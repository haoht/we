<?php
namespace Portal\Controller;
use Common\Controller\HomebaseController;

class groupController extends HomebaseController{

    protected $groupModel,$topicModel,$joinModel;
    function _initialize()
    {
        header("Content-type:text/html;charset=utf-8");
        $this->groupModel = M('group');
        $this->topicModel = M('topic');
        $this->joinModel = M('join');
        parent::_initialize(); // TODO: Change the autogenerated stub
    }
    /*
     * 小组首页模板
     */
    function group(){
        //type分类
        $sort = I('get.sort');
        if (empty($sort)) {
            $where=array('group_status' => 1);//已审核
        }else{
            $where = array('group_status' => 1, 'group_type' => $sort);
        }
        //所有小组
        $group = $this->groupModel->order('group_id')
            ->where($where)->select();
        //限制介绍的字数
        $group = substring($group, 'group_introduce', 132);
        $group = NullGroupCover($group);

        $this->getActivitySort();
        //$this->member_want();
        $this->assign('hasJoin', $this->hasJoin());
        $this->assign('group',$group);
        $this->assign('newCreateGroup', $this->groupModel->order('group_id desc')->where($where)->limit(5)->select());
        $this->display(":group");
    }

    function hasJoin()
    {
        //已经加入小组的id数组
        $hasJoin = [];
        if (sp_is_user_login()) {
            $hasJoin = $this->joinModel->where('user_id=%d',sp_get_current_userid())->getField('group_id',true);
        }
        return $hasJoin;
    }
    /**
     * 小组分类assign
     */
    protected function getActivitySort(){
        $optionsModel = M('options');
        $typeJson = $optionsModel->where(array('option_name'=>'activity_type'))->getField('option_value');
        $this->assign('activity_type', json_decode($typeJson, true));
    }
    /**
     * 小组详情页模板
     */
    function group_detail(){
        $id = intval(I('group_id'));
        $type = I('order');
        $this->getTopicMsg($id,$type);
        if(empty($id)){
            $this->error("参数错误");
        }else{
            $group = $this->groupModel->where("group_id=%d",$id)->find();
            if(empty($group)){
                $this->error('参数错误');
            }else{

                $this->NullPic($group);
            }
        }
        $this->isJoin($id);
        $this->group_member($id);
        $this->member_want();
        $this->display(":group_detail");
    }

    /**判断是否加入小组
     * @param $id小组id
     */
    protected function isJoin($id){

        if (sp_is_user_login()) {
            $user_id = sp_get_current_userid();
            $if = $this->joinModel->where(array('group_id'=>$id,'user_id'=>$user_id))->find();
            if (empty($if)) {
                $rst =false;
            }else{
                    $rst=true;
                }
        }else{
                $rst = false;
        }
        $this->assign('joinStatus', $rst);
    }

    /**当封面为空时，用默认pic
     * @param $arr 查询结果
     */
    protected function NullPic($arr){

        foreach ($arr as $key => $item) {
            if($key=="group_cover" && empty($item)){
                $arr[$key]=sp_get_asset_upload_path("cover/default_tupian4.png");
            }
        }
        $this->assign('groupMes',$arr);
    }
    /**取得topic详情信息
     * @param $id
     * @param $type
     */
    protected function getTopicMsg($id,$type){
        if (empty($type)) {
            $msg = $this->groupModel->alias('a')
                ->join(C('DB_PREFIX')."topic b ON a.group_id = b.group_id")
                ->join(C('DB_PREFIX')."users c ON b.user_id = c.id")
                ->where("a.group_id=%d and b.topic_status=%d",$id,1)
                ->select();
        }else if($type=="lately"){      //最近话题
            $msg = $this->groupModel->alias('a')
                ->join(C('DB_PREFIX')."topic b ON a.group_id = b.group_id")
                ->join(C('DB_PREFIX')."users c ON b.user_id = c.id")
                ->where("a.group_id=%d and b.topic_status=%d",$id,1)
                ->order('b.topic_id DESC')
                ->select();

        }else if ($type == "hot") {     //最热话题`

        }
        $this->assign('topicMsg',$msg);
    }

    /**
     * 小组新增模板渲染
     */
    function group_add(){

        if (!sp_is_user_login()) {
            $this->error('请登陆后再操作');
        }else if (!sp_auth_check(sp_get_current_userid(), "portal/group/group_add")) {
            $this->error('只有组织用户才能创建小组');
        }else{
            $this->check_user();
            $gid = I('group_id');
            if (!empty($gid)) {
                $currentGroups = $this->groupModel->where(array('group_id'=>I('group_id')))->find();
                $this->assign('currentGroups', $currentGroups);
            }
            //活动类型
            $types = M('options')->where(array('option_name' => 'activity_type'))->find();
            $this->assign('types',  json_decode($types['option_value']));
        }
        $this->display(":group_add");
    }

    /**
     * ajax小组增加
     */
    function do_group_add(){

        $GroupModel =  D('group');
        if (!sp_is_user_login()){
            $this->error('请登陆后重试');
        }
        else if (!sp_auth_check(sp_get_current_userid(), "portal/group/group_add")) {
            $this->error('只有组织用户才能创建小组');
        }else if (!$GroupModel->create()){
            $this->error($GroupModel->getError());
        }else if(!sp_check_verify_code()){
            $this->error('验证码错误，请重新输入');
        }else{
            $this->check_user();
            $gid = I('group_id');
            if (!empty($gid)) {
                $GroupModel->save();
                $this->success('保存编辑成功！');
            }
                $id = $GroupModel->add();
                //存写进去的id，便于上传封面的操作
                setcookie("group_id", $id, time() + 3600);
                //$this->success('新增小组成功，请等待管理员审核',U('group_add/#upload_cover'));
                $this->success('新增小组成功，请等待管理员审核',U('group'));
        }
    }
    /**
     * 加入小组
     */
    function join_group()
    {


        $joinModel = M('join');
        $id = I('get.id');
        $grp = $this->groupModel->where(array('group_id'=>$id))->find();

        $joinMsg = $joinModel->where(array('group_id' => $id, 'user_id' => sp_get_current_userid()))->find();
        if (!sp_is_user_login()) {
            $this->error("您未登陆，请登陆再试",U('user/login/index'));
        }else if(empty($grp)){
            $this->error('小组id不存在');
        }else if($joinMsg){
            $this->error('您已加入小组，不能重复加入');
        }else{
            $this->check_user();
            $res = $joinModel->add(array(
                'group_id'=>$id,
                'user_id'=>sp_get_current_userid(),
                'create_time' => date("Y-m-d H:i:s"),
            ));
            //人数加1
            $this->groupModel->where(array('group_id' => $id))->setInc("group_total");
            if ($res) {
                $this->success('加入小组成功',U('group_detail',array('group_id'=>$id)));
            }
        }
    }

    /**
     * 退出小组
     */
    function exit_group(){

        $id = I('get.id');
        $joinMsg = $this->joinModel->where(array('group_id' => $id, 'user_id' => sp_get_current_userid()))->find();
        if (!sp_is_user_login()) {
            $this->error("您未登陆，请登陆再试",U('user/login/index'));
        }else if(empty($joinMsg)){
            $this->error('已退出该小组，不能重复退出');
        }else{
            $this->check_user();
            //人数减1
            $this->groupModel->where(array('group_id' => $id))->setDec('group_total');
            $res = $this->joinModel->where(array(
                'group_id'=>$id,
                'user_id'=>sp_get_current_userid()
            ))->delete();
            if ($res) {
                $this->success('退出小组成功', U('group'));
            }
        }
    }

    /**返回小组成员相关信息
     * @param $groupId 小组id
     */
    protected function group_member($groupId)
    {
        $result = $this->joinModel->alias("a")
            ->join(C('DB_PREFIX')."users as b ON a.user_id=b.id")
            ->field('b.user_login,b.avatar,b.id')
            ->where("a.group_id=%d",$groupId)
            ->select();
        shuffle($result);
        $slice = array_slice($result, 0, 9);
        $slice = UserAvatar($slice);
        $this->assign('member', $slice);
    }
    private function member_want(){
        $wants = $this->groupModel->field('group_id,group_name,group_introduce,group_total')
            ->limit(130)->select();
        shuffle($wants);
        $slice = array_slice($wants, 0, 5);
        $slices = NullGroupCover($slice);
        $slices = substring($slices,'group_introduce',123);
        $this->assign('wants', $slices);
    }
    /**
     * 组员们想去的小组ajax
     */
    public function member_want_ajax(){

        $JoinNums = $this->hasJoin();
        $JoinNums = empty($JoinNums)?[]:$JoinNums;

        $wants = $this->groupModel->field('group_id,group_name,group_introduce,group_total')
            ->limit(130)->select();
        shuffle($wants);
        $slice = array_slice($wants, 0, 5);
        $slices = NullGroupCover($slice);
        $slices = substring($slices,'group_introduce',123);

        foreach ($slices as $key => $slice) {
            $id = $slice['group_id'];
            //$slices[$key]['isjoin']
            if (in_array($id, $JoinNums)) {
                $slices[$key]['isjoin']=true;
                $slices[$key]['href'] = U('group/group_detail', array('group_id' => $id));

            }else{
                $slices[$key]['isjoin']=false;
                $slices[$key]['href'] = U('group/join_group', array('id' => $id));
            }
        }
        $this->arrayRecursive($slices, 'urlencode', true);
        $json = json_encode($slices);
        echo urldecode($json);
    }


    function arrayRecursive(&$array, $function, $apply_to_keys_also = false)
    {
        static $recursive_counter = 0;
        if (++$recursive_counter > 1000) {
            die('possible deep recursion attack');
        }
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $this->arrayRecursive($array[$key], $function, $apply_to_keys_also);
            } else {
                $array[$key] = $function($value);
            }

            if ($apply_to_keys_also && is_string($key)) {
                $new_key = $function($key);
                if ($new_key != $key) {
                    $array[$new_key] = $array[$key];
                    unset($array[$key]);
                }
            }
        }
        $recursive_counter--;
    }
}