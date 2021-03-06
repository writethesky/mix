<?php

namespace WebSocket\Controllers;

use Mix\Core\Timer;
use Mix\Helper\JsonHelper;
use Mix\Redis\Coroutine\RedisConnection;
use Mix\WebSocket\Frame\TextFrame;
use WebSocket\Models\JoinForm;

/**
 * Class JoinController
 * @package WebSocket\Controllers
 * @author liu,jian <coder.keda@gmail.com>
 */
class JoinController
{

    /**
     * 加入房间
     * @param $params
     * @param $id
     */
    public function actionRoom($params, $id)
    {
        // 验证数据
        $model             = new JoinForm();
        $model->attributes = $params;
        $model->setScenario('actionRoom');
        if (!$model->validate()) {
            $response = new TextFrame([
                'data' => JsonHelper::encode([
                    'result' => [
                        'message' => $model->getError(),
                    ],
                    'id'     => $id,
                ], JSON_UNESCAPED_UNICODE),
            ]);
            app()->ws->push($response);
            return;
        }

        // 保存当前加入的房间
        app()->tcpSession->set('roomid', $model->roomid);

        // 重复加入处理
        if ($subConn = app()->tcpSession->get('subConn')) {
            /** @var \Mix\Redis\Coroutine\RedisConnection $subConn */
            $subConn->disconnect();
        }

        // 订阅房间的频道
        xgo(function () use ($model) {
            // 订阅房间的频道
            $subConn = RedisConnection::newInstance();
            app()->tcpSession->set('subConn', $subConn);
            try {
                $subConn->subscribe(["room_{$model->roomid}"], function ($instance, $channel, $message) {
                    $frame = new TextFrame([
                        'data' => $message,
                    ]);
                    app()->ws->push($frame);
                });
            } catch (\Throwable $e) {
                // disconnect() 时会导致 subscribe 抛出错误
            }
        });

        // 给当前房间发送消息
        Timer::new()->after(100, function () use ($model, $id) {
            $name     = app()->tcpSession->get('name');
            $response = JsonHelper::encode([
                'result' => [
                    'message' => "{$name} 加入 {$model->roomid} 房间.",
                ],
                'id'     => $id,
            ], JSON_UNESCAPED_UNICODE);
            $conn     = app()->redisPool->getConnection();
            $conn->publish("room_{$model->roomid}", $response);
            $conn->release();
        });
    }

}
