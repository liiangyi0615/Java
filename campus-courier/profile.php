<?php
require_once 'includes/config.php';
require_once 'includes/session.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user_id = get_user_id();
$success = '';
$error = '';

// 处理个人信息更新
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $real_name = trim($_POST['real_name']);
    $phone = trim($_POST['phone']);
    $school = isset($_POST['school']) ? trim($_POST['school']) : '';
    $student_id = isset($_POST['student_id']) ? trim($_POST['student_id']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';

    if (empty($real_name) || empty($phone)) {
        $error = '姓名和手机号不能为空';
    } else {
        $stmt = $conn->prepare("UPDATE users SET real_name = ?, phone = ?, school = ?, student_id = ?, address = ? WHERE id = ?");
        $stmt->execute([$real_name, $phone, $school, $student_id, $address, $user_id]);

        if ($stmt->rowCount() !== false) {
            $success = '个人信息更新成功！';
            $_SESSION['real_name'] = $real_name;
        } else {
            $error = '更新失败，请重试';
        }
    }
}

// 处理密码修改
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = '请填写所有密码字段';
    } elseif ($new_password !== $confirm_password) {
        $error = '两次输入的新密码不一致';
    } elseif (strlen($new_password) < 6) {
        $error = '新密码长度不能少于6位';
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($current_password, $user['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            $success = '密码修改成功！';
        } else {
            $error = '当前密码错误';
        }
    }
}

// 获取当前用户信息
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// 获取用户统计数据
$stats = [
    'total_orders' => 0,
    'pending_orders' => 0,      // 待接取
    'accepted_orders' => 0,     // 已接单
    'picked_up_orders' => 0,    // 已取件
    'delivered_orders' => 0,    // 已送达
    'completed_orders' => 0,    // 已完成
    'cancelled_orders' => 0,    // 已取消
    'total_reward' => 0
];

if ($user['role'] == 'user') {
    $stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM orders WHERE user_id = ? GROUP BY status");
    $stmt->execute([$user_id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats['total_orders'] += $row['count'];
        switch ($row['status']) {
            case 'pending':
                $stats['pending_orders'] += $row['count'];
                break;
            case 'accepted':
                $stats['accepted_orders'] += $row['count'];
                break;
            case 'picked_up':
                $stats['picked_up_orders'] += $row['count'];
                break;
            case 'delivered':
                $stats['delivered_orders'] += $row['count'];
                break;
            case 'completed':
                $stats['completed_orders'] += $row['count'];
                break;
            case 'cancelled':
                $stats['cancelled_orders'] += $row['count'];
                break;
        }
    }
} elseif ($user['role'] == 'courier') {
    $stmt = $conn->prepare("SELECT status, COUNT(*) as count, SUM(reward) as total FROM orders WHERE courier_id = ? GROUP BY status");
    $stmt->execute([$user_id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats['total_orders'] += $row['count'];
        switch ($row['status']) {
            case 'accepted':
                $stats['accepted_orders'] += $row['count'];
                break;
            case 'picked_up':
                $stats['picked_up_orders'] += $row['count'];
                break;
            case 'delivered':
                $stats['delivered_orders'] += $row['count'];
                break;
            case 'completed':
                $stats['completed_orders'] += $row['count'];
                break;
            case 'cancelled':
                $stats['cancelled_orders'] += $row['count'];
                break;
        }
        if ($row['total']) {
            $stats['total_reward'] += $row['total'];
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<style>
.profile-page {
    max-width: 900px;
    margin: 0 auto;
}

/* 用户头部卡片 */
.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 40px;
    color: white;
    text-align: center;
    margin-bottom: 30px;
    position: relative;
    overflow: hidden;
}

/* 订单状态角标 */
.order-status-badge {
    position: absolute;
    bottom: 20px;
    right: 20px;
    display: flex;
    gap: 12px;
    z-index: 2;
}

.status-item {
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
    padding: 8px 14px;
    border-radius: 12px;
    text-align: center;
    min-width: 60px;
    transition: transform 0.3s, box-shadow 0.3s;
}

.status-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.status-item.pending {
    border-left: 3px solid #ffc107;
}

.status-item.accepted {
    border-left: 3px solid #17a2b8;
}

.status-item.picked_up {
    border-left: 3px solid #ff8c00;
}

.status-item.delivered {
    border-left: 3px solid #1e90ff;
}

.status-item.completed {
    border-left: 3px solid #28a745;
}

.status-item.cancelled {
    border-left: 3px solid #dc3545;
}

.status-count {
    display: block;
    font-size: 20px;
    font-weight: 700;
    line-height: 1.2;
}

.status-label {
    display: block;
    font-size: 11px;
    opacity: 0.9;
    margin-top: 2px;
}

.profile-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 300px;
    height: 300px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
}

.profile-header::after {
    content: '';
    position: absolute;
    bottom: -30%;
    left: -10%;
    width: 200px;
    height: 200px;
    background: rgba(255,255,255,0.08);
    border-radius: 50%;
}

/* 头像 */
.profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: white;
    color: #667eea;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 50px;
    font-weight: bold;
    margin: 0 auto 20px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    position: relative;
    z-index: 1;
}

.profile-name {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 8px;
    position: relative;
    z-index: 1;
}

/* 角色标签 */
.profile-role {
    display: inline-block;
    background: rgba(255,255,255,0.2);
    padding: 6px 20px;
    border-radius: 25px;
    font-size: 14px;
    margin-bottom: 20px;
    position: relative;
    z-index: 1;
}

/* 基本信息行 */
.profile-info-row {
    display: flex;
    justify-content: center;
    gap: 40px;
    flex-wrap: wrap;
    position: relative;
    z-index: 1;
}

.profile-info-item {
    text-align: center;
    min-width: 100px;
}

.profile-info-value {
    font-size: 22px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.profile-info-label {
    font-size: 12px;
    opacity: 0.85;
    margin-top: 4px;
}

/* 统计卡片 */
.profile-stats {
    display: flex;
    justify-content: center;
    gap: 25px;
    margin-top: 30px;
    padding-top: 25px;
    border-top: 1px solid rgba(255,255,255,0.15);
    position: relative;
    z-index: 1;
}

.profile-stat-card {
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    padding: 20px 30px;
    border-radius: 16px;
    text-align: center;
    min-width: 120px;
    transition: transform 0.3s, box-shadow 0.3s;
}

.profile-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}

.profile-stat-value {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 5px;
}

.profile-stat-label {
    font-size: 12px;
    opacity: 0.9;
}

/* 内容区域 */
.profile-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
}

/* 信息卡片 */
.profile-card {
    background: white;
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 4px 25px rgba(0,0,0,0.06);
    transition: transform 0.3s, box-shadow 0.3s;
}

.profile-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.08);
}

.profile-card-title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin-bottom: 22px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* 表单样式 */
.form-group {
    margin-bottom: 18px;
}

.form-label {
    display: block;
    font-size: 14px;
    color: #555;
    margin-bottom: 8px;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #e5e5e5;
    border-radius: 12px;
    font-size: 14px;
    transition: all 0.3s;
    background: #fafafa;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    background: white;
}

.form-control:disabled {
    background: #f5f5f5;
    cursor: not-allowed;
    color: #999;
}

/* 按钮样式 */
.btn-save {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 10px;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

/* 角色徽章 */
.role-badge {
    display: inline-block;
    padding: 6px 18px;
    border-radius: 25px;
    font-size: 14px;
    font-weight: 500;
}

.role-admin {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.role-user {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.role-courier {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
}

/* 提示信息 */
.alert {
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* 分隔线 */
.divider {
    height: 1px;
    background: linear-gradient(90deg, transparent, #e5e5e5, transparent);
    margin: 20px 0;
}

/* 信息项 */
.info-box {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 15px;
    background: #fafafa;
    border-radius: 10px;
    margin-bottom: 10px;
}

.info-box-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
}

.info-box-content {
    flex: 1;
}

.info-box-label {
    font-size: 12px;
    color: #999;
    margin-bottom: 2px;
}

.info-box-value {
    font-size: 14px;
    color: #333;
    font-weight: 500;
}

/* 响应式 */
@media (max-width: 768px) {
    .profile-content {
        grid-template-columns: 1fr;
    }

    .profile-header {
        padding: 30px 20px;
    }

    .profile-avatar {
        width: 100px;
        height: 100px;
        font-size: 40px;
    }

    .profile-name {
        font-size: 24px;
    }

    .profile-info-row {
        gap: 25px;
    }

    .profile-stats {
        flex-wrap: wrap;
        gap: 15px;
    }

    .profile-stat-card {
        min-width: calc(50% - 10px);
        padding: 15px 20px;
    }

    .profile-stat-value {
        font-size: 26px;
    }
}
</style>

<div class="container mt-4">
    <div class="profile-page">
        <?php if ($success): ?>
            <div class="alert alert-success">✓ <?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">✗ <?php echo $error; ?></div>
        <?php endif; ?>

        <!-- 用户头部信息 -->
        <div class="profile-header">
            <!-- 订单状态角标 -->
            <div class="order-status-badge">
                <?php if ($user['role'] == 'user' && $stats['pending_orders'] > 0): ?>
                <div class="status-item pending">
                    <span class="status-count"><?php echo $stats['pending_orders']; ?></span>
                    <span class="status-label">待接取</span>
                </div>
                <?php endif; ?>
                <?php if ($stats['accepted_orders'] > 0): ?>
                <div class="status-item accepted">
                    <span class="status-count"><?php echo $stats['accepted_orders']; ?></span>
                    <span class="status-label">已接单</span>
                </div>
                <?php endif; ?>
                <?php if ($stats['picked_up_orders'] > 0): ?>
                <div class="status-item picked_up">
                    <span class="status-count"><?php echo $stats['picked_up_orders']; ?></span>
                    <span class="status-label">已取件</span>
                </div>
                <?php endif; ?>
                <?php if ($stats['delivered_orders'] > 0): ?>
                <div class="status-item delivered">
                    <span class="status-count"><?php echo $stats['delivered_orders']; ?></span>
                    <span class="status-label">已送达</span>
                </div>
                <?php endif; ?>
                <?php if ($stats['completed_orders'] > 0): ?>
                <div class="status-item completed">
                    <span class="status-count"><?php echo $stats['completed_orders']; ?></span>
                    <span class="status-label">已完成</span>
                </div>
                <?php endif; ?>
                <?php if ($stats['cancelled_orders'] > 0): ?>
                <div class="status-item cancelled">
                    <span class="status-count"><?php echo $stats['cancelled_orders']; ?></span>
                    <span class="status-label">已取消</span>
                </div>
                <?php endif; ?>
            </div>

            <div class="profile-avatar">
                <?php echo mb_substr($user['real_name'] ?: $user['username'], 0, 1); ?>
            </div>
            <h1 class="profile-name"><?php echo htmlspecialchars($user['real_name'] ?: $user['username']); ?></h1>
            
            <!-- 角色标签 -->
            <div class="profile-role">
                <?php if ($user['role'] == 'admin'): ?>
                    👑 管理员
                <?php elseif ($user['role'] == 'user'): ?>
                    👤 用户
                <?php elseif ($user['role'] == 'courier'): ?>
                    🚴 代取员
                <?php endif; ?>
            </div>

            <!-- 基本信息 -->
            <div class="profile-info-row">
                <div class="profile-info-item">
                    <div class="profile-info-value">📧 <?php echo htmlspecialchars($user['username']); ?></div>
                    <div class="profile-info-label">用户名</div>
                </div>
                <div class="profile-info-item">
                    <div class="profile-info-value">📱 <?php echo $user['phone'] ?: '-'; ?></div>
                    <div class="profile-info-label">手机号</div>
                </div>
                <?php if ($user['school']): ?>
                    <div class="profile-info-item">
                        <div class="profile-info-value">🏛️ <?php echo htmlspecialchars($user['school']); ?></div>
                        <div class="profile-info-label">学校</div>
                    </div>
                <?php endif; ?>
                <?php if ($user['student_id']): ?>
                    <div class="profile-info-item">
                        <div class="profile-info-value">🆔 <?php echo htmlspecialchars($user['student_id']); ?></div>
                        <div class="profile-info-label">学号</div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 统计数据 -->
            <div class="profile-stats">
                <div class="profile-stat-card">
                    <div class="profile-stat-value"><?php echo $stats['total_orders']; ?></div>
                    <div class="profile-stat-label"><?php echo $user['role'] == 'courier' ? '接取订单' : '发布订单'; ?></div>
                </div>
                <div class="profile-stat-card">
                    <div class="profile-stat-value"><?php echo $stats['completed_orders']; ?></div>
                    <div class="profile-stat-label">已完成</div>
                </div>
                <?php if ($user['role'] == 'courier'): ?>
                    <div class="profile-stat-card">
                        <div class="profile-stat-value">¥<?php echo number_format($stats['total_reward'], 2); ?></div>
                        <div class="profile-stat-label">累计收益</div>
                    </div>
                <?php else: ?>
                    <div class="profile-stat-card">
                        <div class="profile-stat-value"><?php echo $stats['pending_orders']; ?></div>
                        <div class="profile-stat-label">进行中</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 内容区域 -->
        <div class="profile-content">
            <!-- 个人信息 -->
            <div class="profile-card">
                <h3 class="profile-card-title">👤 个人信息</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">用户名</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label">真实姓名 <span class="text-danger">*</span></label>
                        <input type="text" name="real_name" class="form-control" value="<?php echo htmlspecialchars($user['real_name']); ?>" required placeholder="请输入真实姓名">
                    </div>
                    <div class="form-group">
                        <label class="form-label">手机号 <span class="text-danger">*</span></label>
                        <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" required placeholder="请输入手机号">
                    </div>
                    <?php if ($user['role'] != 'admin'): ?>
                    <div class="form-group">
                        <label class="form-label">学校名称</label>
                        <input type="text" name="school" class="form-control" value="<?php echo htmlspecialchars($user['school'] ?? ''); ?>" placeholder="请输入学校名称">
                    </div>
                    <div class="form-group">
                        <label class="form-label">学号</label>
                        <input type="text" name="student_id" class="form-control" value="<?php echo htmlspecialchars($user['student_id'] ?? ''); ?>" placeholder="请输入学号">
                    </div>
                    <div class="form-group">
                        <label class="form-label">常用地址</label>
                        <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" placeholder="例如：学生公寓5号楼305">
                    </div>
                    <?php endif; ?>
                    <button type="submit" name="update_profile" class="btn-save">保存修改</button>
                </form>
            </div>

            <!-- 修改密码 -->
            <div class="profile-card">
                <h3 class="profile-card-title">🔐 修改密码</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">当前密码</label>
                        <input type="password" name="current_password" class="form-control" required placeholder="请输入当前密码">
                    </div>
                    <div class="form-group">
                        <label class="form-label">新密码</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6" placeholder="请输入新密码（至少6位）">
                    </div>
                    <div class="form-group">
                        <label class="form-label">确认新密码</label>
                        <input type="password" name="confirm_password" class="form-control" required placeholder="请再次输入新密码">
                    </div>
                    <button type="submit" name="change_password" class="btn-save">修改密码</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>