# 钉钉登录插件 for 禅道开源版 20.6

为禅道开源版 20.6 提供钉钉扫码登录和企业内部应用免登功能。

## 功能特性

- **扫码登录**：在禅道登录页点击"钉钉登录"，使用手机钉钉扫码即可完成登录。
- **企业内部应用免登**：在钉钉工作台内点击禅道应用，无需输入密码直接登录。
- **零侵入**：不修改任何禅道核心代码，纯扩展方式实现。
- **复用现有配置**：自动复用 webhook 中钉钉工作消息通知的配置和用户绑定关系。

## 安装要求

- 禅道开源版 20.6
- PHP 8.1
- 已配置钉钉工作消息通知（`dinguser` 类型 webhook）
- 已在 webhook 中完成禅道用户与钉钉用户的绑定

## 安装方式

### 方式一：后台插件安装（推荐）

1. 将整个项目打包为 ZIP 文件。
2. 登录禅道后台 → **插件** → **本地安装**。
3. 上传 ZIP 文件并点击安装。

### 方式二：手动部署

1. 将本项目 `extension/custom/` 目录下的内容复制到禅道根目录的 `extension/custom/` 下。
2. 确保目录权限正确（Web 服务器可读取）。
3. 清理缓存并重启 PHP-FPM：
   ```bash
   rm -rf /path/to/zentao/tmp/cache/*
   systemctl restart php-fpm
   ```

## 钉钉开放平台配置

1. 登录 [钉钉开放平台](https://open.dingtalk.com/)。
2. 创建**企业内部应用**（H5 微应用）。
3. 获取 **AppKey**、**AppSecret**、**AgentId**。
4. 在"钉钉登录与分享"中配置**回调域名**（如 `https://zentao.example.com`）。
5. 在禅道后台 → **通知** → **Webhook** → 创建 `dinguser` 类型通知，填入上述凭证。
6. 在 webhook 的**用户绑定**页面，将禅道用户与钉钉用户进行绑定。

## 目录结构

```
dingtalklogin/
├── extension/
│   └── custom/
│       ├── dingtalklogin/              # 插件主模块
│       │   ├── control.php             # HTTP 入口 (scan / callback / sso)
│       │   ├── zen.php                 # 业务逻辑层
│       │   ├── model.php               # 对外模型层
│       │   ├── tao.php                 # 钉钉 API 原子操作
│       │   ├── config.php              # 模块配置
│       │   ├── lang/
│       │   │   └── zh-cn.php          # 中文语言包
│       │   ├── view/
│       │   │   ├── scan.html.php      # 扫码登录页 (传统视图)
│       │   │   └── sso.html.php       # 免登授权页 (传统视图)
│       │   └── test/                  # 单元测试
│       └── user/
│           └── ext/
│               ├── ui/
│               │   └── login.dingtalk.html.hook.php   # ZIN 模式登录页注入
│               └── view/
│                   └── login.dingtalk.html.hook.php   # 传统模式登录页注入
├── doc/
│   ├── copyright.txt                   # 插件版权信息
│   └── zh-cn.yaml                      # 插件元数据
├── db/
│   ├── install.sql                     # 安装 SQL
│   └── uninstall.sql                   # 卸载 SQL
└── hook/
    └── postinstall.php                 # 安装后钩子
```

## 禅道 20.6 兼容性说明

禅道 20.6 引入了 **ZIN 新 UI 框架**，登录页默认走 ZIN 渲染（`ui/login.html.php`）。本插件已做双重兼容：

- **ZIN 模式**：Hook 文件位于 `ext/ui/login.dingtalk.html.hook.php`，使用 `$this->control` 访问 baseControl 对象。
- **传统模式**：Hook 文件位于 `ext/view/login.dingtalk.html.hook.php`，使用 `$this` 直接访问。
- **scan / sso 页面**：通过 `$_GET['zin'] = '0'` 强制回传统视图渲染，避免 ZIN 兼容问题。

## 技术说明

- **扫码登录流程**：`DDLogin` JS SDK → `loginTmpCode` → 钉钉 OAuth → 回调 `code` → `sns/getuserinfo_bycode` → 查询 `zt_oauth` 绑定 → 写入禅道登录态。
- **免登流程**：钉钉 JSAPI `requestAuthCode` → `authCode` → `topapi/user/getuserinfo` → 查询 `zt_oauth` 绑定 → 写入禅道登录态。
- **安全**：采用 CSRF state 校验、密钥后端存储、DAO 参数自动转义。

## 许可证

ZPL / AGPL
