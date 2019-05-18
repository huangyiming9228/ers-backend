<?php
namespace app\api\controller;

use think\Controller;
use think\Session;
use think\Db;
use think\Log;

class Login extends Controller {

  private $default_time = 3600;

  public function login_check() {
      $user_no = request()->param('user_no');
      $psw = request()->param('psw');
      if (empty($user_no) || empty($psw)) {
        $data = [
          'data' => null,
          'message' => '用户名或密码不能为空！',
          'status' => 'error'
        ];
        return json_encode($data);
      } else {
        $user_info = Db::table('user')->where('user_no', $user_no)->find();
        if (empty($user_info)) {
          $data = [
            'data' => null,
            'message' => '用户不存在！',
            'status' => 'error'
          ];
          return json_encode($data);
        } else {
          if (sha1($psw) != $user_info['psw']) {
            $data = [
              'data' => null,
              'message' => '密码错误！',
              'status' => 'error'
            ];
            return json_encode($data);
          } else {
            $token = $this->make_token();
            $time_out = time() + 3600;
            $is_login_success = Db::table('user')->where('user_no', $user_no)->update([
              'token' => $token,
              'time_out' => $time_out,
            ]);
            if ($is_login_success) {
              $data = [
                'data' => [
                  'token' => $token,
                  'user_name' => $user_info['user_name'],
                  'user_no' => $user_info['user_no'],
                  'auth' => $user_info['auth'],
                  'auth_name' => $user_info['auth_name'],
                  'tel' => $user_info['tel'],
                  'email' => $user_info['email'],
                ],
                'message' => '登录成功！',
                'status' => 'ok'
              ];
              return json_encode($data);
            } else {
              $data = [
                'data' => null,
                'message' => '服务器错误！',
                'status' => 'error'
              ];
              return json_encode($data);
            }
          }
        }
      }
  }

  protected function make_token(){
    $str = md5(uniqid(md5(microtime(true)), true)); //生成一个不会重复的字符串
    $str = sha1($str); //加密
    return $str;
  }

  public function check_token($token) {
    $user_info = Db::table('user')->where('token', $token)->find();
    if (!empty($user_info)) {
      if (time() - $user_info['time_out'] < 0) {
        $new_time_out = time() + $this->$default_time;
        $is_refresh = Db::table('user')->where('token', $token)->update([
          'time_out' => $new_time_out
        ]);
        return $is_refresh ? 1001 : 1009;
      } else {
        return 1002;
      }
    } else {
      return 1003;
    }
  }
}