<?php
/*
 * 阿里云短信认证服务（PNVS）- 开发者版
 * 调用号码认证服务 Dypnsapi / SendSmsVerifyCode。
 * 魔方业务系统负责生成/校验验证码；插件把验证码作为模板变量传给阿里云发送。
 */
return [
    'access_key_id' => [
        'title' => 'AccessKey ID',
        'type'  => 'text',
        'value' => '',
        'tip'   => '阿里云 RAM 用户 AccessKey ID，需要 dypns:SendSmsVerifyCode 权限。',
    ],
    'access_key_secret' => [
        'title' => 'AccessKey Secret',
        'type'  => 'text',
        'value' => '',
        'tip'   => '阿里云 RAM 用户 AccessKey Secret，请妥善保管。',
    ],
    'sign_name' => [
        'title' => '赠送签名名称',
        'type'  => 'select',
        'value' => '恒创联众',
        'options' => [
            '恒创联众'     => '恒创联众',
            '恒创联众科技' => '恒创联众科技',
            '北京恒创联众' => '北京恒创联众',
            '恒锐创岳'     => '恒锐创岳',
            '恒锐创岳科技' => '恒锐创岳科技',
            '北京恒锐创岳' => '北京恒锐创岳',
        ],
        'tip'   => '号码认证控制台赠送签名；如赠送签名有变化，直接修改 config.php 的 options。',
    ],
    'interval' => [
        'title' => '发送间隔（秒）',
        'type'  => 'text',
        'value' => '60',
        'tip'   => '阿里云侧频控间隔，默认 60 秒。',
    ],
    'auto_retry' => [
        'title' => '自动重试',
        'type'  => 'select',
        'value' => '1',
        'options' => [
            '1' => '开启',
            '0' => '关闭',
        ],
        'tip'   => '运营商明确失败时，阿里云可尝试其他方式提升成功率。',
    ],
];
