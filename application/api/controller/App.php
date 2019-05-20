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

  public function getAllAreas() {
    $data = Db::table('areas')->select();
    return $this->formatData('ok', $data);
  }

  public function getAllRooms($id) {
    $data = Db::table('rooms')->where('area_id', $id)->select();
    return $this->formatData('ok', $data);
  }

  public function saveFoodcomplaint() {
    $params = request()->param();
    $image_list = json_decode($params['image_list']);
    unset($params['image_list']);
    $params['submit_time'] = date('Y-m-d H:i:s');
    $foodcomplaint_id = Db::table('food_complaint')->insertGetId($params);
    if ($foodcomplaint_id) {
      foreach($image_list as $key => $value) {
        Db::table('foodcomplaint_image')->insert([
          'foodcomplaint_id' => $foodcomplaint_id ,
          'image_id' => $value
        ]);
      }
      return $this->formatData('ok', null, '提交成功！');
    } else {
      return $this->formatData('error', null, '提交失败！');
    }
  }

  public function saveFaultcomplaint() {
    $params = request()->param();
    $image_list = json_decode($params['image_list']);
    unset($params['image_list']);
    $params['submit_time'] = date('Y-m-d H:i:s');
    $faultcomplaint_id = Db::table('fault_complaint')->insertGetId($params);
    if ($faultcomplaint_id) {
      foreach($image_list as $key => $value) {
        Db::table('faultcomplaint_image')->insert([
          'faultcomplaint_id' => $faultcomplaint_id ,
          'image_id' => $value
        ]);
      }
      return $this->formatData('ok', null, '提交成功！');
    } else {
      return $this->formatData('error', null, '提交失败！');
    }
  }

  public function getFaultClassList() {
    $params = request()->param();
    $data = Db::table('faults_class')->where('class_id', $params['class_id'])->select();
    return $this->formatData('ok', $data);
  }

  public function saveFaulthanding() {
    $params = request()->param();
    $fault_list = json_decode($params['fault_list']);
    unset($params['fault_list']);
    $params['submit_time'] = date('Y-m-d H:i:s');
    $faulthanding_id = Db::table('faulthanding_list')->insertGetId($params);
    if ($faulthanding_id) {
      foreach ($fault_list as $key => $value) {
        Db::table('equipment_fault')->insert([
          'faulthanding_id' => $faulthanding_id,
          'fault_id' => $value
        ]);
      }
      return $this->formatData('ok', null, '提交成功！');
    } else {
      return $this->formatData('error', null, '服务器错误！');
    }
  }

  public function getUsers() {
    $data = Db::table('user')->select();
    return $this->formatData('ok', $data);
  }

  public function saveTechhanding() {
    $params = request()->param();
    $fault_list = json_decode($params['fault_list']);
    unset($params['fault_list']);
    $params['submit_time'] = date('Y-m-d H:i:s');
    $params['status'] = 0;
    $techhanding_id = Db::table('techhanding_list')->insertGetId($params);
    if ($techhanding_id) {
      foreach ($fault_list as $key => $value) {
        Db::table('techhanding_fault')->insert([
          'techhanding_id' => $techhanding_id,
          'fault_id' => $value
        ]);
      }
      return $this->formatData('ok', null, '提交成功！');
    } else {
      return $this->formatData('error', null, '服务器错误！');
    }
  }

  public function getFaultList() {
    $params = request()->param();
    $techhanding_list = [];
    if ($params['auth'] == 'admin') {
      $techhanding_list = Db::table('techhanding_list')->where('status', 0)->order('submit_time', 'desc')->select();
    } else {
      $techhanding_list = Db::table('techhanding_list')->where('status', 0)->where('user', $params['user_no'])->order('submit_time', 'desc')->select();
    }
    foreach ($techhanding_list as $key => $value) {
      $techhanding_list[$key]['area_name'] = Db::table('areas')->where('id', $value['area_id'])->value('area_name');
      $techhanding_list[$key]['room_name'] = Db::table('rooms')->where('id', $value['room_id'])->value('room_name');

      // 获取设备信息
      $equipment_info = Db::table('equipments')->where('id', $value['equipment_id'])->find();
      $equipment_info['type'] = Db::table('equipment_class')->where('id', $equipment_info['class_id'])->value('equipment_name');
      $techhanding_list[$key]['equipment_info'] = $equipment_info;

      // 获取故障信息
      $fault_list = Db::table('techhanding_fault')->where('techhanding_id', $value['id'])->select();
      foreach ($fault_list as $sub_key => $sub_value) {
        $fault_list[$sub_key]['fault_name'] = Db::table('faults_class')->where('id', $sub_value['fault_id'])->value('fault_name');
      }
      $techhanding_list[$key]['fault_list'] = $fault_list;
    }
    return $this->formatData('ok', $techhanding_list);
  }

  public function updateTechhanding($id) {
    $flag = Db::table('techhanding_list')->where('id', $id)->update([
      'complete_time' => date('Y-m-d H:i:s'),
      'status' => 1
    ]);
    if ($flag) {
      return $this->formatData('ok', null, '提交成功！');
    } else {
      return $this->formatData('error', null, '提交失败！');
    }
  }

  public function getMachineAreaList() {
    $params = request()->param();
    $data = Db::table('machine_areas')->select();
    return $this->formatData('ok', $data);
  }

  public function getMachineRoomList($id) {
    $data = Db::table('machine_rooms')->where('area_id', $id)->select();
    return $this->formatData('ok', $data);
  }

  public function saveMachineCheck() {
    $params = request()->param();
    $image_list = json_decode($params['image_list']);
    unset($params['image_list']);
    $params['submit_time'] = date('Y-m-d H:i:s');
    $machine_id = Db::table('machine_list')->insertGetId($params);
    if ($machine_id) {
      foreach($image_list as $key => $value) {
        Db::table('machine_image')->insert([
          'machine_id' => $machine_id ,
          'image_id' => $value
        ]);
      }
      return $this->formatData('ok', null, '提交成功！');
    } else {
      return $this->formatData('error', null, '服务器错误！');
    }
  }

  public function getWarehouseAreaList() {
    $params = request()->param();
    $data = Db::table('warehouse_areas')->select();
    return $this->formatData('ok', $data);
  }

  public function getWarehouseRoomList($id) {
    $data = Db::table('warehouse_rooms')->where('area_id', $id)->select();
    return $this->formatData('ok', $data);
  }

  public function saveWarehouseCheck() {
    $params = request()->param();
    $image_list = json_decode($params['image_list']);
    unset($params['image_list']);
    $params['submit_time'] = date('Y-m-d H:i:s');
    $warehouse_id = Db::table('warehouse_list')->insertGetId($params);
    if ($warehouse_id) {
      foreach($image_list as $key => $value) {
        Db::table('warehouse_image')->insert([
          'warehouse_id' => $warehouse_id ,
          'image_id' => $value
        ]);
      }
      return $this->formatData('ok', null, '提交成功！');
    } else {
      return $this->formatData('error', null, '服务器错误！');
    }
  }

}