<?php
namespace app\api\controller;

use think\Controller;
use think\Session;
use think\Db;
use think\Log;

class EtManage extends Controller {

  public function _initialize() {
    // sleep(1);
  }

  protected function formatData($status, $data, $message = null) {
    return json_encode([
      'status' => $status,
      'data' => $data,
      'message' => $message
    ]);
  }

  protected function getImageUrl($image_id) {
    $image_info = Db::table('image_list')->where('id', $image_id)->find();
    $url = 'http://localhost/ers/'.$image_info['save_path'].$image_info['file_name'];
    return $url;
  }

  public function getAllAreas() {
    $data = Db::table('areas')->select();
    return $this->formatData('ok', $data);
  }

  public function getAllRooms($id) {
    $data = Db::table('rooms')->where('area_id', $id)->select();
    return $this->formatData('ok', $data);
  }

  public function getEtCheckoutList() {
    $params = request()->param();
    $data = Db::table('et_checkout')
      ->where('checkout_date', 'between', [$params['start_time'], $params['end_time']])
      ->order('checkout_date', 'desc')
      ->select();
    foreach ($data as $key => $value) {
      $data[$key]['image_url'] = $this->getImageUrl($value['image_id']);
    }
    return $this->formatData('ok', $data);
  }

  public function getFoodComplaintList() {
    $params = request()->param();
    $auth = Session::get('user_auth');
    $user_no = Session::get('user_no');
    $data = [];
    if ($params['start_time'] && $params['end_time']) {
      $data = Db::table('food_complaint')
        ->where('submit_time', 'between', [$params['start_time'], $params['end_time']])
        ->order('submit_time', 'desc')
        ->select();
    } else {
      $data = Db::table('food_complaint')
        ->order('submit_time', 'desc')
        ->select();
    }
    foreach ($data as $key => $value) {
      // 获取教室
      $room_info = Db::table('rooms')->where('id', $value['room_id'])->find();
      $area_info = Db::table('areas')->where('id', $room_info['area_id'])->find();
      $data[$key]['area_name'] = $area_info['area_name'];
      $data[$key]['room_name'] = $room_info['room_name'];

      // 获取教室负责人
      $room_user_info =  Db::table('user')->where('user_no', $room_info['user_no'])->find();
      $data[$key]['room_user_name'] = $room_user_info['user_name'];
      $data[$key]['room_user_no'] = $room_user_info['user_no'];

      // 获取区域负责人
      $area_user_info =  Db::table('user')->where('user_no', $area_info['user_no'])->find();
      $data[$key]['area_user_name'] = $area_user_info['user_name'];
      $data[$key]['area_user_no'] = $area_user_info['user_no'];

      // 获取图片
      $image_list = Db::table('foodcomplaint_image')->where('foodcomplaint_id', $value['id'])->select();
      foreach ($image_list as $sub_key => $sub_value) {
        $image_list[$sub_key]['image_url'] = $this->getImageUrl($sub_value['image_id']);
      }
      $data[$key]['image_list'] = $image_list;
    }
    $res = [];
    if ($auth == 'room_admin') {
      foreach ($data as $key => $value) {
        if ($value['room_user_no'] == $user_no) {
          array_push($res, $value);
        }
      }
    } else if ($auth == 'area_admin') {
      foreach ($data as $key => $value) {
        if ($value['area_user_no'] == $user_no) {
          array_push($res, $value);
        }
      }
    } else {
      $res = $data;
    }
    return $this->formatData('ok', $res);
  }

  public function getFaultComplaintList() {
    $params = request()->param();
    $auth = Session::get('user_auth');
    $user_no = Session::get('user_no');
    $data = [];
    if ($params['start_time'] && $params['end_time']) {
      $data = Db::table('fault_complaint')
        ->where('submit_time', 'between', [$params['start_time'], $params['end_time']])
        ->order('submit_time', 'desc')
        ->select();
    } else {
      $data = Db::table('fault_complaint')
        ->order('submit_time', 'desc')
        ->select();
    }
    foreach ($data as $key => $value) {
      // 获取教室
      $room_info = Db::table('rooms')->where('id', $value['room_id'])->find();
      $area_info = Db::table('areas')->where('id', $room_info['area_id'])->find();
      $data[$key]['area_name'] = $area_info['area_name'];
      $data[$key]['room_name'] = $room_info['room_name'];

      // 获取教室负责人
      $room_user_info =  Db::table('user')->where('user_no', $room_info['user_no'])->find();
      $data[$key]['room_user_name'] = $room_user_info['user_name'];
      $data[$key]['room_user_no'] = $room_user_info['user_no'];

      // 获取区域负责人
      $area_user_info =  Db::table('user')->where('user_no', $area_info['user_no'])->find();
      $data[$key]['area_user_name'] = $area_user_info['user_name'];
      $data[$key]['area_user_no'] = $area_user_info['user_no'];

      // 获取图片
      $image_list = Db::table('faultcomplaint_image')->where('faultcomplaint_id', $value['id'])->select();
      foreach ($image_list as $sub_key => $sub_value) {
        $image_list[$sub_key]['image_url'] = $this->getImageUrl($sub_value['image_id']);
      }
      $data[$key]['image_list'] = $image_list;
    }
    $res = [];
    if ($auth == 'room_admin') {
      foreach ($data as $key => $value) {
        if ($value['room_user_no'] == $user_no) {
          array_push($res, $value);
        }
      }
    } else if ($auth == 'area_admin') {
      foreach ($data as $key => $value) {
        if ($value['area_user_no'] == $user_no) {
          array_push($res, $value);
        }
      }
    } else {
      $res = $data;
    }
    return $this->formatData('ok', $res);
  }

  public function getFaultHandingList() {
    $params = request()->param();
    $conditions = [];
    if ($params['area_id']) $conditions['area_id']  = $params['area_id'];
    if ($params['room_id']) $conditions['room_id']  = $params['room_id'];
    if ($params['start_time'] && $params['end_time']) {
      $conditions['submit_time']  = ['between', [$params['start_time'], $params['end_time']]];
    }
    $data = Db::table('faulthanding_list')->where($conditions)->order('submit_time', 'desc')->select();
    foreach ($data as $key => $value) {
      // 获取区域、教室信息
      $room_info = Db::table('rooms')->where('id', $value['room_id'])->find();
      $area_info = Db::table('areas')->where('id', $value['area_id'])->find();
      $data[$key]['area_name'] = $area_info['area_name'];
      $data[$key]['room_name'] = $room_info['room_name'];

      // 获取设备信息
      $et_info = Db::table('equipments')->where('id', $value['equipment_id'])->find();
      $et_info['et_type'] = Db::table('equipment_class')->where('id', $et_info['class_id'])->value('equipment_name');
      $data[$key]['et_info'] = $et_info;

      // 获取故障列表
      $fault_list = Db::table('equipment_fault')->where('faulthanding_id', $value['id'])->select();
      foreach ($fault_list as $sub_key => $sub_value) {
        $fault_list[$sub_key]['fault_name'] = Db::table('faults_class')->where('id', $sub_value['fault_id'])->value('fault_name');
      }
      $data[$key]['fault_list'] = $fault_list;
    }
    return $this->formatData('ok', $data);
  }

  public function getTechHandingList() {
    $params = request()->param();
    $conditions = [];
    if ($params['area_id']) $conditions['area_id']  = $params['area_id'];
    if ($params['room_id']) $conditions['room_id']  = $params['room_id'];
    if ($params['start_time'] && $params['end_time']) {
      $conditions['submit_time']  = ['between', [$params['start_time'], $params['end_time']]];
    }
    $data = Db::table('techhanding_list')->where($conditions)->order('submit_time', 'desc')->select();
    foreach ($data as $key => $value) {
      // 获取区域、教室信息
      $room_info = Db::table('rooms')->where('id', $value['room_id'])->find();
      $area_info = Db::table('areas')->where('id', $value['area_id'])->find();
      $data[$key]['area_name'] = $area_info['area_name'];
      $data[$key]['room_name'] = $room_info['room_name'];

      // 获取设备信息
      $et_info = Db::table('equipments')->where('id', $value['equipment_id'])->find();
      $et_info['et_type'] = Db::table('equipment_class')->where('id', $et_info['class_id'])->value('equipment_name');
      $data[$key]['et_info'] = $et_info;

      // 获取故障列表
      $fault_list = Db::table('techhanding_fault')->where('techhanding_id', $value['id'])->select();
      foreach ($fault_list as $sub_key => $sub_value) {
        $fault_list[$sub_key]['fault_name'] = Db::table('faults_class')->where('id', $sub_value['fault_id'])->value('fault_name');
      }
      $data[$key]['fault_list'] = $fault_list;
    }
    return $this->formatData('ok', $data);
  }


}
