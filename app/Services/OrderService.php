<?php
/**
 * Created by PhpStorm.
 * User: chenrongrong
 * Date: 2019/9/22
 * Time: 7:10 PM
 */

namespace App\Services;

use App\Common\Utils\HttpUrl;
use App\Models\Dics;
use App\Models\Order;
use App\Models\OrderTemplateParams;

class OrderService
{

    /**
     * @param $userId
     * @param $tempId
     * @param $client
     * @param $jobName
     * @param $params array 按顺序传值
     *
     * @return array
     */
    function createOrder($userId, $client, $tempId, $params, $jobName)
    {
        $job = Order::where("job_name", $jobName)->first();
        if (!empty($job)) {
            return [
                "code" => 3001,
                "msg" => "工单重复"
            ];
        }

        $tempParams = OrderTemplateParams::where("temp_id", $tempId)->orderBy("sort")->get()->toArray();

        $options = [];
        // 组装数据
        foreach ($tempParams as $key => $v) {
            $tempParams[$key]["value"] = $params[$key];
            $options[] = [$v["name"] => $params[$key]];
        }

        Order::create([
            "user_id" => $userId,
            "job_name" => $jobName,
            "temp_id" => $tempId,
            "client" => $client,
            "order_detail" => json_encode($tempParams),
        ]);

        // 调用第三方创建工单数据
        // 获取调用地址
        $res = Dics::where("key_name", "job_url")->first();
        $urlArray = json_decode($res['value'], true);
        HttpUrl::get($urlArray['create_url'], $options);

        return [
            "code" => 0,
            "msg" => "success"
        ];
    }

    /**
     * @param $filter
     */
    function orderList($filter)
    {
        $order = Order::where("is_enabled", 1);
        if (isset($filter['userId']) && !empty($filter["userId"])) {
            $order->where("user_id", $filter["userId"]);
        }

        if (isset($filter['startTime']) && !empty($filter["startTime"])) {
            $order->where("create_time", ">=", $filter["startTime"]);
        }

        if (isset($filter['endTime']) && !empty($filter["endTime"])) {
            $order->where("create_time", "<=", $filter["endTime"]);
        }

        if (isset($filter['jobId']) && !empty($filter["jobId"])) {
            $order->where("job_id", $filter["jobId"]);
        }

        if (isset($filter['jobName']) && !empty($filter["jobName"])) {
            $order->where("job_name", $filter["jobName"]);
        }


        if (!empty($filter["sort"])) {
            switch ($filter["sort"]) {
                case 1:
                    $order->orderBy("create_time", "desc");
                    break;
                case 2:
                    $order->orderBy("job_name", "desc");
                    break;
                case 3:
                    $order->orderBy("client", "desc");
                    break;
                default :
                    $order->orderBy("create_time", "desc");
            }
        } else {
            $order->orderBy("create_time", "desc");
        }
        $total = $order->count();
        $limit = 20;
        $pageTotal = ceil($total / $limit);

        $res = $order->limit($limit)->offset(($filter['page'] - 1) * $limit)->get()->toArray();
        $result = [];
        foreach ($res as $v) {
            $result[] = [
                'jobName' => $v['job_name'],
                'client' => $v['client'],
                'createTime' => $v['create_time'],
                'detail' => json_decode($v['order_detail'])
            ];
        }

        return [
            "total" => $total,
            "pageTotal" => $pageTotal,
            "pageSize" => $limit,
            "currentPage" => $filter['page'],
            "list" => $result,
        ];
    }

    function deleteJob($id)
    {
        $order = Order::where("id", $id)->first();
        $order->is_enabled = 0;
        $order->save();
    }

    function editJob($id, $data)
    {
        $order = Order::where("id", $id)->first();
        if (empty($order)) {
            return [
                "code" => 3002,
                "msg" => "工单不存在"
            ];
        }

        if (isset($data["jobName"]) && !empty($data["jobName"])) {
            $job = Order::where("job_name", $data["jobName"])->first();
            if (!empty($job)) {
                return [
                    "code" => 3001,
                    "msg" => "工单重复"
                ];
            }
            $order->job_name = $data["jobName"];
            $order->save();
        }

        if (isset($data["order_detail"]) && !empty($data["order_detail"])) {
            $order->order_detail = json_encode($data["order_detail"]);
            $order->save();
        }

        return [
            "code" => 0,
            "msg" => "success"
        ];
    }
}