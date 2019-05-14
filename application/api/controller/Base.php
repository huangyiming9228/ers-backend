<?php
namespace app\api\controller;

use think\Controller;
use think\Session;
use think\Db;
use think\Log;

class Base extends Controller {

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

  protected function formatLoginData($status, $currentAuthority, $type, $message = null) {
    return json_encode([
      'status' => $status,
      'currentAuthority' => $currentAuthority,
      'type' => $type,
      'message' => $message
    ]);
  }

  public function backLogin() {
    $user_no = request()->param('userName');
    $psw = request()->param('password');
    $type = request()->param('type');
    if (empty($user_no) || empty($psw)) {
      return $this->formatLoginData('error', 'guest', $type, '账号或密码不能为空！');
    } else {
      $user_info = Db::table('user')->where('user_no', $user_no)->find();
      if (empty($user_info)) {
        return $this->formatLoginData('error', 'guest', $type, '账号不存在！');
      } else {
        if (sha1($psw) != $user_info['psw']) {
          return $this->formatLoginData('error', 'guest', $type, '密码错误！');
        } else {
          Session::set('user_no', $user_info['user_no']);
          return json_encode([
            'status' => 'ok',
            'currentAuthority' => $user_info['auth'],
            'type' => $type,
            'message' => '登录成功！'
          ]);
        }
      }
    }
  }

  public function getCurrentUser() {
    $user_no = Session::get('user_no');
    $user_info = Db::table('user')->where('user_no', $user_no)->find();
    return json_encode([
      'userid' => $user_info['id'],
      'name' => $user_info['user_name'],
      'no' => $user_info['user_no'],
      'tel' => $user_info['tel'],
      'avatar' => 'https://gw.alipayobjects.com/zos/antfincdn/XAosXuNZyF/BiazfanxmamNRoxxVxka.png',
      'email' => $user_info['email'],
      'profile' => $user_info['profile'],
    ]);
  }

  public function updateUserInfo() {
    $params = request()->param();
    $update_success = Db::table('user')->where('id', $params['userid'])->update([
      'tel' => $params['tel'],
      'email' => $params['email'],
      'profile' => $params['profile'],
    ]);
    return $this->formatData('ok', $params, '更新成功！');
    // if ($update_success) {
    //   return $this->formatData('ok', $params, '更新成功！');
    // } else {
    //   return $this->formatData('error', $params, '更新失败！');
    // }
  }

  public function updatePassword() {
    $params = request()->param();
    $old_psw = Db::table('user')->where('user_no', Session::get('user_no'))->value('psw');
    if (sha1($params['oldPassword']) != $old_psw) {
      return $this->formatData('error', null, '原密码错误，修改失败！');
    } else {
      Db::table('user')->where('user_no', Session::get('user_no'))->update([
        'psw' => sha1($params['newPassword1'])
      ]);
      return $this->formatData('ok', null, '修改成功！');
    }
  }

  public function jon() {
    $users = Db::table('user')->select();
    foreach ($users as $key => $value) {
      Db::table('user')->where('id', $value['id'])->update([
        'psw' => sha1($value['psw'])
      ]);
    }
  }

  public function getAreas() {
    $data = Db::table('areas')->select();
    return $this->formatData('ok', $data);
  }

  public function getRooms($id) {
    $data = Db::table('rooms')->where('area_id', $id)->select();
    return $this->formatData('ok', $data);
  }

  public function getEquipments($room) {
    $et_array = Db::table('room_equipment')->where('room_id', $room)->select();
    $data = [];
    foreach ($et_array as $key => $value) {
      $et = Db::table('equipments')->where('id', $value['equipment_id'])->find();
      array_push($data, $et);
    }
    foreach ($data as $key => $value) {
      $data[$key]['type'] = Db::table('equipment_class')->where('id', $value['class_id'])->value('equipment_name');
    }
    return $this->formatData('ok', $data);
  }

  public function getEquipmentClass() {
    $data = Db::table('equipment_class')->select();
    return $this->formatData('ok', $data);
  }

  public function getEquipmentInfo($et_id) {
    $et_info = Db::table('equipments')->where('id', $et_id)->find();
    $room_id = Db::table('room_equipment')->where('equipment_id', $et_id)->value('room_id');
    $area_id = Db::table('rooms')->where('id', $room_id)->value('area_id');
    $et_info['room_id'] = $room_id;
    $et_info['area_id'] = $area_id;
    return $this->formatData('ok', $et_info);
  }

  public function updateEquipmentInfo() {
    $params = request()->param();
    Db::table('equipments')->where('id', $params['id'])->update([
      'et_name' => $params['et_name'],
      'et_no' => $params['et_no'],
      'et_status' => $params['et_status']
    ]);
    Db::table('room_equipment')->where('equipment_id', $params['id'])->update([
      'room_id' => $params['room_id']
    ]);
    return $this->formatData('ok', 'success');
  }

}
