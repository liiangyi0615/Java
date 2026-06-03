<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>校园快递代取系统</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">校园快递代取</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">首页</a>
                    </li>
                    <?php if (is_admin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin.php">管理后台</a>
                        </li>
                    <?php elseif (is_logged_in()): ?>
                        <?php if (is_user()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="publish.php">发布订单</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="my_orders.php">我的订单</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">登录</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">注册</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <?php if (is_logged_in()): ?>
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                👤 <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>
                                <span class="badge badge-light ml-1">
                                    <?php 
                                    $role_names = [
                                        'admin' => '管理员',
                                        'courier' => '代取员',
                                        'user' => '用户'
                                    ];
                                    echo $role_names[get_user_role()] ?? '用户';
                                    ?>
                                </span>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="profile.php">👤 个人中心</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="logout.php">🚪 退出登录</a>
                            </div>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <div class="container mt-4">