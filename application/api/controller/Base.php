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
