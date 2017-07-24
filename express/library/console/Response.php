<?php

/**
 * Response类
 * @author 刘健 <code.liu@qq.com>
 */

namespace express\web;

use express\base\Object;

class Response extends Object
{

    // 格式值
    const FORMAT_RAW = -1;
    // 输出格式
    public $format = self::FORMAT_RAW;
    // 内容
    private $content;

    // 设置内容
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    // 输出
    public function send()
    {
        $content = $this->content;
        if (is_array($content)) {
            switch ($this->format) {
                case self::FORMAT_RAW:
                    $content = 'Array';
                    break;
            }
        }
        if (is_scalar($content)) {
            echo $content;
        }
    }

}
