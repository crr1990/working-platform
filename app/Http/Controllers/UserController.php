<?php
/**
 * Created by PhpStorm.
 * User: chenrongrong
 * Date: 2019/8/19
 * Time: 2:53 PM
 */

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\OrderTemplateService;
use App\Services\UserInfoService;
use Illuminate\Filesystem\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Monolog\Logger;


class UserController extends Controller
{
    /**
     * 用户列表
     *
     * @param Request $request
     * @param UserInfoService $service
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function userList(Request $request, UserInfoService $service)
    {

        $filter = $request->all();
        $page = $request->input("page", 1);
        $pageSize = $request->input("pageSize", 20);
        $res = $service->userList($filter, $pageSize, ($page - 1) * $pageSize, $page);

        return response()->json([
            "code" => $res['code'],
            "message" => $res['msg'],
            "data" => $res['data']
        ]);
    }

    /**
     * 增加用户
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addUser(Request $request)
    {
        $name = $request->input("name");
        $email = $request->input("email");
        $allowCapacity = $request->input("allowCapacity", 1);
        $desc = $request->input("desc", "");
        $type = $request->input("type", 0);
        $password = $request->input("password", "");
        $template = $request->input("template", "");
        $isEnabled = $request->input("isEnabled", 1);
        $unionId = $request->input("unionId", 0);

        $validator = Validator::make($request->all(), [
            "name" => "required",
            "email" => "required"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "code" => 1004,
                "message" => $validator->errors()->first()
            ]);
        }

        $res = (new UserInfoService())->register($name, $email, intval($allowCapacity), $desc, $password, $type, $template, $isEnabled, $unionId);

        return response()->json([
            "code" => $res['code'],
            "message" => $res['msg']
        ]);
    }

    /**
     * 用户登录
     *
     * @param Request $request
     * @param UserInfoService $userInfoService
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request, UserInfoService $userInfoService)
    {
        $name = $request->input("name");
        $password = $request->input("password");
        $validator = Validator::make($request->all(), [
            "name" => "required",
            "password" => "required"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "code" => 1004,
                "message" => $validator->errors()->first()
            ]);
        }

        session_start();
        if ($request->input("code") && !empty($_SESSION['code'])) {

            if ($request->input("code") != $_SESSION['code']) {
                return response()->json([
                    "code" => 1005,
                    "message" => "验证码错误"
                ]);
            }
        }

        $response = $userInfoService->login($name, $password);
        $response['cede'] = !empty($_SESSION['code']) ? $_SESSION['code'] : 0;
        return response()->json($response);
    }


    /**
     * 用户编辑
     *
     * @param Request $request
     * @param UserInfoService $userInfoService
     * @return \Illuminate\Http\JsonResponse
     */
    public function editUser(Request $request, UserInfoService $userInfoService)
    {
        $id = $request->input("id", "");
        $name = $request->input("name", "");
        $email = $request->input("email", "");
        $unionId = $request->input("union_id", 0);
        $allowCapacity = $request->input("allowCapacity", "");
        $password = $request->input("password", "");
        $isEnabled = $request->input("isEnabled", 1);
        $template = $request->input("template", "");
        $type = $request->input("type", 0);
        $validator = Validator::make($request->all(), [
            "id" => "required"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "code" => 1004,
                "message" => $validator->errors()->first()
            ]);
        }
        $response = $userInfoService->editUserInfo($name, $email,
            $allowCapacity, $password, $isEnabled, $id, $template, $type, $unionId);

        return response()->json($response);
    }

    public function userInfo(Request $request)
    {
        $id = $request->input("userId", 0);
        $validator = Validator::make($request->all(), [
            "userId" => "required",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "code" => 1010,
                "message" => $validator->errors()->first()
            ]);
        }



        $user = User::where('id', (int)$id)->first();
        $userId = $user->id;
        if ($user->type == 0) {
            $user->child = User::where("union_id", $user->id)->where('is_del', 0)->pluck('name', 'id');

        } elseif ($user->type == 2) {
            $user->parent = User::where("id", $user->union_id)->where('is_del', 0)->select("id", "name")->first();
            $userId = $user->union_id;
        }

        $user->template = (new OrderTemplateService())->tempIdByUser($userId);

        $response = [
            'code' => 0,
            'message' => 'success',
            'data' => $user
        ];
        return response()->json($response);

    }

    public function delete(Request $request, UserInfoService $userInfoService)
    {
        $id = $request->input("id", "");
        $userId = $request->input("userId", "");
        if ($id == $userId) {
            return response()->json([
                "code" => 2010,
                "message" => "不能删自己"
            ]);
        }
        $response = $userInfoService->delete($id);
        return response()->json($response);
    }

    public function resetPassword(Request $request, UserInfoService $userInfoService)
    {
        $email = $request->input("email", "");
        $name = $request->input("name", "");
        $validator = Validator::make($request->all(), [
            "name" => "required",
            "email" => "required"
        ]);

        if ($validator->fails()) {
            return response()->json([
                "code" => 1010,
                "message" => $validator->errors()->first()
            ]);
        }

        $res = $userInfoService->resetPassword($name, $email);
        if ($res) {
            return response()->json([
                "code" => 0,
                "message" => 'success'
            ]);
        } else {
            return response()->json([
                "code" => 1011,
                "message" => '用户名和邮箱匹配失败'
            ]);
        }

    }

    public function getMainUserList(Request $request, UserInfoService $userInfoService)
    {
        $name = $request->input("name", "");
        $res = $userInfoService->getMainUserList($name);
        return response()->json([
            "code" => 0,
            "data" => $res,
            "message" => 'success'
        ]);
    }
}