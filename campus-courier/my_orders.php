<?php
require_once 'includes/config.php';
require_once 'includes/session.php';

$success = '';
$error = '';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user_id = get_user_id();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['complete_order'])) {
        $order_id = intval($_POST['order_id']);
        $stmt = $conn->prepare("UPDATE orders SET status = 'completed' WHERE id = ? AND user_id = ? AND status = 'delivered'");
        $stmt->execute([$order_id, $user_id]);

        if ($stmt->rowCount() > 0) {
            $success = '订单已完成！';
        } else {
            $error = '操作失败';
        }
        $stmt = null;
    } elseif (isset($_POST['cancel_order'])) {
        $order_id = intval($_POST['order_id']);
        $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status IN ('pending', 'accepted')");
        $stmt->execute([$order_id, $user_id]);

        if ($stmt->rowCount() > 0) {
            $success = '订单已取消！';
        } else {
            $error = '操作失败，订单已被取件或已完成';
        }
        $stmt = null;
    } elseif (isset($_POST['deliver_order'])) {
        $order_id = intval($_POST['order_id']);
        $stmt = $conn->prepare("UPDATE orders SET status = 'delivered' WHERE id = ? AND courier_id = ? AND status = 'picked_up'");
        $stmt->execute([$order_id, $user_id]);

        if ($stmt->rowCount() > 0) {
            $success = '已送达，请等待确认！';
        } else {
            $error = '操作失败';
        }
        $stmt = null;
    } elseif (isset($_POST['pickup_order'])) {
        $order_id = intval($_POST['order_id']);
        $stmt = $conn->prepare("UPDATE orders SET status = 'picked_up' WHERE id = ? AND courier_id = ? AND status = 'accepted'");
        $stmt->execute([$order_id, $user_id]);

        if ($stmt->rowCount() > 0) {
            $success = '已取件，正在配送中！';
        } else {
            $error = '操作失败';
        }
        $stmt = null;
    }
}

$user_role = get_user_role();
?>
<?php include 'includes/header.php'; ?>

<div class="page-header">
    <h1>📋 我的订单</h1>
    <p class="text-muted mb-0">
        <?php
        $role_tips = [
            'user' => '您是用户，可以发布和管理订单',
            'courier' => '您是代取员，可以接单并完成配送',
            'admin' => '您是管理员，请前往管理后台'
        ];
        echo $role_tips[$user_role] ?? '';
        ?>
    </p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (is_user()): ?>
    <div class="section-header">
        <h2 class="section-title">📦 我发布的订单</h2>
        <a href="publish.php" class="btn btn-primary">发布新订单</a>
    </div>

    <?php
    $stmt_published = $conn->prepare("SELECT o.*, u.real_name as courier_name FROM orders o LEFT JOIN users u ON o.courier_id = u.id WHERE o.user_id = ? ORDER BY o.created_at DESC");
    $stmt_published->execute([$user_id]);
    ?>

    <?php if ($stmt_published->rowCount() > 0): ?>
        <div class="orders-list">
            <?php while ($order = $stmt_published->fetch(PDO::FETCH_ASSOC)): ?>
                <div class="order-card-modern">
                    <div class="order-card-header">
                        <div class="courier-badge <?php echo strtolower($order['courier_company']); ?>">
                            <?php echo htmlspecialchars($order['courier_company']); ?>
                        </div>
                        <span class="order-status-badge status-<?php echo $order['status']; ?>">
                            <?php echo get_status_text($order['status']); ?>
                        </span>
                    </div>
                    <div class="order-card-body">
                        <div class="order-info-grid">
                            <div class="info-item">
                                <span class="info-label">📍 取件地</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['pickup_location']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">🏠 送达地</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['delivery_location']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">👤 收件人</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['recipient_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">📞 联系电话</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['recipient_phone'] ?: '无'); ?></span>
                            </div>
                            <?php if ($order['courier_name']): ?>
                                <div class="info-item">
                                    <span class="info-label">🤝 代取员</span>
                                    <span class="info-value"><?php echo htmlspecialchars($order['courier_name']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($order['tracking_number']): ?>
                                <div class="info-item">
                                    <span class="info-label">🔢 快递单号</span>
                                    <span class="info-value tracking-number"><?php echo htmlspecialchars($order['tracking_number']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($order['remark']): ?>
                                <div class="info-item full-width">
                                    <span class="info-label">💬 备注</span>
                                    <span class="info-value remark"><?php echo htmlspecialchars($order['remark']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="order-card-footer">
                            <div class="order-meta">
                                <span class="publish-time">发布时间：<?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div class="order-actions">
                                <?php if ($order['status'] == 'pending' || $order['status'] == 'accepted'): ?>
                                    <form method="POST" action="" style="margin: 0;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <button type="submit" name="cancel_order" class="btn btn-sm btn-danger" onclick="return confirm('确定取消订单？')">取消订单</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($order['status'] == 'pending'): ?>
                                    <span class="badge badge-warning">待接单</span>
                                <?php elseif ($order['status'] == 'accepted'): ?>
                                    <span class="badge badge-info">代取员已接单</span>
                                <?php elseif ($order['status'] == 'picked_up'): ?>
                                    <span class="badge badge-warning">代取员已取件，配送中</span>
                                <?php elseif ($order['status'] == 'delivered'): ?>
                                    <form method="POST" action="" style="margin: 0;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <button type="submit" name="complete_order" class="btn btn-sm btn-success">确认完成</button>
                                    </form>
                                <?php elseif ($order['status'] == 'completed'): ?>
                                    <span class="badge badge-success">已完成</span>
                                <?php elseif ($order['status'] == 'cancelled'): ?>
                                    <span class="badge badge-secondary">已取消</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="reward-tag">
                        <span class="reward-amount">¥<?php echo number_format($order['reward'], 2); ?></span>
                        <span class="reward-label">赏金</span>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">📦</div>
            <h3>暂无发布的订单</h3>
            <p>点击上方按钮发布您的第一个订单</p>
        </div>
    <?php endif; ?>

<?php elseif (is_courier()): ?>
    <div class="section-header">
        <h2 class="section-title">🚴 我接的订单</h2>
        <span class="badge-count">代取员身份</span>
    </div>

    <?php
    $stmt_accepted = $conn->prepare("SELECT o.*, u.real_name as publisher_name FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.courier_id = ? ORDER BY o.created_at DESC");
    $stmt_accepted->execute([$user_id]);
    ?>

    <?php if ($stmt_accepted->rowCount() > 0): ?>
        <div class="orders-list">
            <?php while ($order = $stmt_accepted->fetch(PDO::FETCH_ASSOC)): ?>
                <div class="order-card-modern">
                    <div class="order-card-header">
                        <div class="courier-badge <?php echo strtolower($order['courier_company']); ?>">
                            <?php echo htmlspecialchars($order['courier_company']); ?>
                        </div>
                        <span class="order-status-badge status-<?php echo $order['status']; ?>">
                            <?php echo get_status_text($order['status']); ?>
                        </span>
                    </div>
                    <div class="order-card-body">
                        <div class="order-route">
                            <div class="route-point pickup">
                                <div class="point-dot"></div>
                                <div class="point-info">
                                    <span class="point-label">取件地</span>
                                    <span class="point-value"><?php echo htmlspecialchars($order['pickup_location']); ?></span>
                                </div>
                            </div>
                            <div class="route-line"></div>
                            <div class="route-point delivery">
                                <div class="point-dot"></div>
                                <div class="point-info">
                                    <span class="point-label">送达地</span>
                                    <span class="point-value"><?php echo htmlspecialchars($order['delivery_location']); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="order-info-grid">
                            <div class="info-item">
                                <span class="info-label">👤 发布者</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['publisher_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">👤 收件人</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['recipient_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">📞 收件电话</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['recipient_phone'] ?: '无'); ?></span>
                            </div>
                            <?php if ($order['tracking_number']): ?>
                                <div class="info-item">
                                    <span class="info-label">🔢 快递单号</span>
                                    <span class="info-value tracking-number"><?php echo htmlspecialchars($order['tracking_number']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($order['remark']): ?>
                                <div class="info-item full-width">
                                    <span class="info-label">💬 备注</span>
                                    <span class="info-value remark"><?php echo htmlspecialchars($order['remark']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="order-card-footer">
                            <div class="order-meta">
                                <span class="publish-time">接单时间：<?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div class="order-actions">
                                <?php if ($order['status'] == 'accepted'): ?>
                                    <form method="POST" action="" style="margin: 0;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <button type="submit" name="pickup_order" class="btn btn-sm btn-info">确认取件</button>
                                    </form>
                                <?php elseif ($order['status'] == 'picked_up'): ?>
                                    <form method="POST" action="" style="margin: 0;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <button type="submit" name="deliver_order" class="btn btn-sm btn-primary">确认送达</button>
                                    </form>
                                <?php elseif ($order['status'] == 'delivered'): ?>
                                    <span class="badge badge-warning">等待用户确认</span>
                                <?php elseif ($order['status'] == 'completed'): ?>
                                    <span class="badge badge-success">已完成</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="reward-tag">
                        <span class="reward-amount">¥<?php echo number_format($order['reward'], 2); ?></span>
                        <span class="reward-label">赏金</span>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">🚴</div>
            <h3>暂无接单记录</h3>
            <p>前往首页接单吧！</p>
            <a href="index.php" class="btn btn-primary mt-3">查看待接订单</a>
        </div>
    <?php endif; ?>

<?php elseif (is_admin()): ?>
    <div class="alert alert-info">
        <strong>管理员提示：</strong>管理后台提供完整的订单和用户管理功能。
    </div>
    <div class="text-center py-5">
        <a href="admin.php" class="btn btn-lg btn-primary">前往管理后台</a>
    </div>
<?php endif; ?>

<style>
.page-header {
    margin-bottom: 30px;
}

.page-header h1 {
    font-size: 28px;
    font-weight: 600;
    margin-bottom: 5px;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 25px;
}

.section-title {
    font-size: 20px;
    font-weight: 600;
    color: #333;
}

.badge-count {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 14px;
}

.orders-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.order-card-modern {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    overflow: hidden;
    transition: all 0.3s;
    position: relative;
}

.order-card-modern:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.1);
}

.order-card-header {
    padding: 18px 22px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #f5f5f5;
}

.courier-badge {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    padding: 5px 14px;
    border-radius: 18px;
    font-size: 13px;
    font-weight: 500;
}

.order-status-badge {
    padding: 5px 14px;
    border-radius: 18px;
    font-size: 12px;
    font-weight: 500;
}

.status-pending {
    background: linear-gradient(135deg, #fff3cd, #ffeeba);
    color: #856404;
}

.status-accepted {
    background: linear-gradient(135deg, #c9f0ff, #a8e6cf);
    color: #2d6a4f;
}

.status-delivered {
    background: linear-gradient(135deg, #a8e6cf, #56ab2f);
    color: white;
}

.status-completed {
    background: linear-gradient(135deg, #56ab2f, #2d6a4f);
    color: white;
}

.status-cancelled {
    background: #e9ecef;
    color: #6c757d;
}

.order-card-body {
    padding: 22px;
}

.order-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 15px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.info-item.full-width {
    grid-column: span 2;
}

.info-label {
    font-size: 12px;
    color: #999;
}

.info-value {
    font-size: 14px;
    color: #555;
}

.tracking-number {
    font-family: monospace;
    background: #f5f5f5;
    padding: 3px 8px;
    border-radius: 5px;
}

.remark {
    background: #fff9e6;
    padding: 8px 12px;
    border-radius: 8px;
    border-left: 3px solid #ffc107;
}

.order-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 15px;
    border-top: 1px solid #f5f5f5;
}

.order-meta {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.publish-time {
    font-size: 12px;
    color: #999;
}

.order-actions {
    display: flex;
    gap: 10px;
}

.reward-tag {
    position: absolute;
    top: 15px;
    right: 20px;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    padding: 10px 18px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(245, 87, 108, 0.3);
}

.reward-amount {
    display: block;
    font-size: 20px;
    font-weight: 700;
}

.reward-label {
    font-size: 10px;
    opacity: 0.9;
}

.empty-state {
    text-align: center;
    padding: 50px 20px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
}

.empty-icon {
    font-size: 60px;
    margin-bottom: 15px;
}

.empty-state h3 {
    font-size: 20px;
    color: #333;
    margin-bottom: 8px;
}

.empty-state p {
    color: #888;
    font-size: 14px;
}

.order-route {
    display: flex;
    flex-direction: column;
    gap: 0;
    margin-bottom: 20px;
    padding-left: 8px;
}

.route-point {
    display: flex;
    align-items: center;
    gap: 12px;
}

.point-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 3px solid #667eea;
    background: white;
    z-index: 1;
}

.route-point.delivery .point-dot {
    border-color: #56ab2f;
}

.route-line {
    width: 2px;
    height: 25px;
    background: linear-gradient(to bottom, #667eea, #56ab2f);
    margin-left: 4px;
    margin-top: -4px;
}

.point-info {
    display: flex;
    flex-direction: column;
}

.point-label {
    font-size: 11px;
    color: #999;
}

.point-value {
    font-size: 14px;
    font-weight: 500;
    color: #333;
}

@media (max-width: 768px) {
    .order-info-grid {
        grid-template-columns: 1fr;
    }

    .info-item.full-width {
        grid-column: span 1;
    }

    .order-card-footer {
        flex-direction: column;
        gap: 12px;
        align-items: flex-start;
    }

    .reward-tag {
        position: relative;
        top: 0;
        right: 0;
        margin-bottom: 12px;
        display: inline-block;
    }

    .section-header {
        flex-direction: column;
        gap: 12px;
        align-items: flex-start;
    }
}
</style>

<?php include 'includes/footer.php'; ?>