<?php
namespace app\api\controller;

use think\Controller;
use think\Session;
use think\Db;
use think\Log;

class Polling extends Controller {

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

  public function getUpsAreas() {
    $data = Db::table('ups_areas')->select();
    foreach ($data as $key => $value) {
      $data[$key]['user_name'] = Db::table('user')->where('user_no', $value['user_no'])->value('user_name');
      $data[$key]['room_count'] = Db::table('ups_rooms')->where('area_id', $value['id'])->count();
    }
    return $this->formatData('ok', $data);
  }

  public function updateUpsAreaUser() {
    $params = request()->param();
    $rooms = $params['keys'];
    $user_no = $params['user_no'];
    foreach ($rooms as $key => $value) {
      Db::table('ups_areas')->where('id', $value)->update(['user_no' => $user_no]);
    }
    return $this->formatData('ok', null);
  }

  public function addUpsArea() {
    $params = request()->param();
    $is_exist = Db::table('ups_areas')->where('area_name', $params['area_name'])->find();
    if ($is_exist) {
      return $this->formatData('error', null, '新增失败，已存在相同名称的区域！');
    } else {
      $flag = Db::table('ups_areas')->insert([
        'area_name' => $params['area_name'],
        'user_no' => $params['user_no'],
      ]);
      if ($flag) {
        return $this->formatData('ok', null, '新增成功！');
      } else {
        return $this->formatData('error', null, '新增失败！');
      }
    }
  }

  public function deleteUpsArea($area_id) {
    $flag = Db::table('ups_areas')->where('id', $area_id)->delete();
    if ($flag) {
      return $this->formatData('ok', null, '删除成功！');
    } else {
      return $this->formatData('error', null, '删除失败！');
    }
  }

  public function getUpsRooms($id) {
    $data = Db::table('ups_rooms')->where('area_id', $id)->select();
    foreach ($data as $key => $value) {
      $data[$key]['user_name'] = Db::table('user')->where('user_no', $value['user_no'])->value('user_name');
    }
    return $this->formatData('ok', $data);
  }

  public function updateUpsRoomUser() {
    $params = request()->param();
    $rooms = $params['keys'];
    $user_no = $params['user_no'];
    foreach ($rooms as $key => $value) {
      Db::table('ups_rooms')->where('id', $value)->update(['user_no' => $user_no]);
    }
    return $this->formatData('ok', null);
  }

  public function addUpsRoom() {
    $params = request()->param();
    $is_exist = Db::table('ups_rooms')->where('area_id', $params['area_id'])->where('room_name', $params['room_name'])->find();
    if ($is_exist) {
      return $this->formatData('error', null, '新增失败，此区域已存在相同名称的教室！');
    } else {
      $flag = Db::table('ups_rooms')->insert([
        'room_name' => $params['room_name'],
        'area_id' => $params['area_id'],
        'user_no' => $params['user_no'],
      ]);
      if ($flag) {
        return $this->formatData('ok', null, '新增成功！');
      } else {
        return $this->formatData('error', null, '新增失败！');
      }
    }
  }

  public function deleteUpsRoom($room_id) {
    $flag = Db::table('ups_rooms')->where('id', $room_id)->delete();
    if ($flag) {
      return $this->formatData('ok', null, '删除成功！');
    } else {
      return $this->formatData('error', null, '删除失败！');
    }
  }

  public function getUpsCheckList() {
    $params = request()->param();
    $conditions = [];
    if ($params['area_id']) $conditions['area_id']  = $params['area_id'];
    if ($params['room_id']) $conditions['room_id']  = $params['room_id'];
    if ($params['start_time'] && $params['end_time']) {
      $conditions['submit_time']  = ['between', [$params['start_time'], $params['end_time']]];
    }
    $data = Db::table('ups_list')->where($conditions)->order('submit_time', 'desc')->select();
    foreach ($data as $key => $value) {
      // 获取图片
      $image_list = Db::table('ups_image')->where('ups_id', $value['id'])->select();
      foreach ($image_list as $sub_key => $sub_value) {
        $image_list[$sub_key]['image_url'] = $this->getImageUrl($sub_value['image_id']);
      }
      $data[$key]['image_list'] = $image_list;
    }
    return $this->formatData('ok', $data);
  }

  public function getMachineAreas() {
    $data = Db::table('machine_areas')->select();
    foreach ($data as $key => $value) {
      $data[$key]['user_name'] = Db::table('user')->where('user_no', $value['user_no'])->value('user_name');
      $data[$key]['room_count'] = Db::table('machine_rooms')->where('area_id', $value['id'])->count();
    }
    return $this->formatData('ok', $data);
  }

  public function updateMachineAreaUser() {
    $params = request()->param();
    $rooms = $params['keys'];
    $user_no = $params['user_no'];
    foreach ($rooms as $key => $value) {
      Db::table('machine_areas')->where('id', $value)->update(['user_no' => $user_no]);
    }
    return $this->formatData('ok', null);
  }

  public function addMachineArea() {
    $params = request()->param();
    $is_exist = Db::table('machine_areas')->where('area_name', $params['area_name'])->find();
    if ($is_exist) {
      return $this->formatData('error', null, '新增失败，已存在相同名称的区域！');
    } else {
      $flag = Db::table('machine_areas')->insert([
        'area_name' => $params['area_name'],
        'user_no' => $params['user_no'],
      ]);
      if ($flag) {
        return $this->formatData('ok', null, '新增成功！');
      } else {
        return $this->formatData('error', null, '新增失败！');
      }
    }
  }

  public function deleteMachineArea($area_id) {
    $flag = Db::table('machine_areas')->where('id', $area_id)->delete();
    if ($flag) {
      return $this->formatData('ok', null, '删除成功！');
    } else {
      return $this->formatData('error', null, '删除失败！');
    }
  }

  public function getMachineRooms($id) {
    $data = Db::table('machine_rooms')->where('area_id', $id)->select();
    foreach ($data as $key => $value) {
      $data[$key]['user_name'] = Db::table('user')->where('user_no', $value['user_no'])->value('user_name');
    }
    return $this->formatData('ok', $data);
  }

  public function updateMachineRoomUser() {
    $params = request()->param();
    $rooms = $params['keys'];
    $user_no = $params['user_no'];
    foreach ($rooms as $key => $value) {
      Db::table('machine_rooms')->where('id', $value)->update(['user_no' => $user_no]);
    }
    return $this->formatData('ok', null);
  }

  public function addMachineRoom() {
    $params = request()->param();
    $is_exist = Db::table('machine_rooms')->where('area_id', $params['area_id'])->where('room_name', $params['room_name'])->find();
    if ($is_exist) {
      return $this->formatData('error', null, '新增失败，此区域已存在相同名称的教室！');
    } else {
      $flag = Db::table('machine_rooms')->insert([
        'room_name' => $params['room_name'],
        'area_id' => $params['area_id'],
        'user_no' => $params['user_no'],
      ]);
      if ($flag) {
        return $this->formatData('ok', null, '新增成功！');
      } else {
        return $this->formatData('error', null, '新增失败！');
      }
    }
  }

  public function deleteMachineRoom($room_id) {
    $flag = Db::table('machine_rooms')->where('id', $room_id)->delete();
    if ($flag) {
      return $this->formatData('ok', null, '删除成功！');
    } else {
      return $this->formatData('error', null, '删除失败！');
    }
  }

  public function getMachineCheckList() {
    $params = request()->param();
    $conditions = [];
    if ($params['area_id']) $conditions['area_id']  = $params['area_id'];
    if ($params['room_id']) $conditions['room_id']  = $params['room_id'];
    if ($params['start_time'] && $params['end_time']) {
      $conditions['submit_time']  = ['between', [$params['start_time'], $params['end_time']]];
    }
    $data = Db::table('machine_list')->where($conditions)->order('submit_time', 'desc')->select();
    foreach ($data as $key => $value) {
      // 获取图片
      $image_list = Db::table('machine_image')->where('machine_id', $value['id'])->select();
      foreach ($image_list as $sub_key => $sub_value) {
        $image_list[$sub_key]['image_url'] = $this->getImageUrl($sub_value['image_id']);
      }
      $data[$key]['image_list'] = $image_list;
    }
    return $this->formatData('ok', $data);
  }

  public function getWarehouseAreas() {
    $data = Db::table('warehouse_areas')->select();
    foreach ($data as $key => $value) {
      $data[$key]['user_name'] = Db::table('user')->where('user_no', $value['user_no'])->value('user_name');
      $data[$key]['room_count'] = Db::table('warehouse_rooms')->where('area_id', $value['id'])->count();
    }
    return $this->formatData('ok', $data);
  }

  public function updateWarehouseAreaUser() {
    $params = request()->param();
    $rooms = $params['keys'];
    $user_no = $params['user_no'];
    foreach ($rooms as $key => $value) {
      Db::table('warehouse_areas')->where('id', $value)->update(['user_no' => $user_no]);
    }
    return $this->formatData('ok', null);
  }

  public function addWarehouseArea() {
    $params = request()->param();
    $is_exist = Db::table('warehouse_areas')->where('area_name', $params['area_name'])->find();
    if ($is_exist) {
      return $this->formatData('error', null, '新增失败，已存在相同名称的区域！');
    } else {
      $flag = Db::table('warehouse_areas')->insert([
        'area_name' => $params['area_name'],
        'user_no' => $params['user_no'],
      ]);
      if ($flag) {
        return $this->formatData('ok', null, '新增成功！');
      } else {
        return $this->formatData('error', null, '新增失败！');
      }
    }
  }

  public function deleteWarehouseArea($area_id) {
    $flag = Db::table('warehouse_areas')->where('id', $area_id)->delete();
    if ($flag) {
      return $this->formatData('ok', null, '删除成功！');
    } else {
      return $this->formatData('error', null, '删除失败！');
    }
  }

  public function getWarehouseRooms($id) {
    $data = Db::table('warehouse_rooms')->where('area_id', $id)->select();
    foreach ($data as $key => $value) {
      $data[$key]['user_name'] = Db::table('user')->where('user_no', $value['user_no'])->value('user_name');
    }
    return $this->formatData('ok', $data);
  }

  public function updateWarehouseRoomUser() {
    $params = request()->param();
    $rooms = $params['keys'];
    $user_no = $params['user_no'];
    foreach ($rooms as $key => $value) {
      Db::table('warehouse_rooms')->where('id', $value)->update(['user_no' => $user_no]);
    }
    return $this->formatData('ok', null);
  }

  public function addWarehouseRoom() {
    $params = request()->param();
    $is_exist = Db::table('warehouse_rooms')->where('area_id', $params['area_id'])->where('room_name', $params['room_name'])->find();
    if ($is_exist) {
      return $this->formatData('error', null, '新增失败，此区域已存在相同名称的教室！');
    } else {
      $flag = Db::table('warehouse_rooms')->insert([
        'room_name' => $params['room_name'],
        'area_id' => $params['area_id'],
        'user_no' => $params['user_no'],
      ]);
      if ($flag) {
        return $this->formatData('ok', null, '新增成功！');
      } else {
        return $this->formatData('error', null, '新增失败！');
      }
    }
  }

  public function deleteWarehouseRoom($room_id) {
    $flag = Db::table('warehouse_rooms')->where('id', $room_id)->delete();
    if ($flag) {
      return $this->formatData('ok', null, '删除成功！');
    } else {
      return $this->formatData('error', null, '删除失败！');
    }
  }

  public function getWarehouseCheckList() {
    $params = request()->param();
    $conditions = [];
    if ($params['area_id']) $conditions['area_id']  = $params['area_id'];
    if ($params['room_id']) $conditions['room_id']  = $params['room_id'];
    if ($params['start_time'] && $params['end_time']) {
      $conditions['submit_time']  = ['between', [$params['start_time'], $params['end_time']]];
    }
    $data = Db::table('warehouse_list')->where($conditions)->order('submit_time', 'desc')->select();
    foreach ($data as $key => $value) {
      // 获取图片
      $image_list = Db::table('warehouse_image')->where('warehouse_id', $value['id'])->select();
      foreach ($image_list as $sub_key => $sub_value) {
        $image_list[$sub_key]['image_url'] = $this->getImageUrl($sub_value['image_id']);
      }
      $data[$key]['image_list'] = $image_list;
    }
    return $this->formatData('ok', $data);
  }

}
