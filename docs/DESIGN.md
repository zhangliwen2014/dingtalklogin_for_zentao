# 禅道开源版 20.6 钉钉登录插件设计文档

## 1. 背景与目标

### 1.1 背景
禅道开源版 20.6 已内置钉钉工作消息通知（`webhook` 模块的 `dinguser` 类型），管理员可在 webhook 中将禅道用户与钉钉 userid 进行绑定，绑定数据存储在 `zt_oauth` 表中。但禅道目前未提供基于该绑定关系的钉钉登录能力。

### 1.2 目标
开发一个钉钉登录插件，让已绑定钉钉账号的禅道用户可以通过以下两种方式登录：
1. **扫码登录**：在浏览器中访问禅道登录页，点击"钉钉登录"按钮，使用钉钉扫码完成登录。
2. **企业内部应用免登**：在钉钉工作台内点击禅道应用，无需输入密码直接登录。

插件需遵循禅道 20.x 扩展机制和插件打包规范，实现零侵入核心代码。

## 2. 需求概述

### 2.1 功能需求

| 编号 | 需求 | 优先级 | 说明 |
|------|------|--------|------|
| FR-1 | 扫码登录 | P0 | 在禅道登录页展示钉钉扫码入口，用户扫码后完成登录 |
| FR-2 | 企业内部应用免登 | P0 | 在钉钉内打开禅道时自动完成登录 |
| FR-3 | 复用 webhook 钉钉配置 | P0 | 自动读取 `dinguser` 类型 webhook 的 AppKey/AppSecret/AgentId |
| FR-4 | 复用 webhook 用户绑定 | P0 | 使用 `zt_oauth` 表中 `providerType='webhook'` 的绑定记录 |
| FR-5 | 未绑定拦截 | P0 | 若钉钉用户未在禅道绑定，拒绝登录并提示"请联系管理员绑定账号" |
| FR-6 | 路由白名单 | P1 | 钉钉回调地址免登录校验，避免死循环 |

### 2.2 非功能需求

| 编号 | 需求 | 说明 |
|------|------|------|
| NFR-1 | 零侵入 | 不修改任何禅道核心代码文件 |
| NFR-2 | 可升级 | 插件代码与主干代码物理隔离，禅道升级不受影响 |
| NFR-3 | 可卸载 | 提供 uninstall.sql，清理插件相关数据（如有） |
| NFR-4 | 安全 | 校验钉钉回调 state 参数，防止 CSRF；不传输敏感密钥到前端 |

## 3. 架构设计

### 3.1 整体架构

采用禅道 20.x 官方推荐的**扩展模块 + View Hook**方案：

- **新增模块**：`dingtalklogin` 模块处理所有钉钉登录相关的 HTTP 请求和业务逻辑。
- **扩展视图**：通过 `login.dingtalk.html.hook.php` 在原生登录页注入钉钉登录入口。

### 3.2 技术栈

- PHP 8.1 + `declare(strict_types=1);`
- 禅道 zentaoPHP 框架（control / zen / model / tao 四层）
- 钉钉开放平台 OAuth2.0 接口（`oapi.dingtalk.com`）
- 钉钉扫码登录 JS SDK（`ddLogin.js`）
- 钉钉 JSAPI（`dd.runtime.permission.requestAuthCode`）

## 4. 目录结构

### 4.1 标准插件包结构（zip）

遵循《禅道项目管理软件打包规范 1.1》：

```
dingtalklogin.zip
├── extension/
│   └── custom/
│       ├── dingtalklogin/
│       │   ├── control.php              # 控制器（public 方法暴露 HTTP 入口）
│       │   ├── zen.php                  # 业务层（protected 方法处理逻辑）
│       │   ├── model.php                # 模型层（public 方法对外提供能力）
│       │   ├── tao.php                  # 原子操作层（protected 方法调用钉钉 API）
│       │   ├── config.php               # 模块配置
│       │   ├── lang/
│       │   │   └── zh-cn.php            # 语言包
│       │   └── view/
│       │       └── login.html.php       # 登录中/失败提示页
│       └── user/
│           └── ext/
│               └── view/
│                   └── login.dingtalk.html.hook.php   # 在登录页注入钉钉入口
├── doc/
│   ├── copyright.txt                    # 插件版权信息
│   └── zh-cn.yaml                       # 插件元数据（名称、版本、兼容性）
├── db/
│   ├── install.sql                      # 安装 SQL（本插件无需建表，可为空）
│   └── uninstall.sql                    # 卸载 SQL（本插件无数据需清理，可为空）
└── hook/
    └── postinstall.php                  # 安装后钩子（如需要刷新缓存）
```

### 4.2 安装后实际路径

通过禅道后台"插件"功能安装后，文件被复制到：

```
{appRoot}/extension/custom/
├── dingtalklogin/
│   ├── control.php
│   ├── zen.php
│   ├── model.php
│   ├── tao.php
│   ├── config.php
│   ├── lang/
│   │   └── zh-cn.php
│   └── view/
│       └── login.html.php
└── user/
    └── ext/
        └── view/
            └── login.dingtalk.html.hook.php
```

> **命名规范**：新增模块名和 control 文件名必须**全部小写**（`dingtalklogin`）。

## 5. 组件职责

严格遵循禅道 `control → zen → model → tao` 分层规范：

| 组件 | 访问修饰 | 职责 |
|------|----------|------|
| `dingtalkloginControl` | public | 接收 HTTP 请求，调用 zen 层，返回响应（跳转/JSON/页面渲染）。禁止直接操作数据库或调用外部 API。 |
| `dingtalkloginZen` | protected | 业务编排：判断登录方式、构造钉钉授权参数、调用 model 获取 userid、查询 OAuth 绑定、调用 `user->login()` 写入禅道登录态、处理错误响应。 |
| `dingtalkloginModel` | public | 对外提供 `getUseridByCode(string $type, string $code): string\|false`、`getBoundUser(string $userid): object\|false` 等方法。 |
| `dingtalkloginTao` | protected | 原子操作：获取钉钉 AccessToken、调用 `sns/getuserinfo_bycode`、调用 `topapi/user/getuserinfo`。复用禅道内置 `lib/dingapi/dingapi.class.php`。 |
| `login.dingtalk.html.hook.php` | — | 在原生登录页渲染完成后，注入"钉钉登录"按钮和 `DDLogin` JS SDK 初始化代码。 |

### 5.1 Control 层入口方法

| 方法 | 路由 | 说明 |
|------|------|------|
| `scan()` | `dingtalklogin-scan.html` | 扫码登录跳转页：后端生成 state，前端初始化 DDLogin 二维码 |
| `callback()` | `dingtalklogin-callback.html?code=xxx&state=xxx` | 钉钉扫码回调：接收临时授权码，完成登录 |
| `sso()` | `dingtalklogin-sso.html` | 免登入口：接收前端传来的 authCode，完成登录 |

## 6. 数据流

### 6.1 扫码登录流程

```
用户访问禅道登录页 (/user-login.html)
    │
    ▼
Hook 文件注入"钉钉登录"按钮 + DDLogin JS SDK
    │
    ▼
用户点击"钉钉登录"
    │
    ▼
浏览器加载 dingtalklogin-scan.html
    │
    ▼
dingtalkloginControl::scan()
  ├─ 生成随机 state，存入 session（防 CSRF）
  ├─ 读取 webhook 钉钉配置（AppKey）
  └─ 渲染 view/login.html.php，输出 DDLogin 初始化参数
    │
    ▼
前端 DDLogin 渲染二维码
    │
    ▼
用户使用手机钉钉扫码
    │
    ▼
钉钉通过 postMessage 返回 loginTmpCode 到前端
    │
    ▼
前端构造跳转 URL：
  https://oapi.dingtalk.com/connect/oauth2/sns_authorize
  ?appid={AppKey}&response_type=code&scope=snsapi_login
  &state={state}&redirect_uri={callbackUri}&loginTmpCode={loginTmpCode}
    │
    ▼
钉钉 302 跳转回 callbackUri（/dingtalklogin-callback.html?code=xxx&state=xxx）
    │
    ▼
dingtalkloginControl::callback()
  ├─ 校验 state 与 session 中存储的是否一致
  ├─ dingtalkloginZen::handleCallback($code)
  │   ├─ dingtalkloginModel::getUseridByCode('scan', $code)
  │   │   ├─ 获取 AccessToken（复用 dingapi）
  │   │   └─ 调用 sns/getuserinfo_bycode 获取钉钉 userid
  │   ├─ dingtalkloginModel::getBoundUser($userid)
  │   │   └─ 查询 zt_oauth WHERE openID=$userid AND providerType='webhook'
  │   ├─ 未绑定 → 返回失败，提示"账号未绑定，请联系管理员"
  │   └─ 已绑定 → $this->loadModel('user')->login($user)
  └─ 登录成功 → 跳转首页
```

### 6.2 企业内部应用免登流程

```
用户在钉钉内点击禅道工作台图标
    │
    ▼
打开配置的应用首页地址（如 /dingtalklogin-sso.html）
    │
    ▼
前端页面引入钉钉 JSAPI，调用 dd.runtime.permission.requestAuthCode
    │
    ▼
获得 authCode，通过 AJAX POST 到 /dingtalklogin-sso.html
    │
    ▼
dingtalkloginControl::sso()
  ├─ dingtalkloginZen::handleSso($authCode)
  │   ├─ dingtalkloginModel::getUseridByCode('sso', $authCode)
  │   │   ├─ 获取 AccessToken（复用 dingapi）
  │   │   └─ 调用 topapi/user/getuserinfo 获取钉钉 userid
  │   ├─ dingtalkloginModel::getBoundUser($userid)
  │   ├─ 未绑定 → 返回失败
  │   └─ 已绑定 → $this->loadModel('user')->login($user)
  └─ 登录成功 → 跳转首页
```

## 7. 接口设计

### 7.1 钉钉开放平台接口

| 场景 | 接口 | 说明 |
|------|------|------|
| 获取 AccessToken | `GET https://oapi.dingtalk.com/gettoken?appkey=xxx&appsecret=xxx` | 复用 `lib/dingapi/dingapi.class.php` |
| 扫码登录换 userid | `GET https://oapi.dingtalk.com/sns/getuserinfo_bycode?access_token=xxx&tmp_auth_code=xxx` | 使用临时授权码获取用户信息 |
| 免登换 userid | `POST https://oapi.dingtalk.com/topapi/user/getuserinfo?access_token=xxx` Body: `{"code":"xxx"}` | 使用 JSAPI 获取的 authCode 获取用户信息 |

### 7.2 禅道内部接口

| 接口 | 说明 |
|------|------|
| `$this->loadModel('webhook')->getById($id)` | 获取 webhook 配置，提取钉钉 AppKey/AppSecret/AgentId |
| `$this->dao->select(...)->from(TABLE_OAUTH)->where(...)->fetch()` | 查询 OAuth 绑定记录 |
| `$this->loadModel('user')->login($user)` | 写入禅道登录态（session + cookie） |
| `$this->loadModel('user')->identify($account, $password)` | 用户身份校验（标准登录流程） |

## 8. 错误处理

| 错误场景 | 处理策略 | 用户提示 |
|----------|----------|----------|
| 未配置 `dinguser` webhook | Hook 中判断，隐藏钉钉登录按钮 | 无 |
| 钉钉 API 调用失败（网络/凭证错误） | 记录日志 `tmp/log/dingtalklogin.yyyy-mm-dd.php` | "钉钉服务异常，请稍后重试" |
| 扫码回调 state 不匹配 | 拒绝请求，返回登录页 | "登录验证失败，请重新尝试" |
| 钉钉用户未在禅道绑定 | 跳转登录页 | "您的钉钉账号尚未绑定禅道账号，请联系管理员在【后台-通知-webhook-钉钉工作消息】中进行绑定" |
| 获取 userid 失败 | 记录详细错误日志 | "无法获取钉钉用户信息，请稍后重试" |

## 9. 安全考虑

1. **CSRF 防护**：扫码登录流程中，后端生成随机 state 存入 session，回调时严格校验。
2. **密钥安全**：AppSecret 仅在后端使用，绝不暴露到前端。
3. **回调域名校验**：钉钉开放平台已配置回调域名，防止恶意回调。
4. **登录白名单**：插件 config 中将 `dingtalklogin-scan`、`dingtalklogin-callback`、`dingtalklogin-sso` 加入免登录路由白名单。
5. **SQL 注入**：所有数据库查询使用禅道 DAO 链式调用，自动转义参数。

## 10. 测试策略

### 10.1 单元测试

在 `extension/custom/dingtalklogin/test/` 下遵循禅道 TDD 规范编写：

```
test/
├── model/
│   ├── getuseridbycode.php      # 测试 getUseridByCode：正常返回 / 异常返回 / 空码
│   └── getbounduser.php         # 测试 getBoundUser：已绑定 / 未绑定 / 多 providerID
└── control/
    └── callback.php             # 测试 callback：正常登录 / state 错误 / 未绑定
```

### 10.2 集成测试

| 测试项 | 步骤 |
|--------|------|
| 扫码登录全流程 | 1. 配置 webhook 并绑定用户 → 2. 访问登录页点击钉钉登录 → 3. 扫码 → 4. 验证登录成功 |
| 免登全流程 | 1. 钉钉内打开应用 → 2. 验证自动登录成功 |
| 未绑定拦截 | 1. 用未绑定的钉钉账号扫码 → 2. 验证提示"未绑定"且无法登录 |
| 多 webhook 场景 | 配置多个 dinguser webhook，验证插件能正确读取第一个有效配置 |

## 11. 部署与安装

### 11.1 安装方式一：后台插件安装（推荐）

1. 管理员登录禅道后台 → 插件 → 本地安装。
2. 上传 `dingtalklogin.zip`。
3. 点击安装，系统自动解压并复制文件到 `extension/custom/`。
4. 确保已配置至少一个 `dinguser` 类型的 webhook。
5. 确保已在 webhook 中绑定需要登录的用户。

### 11.2 安装方式二：手动部署

1. 将插件包解压。
2. 将 `extension/custom/` 下的内容复制到禅道根目录的 `extension/custom/` 下。
3. 确保目录权限正确（web 服务器可读取）。

### 11.3 钉钉开放平台配置要求

- 已创建**企业内部应用**（H5 微应用）。
- 已获取 **AppKey**、**AppSecret**、**AgentId**。
- 已在"钉钉登录与分享"中配置**回调域名**（如 `https://zentao.example.com`）。
- 已在 webhook 中创建 `dinguser` 类型通知，填入上述凭证。
- 已在 webhook 用户绑定页面完成禅道用户与钉钉用户的绑定。

## 12. 风险与限制

| 风险 | 说明 | 缓解措施 |
|------|------|----------|
| 钉钉 API 变更 | 钉钉开放平台接口可能升级或下线 | 封装 tao 层，统一调用入口，便于后续适配 |
| 多 dinguser webhook | 若配置了多个，插件需明确使用哪一个 | 默认使用第一个 `type='dinguser'` 的 webhook |
| 禅道 20.x 视图重构 | 登录页未来可能从 view 迁移到 ui | 当前使用 view hook，若升级需同步调整为 ui hook |

## 13. 附录

### 13.1 相关文档

- [禅道二次开发（20 版本）](https://www.zentao.net/book/extension-dev/custom-dev-1319.html)
- [禅道插件打包规范](https://www.zentao.net/book/api/144.html)
- [zentaoPHP 框架](https://www.zentao.net/book/zentaophphelp/about-1210.html)
- [钉钉开放平台 - 扫码登录](https://open.dingtalk.com/document/isvapp-client/logon-introduction-to-dingtalk)
- [钉钉开放平台 - 企业内部应用免登](https://open.dingtalk.com/document/orgapp/enterprise-internal-application-logon-free)

### 13.2 引用源码

- 禅道内置钉钉 API 封装：`lib/dingapi/dingapi.class.php`
- 禅道 webhook 钉钉配置：`module/webhook/config.php`
- 禅道 OAuth 绑定逻辑：`module/webhook/model.php`（`bind()` 方法）
- 禅道用户登录态写入：`module/user/model.php`（`login()` 方法）
