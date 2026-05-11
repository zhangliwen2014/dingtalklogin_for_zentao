# 钉钉登录插件使用文档

## 目录

- [环境要求](#环境要求)
- [安装](#安装)
- [配置](#配置)
- [使用](#使用)
- [卸载](#卸载)
- [常见问题](#常见问题)

---

## 环境要求

| 项目 | 版本/说明 |
|------|----------|
| 禅道 | 开源版 20.6 |
| PHP | 8.1 |
| 钉钉应用 | 企业内部应用（H5 微应用） |
| 前置条件 | 已配置 `dinguser` 类型 webhook 并完成用户绑定 |

---

## 安装

### 方式一：禅道后台插件安装（推荐）

1. 下载本插件的 ZIP 包（确保包含 `extension/`、`doc/`、`db/`、`hook/` 目录）。
2. 登录禅道，进入 **后台** → **插件**。
3. 点击 **本地安装**，选择 ZIP 包上传。
4. 点击 **安装**，系统会自动将文件解压到 `extension/custom/` 目录。
5. 安装完成后，刷新页面即可在登录页看到"钉钉登录"按钮。

### 方式二：手动部署

1. 将本插件 `extension/custom/` 目录下的所有内容，复制到禅道根目录的 `extension/custom/` 下。

   复制后的目录结构应为：
   ```
   {zentao_root}/extension/custom/
   ├── dingtalklogin/
   │   ├── control.php
   │   ├── model.php
   │   ├── zen.php
   │   ├── tao.php
   │   ├── config.php
   │   ├── lang/zh-cn.php
   │   ├── view/login.html.php
   │   └── test/...
   └── user/
       └── ext/view/login.dingtalk.html.hook.php
   ```

2. 确保 Web 服务器对 `extension/custom/` 有读取权限。
3. 无需执行 SQL，本插件不创建新表。

---

## 配置

### 第一步：钉钉开放平台配置

1. 登录 [钉钉开放平台](https://open.dingtalk.com/)。
2. 进入 **应用开发** → **企业内部开发** → 创建应用。
3. 应用类型选择 **H5 微应用**，填写应用名称（如"禅道登录"）。
4. 记录以下信息：
   - **AppKey**（即 Client ID）
   - **AppSecret**（即 Client Secret）
   - **AgentId**
5. 进入应用详情 → **钉钉登录与分享** → 添加**回调域名**。
   - 回调域名填写禅道的域名，如 `https://zentao.example.com`。
   - 确保该域名可从公网访问（钉钉服务器需要回调到该地址）。

### 第二步：禅道 Webhook 配置

1. 登录禅道后台 → **通知** → **Webhook** → **添加 Webhook**。
2. 类型选择 **钉钉工作消息通知**（`dinguser`）。
3. 填入第一步获取的：
   - AgentId
   - AppKey
   - AppSecret
4. 保存后，点击该 webhook 的 **绑定用户** 按钮。
5. 在绑定页面中，将需要登录的禅道用户与对应的钉钉用户进行绑定。
   > 绑定数据存储在 `zt_oauth` 表中，本插件会直接读取该表进行登录验证。

### 第三步：钉钉工作台配置（免登必需）

1. 在钉钉开放平台 → 应用详情 → **开发管理**。
2. 配置**应用首页地址**和**PC 端首页地址**：
   ```
   https://zentao.example.com/dingtalklogin-sso.html
   ```
3. 进入 **版本管理与发布** → 点击 **确认发布**，将应用发布到企业内部。

---

## 使用

### 扫码登录

1. 在浏览器中访问禅道登录页（`/user-login.html`）。
2. 页面会显示 **钉钉登录** 按钮（仅在已配置 `dinguser` webhook 时显示）。
3. 点击按钮，进入扫码页面。
4. 使用手机钉钉扫描二维码。
5. 扫码成功后，自动完成登录并跳转到禅道首页。

> 如果钉钉用户未在禅道绑定，会提示："您的钉钉账号尚未绑定禅道账号，请联系管理员..."

### 企业内部应用免登

1. 在钉钉手机端或 PC 端，进入 **工作台**。
2. 找到并点击已发布的禅道应用图标。
3. 系统会自动获取免登授权码并完成登录，无需输入密码。

> 免登依赖钉钉 JSAPI，必须在钉钉内置浏览器或钉钉客户端中打开才生效。

---

## 卸载

### 后台卸载

1. 禅道后台 → **插件** → 找到"钉钉登录插件"。
2. 点击 **卸载**，系统会自动清理插件文件。

### 手动卸载

1. 删除 `extension/custom/dingtalklogin/` 目录。
2. 删除 `extension/custom/user/ext/view/login.dingtalk.html.hook.php` 文件。
3. 本插件不创建新表，无需清理数据库。

---

## 常见问题

### Q1：登录页没有显示"钉钉登录"按钮？

- 检查是否已创建 `dinguser` 类型的 webhook。
- 检查 webhook 是否被删除（`deleted=0`）。
- 检查 `extension/custom/user/ext/view/login.dingtalk.html.hook.php` 是否存在。

### Q2：扫码后提示"无法获取钉钉用户信息"？

- 检查钉钉开放平台的 AppKey/AppSecret 是否正确。
- 检查回调域名是否配置正确（必须与禅道访问域名一致）。
- 检查禅道服务器是否能访问 `oapi.dingtalk.com`（出口网络限制）。

### Q3：提示"您的钉钉账号尚未绑定禅道账号"？

- 该钉钉用户没有在禅道 webhook 中绑定。
- 进入禅道后台 → 通知 → Webhook → 钉钉工作消息 → 绑定用户，将该钉钉用户与禅道账号关联。

### Q4：免登无效，仍跳转到登录页？

- 确保在钉钉客户端或钉钉内置浏览器中打开。
- 确保应用首页地址配置为 `https://你的域名/dingtalklogin-sso.html`。
- 确保应用已发布（未发布的应用只有管理员可见）。

### Q5：升级禅道后插件是否受影响？

- 本插件所有代码位于 `extension/custom/` 下，与禅道核心代码物理隔离。
- 正常升级禅道不会影响插件功能。
- 如果禅道登录页进行了重大重构（如从 view 迁移到 ui），可能需要更新 hook 文件。

---

## 技术支持

- 禅道二次开发文档：https://www.zentao.net/book/extension-dev/custom-dev-1319.html
- 钉钉开放平台文档：https://open.dingtalk.com/document/
