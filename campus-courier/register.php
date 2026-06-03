<?php
require_once 'includes/config.php';
require_once 'includes/session.php';

$error = '';
$success = '';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $real_name = $_POST['real_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if (empty($username) || empty($password) || empty($confirm_password) || empty($real_name)) {
        $error = '请填写所有必填字段';
    } elseif ($password != $confirm_password) {
        $error = '两次输入的密码不一致';
    } elseif (strlen($password) < 6) {
        $error = '密码长度至少6位';
    } elseif (!in_array($role, ['user', 'courier'])) {
        $error = '请选择正确的角色';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);

        if ($stmt->rowCount() > 0) {
            $error = '用户名已存在';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, real_name, phone, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $real_name, $phone, $role]);

            if ($stmt->rowCount() > 0) {
                $success = '注册成功，请登录';
            } else {
                $error = '注册失败，请重试';
            }
            $stmt = null;
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<h1 class="mb-4">用户注册</h1>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<form method="POST" action="register.php">
    <div class="form-group">
        <label for="username">用户名 <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="username" name="username" required>
    </div>
    <div class="form-group">
        <label for="password">密码 <span class="text-danger">*</span></label>
        <input type="password" class="form-control" id="password" name="password" required>
    </div>
    <div class="form-group">
        <label for="confirm_password">确认密码 <span class="text-danger">*</span></label>
        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
    </div>
    <div class="form-group">
        <label for="real_name">真实姓名 <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="real_name" name="real_name" required>
    </div>
    <div class="form-group">
        <label for="phone">联系电话</label>
        <input type="tel" class="form-control" id="phone" name="phone">
    </div>
    <div class="form-group">
        <label for="role">角色 <span class="text-danger">*</span></label>
        <select class="form-control" id="role" name="role" required>
            <option value="user">用户（发布快递代取需求）</option>
            <option value="courier">代取员（接单帮忙代取快递）</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">注册</button>
    <p class="mt-3">已有账号？<a href="login.php">立即登录</a></p>
</form>

<?php include 'includes/footer.php'; ?>