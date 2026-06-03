
# 校园快递代取管理系统

基于 PHP + MySQL 的校园快递代取管理系统

## 功能特性

- 用户注册和登录
- 发布快递代取订单
- 浏览和接单
- 订单状态管理（待接单、已接单、已送达、已完成、已取消）
- 订单管理（我发布的订单、我接的订单）

## 目录结构

```
campus-courier/
├── includes/
│   ├── config.php      # 数据库配置
│   ├── session.php # 会话管理
│   ├── header.php  # 页面头部
│   └── footer.php  # 页面底部
├── css/
│   └── style.css     # 样式文件
├── index.php       # 首页（订单列表）
├── register.php    # 注册页面
├── login.php       # 登录页面
├── logout.php      # 退出登录
├── publish.php    # 发布订单
├── my_orders.php  # 我的订单
├── test.php       # 测试页面
├── phpinfo.php    # PHP 配置查看
├── start.bat      # Windows 启动脚本
├── start.sh       # Linux/Mac 启动脚本
├── database.sql   # 数据库初始化文件
├── WINDOWS_SETUP.md  # Windows 环境配置指南
└── README.md      # 说明文档
```

## 本地运行

### 1. 环境要求

- PHP 7.0 或更高版本
- MySQL 5.6 或更高版本

### 2. 数据库配置

1. 启动 MySQL 服务

2. 创建数据库并导入 `database.sql` 文件：

```bash
mysql -u root -p < database.sql
```

3. 修改 `includes/config.php` 中的数据库连接信息（如需要）：

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'campus_courier');
```

### 3. 启动本地服务器

#### 方式一：使用启动脚本（推荐）

**Windows 用户：**
双击运行 `start.bat`

**Linux/Mac 用户：**
```bash
chmod +x start.sh
./start.sh
```

#### 方式二：手动启动

在项目根目录下打开命令行，运行：

```bash
php -S localhost:8000
```

### 4. 访问系统

在浏览器中访问以下地址即可开始使用：

- 测试页面：http://localhost:8000/test.php
- 首页：http://localhost:8000/index.php

### 注意事项

- 确保 MySQL 服务正在运行
- 确保 PHP 的 mysqli 和 session 扩展已启用
- 默认端口是 8000，可根据需要修改

**详细配置步骤请查看：[Windows 环境配置指南](WINDOWS_SETUP.md)**

## 使用说明

### 注册账号

1. 点击右上角点击"注册"
2. 填写用户名、密码、真实姓名、手机号等信息
3. 完成注册后登录

### 发布订单

1. 登录后点击"发布订单"
2. 填写快递公司、取件地点、送达地点、收件人信息和代取酬劳
3. 点击"发布订单"

### 接单

1. 在首页查看待接订单列表
2. 选择合适的订单点击"立即接单"

### 管理订单

1. 点击"我的订单"查看自己发布和接的订单
2. 可以取消待接单的订单、标记已送达、确认完成

## 测试

### 运行测试

1. 确保已安装 PHP 和 MySQL
2. 配置好数据库连接
3. 在浏览器中访问 `test.php` 进行系统测试

### 测试内容

- PHP 版本检查
- PHP 扩展检查（mysqli, session）
- 数据库连接测试
- 数据库表检查
- 文件完整性检查

## 技术栈

- 后端：PHP
- 数据库：MySQL
- 前端：HTML5, CSS3
