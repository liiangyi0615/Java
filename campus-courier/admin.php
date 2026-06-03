<?php
require_once 'includes/config.php';
require_once 'includes/session.php';

require_role('admin');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_order_status'])) {
        $order_id = intval($_POST['order_id']);
        $new_status = $_POST['new_status'];
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
        $success = '订单状态已更新';
        $stmt = null;
    } elseif (isset($_POST['delete_order'])) {
        $order_id = intval($_POST['order_id']);
        $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $success = '订单已删除';
        $stmt = null;
    } elseif (isset($_POST['delete_user'])) {
        $user_id = intval($_POST['user_id']);
        if ($user_id != get_user_id()) {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $success = '用户已删除';
            $stmt = null;
        } else {
            $error = '不能删除自己';
        }
    }
}

$users = $conn->query("SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC");
$orders = $conn->query("SELECT o.*, u.username as publisher_name FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC");
$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn(),
    'total_orders' => $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'pending_orders' => $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
    'completed_orders' => $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'")->fetchColumn()
];
?>
<?php include 'includes/header.php'; ?>

<div class="page-header">
    <h1>🎛️ 管理后台</h1>
    <p class="text-muted mb-0">管理系统用户和订单</p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="card-title">👥 用户总数</div>
            <div class="card-text"><?php echo $stats['total_users']; ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="card-title">📦 订单总数</div>
            <div class="card-text"><?php echo $stats['total_orders']; ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card text-warning">
            <div class="card-title">⏳ 待处理订单</div>
            <div class="card-text"><?php echo $stats['pending_orders']; ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card text-success">
            <div class="card-title">✅ 已完成订单</div>
            <div class="card-text"><?php echo $stats['completed_orders']; ?></div>
        </div>
    </div>
</div>

<h2 class="mt-5 mb-3">👥 用户管理</h2>
<div class="card">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>ID</th>
                <th>用户名</th>
                <th>真实姓名</th>
                <th>电话</th>
                <th>角色</th>
                <th>注册时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($user = $users->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                    <td><?php echo htmlspecialchars($user['real_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                    <td>
                        <?php
                        $role_badge_class = [
                            'user' => 'badge-primary',
                            'courier' => 'badge-success'
                        ];
                        $role_names = [
                            'user' => '用户',
                            'courier' => '代取员'
                        ];
                        ?>
                        <span class="badge <?php echo $role_badge_class[$user['role']] ?? 'badge-secondary'; ?>">
                            <?php echo $role_names[$user['role']] ?? $user['role']; ?>
                        </span>
                    </td>
                    <td class="text-muted"><?php echo $user['created_at']; ?></td>
                    <td>
                        <?php if ($user['id'] != get_user_id()): ?>
                            <form method="POST" class="form-inline">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="delete_user" class="btn btn-sm btn-danger" onclick="return confirm('确定删除该用户？')">删除</button>
                            </form>
                        <?php else: ?>
                            <span class="badge badge-primary">当前账号</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<h2 class="mt-5 mb-3">📦 订单管理</h2>
<div class="card">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>ID</th>
                <th>快递公司</th>
                <th>取件地点</th>
                <th>送达地点</th>
                <th>发布者</th>
                <th>状态</th>
                <th>奖励</th>
                <th>创建时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($order = $orders->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td><?php echo $order['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($order['courier_company']); ?></strong></td>
                    <td><?php echo htmlspecialchars($order['pickup_location']); ?></td>
                    <td><?php echo htmlspecialchars($order['delivery_location']); ?></td>
                    <td><?php echo htmlspecialchars($order['publisher_name']); ?></td>
                    <td>
                        <?php
                        $status_badge_class = [
                            'pending' => 'badge-warning',
                            'accepted' => 'badge-info',
                            'delivered' => 'badge-success',
                            'completed' => 'badge-primary',
                            'cancelled' => 'badge-secondary',
                            'picked_up' => 'badge-info'
                        ];
                        $status_names = [
                            'pending' => '待接单',
                            'accepted' => '已接单',
                            'picked_up' => '已取件',
                            'delivered' => '已送达',
                            'completed' => '已完成',
                            'cancelled' => '已取消'
                        ];
                        ?>
                        <span class="badge <?php echo $status_badge_class[$order['status']] ?? 'badge-secondary'; ?>">
                            <?php echo $status_names[$order['status']] ?? $order['status']; ?>
                        </span>
                    </td>
                    <td><span class="order-reward">¥<?php echo number_format($order['reward'], 2); ?></span></td>
                    <td class="text-muted"><?php echo $order['created_at']; ?></td>
                    <td>
                        <div class="btn-group" role="group">
                            <form method="POST" action="" style="margin: 0;">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <select name="new_status" class="form-control form-control-sm" style="width: auto; display: inline;">
                                    <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>待接单</option>
                                    <option value="accepted" <?php echo $order['status'] === 'accepted' ? 'selected' : ''; ?>>已接单</option>
                                    <option value="picked_up" <?php echo $order['status'] === 'picked_up' ? 'selected' : ''; ?>>已取件</option>
                                    <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>已送达</option>
                                    <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>已完成</option>
                                    <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>已取消</option>
                                </select>
                                <button type="submit" name="update_order_status" class="btn btn-sm btn-primary ml-1">更新</button>
                            </form>
                            <form method="POST" action="" style="margin: 0; margin-left: 5px;">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <button type="submit" name="delete_order" class="btn btn-sm btn-danger" onclick="return confirm('确定删除该订单？')">删除</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>