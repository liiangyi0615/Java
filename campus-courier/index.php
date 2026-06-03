<?php
require_once 'includes/config.php';
require_once 'includes/session.php';

$success = '';
$error = '';

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accept_order'])) {
    $order_id = intval($_POST['order_id']);
    $user_id = get_user_id();

    $stmt = $conn->prepare("UPDATE orders SET status = 'accepted', courier_id = ? WHERE id = ? AND status = 'pending' AND user_id != ?");
    $stmt->execute([$user_id, $order_id, $user_id]);

    if ($stmt->rowCount() > 0) {
        $success = '接单成功！';
    } else {
        $error = '接单失败，订单可能已被接取或不存在';
    }
    $stmt = null;
}

$stmt = $conn->query("SELECT o.*, u.real_name as publisher_name FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.status = 'pending' ORDER BY o.created_at DESC");
$pending_count = $stmt->rowCount();

// 获取订单的快递包裹
function getOrderPackages($conn, $order_id) {
    $stmt = $conn->prepare("SELECT * FROM order_packages WHERE order_id = ? ORDER BY id");
    $stmt->execute([$order_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<?php include 'includes/header.php'; ?>

<div class="hero-section">
    <div class="hero-content">
        <h1 class="hero-title">🚀 校园快递代取</h1>
        <p class="hero-subtitle">轻松发布订单，快速响应送达</p>
        <?php if (!is_logged_in()): ?>
            <div class="hero-actions">
                <a href="login.php" class="btn btn-lg btn-primary">立即登录</a>
                <a href="register.php" class="btn btn-lg btn-outline-light">注册账号</a>
            </div>
        <?php endif; ?>
    </div>
    <div class="hero-stats">
        <div class="hero-stat">
            <span class="stat-number"><?php echo $pending_count; ?></span>
            <span class="stat-label">待接订单</span>
        </div>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success animate-fade-in"><?php echo $success; ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger animate-fade-in"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (!is_logged_in()): ?>
    <div class="features-section">
        <div class="feature-card">
            <div class="feature-icon">📦</div>
            <h3>发布需求</h3>
            <p>填写快递信息，一键发布代取需求</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🤝</div>
            <h3>快速接单</h3>
            <p>代取员实时接单，提供高效服务</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">✅</div>
            <h3>安全送达</h3>
            <p>确认收件，安全便捷</p>
        </div>
    </div>
    <div class="promo-section">
        <div class="promo-card promo-card-primary">
            <div class="promo-content">
                <h3>为什么选择我们？</h3>
                <ul>
                    <li>🚀 平均15分钟内响应接单</li>
                    <li>💰 透明定价，无隐藏费用</li>
                    <li>🔒 安全保障，全程追踪</li>
                    <li>💯 专业代取员，服务有保障</li>
                </ul>
            </div>
            <div class="promo-image">
                <span class="promo-emoji">🌟</span>
            </div>
        </div>
        <div class="promo-cards-grid">
            <div class="promo-card-small">
                <div class="promo-icon">⏱️</div>
                <h4>极速响应</h4>
                <p>代取员在线待命，快速响应您的需求</p>
            </div>
            <div class="promo-card-small">
                <div class="promo-icon">📍</div>
                <h4>实时定位</h4>
                <p>随时查看订单状态和代取员位置</p>
            </div>
            <div class="promo-card-small">
                <div class="promo-icon">💳</div>
                <h4>安全支付</h4>
                <p>完成后确认付款，资金安全有保障</p>
            </div>
            <div class="promo-card-small">
                <div class="promo-icon">⭐</div>
                <h4>评价体系</h4>
                <p>服务评价透明，选择优质代取员</p>
            </div>
        </div>
        <div class="how-it-works">
            <h3 class="section-title">📋 如何使用</h3>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h4>注册登录</h4>
                    <p>注册账号，选择您的角色</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h4>发布订单</h4>
                    <p>填写快递信息和赏金金额</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h4>等待接单</h4>
                    <p>代取员接单后开始配送</p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h4>确认收货</h4>
                    <p>收到包裹后确认完成</p>
                </div>
            </div>
        </div>
    </div>
<?php elseif (is_courier()): ?>
    <div class="section-header">
        <h2 class="section-title">📋 待接订单</h2>
        <span class="badge-count"><?php echo $pending_count; ?> 个订单</span>
    </div>

    <?php if ($pending_count > 0): ?>
        <div class="orders-grid">
            <?php while ($order = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <?php $packages = getOrderPackages($conn, $order['id']); ?>
                <div class="order-card">
                    <!-- 顶部装饰条 -->
                    <div class="card-gradient"></div>
                    
                    <div class="card-inner">
                        <!-- 头部信息 -->
                        <div class="card-header">
                            <div class="header-left">
                                <div class="courier-tags">
                                    <?php foreach ($packages as $pkg): ?>
                                        <span class="courier-tag">
                                            <?php echo htmlspecialchars($pkg['courier_company']); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                                <span class="order-time">📅 <?php echo date('m月d日 H:i', strtotime($order['created_at'])); ?></span>
                            </div>
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo get_status_text($order['status']); ?>
                            </span>
                        </div>

                        <!-- 路线信息 -->
                        <div class="card-route">
                            <div class="route-item pickup">
                                <div class="route-icon">📍</div>
                                <div class="route-content">
                                    <span class="route-label">取件</span>
                                    <span class="route-value"><?php echo htmlspecialchars($order['pickup_location']); ?></span>
                                </div>
                            </div>
                            <div class="route-divider"></div>
                            <div class="route-item delivery">
                                <div class="route-icon">🏠</div>
                                <div class="route-content">
                                    <span class="route-label">送达</span>
                                    <span class="route-value"><?php echo htmlspecialchars($order['delivery_location']); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- 快递包裹详情 -->
                        <div class="card-packages">
                            <div class="packages-header">
                                <span class="packages-icon">📦</span>
                                <span class="packages-title">快递包裹 (<?php echo count($packages); ?>件)</span>
                            </div>
                            <div class="packages-list">
                                <?php foreach ($packages as $index => $pkg): ?>
                                    <div class="package-item">
                                        <span class="package-num">#<?php echo $index + 1; ?></span>
                                        <span class="package-info">
                                            <span class="company-name"><?php echo htmlspecialchars($pkg['courier_company']); ?></span>
                                            <span class="tracking-code"><?php echo htmlspecialchars($pkg['tracking_number']); ?></span>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- 收件人信息 -->
                        <div class="card-info">
                            <div class="info-label-row">
                                <span class="info-label-title">📮 收件人信息</span>
                            </div>
                            <div class="info-row">
                                <div class="info-box">
                                    <span class="info-icon">👤</span>
                                    <span class="info-label">姓名</span>
                                    <span class="info-text"><?php echo htmlspecialchars($order['recipient_name']); ?></span>
                                </div>
                                <?php if ($order['recipient_phone']): ?>
                                    <div class="info-box">
                                        <span class="info-icon">📞</span>
                                        <span class="info-label">电话</span>
                                        <span class="info-text"><?php echo htmlspecialchars($order['recipient_phone']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($order['remark']): ?>
                                <div class="remark-box">
                                    <span class="remark-icon">💬</span>
                                    <span class="remark-label">备注</span>
                                    <span class="remark-text"><?php echo htmlspecialchars($order['remark']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- 底部信息 -->
                        <div class="card-footer">
                            <div class="publisher-info">
                                <span class="publisher-icon">👤</span>
                                <span class="publisher-label">发布人</span>
                                <span class="publisher-text"><?php echo htmlspecialchars($order['publisher_name']); ?></span>
                            </div>
                            <div class="footer-right">
                                <div class="reward-box">
                                    <span class="reward-label">奖励</span>
                                    <span class="reward-amount">¥<?php echo number_format($order['reward'], 2); ?></span>
                                </div>
                                <?php if ($order['user_id'] != get_user_id()): ?>
                                    <form method="POST" action="" class="accept-form">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <button type="submit" name="accept_order" class="btn-accept">🎯 立即接单</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">📭</div>
            <h3>暂无待接订单</h3>
            <p>当前没有新的代取需求，敬请期待～</p>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="features-section">
        <div class="feature-card">
            <div class="feature-icon">📦</div>
            <h3>发布需求</h3>
            <p>填写快递信息，一键发布代取需求</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🤝</div>
            <h3>快速接单</h3>
            <p>代取员实时接单，提供高效服务</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">✅</div>
            <h3>安全送达</h3>
            <p>确认收件，安全便捷</p>
        </div>
    </div>
    <div class="promo-section">
        <div class="promo-card promo-card-primary">
            <div class="promo-content">
                <h3>为什么选择我们？</h3>
                <ul>
                    <li>🚀 平均15分钟内响应接单</li>
                    <li>💰 透明定价，无隐藏费用</li>
                    <li>🔒 安全保障，全程追踪</li>
                    <li>💯 专业代取员，服务有保障</li>
                </ul>
            </div>
            <div class="promo-image">
                <span class="promo-emoji">🌟</span>
            </div>
        </div>
        <div class="promo-cards-grid">
            <div class="promo-card-small">
                <div class="promo-icon">⏱️</div>
                <h4>极速响应</h4>
                <p>代取员在线待命，快速响应您的需求</p>
            </div>
            <div class="promo-card-small">
                <div class="promo-icon">📍</div>
                <h4>实时定位</h4>
                <p>随时查看订单状态和代取员位置</p>
            </div>
            <div class="promo-card-small">
                <div class="promo-icon">💳</div>
                <h4>安全支付</h4>
                <p>完成后确认付款，资金安全有保障</p>
            </div>
            <div class="promo-card-small">
                <div class="promo-icon">⭐</div>
                <h4>评价体系</h4>
                <p>服务评价透明，选择优质代取员</p>
            </div>
        </div>
        <div class="how-it-works">
            <h3 class="section-title">📋 如何使用</h3>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h4>注册登录</h4>
                    <p>注册账号，选择您的角色</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h4>发布订单</h4>
                    <p>填写快递信息和赏金金额</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h4>等待接单</h4>
                    <p>代取员接单后开始配送</p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h4>确认收货</h4>
                    <p>收到包裹后确认完成</p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 50px 40px;
    margin-bottom: 40px;
    color: white;
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 400px;
    height: 400px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
}

.hero-section::after {
    content: '';
    position: absolute;
    bottom: -30%;
    left: -10%;
    width: 300px;
    height: 300px;
    background: rgba(255,255,255,0.05);
    border-radius: 50%;
}

.hero-content {
    position: relative;
    z-index: 1;
}

.hero-title {
    font-size: 42px;
    font-weight: 700;
    margin-bottom: 10px;
    text-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.hero-subtitle {
    font-size: 18px;
    opacity: 0.9;
    margin-bottom: 30px;
}

.hero-actions {
    display: flex;
    gap: 15px;
}

.btn-lg {
    padding: 14px 32px;
    font-size: 16px;
    border-radius: 30px;
}

.btn-outline-light {
    background: transparent;
    border: 2px solid white;
    color: white;
}

.btn-outline-light:hover {
    background: white;
    color: #667eea;
}

.hero-stats {
    position: absolute;
    right: 40px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 1;
}

.hero-stat {
    text-align: center;
    background: rgba(255,255,255,0.2);
    padding: 20px 30px;
    border-radius: 15px;
    backdrop-filter: blur(10px);
}

.stat-number {
    display: block;
    font-size: 48px;
    font-weight: 700;
}

.stat-label {
    font-size: 14px;
    opacity: 0.9;
}

.features-section {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 25px;
    margin-bottom: 40px;
}

.feature-card {
    background: white;
    border-radius: 16px;
    padding: 30px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    transition: all 0.3s;
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.1);
}

.feature-icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.feature-card h3 {
    font-size: 18px;
    margin-bottom: 10px;
    color: #333;
}

.feature-card p {
    font-size: 14px;
    color: #888;
}

.promo-section {
    margin-top: 40px;
}

.promo-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    overflow: hidden;
    margin-bottom: 30px;
}

.promo-card-primary {
    display: flex;
    align-items: center;
    padding: 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.promo-content {
    flex: 1;
}

.promo-content h3 {
    font-size: 24px;
    margin-bottom: 20px;
}

.promo-content ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.promo-content li {
    font-size: 16px;
    padding: 10px 0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.promo-content li:last-child {
    border-bottom: none;
}

.promo-image {
    flex-shrink: 0;
    margin-left: 40px;
}

.promo-emoji {
    font-size: 100px;
}

.promo-cards-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.promo-card-small {
    background: white;
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    text-align: center;
    transition: all 0.3s;
}

.promo-card-small:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.promo-icon {
    font-size: 40px;
    margin-bottom: 15px;
}

.promo-card-small h4 {
    font-size: 16px;
    color: #333;
    margin-bottom: 10px;
}

.promo-card-small p {
    font-size: 13px;
    color: #888;
    line-height: 1.6;
}

.how-it-works {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
}

.steps {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 30px;
    margin-top: 30px;
}

.step {
    text-align: center;
    position: relative;
}

.step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 40px;
    left: calc(50% + 50px);
    width: calc(100% - 100px);
    height: 2px;
    background: linear-gradient(90deg, #667eea, #764ba2);
}

.step-number {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 700;
    margin: 0 auto 15px;
}

.step h4 {
    font-size: 16px;
    color: #333;
    margin-bottom: 10px;
}

.step p {
    font-size: 13px;
    color: #888;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 25px;
}

.section-title {
    font-size: 24px;
    font-weight: 600;
    color: #333;
}

.badge-count {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 14px;
}

.orders-grid {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.order-card {
    background: white;
    border-radius: 24px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.order-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.12);
}

.card-gradient {
    height: 6px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
}

.card-inner {
    padding: 24px;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}

.header-left {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.courier-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.courier-tag {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    color: #667eea;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    border: 1px solid rgba(102, 126, 234, 0.2);
}

.order-time {
    font-size: 13px;
    color: #999;
}

.status-badge {
    padding: 8px 18px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-pending {
    background: linear-gradient(135deg, #fff3cd, #ffeeba);
    color: #856404;
}

.status-accepted {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    color: #155724;
}

.status-completed {
    background: linear-gradient(135deg, #d1ecf1, #bee5eb);
    color: #0c5460;
}

.card-route {
    background: linear-gradient(135deg, #f8f9ff 0%, #f0f2ff 100%);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 20px;
}

.route-item {
    display: flex;
    align-items: center;
    gap: 12px;
}

.route-icon {
    font-size: 20px;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.route-content {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.route-label {
    font-size: 12px;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.route-value {
    font-size: 15px;
    font-weight: 600;
    color: #333;
}

.route-divider {
    height: 40px;
    width: 2px;
    background: linear-gradient(to bottom, #667eea, #56ab2f);
    margin: 10px 0;
    margin-left: 17px;
}

.card-packages {
    background: #fafafa;
    border-radius: 14px;
    padding: 18px;
    margin-bottom: 18px;
}

.packages-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 14px;
}

.packages-icon {
    font-size: 18px;
}

.packages-title {
    font-size: 15px;
    font-weight: 600;
    color: #333;
}

.packages-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.package-item {
    display: flex;
    align-items: center;
    gap: 12px;
    background: white;
    padding: 12px 14px;
    border-radius: 10px;
    border-left: 3px solid #667eea;
}

.package-num {
    font-size: 12px;
    color: #999;
    font-weight: 500;
}

.package-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
    flex: 1;
}

.company-name {
    font-size: 14px;
    font-weight: 600;
    color: #667eea;
}

.tracking-code {
    font-family: 'SF Mono', Monaco, 'Courier New', monospace;
    font-size: 12px;
    color: #666;
}

.card-info {
    margin-bottom: 20px;
}

.info-label-row {
    margin-bottom: 12px;
}

.info-label-title {
    font-size: 14px;
    font-weight: 600;
    color: #333;
    padding: 8px 0;
    border-bottom: 1px dashed #eee;
    display: block;
}

.info-row {
    display: flex;
    gap: 20px;
    margin-bottom: 12px;
}

.info-box {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 14px;
    background: #fafafa;
    border-radius: 10px;
}

.info-icon {
    font-size: 16px;
}

.info-label {
    font-size: 12px;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-text {
    font-size: 14px;
    color: #555;
    font-weight: 500;
}

.remark-box {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 14px 16px;
    background: linear-gradient(135deg, #fff9e6, #fff5d6);
    border-radius: 12px;
    border-left: 4px solid #ffc107;
}

.remark-icon {
    font-size: 16px;
    margin-top: 2px;
}

.remark-label {
    font-size: 12px;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.remark-text {
    font-size: 14px;
    color: #856404;
    line-height: 1.5;
}
}

.remark-icon {
    font-size: 16px;
    margin-top: 2px;
}

.remark-text {
    font-size: 14px;
    color: #856404;
    line-height: 1.5;
}

.card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 20px;
    border-top: 1px solid #f0f0f0;
}

.publisher-info {
    display: flex;
    align-items: center;
    gap: 6px;
}

.publisher-icon {
    font-size: 16px;
}

.publisher-label {
    font-size: 12px;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.publisher-text {
    font-size: 14px;
    color: #666;
}

.footer-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

.reward-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 10px 20px;
    background: linear-gradient(135deg, rgba(240, 147, 251, 0.1), rgba(245, 87, 108, 0.1));
    border-radius: 12px;
}

.reward-label {
    font-size: 11px;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.reward-amount {
    font-size: 22px;
    font-weight: 700;
    background: linear-gradient(135deg, #f093fb, #f5576c);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.accept-form {
    margin: 0;
}

.btn-accept {
    padding: 14px 32px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    border-radius: 25px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-accept:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
}

.btn-accept:active {
    transform: translateY(0);
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
}

.empty-icon {
    font-size: 80px;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 22px;
    color: #333;
    margin-bottom: 10px;
}

.empty-state p {
    color: #888;
    font-size: 15px;
}

.animate-fade-in {
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 768px) {
    .hero-section {
        padding: 30px 25px;
    }

    .hero-title {
        font-size: 28px;
    }

    .hero-stats {
        display: none;
    }

    .features-section {
        grid-template-columns: 1fr;
    }

    .promo-card-primary {
        flex-direction: column;
        text-align: center;
        padding: 30px 25px;
    }

    .promo-image {
        margin-left: 0;
        margin-top: 20px;
    }

    .promo-emoji {
        font-size: 60px;
    }

    .promo-cards-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .steps {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

    .step:not(:last-child)::after {
        display: none;
    }

    .how-it-works {
        padding: 30px 25px;
    }

    .order-info-grid {
        grid-template-columns: 1fr;
    }

    .info-item.full-width {
        grid-column: span 1;
    }

    .order-card-footer {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }

    .reward-tag {
        position: relative;
        top: 0;
        right: 0;
        margin-bottom: 15px;
        display: inline-block;
    }

    .order-card-header {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }
}
</style>

<?php include 'includes/footer.php'; ?>