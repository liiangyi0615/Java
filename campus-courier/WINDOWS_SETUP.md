# Windows 环境配置指南

本指南详细说明如何在 Windows 系统上配置 PHP 和 MySQL 环境。

## 目录
1. [安装 PHP](#安装-php)
2. [安装 MySQL](#安装-mysql)
3. [开启 MySQL 服务](#开启-mysql-服务)
4. [配置 PHP 扩展](#配置-php-扩展)
5. [常见问题解决](#常见问题解决)

---

## 安装 PHP

### 方式一：通过 PHP 官网下载（推荐）

#### 步骤 1：下载 PHP

1. 访问 PHP 官网下载页面：https://www.php.net/downloads.php
2. 选择 Windows 版本
3. 下载 **Thread Safe** 版本（通常文件名包含 `php-x.x.x-Win32-vs16-x64.zip`）

#### 步骤 2：解压安装

1. 选择安装目录，例如 `C:\php`
2. 将下载的 ZIP 文件解压到该目录

#### 步骤 3：配置环境变量

1. 按 `Win + R`，输入 `sysdm.cpl`，回车
2. 点击「高级」选项卡 → 「环境变量」
3. 在「系统变量」中找到 `Path`，双击编辑
4. 点击「新建」，添加 `C:\php`（根据实际安装路径）
5. 点击「确定」保存

#### 步骤 4：验证安装

打开命令提示符，输入：
```cmd
php -v
```
如果显示 PHP 版本信息，说明安装成功。

---

### 方式二：通过 phpstudy（新手推荐）

phpstudy 是一款集成了 Apache/Nginx、PHP、MySQL 的软件包，一键安装配置。

#### 步骤 1：下载 phpstudy

1. 访问官网：https://www.xp.cn/download.html
2. 下载 Windows 版本

#### 步骤 2：安装运行

1. 解压并运行安装程序
2. 选择安装目录（建议使用默认目录）
3. 点击「启动」按钮，启动 Apache 和 MySQL 服务

#### 步骤 3：切换 PHP 版本

1. 点击「设置」→「PHP版本」
2. 选择 PHP 7.4 或更高版本

#### 步骤 4：验证安装

1. 点击「管理」→ 「首页」
2. 查看服务状态，确认 Apache 和 MySQL 都已启动

**优点**：图形化界面，一键启动，配置简单
**缺点**：软件包较大，部分功能可能用不到

---

### 方式三：通过 Laragon（推荐用于开发）

Laragon 是一款轻量级的开发环境，支持多版本 PHP、MySQL、Apache/Nginx。

#### 步骤 1：下载 Laragon

1. 访问官网：https://laragon.org/download/
2. 下载完整版（含 PHP + MySQL）

#### 步骤 2：安装运行

1. 运行安装程序
2. 安装完成后， Laragon 会自动启动
3. 点击「启动所有服务」

#### 步骤 3：配置

1. 右键点击 Laragon 图标 → 「PHP」→ 选择 PHP 版本
2. 右键点击 → 「数据库」→ 选择 MySQL 版本

**优点**：自动配置虚拟主机、自动配置 DNS、功能丰富
**缺点**：相对较重，可能有学习成本

---

## 安装 MySQL

### 方式一：通过 MySQL 官网下载

#### 步骤 1：下载 MySQL

1. 访问：https://dev.mysql.com/downloads/mysql/
2. 选择 Windows 版本
3. 下载 MSI 安装包或 ZIP 压缩包

#### 步骤 2：安装

1. 运行 MSI 安装包
2. 选择「Typical」或「Custom」安装
3. 设置 root 用户密码
4. 完成安装向导

#### 步骤 3：配置环境变量

1. 按 `Win + R`，输入 `sysdm.cpl`，回车
2. 点击「高级」选项卡 → 「环境变量」
3. 在「系统变量」中找到 `Path`，双击编辑
4. 添加 MySQL 的 bin 目录路径（例如 `C:\Program Files\MySQL\MySQL Server 8.0\bin`）
5. 点击「确定」保存

#### 步骤 4：验证安装

打开命令提示符，输入：
```cmd
mysql -u root -p
```
输入密码后如果能进入 MySQL 命令行，说明安装成功。

---

### 方式二：通过 phpstudy 安装（已包含 MySQL）

如果使用 phpstudy，MySQL 已经包含在内，无需单独安装。

---

## 开启 MySQL 服务

### 方式一：通过服务管理器

1. 按 `Win + R` 键，输入 `services.msc`，回车
2. 在服务列表中找到 MySQL 相关服务（常见名称：`MySQL`、`MySQL80`、`MySQL Server`）
3. 右键点击服务，选择「启动」
4. （可选）将启动类型设置为「自动」，以便开机自动启动

### 方式二：通过命令提示符

1. 以管理员身份打开命令提示符（CMD）或 PowerShell
2. 启动服务：
```cmd
net start MySQL80
```
注意：服务名可能是 `MySQL` 或其他版本号，请根据实际情况修改

3. 停止服务：
```cmd
net stop MySQL80
```

### 方式三：通过任务管理器

1. 按 `Ctrl + Shift + Esc` 打开任务管理器
2. 切换到「服务」选项卡
3. 找到 MySQL 服务，右键选择「启动」

### 验证 MySQL 是否运行

1. 打开命令提示符，输入：
```cmd
mysql -u root -p
```
2. 如果能进入 MySQL 命令行，说明服务已启动

---

## 配置 PHP 扩展

### 步骤 1：找到 php.ini 配置文件

1. 在项目目录创建一个 `phpinfo.php` 文件：
```php
<?php
phpinfo();
?>
```

2. 在浏览器中访问此文件，查找「Loaded Configuration File」项，找到 `php.ini` 的路径

### 步骤 2：编辑 php.ini 文件

1. 用文本编辑器打开 `php.ini`
2. 查找以下扩展配置，删除前面的分号 `;` 来启用：

```ini
extension=mysqli
extension=session
```

注意：在较新的 PHP 版本中，可能需要指定完整路径：
```ini
extension_dir = "ext"
extension=mysqli
extension=session
```

### 步骤 3：验证扩展是否启用

1. 刷新 `phpinfo.php` 页面
2. 查找以下部分确认扩展已加载：
   - mysqli 部分
   - session 部分

3. 或者使用我们项目中的 `test.php` 文件检查

### 步骤 4：重启 PHP 服务

如果使用 PHP 内置服务器，直接停止（Ctrl+C）后重新启动即可。

---

## 常见问题解决

### 问题 1：找不到 php.ini 文件

- 解决方案：在 PHP 安装目录下查找 `php.ini-development` 或 `php.ini-production`，复制并重命名为 `php.ini`

### 问题 2：extension_dir 路径错误

- 解决方案：确保 extension_dir 指向 PHP 的 ext 目录，例如：
```ini
extension_dir = "C:\php\ext"
```

### 问题 3：MySQL 服务启动失败

- 检查端口 3306 是否被占用
- 查看 MySQL 错误日志文件（通常在 MySQL 安装目录的 data 文件夹中）

### 问题 4：扩展仍未加载

- 确认 PHP 版本与扩展版本匹配
- 检查 PHP 错误日志
- 尝试绝对路径指定扩展：
```ini
extension=C:\php\ext\php_mysqli.dll
```

---

## 快速检查清单

- [ ] PHP 已安装并可通过 php -v 查看版本
- [ ] MySQL 已安装并可通过 mysql -u root -p 连接
- [ ] MySQL 服务正在运行
- [ ] php.ini 中 mysqli 扩展已启用
- [ ] php.ini 中 session 扩展已启用
- [ ] PHP 可以正常连接 MySQL 数据库
- [ ] 项目中的 test.php 所有检查项通过

完成以上配置后，就可以正常使用校园快递代取系统了！

---

## 推荐：新手使用 phpstudy

如果您是 PHP 新手，强烈推荐使用 **phpstudy**，因为它：

1. **一键安装**：省去繁琐的手动配置步骤
2. **图形界面**：所有配置通过界面操作，无需修改配置文件
3. **集成环境**：PHP、MySQL、Apache/Nginx 一次性配置完成
4. **版本切换**：可以轻松切换不同的 PHP 和 MySQL 版本
5. **教程丰富**：网上有大量 phpstudy 使用教程，遇到问题容易找到解决方案

**使用 phpstudy 的完整流程：**
1. 下载并安装 phpstudy
2. 启动 phpstudy，确保 Apache 和 MySQL 都是运行状态
3. 将项目文件放到 phpstudy 的网站根目录（通常是 `C:\phpstudy_pro\WWW`）
4. 访问 http://localhost/campus-courier/test.php 进行测试
5. 如果看到测试页面，说明环境配置成功！
