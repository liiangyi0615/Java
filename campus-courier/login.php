<?php
require_once 'includes/config.php';
require_once 'includes/session.php';

$error = '';

if (is_logged_in()) {
    if (is_admin()) {
        header('Location: admin.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = '请填写用户名和密码';
    } else {
        $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
        $stmt->execute([$username]);

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password'])) {
                login($user['id'], $user['role'], $username);
                if ($user['role'] === 'admin') {
                    header('Location: admin.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            } else {
                $error = '密码错误';
            }
        } else {
            $error = '用户不存在';
        }
        $stmt = null;
    }
}
?>
<?php include 'includes/header.php'; ?>

<h1 class="mb-4">用户登录</h1>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST" action="login.php">
    <div class="form-group">
        <label for="username">用户名</label>
        <input type="text" class="form-control" id="username" name="username" required>
    </div>
    <div class="form-group">
        <label for="password">密码</label>
        <input type="password" class="form-control" id="password" name="password" required>
    </div>
    <button type="submit" class="btn btn-primary">登录</button>
    <p class="mt-3">还没有账号？<a href="register.php">立即注册</a></p>
</form>

<?php include 'includes/footer.php'; ?>