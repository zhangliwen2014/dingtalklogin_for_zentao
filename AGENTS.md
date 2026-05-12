# AGENTS.md — 禅道钉钉登录插件

## 项目概述

本项目是 **禅道开源版 20.x** 的钉钉扫码登录插件。用户在禅道登录页点击"钉钉登录"后，当前页面直接嵌入钉钉二维码；扫码认证通过后自动登录禅道（等同于用户名密码登录）。支持 ZIN 框架（禅道 20.x 默认）和传统视图模式两种渲染方式。

## 项目结构

```
dingtalklogin/
├── README.md
├── hook/
│   └── postinstall.php              # 插件安装后钩子
├── db/
│   ├── install.sql                  # 安装脚本
│   └── uninstall.sql                # 卸载脚本
├── doc/
│   ├── copyright.txt
│   └── zh-cn.yaml                   # 插件元数据
└── extension/custom/                # 插件扩展代码
    ├── dingtalklogin/               # 钉钉登录模块
    │   ├── config.php               # 模块配置（openMethods）
    │   ├── control.php              # 控制器：scan/callback/sso
    │   ├── model.php                # 对外模型层
    │   ├── tao.php                  # 内部原子操作层（API调用）
    │   ├── zen.php                  # 业务逻辑编排层
    │   ├── lang/
    │   │   └── zh-cn.php            # 语言包
    │   ├── ui/
    │   │   ├── scan.html.php        # ZIN 扫码页（备用独立入口）
    │   │   └── sso.html.php         # ZIN 免登页
    │   ├── view/
    │   │   ├── scan.html.php        # 传统扫码页（备用独立入口）
    │   │   └── sso.html.php         # 传统免登页
    │   └── test/                    # 单元测试
    └── user/
        └── ext/
            ├── ui/
            │   └── login.dingtalk.html.hook.php   # ZIN 登录页嵌入二维码
            └── view/
                └── login.dingtalk.html.hook.php     # 传统登录页嵌入二维码
```

## 禅道版本兼容性

- **目标版本**：禅道开源版 20.6+
- **PHP 版本**：8.1+（使用 `string|false` 等联合返回类型）
- **框架模式**：ZIN（默认）+ 传统视图（fallback）
- **Hook 路径**：
  - ZIN 模式 → `ext/ui/`
  - 传统模式 → `ext/view/`
- **免登录配置**：使用 `$config->openMethods[] = 'module.method'`（禅道 20.x 标准，非 18.x 的 `$config->openModules`）

## 架构设计

### Model-Tao-Zen-Control 四层结构

```
control.php    ← HTTP 入口，参数校验，调用 Zen
    ↓
zen.php        ← 业务编排（generateState/handleCallback/processLogin）
    ↓
model.php      ← 对外公共接口（getUseridByCode/getBoundUser）
    ↓
tao.php        ← 原子操作（getUseridByScanCode/getUseridByAuthCode/getDingWebhook）
```

**关键机制**：`baseModel::__call()` 会将 Model 上不存在的方法调用自动转发到 `$moduleName . 'Tao'` 对象。因此：
- `model.php` **不要**定义空实现的方法（如之前 `getDingWebhook()` 返回 `false`），否则会拦截 Tao 中的真实实现
- Tao 中的方法默认 `protected`，仅内部调用；需要外部调用的必须声明为 `public`

### 扫码登录流程

1. 用户访问 `/user-login.html`
2. Hook 注入"钉钉登录"按钮到登录表单底部
3. 用户点击 → 隐藏表单，显示 `#dingtalkQrcodeWrap` 二维码区域
4. Hook 中通过 `$control->dingtalklogin->getDingWebhook()` 读取 webhook 配置
5. 生成 `state` 存入 session，构建 `gotoUrl`
6. `DDLogin.js` 在 iframe 中加载钉钉二维码
7. 用户扫码 → 钉钉 iframe 通过 `postMessage` 发送 `loginTmpCode`
8. JS 拼接 `gotoUrl + "&loginTmpCode=" + loginTmpCode` 跳转
9. 钉钉服务端重定向到 `dingtalklogin-callback.html?code=xxx&state=yyy`
10. `callback()` 校验 state → `getUseridByCode('scan', $code)` → `processLogin()`
11. 写入禅道 session → 跳转首页

## 关键文件说明

### control.php

- `scan()`：渲染备用独立扫码页（Hook 已覆盖主要入口，此页作为 fallback）
- `callback()`：处理扫码回调，校验 state 后执行登录
- `sso()`：企业内部应用免登（JSAPI 获取 authCode）

### zen.php

- `handleCallback(string $code, string $state)`：扫码回调业务逻辑
- `handleSso(string $authCode)`：免登业务逻辑
- `processLogin(string $userid)`：查询 zt_oauth 绑定 → 调用 `$this->loadModel('user')->login()`
- `generateState()`：生成 CSRF state，**protected**
- `getCallbackUrl()`：构建回调地址，**protected**

### tao.php

- `getDingWebhook()`：查询 `zt_webhook` 表中第一个 `type='dinguser'` 且未删除的记录，解析 `secret` JSON。**public**，Hook 中通过 Model 转发调用
- `getUseridByScanCode()`：调用钉钉 `sns/getuserinfo_bycode` 接口
- `getUseridByAuthCode()`：调用钉钉 `topapi/user/getuserinfo` 接口

### Hook 文件（登录页嵌入）

**ZIN：`user/ext/ui/login.dingtalk.html.hook.php`**

- `$this` 指向 `zin\context`，必须通过 `$this->control` 访问禅道对象
- 原生 JS（不依赖 jQuery），监听 `DOMContentLoaded`
- 动态创建二维码容器 `#dingtalkQrcodeWrap`，与 `#loginForm` 同级
- 切换逻辑：隐藏/显示 `#loginForm` 和 `#dingtalkQrcodeWrap`

**传统：`user/ext/view/login.dingtalk.html.hook.php`**

- `$this` 指向当前 control 对象，可直接使用 `$this->dao` / `$this->session`
- 使用 jQuery（禅道传统模式已加载）

## 编码规范

### PHP

- 文件头使用 `<?php` + 注释说明，**不要**在 `namespace` 前添加 `declare(strict_types=1)`（ZIN 视图模板不兼容）
- ZIN 视图模板（`ui/*.html.php`）：`namespace zin;` 必须是 `<?php` 后的第一条有效语句，前不能有空行/BOM/输出
- 方法访问修饰符：被外部调用（包括 Hook、其他模块）必须为 `public`
- 类型声明：PHP 8.1 支持 `string|false`、`object|false` 等联合类型

### JavaScript

- Hook 中使用原生 JS（`document.addEventListener`、`document.createElement`），避免依赖 jQuery（ZIN 模式下可能未加载）
- `DDLogin` 初始化放在 `window.addEventListener('load', ...)` 中，确保 DOM 和资源完全就绪
- `postMessage` 监听器需要防重复绑定：保存引用到 `window._dingtalkMsgHandler`，切换前 `removeEventListener`
- `loginTmpCode` 使用 `encodeURIComponent(event.data)` 编码，防止特殊字符导致跳转异常

## 已知问题与修复记录

| 问题 | 原因 | 修复方式 |
|------|------|---------|
| `Namespace declaration statement has to be the very first statement` | `ui/scan.html.php` 中有 `declare(strict_types=1)` 或文件含 BOM | 去掉 `declare`，确保 `namespace zin;` 紧跟 `<?php` |
| Model 中 `getDingWebhook()` 返回 `false` | model.php 定义了空实现，拦截了 Tao 转发 | 删除 model.php 中的空实现 |
| `protected` 方法被外部调用 fatal error | zen.php/tao.php 中方法访问修饰符为 `protected` | 被外部调用的方法改为 `public` |
| 扫码页浏览器空白 | `.fade` 类默认 `opacity: 0`；ZIN 页面渲染机制 | 设置 `style="opacity:1"`，使用 `window.load` |
| 修改不生效 | 两份代码路径不一致 | 每次修改后同步 `/apps/zentao/` 和 `/data/zentao/extension/pkg/` |

## 部署说明

### 服务器代码路径（两份必须同步）

```bash
# 实际运行目录
/apps/zentao/extension/custom/dingtalklogin/
/apps/zentao/extension/custom/user/ext/

# 插件包解压目录（禅道可能从此加载）
/data/zentao/extension/pkg/dingtalklogin/extension/custom/dingtalklogin/
/data/zentao/extension/pkg/dingtalklogin/extension/custom/user/ext/
```

### 清缓存命令

```bash
rm -rf /apps/zentao/tmp/cache/* /data/zentao/tmp/cache/*
systemctl restart php-fpm
```

### 数据库依赖

- `zt_webhook` 表需存在 `type='dinguser'`、`deleted='0'` 的记录
- `secret` 字段为 JSON：`{"appKey":"xxx","appSecret":"yyy"}`
- `zt_oauth` 表用于存储钉钉 userid 与禅道账号的绑定关系

## 调试技巧

1. **Hook 未生效**：检查 `user/ext/ui/` 和 `user/ext/view/` 下文件是否存在；确认模块已安装启用
2. **二维码不显示**：浏览器 F12 → Console 查看报错；常见原因：广告拦截器、CSP 策略、网络阻止 `g.alicdn.com` / `login.dingtalk.com`
3. **callback 报错 state 无效**：session 未正确写入，检查 PHP session 配置和 `zen.php` 中 `generateState()` 是否被正确调用
4. **Model 方法返回 false**：检查 `tao.php` 中方法是否为 `public`，以及 `model.php` 中是否存在同名空实现

## 测试用例

```bash
# 单元测试路径
extension/custom/dingtalklogin/test/model/getbounduser.php
extension/custom/dingtalklogin/test/model/getuseridbycode.php
```
