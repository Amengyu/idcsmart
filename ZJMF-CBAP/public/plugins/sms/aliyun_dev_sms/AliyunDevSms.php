<?php
namespace sms\aliyun_dev_sms;

use app\common\lib\Plugin;
use think\facade\Db;

/**
 * 阿里云短信认证服务 - 开发者版
 * 普通 v10 短信接口插件：模板和业务场景走魔方短信模板，发送走阿里云 PNVS SendSmsVerifyCode。
 */
class AliyunDevSms extends Plugin
{
    public $info = [
        'name'        => 'AliyunDevSms',
        'title'       => '阿里云短信认证',
        'description' => '阿里云短信认证服务开发者版，调用号码认证服务 SendSmsVerifyCode，适配魔方业务系统短信模板。',
        'status'      => 1,
        'author'      => '梦屿科技',
        'version'     => '1.0.0',
        'help_url'    => 'https://dypns.console.aliyun.com/',
    ];

    public function install()
    {
        $this->syncPresetSmsTemplates();
        return true;
    }

    public function uninstall()
    {
        Db::name('sms_template')
            ->whereIn('sms_name', ['AliyunDevSms', 'aliyundevsms'])
            ->delete();
        return true;
    }

    private function syncPresetSmsTemplates()
    {
        $time = time();
        $templates = [];
        if (file_exists(__DIR__ . '/config/smsTemplate.php')) {
            $templates = require __DIR__ . '/config/smsTemplate.php';
        }
        if (!is_array($templates)) {
            $templates = [];
        }

        foreach ($templates as $item) {
            if (empty($item['title']) || empty($item['content'])) {
                continue;
            }
            $template = [
                'template_id' => trim($item['template_id'] ?? ''),
                'type' => intval($item['type'] ?? 0),
                'title' => trim($item['title']),
                'content' => trim($item['content']),
                'notes' => trim($item['notes'] ?? '阿里云短信认证服务预置模板'),
                'status' => intval($item['status'] ?? 0),
                'sms_name' => 'AliyunDevSms',
                'error' => '',
                'create_time' => $time,
                'update_time' => $time,
                'product_url' => '',
                'remark' => '',
                'notice_setting_name' => trim($item['name'] ?? ''),
            ];
            $exists = Db::name('sms_template')
                ->where('sms_name', 'AliyunDevSms')
                ->where('title', $template['title'])
                ->where('notice_setting_name', $template['notice_setting_name'])
                ->where('type', $template['type'])
                ->find();
            if ($exists) {
                Db::name('sms_template')
                    ->where('id', $exists['id'])
                    ->update([
                        'template_id' => $template['template_id'],
                        'type' => $template['type'],
                        'title' => $template['title'],
                        'content' => $template['content'],
                        'notes' => $template['notes'],
                        'status' => $template['status'],
                        'error' => '',
                        'update_time' => $time,
                    ]);
            } else {
                Db::name('sms_template')->insert($template);
            }
        }
    }

    public function description()
    {
        $file = __DIR__ . '/config/description.html';
        return is_file($file) ? file_get_contents($file) : '';
    }

    /**
     * 读取插件预置模板。以后只需要维护 config/smsTemplate.php：
     * 写多少模板，安装就导入多少模板；删除插件时统一删除 AliyunDevSms 的模板。
     */
    private function presetTemplates()
    {
        $file = __DIR__ . '/config/smsTemplate.php';
        $rows = is_file($file) ? require $file : [];
        return is_array($rows) ? $rows : [];
    }

    private function matchPresetTemplate($params)
    {
        $rows = $this->presetTemplates();
        $title = trim($params['title'] ?? '');
        $templateId = trim($params['template_id'] ?? '');
        $name = trim($params['notice_setting_name'] ?? $params['name'] ?? '');

        foreach ($rows as $row) {
            $rowTitle = trim($row['title'] ?? '');
            $rowTemplateId = trim($row['template_id'] ?? '');
            $rowName = trim($row['name'] ?? '');
            if ($templateId !== '' && $rowTemplateId !== '' && $templateId === $rowTemplateId) {
                return $row;
            }
            if ($title !== '' && $rowTitle !== '' && $title === $rowTitle) {
                return $row;
            }
            if ($name !== '' && $rowName !== '' && $name === $rowName && $rowTemplateId !== '') {
                return $row;
            }
        }
        return null;
    }

    /**
     * 阿里云短信认证赠送模板在号码认证控制台维护，魔方里只保存 TemplateCode。
     * 这里按官方接口返回“已审核通过”，避免 v10 阻塞使用。
     */
    public function getCnTemplate($params)
    {
        $templateId = trim($params['template_id'] ?? '');
        if ($templateId === '') {
            return ['status' => 400, 'msg' => '请填写阿里云短信认证赠送模板 TemplateCode'];
        }
        return [
            'status' => 'success',
            'template' => [
                'template_id' => $templateId,
                'template_status' => 2,
                'msg' => '阿里云短信认证模板状态请以号码认证控制台为准',
            ],
        ];
    }

    public function createCnTemplate($params)
    {
        $preset = $this->matchPresetTemplate($params);
        if (!$preset) {
            return [
                'status' => 400,
                'msg' => '未匹配到插件预置模板，请在 config/smsTemplate.php 中补充模板标题和 TemplateCode',
            ];
        }
        return [
            'status' => 'success',
            'template' => [
                'template_id' => trim($preset['template_id'] ?? ''),
                'template_status' => intval($preset['status'] ?? 2),
                'msg' => '已匹配插件预置模板：' . ($preset['title'] ?? ''),
            ],
        ];
    }

    public function putCnTemplate($params)
    {
        return $this->createCnTemplate($params);
    }

    public function deleteCnTemplate($params)
    {
        return [
            'status' => 'success',
        ];
    }

    public function sendCnSms($params)
    {
        $config = $params['config'] ?? [];
        $accessKeyId = trim($config['access_key_id'] ?? '');
        $accessKeySecret = trim($config['access_key_secret'] ?? '');
        $signName = trim($config['sign_name'] ?? '');
        $mobile = trim($params['mobile'] ?? '');
        $templateCode = trim($params['template_id'] ?? '');
        $templateParam = $params['templateParam'] ?? [];
        if (!is_array($templateParam)) {
            $templateParam = [];
        }

        $content = $this->renderContent($params['content'] ?? '', $templateParam);

        if ($accessKeyId === '' || $accessKeySecret === '') {
            return ['status' => 400, 'content' => $content, 'msg' => '请先配置 AccessKey ID 和 Secret'];
        }
        if ($signName === '') {
            return ['status' => 400, 'content' => $content, 'msg' => '请先配置号码认证赠送签名'];
        }
        if ($mobile === '') {
            return ['status' => 400, 'content' => $content, 'msg' => '手机号不能为空'];
        }
        if ($templateCode === '') {
            return ['status' => 400, 'content' => $content, 'msg' => '请在魔方短信模板中填写阿里云短信认证赠送模板 TemplateCode'];
        }

        // 魔方原生验证码缓存有效期固定为 300 秒，这里保持一致，避免短信文案和实际校验时间不一致。
        $validTime = 300;
        if (!isset($templateParam['min'])) {
            $templateParam['min'] = '5';
        }
        $apiParams = [
            'AccessKeyId' => $accessKeyId,
            'Action' => 'SendSmsVerifyCode',
            'Version' => '2017-05-25',
            'Format' => 'JSON',
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureVersion' => '1.0',
            'SignatureNonce' => $this->nonce(),
            'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'RegionId' => 'cn-hangzhou',
            'CountryCode' => trim($params['phone_code'] ?? '') ?: '86',
            'PhoneNumber' => $mobile,
            'SignName' => $signName,
            'TemplateCode' => $templateCode,
            'TemplateParam' => json_encode($templateParam, JSON_UNESCAPED_UNICODE),
            'ValidTime' => $validTime,
            'DuplicatePolicy' => 1,
            'Interval' => max(1, intval($config['interval'] ?? 60)),
            'AutoRetry' => intval($config['auto_retry'] ?? 1),
        ];
        $apiParams['Signature'] = $this->sign($apiParams, $accessKeySecret);

        $url = 'https://dypnsapi.aliyuncs.com/?' . http_build_query($apiParams);
        $resp = $this->httpGet($url);
        if ($resp['status'] !== 200) {
            return ['status' => 400, 'content' => $content, 'msg' => $resp['msg'] ?: '请求阿里云短信接口失败'];
        }
        $json = json_decode($resp['body'], true);
        if (!is_array($json)) {
            return ['status' => 400, 'content' => $content, 'msg' => '阿里云短信接口返回异常'];
        }
        if (($json['Code'] ?? '') === 'OK' || !empty($json['Success'])) {
            return ['status' => 200, 'content' => $content];
        }
        return [
            'status' => 400,
            'content' => $content,
            'msg' => $this->errorMessage($json),
        ];
    }

    private function renderContent($content, $vars)
    {
        $content = (string)$content;
        foreach ($vars as $key => $val) {
            $content = str_replace('@var(' . $key . ')', (string)$val, $content);
        }
        return preg_replace('/@var\(.*?\)/is', '', $content);
    }

    private function percentEncode($str)
    {
        return str_replace(['+', '*', '%7E'], ['%20', '%2A', '~'], rawurlencode((string)$str));
    }

    private function sign($params, $secret)
    {
        ksort($params);
        $query = [];
        foreach ($params as $key => $value) {
            $query[] = $this->percentEncode($key) . '=' . $this->percentEncode($value);
        }
        $stringToSign = 'GET&%2F&' . $this->percentEncode(implode('&', $query));
        return base64_encode(hash_hmac('sha1', $stringToSign, $secret . '&', true));
    }

    private function nonce()
    {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes(16));
        }
        return md5(uniqid('', true));
    }

    private function httpGet($url)
    {
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            $body = curl_exec($ch);
            $err = curl_error($ch);
            $code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
            curl_close($ch);
            if ($body === false) {
                return ['status' => 0, 'body' => '', 'msg' => $err];
            }
            return ['status' => $code ?: 200, 'body' => $body, 'msg' => ''];
        }
        $body = @file_get_contents($url);
        if ($body === false) {
            return ['status' => 0, 'body' => '', 'msg' => 'file_get_contents 请求失败'];
        }
        return ['status' => 200, 'body' => $body, 'msg' => ''];
    }

    private function errorMessage($json)
    {
        $code = $json['Code'] ?? '';
        $msg = $json['Message'] ?? '';
        $map = [
            'MOBILE_NUMBER_ILLEGAL' => '手机号码格式错误',
            'BUSINESS_LIMIT_CONTROL' => '触发号码天级流控',
            'FREQUENCY_FAIL' => '频控校验未通过，请稍后再试',
            'INVALID_PARAMETERS' => '参数无效，请检查签名、模板和变量',
            'FUNCTION_NOT_OPENED' => '未开通号码认证短信认证功能',
            'SIGN_NOT_EXISTS' => '赠送签名不存在',
            'TEMPLATE_NOT_EXISTS' => '赠送模板不存在',
            'PARAM_ERROR' => '参数错误',
            'SYSTEM_ERROR' => '阿里云系统错误，请稍后重试',
            'InvalidAccessKeyId.NotFound' => 'AccessKey ID 不存在',
            'SignatureDoesNotMatch' => 'AccessKey Secret 错误或签名不匹配',
        ];
        return ($map[$code] ?? ($msg ?: '阿里云短信发送失败')) . ($code ? '（' . $code . '）' : '');
    }
}
