<?php
namespace app\api\controller;

use think\Controller;
use think\Session;
use think\Db;
use think\Log;

class App extends Controller {

  public function _initialize() {
    sleep(1);
  }

  protected function formatData($status, $data, $message = null) {
    return json_encode([
      'status' => $status,
      'data' => $data,
      'message' => $message
    ]);
  }

  public function getAreas() {
    $params = request()->param();
    $data = Db::table('areas')->select();
    return $this->formatData('ok', $data);
  }

  public function getRooms() {
    $params = request()->param();
    $data = Db::table('rooms')->where('area_id', $params['id'])->select();
    return $this->formatData('ok', $data);
  }

  public function getEquipments() {
    $params = request()->param();
    $et_array = Db::table('room_equipment')->where('room_id', $params['room_id'])->select();
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

  public function updateEquipmentInfo() {
    $params = request()->param();
    $flag = Db::table('room_equipment')->where('equipment_id', $params['et_id'])->update([
      'room_id' => $params['room_id']
    ]);
    if ($flag) {
      return $this->formatData('ok', null, '修改成功！');
    } else {
      return $this->formatData('error', null, '修改失败！');
    }
  }

  public function saveEtCheckout() {
    $params = request()->param();
    $flag = Db::table('et_checkout')->insert($params);
    if ($flag) {
      return $this->formatData('ok', null, '提交成功！');
    } else {
      return $this->formatData('error', null, '提交失败！');
    }
  }

}