---
**项目信息**

- **开发者**：梦屿
- **官网**：[imengyu.com](https://www.imengyu.com)
- **许可证**：[Apache License 2.0](LICENSE)
- **版权声明**：本软件及文档 © 2026 imengyu.com，保留所有权利。在遵守 Apache 2.0 许可证条款的前提下，允许修改、复制和分发，但须保留完整版权声明及免责声明。
---

# AliyunDevSms - 阿里云短信认证服务开发者版

魔方业务系统 v10 短信接口插件，按官方 `public/plugins/sms/idcsmart` 插件形态开发，底层调用阿里云号码认证服务 PNVS 的 `SendSmsVerifyCode`。

> 当前插件不是普通阿里云短信服务 `Dysmsapi/SendSms`，而是号码认证服务里的短信认证接口 `Dypnsapi/SendSmsVerifyCode`。

## 目录结构

```text
public/plugins/sms/aliyun_dev_sms/
├── AliyunDevSms.php
├── config.php
├── config/
│   ├── smsTemplate.php
│   └── description.html
└── README.md

## 功能定位

- 用作魔方 v10 的普通短信接口插件。
- 发送接口走阿里云号码认证服务 `SendSmsVerifyCode`。
- 验证码仍由魔方生成和校验，插件只负责把验证码发出去。
- 不改魔方核心文件。
- 不改前台/后台模板。
- 不新建业务表。
- 不接管绑定、换绑、登录、重置密码流程。

## 后台配置项

配置文件：

```text
public/plugins/sms/aliyun_dev_sms/config.php
```

当前配置项：

| 配置项 | 说明 |
| --- | --- |
| AccessKey ID | 阿里云 RAM 用户 AccessKey ID，需要 `dypns:SendSmsVerifyCode` 权限 |
| AccessKey Secret | 阿里云 RAM 用户 AccessKey Secret |
| 赠送签名名称 | 号码认证控制台已通过的赠送签名，下拉选择 |
| 发送间隔（秒） | 默认 60 秒 |
| 自动重试 | 阿里云侧失败重试开关 |

### 修改签名下拉

如阿里云赠送签名变化，修改：

```text
public/plugins/sms/aliyun_dev_sms/config.php
```

找到：

```php
'sign_name' => [
    'type' => 'select',
    'options' => [
        '恒创联众' => '恒创联众',
    ],
],
```

新增签名时添加一行：

```php
'新签名' => '新签名',
```

当前已预留通过签名：

```text
恒创联众
恒创联众科技
北京恒创联众
恒锐创岳
恒锐创岳科技
北京恒锐创岳
```

## 预填模板机制

模板配置文件：

```text
public/plugins/sms/aliyun_dev_sms/config/smsTemplate.php
```

规则：

- 这个文件里写多少模板，安装插件时就自动导入多少模板。
- 每条模板可预填模板名称、内容、默认动作、TemplateCode、状态、备注。
- 删除插件时，会自动删除 `sms_name = AliyunDevSms` 的全部模板。
- 后续新增、修改、删除预填模板，直接改这个文件。

模板字段示例：

```php
[
    'title'       => '默认验证码',
    'content'     => '您的验证码为@var(code)。尊敬的客户，以上验证码@var(min)分钟内有效，请注意保密，切勿告知他人。',
    'template_id' => '100001',
    'status'      => 2,
    'notes'       => '阿里云短信认证服务赠送模板：默认验证码，可用于所有验证码场景',
]
```

字段说明：

| 字段 | 说明 |
| --- | --- |
| title | 魔方后台显示的模板名称 |
| content | 魔方模板内容，变量用 `@var(xxx)` |
| name | 可选。魔方通知动作；不填则模板不预绑定默认动作 |
| template_id | 阿里云赠送模板 CODE，例如 `100001` |
| status | 模板状态，`2` 表示通过 |
| notes | 备注 |
| type | 可选，默认 `0`，表示国内短信 |

### 当前预置模板

| TemplateCode | 模板名称 | 魔方动作 |
| --- | --- | --- |
| 100001 | 默认验证码 | 空 |
| 100001 | 登录/注册模板 | 空 |
| 100002 | 修改绑定手机号模板 | 空 |
| 100003 | 重置密码模板 | 空 |
| 100004 | 绑定新手机号模板 | 空 |
| 100005 | 验证绑定手机号模板 | 空 |

> 注意：这些预置模板不强制绑定默认动作。后台通知设置里按需要选择模板即可。

## 安装/卸载生命周期

### 安装插件

`install()` 会读取：

```text
config/smsTemplate.php
```

并自动写入：

```text
idcsmart_sms_template
```

字段包括：

```text
sms_name = AliyunDevSms
template_id = 配置里的 template_id
notice_setting_name = 配置里的 name；未配置则为空
status = 配置里的 status
```

### 卸载插件

`uninstall()` 会自动删除：

```sql
DELETE FROM idcsmart_sms_template WHERE sms_name IN ('AliyunDevSms', 'aliyundevsms');
```

只清理本插件模板，不删除用户、订单、产品、手机号等业务数据。

## 阿里云接口参数

插件调用接口：

```text
https://dypnsapi.aliyuncs.com/
```

核心参数：

| 参数 | 来源 |
| --- | --- |
| Action | 固定 `SendSmsVerifyCode` |
| Version | 固定 `2017-05-25` |
| RegionId | 插件内部固定 `cn-hangzhou` |
| PhoneNumber | 魔方传入手机号 |
| SignName | 插件配置的赠送签名 |
| TemplateCode | 魔方短信模板的 `template_id` |
| TemplateParam | 魔方模板变量，例如 `code`、`min` |
| ValidTime | 插件内部固定 300 秒，与魔方原生验证码缓存一致 |
| Interval | 插件配置，发送间隔 |
| AutoRetry | 插件配置，自动重试 |

## 变量规则

阿里云模板变量：

```text
${code}
${min}
```

魔方模板变量：

```text
@var(code)
@var(min)
```

插件发送时会把魔方传来的验证码作为：

```json
{"code":"123456","min":"5"}
```

传给阿里云。

## 常见问题



### 有效时长说明

插件不提供“有效时长”配置。

原因：魔方原生验证码缓存固定为 300 秒（5 分钟）。插件内部固定向阿里云传 `ValidTime=300`，并在模板参数中传 `min=5`，避免短信提示时间和魔方实际校验时间不一致。

### 验证码长度说明

插件不提供“验证码长度”配置。

原因：当前验证码由魔方原生逻辑生成并校验，魔方代码里验证码固定为 6 位数字。插件只是把魔方生成好的验证码传给阿里云发送。

阿里云 `CodeLength` 只有在使用 `##code##` 让阿里云生成验证码时才有意义；本插件不使用该模式，否则魔方无法完成原生验证码校验。

### 为什么不用 `Dysmsapi/SendSms`？

因为当前使用的是阿里云号码认证服务里的短信认证服务，文档接口是：

```text
Dypnsapi / SendSmsVerifyCode
```

不是普通短信服务：

```text
Dysmsapi / SendSms
```

### 默认动作是否必须预填？

不是必须。短信模板可以不预填默认动作，后台通知设置通过模板 ID 选择具体模板即可。当前预置模板默认动作留空，避免误导为全部强绑定到 `code`。

### 能否自动按场景选择 100001-100005？

原生魔方短信模板选择链路不直接支持。当前可以在后台手动选择要启用的模板。

如果要做到：

```text
默认验证码/通用验证码 -> 100001
登录/注册模板 -> 100001
修改手机号 -> 100002
重置密码 -> 100003
绑定新手机号 -> 100004
验证绑定手机号 -> 100005
```

需要额外根据 `VerificationCodeLogic` 的 `action` 做分流，这属于新的插件逻辑或系统补丁设计，不能假装原生已经支持。

## 维护原则

- 模板增删改：改 `config/smsTemplate.php`。
- 签名增删改：改 `config.php`。
- 不改魔方核心。
- 不改 clientarea 模板。
- 不手工长期维护数据库模板；数据库模板应由插件安装/卸载生命周期生成和清理。
- 如果需要清理旧插件或旧模板，先确认范围再操作。

