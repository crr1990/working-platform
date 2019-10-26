<?php
/**
 * Created by PhpStorm.
 * User: chenrongrong
 * Date: 2019/9/7
 * Time: 4:46 PM
 */

namespace App\Http\Controllers;


use App\Common\Utils\HttpUrl;
use App\Models\Dics;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController
{
    // 工单执行
    public function start(Request $request)
    {
        // 获取调用地址
        $res = Dics::where("key_name", "job_url")->first();
        $urlArray = json_decode($res);

        HttpUrl::get($urlArray['start_url'], $request->all());
    }

    /**
     * 创建工单
     *
     * @param Request $request
     * @param OrderService $service
     * @return \Illuminate\Http\JsonResponse
     */
    function createJob(Request $request, OrderService $service)
    {
        $validator = Validator::make($request->all(), [
            "tempId" => "required",
            "userId" => "required",
            "jobName" => "required",
            "client" => "required",
            "orderDetail" => "required"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "code" => 2001,
                "message" => $validator->errors()->first()
            ]);
        }

        $data = $request->all();
        $res = $service->createOrder($data["userId"], $data["client"], $data["tempId"], $data["orderDetail"], $data["jobName"]);
        return response()->json([
            "code" => $res["code"],
            "message" => $res["msg"]
        ]);
    }

    /**
     * 删除工单
     *
     * @param Request $request
     * @param OrderService $service
     * @return \Illuminate\Http\JsonResponse
     */
    function deleteJob(Request $request, OrderService $service)
    {
        $validator = Validator::make($request->all(), [
            "ids" => "required",
        ]);
        if ($validator->fails()) {
            return response()->json([
                "code" => 2001,
                "message" => $validator->errors()->first()
            ]);
        }
        $id = $request->get("ids");
        $service->deleteJob($id);
        return response()->json([
            "code" => 0,
            "message" => "success"
        ]);
    }

    /**
     * 编辑工单
     *
     * @param Request $request
     * @param OrderService $service
     * @return \Illuminate\Http\JsonResponse
     */
    function editJob(Request $request, OrderService $service)
    {
        $validator = Validator::make($request->all(), [
            "jobId" => "required",
        ]);
        if ($validator->fails()) {
            return response()->json([
                "code" => 2001,
                "message" => $validator->errors()->first()
            ]);
        }

        $res = $service->editJob($request->get("jobId"), $request->all());
        return response()->json([
            "code" => $res["code"],
            "message" => $res["msg"]
        ]);

    }

    /**
     * 获取工单列表
     *
     * @param Request $request
     * @param OrderService $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function getJobList(Request $request, OrderService $service)
    {
        $data = $request->all();
        $validator = Validator::make($request->all(), [
            "page" => "required",
        ]);
        if ($validator->fails()) {
            return response()->json([
                "code" => 2001,
                "message" => $validator->errors()->first()
            ]);
        }

        $list = $service->orderList($data);
        return response()->json([
            "code" => 0,
            "message" => "success",
            "data" => $list
        ]);
    }


}