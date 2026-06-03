<?php
require_once 'includes/config.php';
require_once 'includes/session.php';

$error = '';
$success = '';

// 只有普通用户可以发布订单，代取员和管理员不能发布
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

if (get_user_role() !== 'user') {
    header('Location: index.php');
    exit;
}

$user_id = get_user_id();

// 处理地址模板操作
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_template'])) {
    $template_name = trim($_POST['template_name']);
    $pickup_location = trim($_POST['pickup_location']);
    $delivery_location = trim($_POST['delivery_location']);
    $recipient_name = trim($_POST['recipient_name']);
    $recipient_phone = trim($_POST['recipient_phone']);
    $set_default = isset($_POST['set_default']) ? 1 : 0;

    if (empty($template_name) || empty($pickup_location) || empty($delivery_location) || empty($recipient_name)) {
        $error = '请填写完整的地址信息';
    } else {
        if ($set_default) {
            $conn->prepare("UPDATE address_templates SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
        }
        $stmt = $conn->prepare("INSERT INTO address_templates (user_id, name, pickup_location, delivery_location, recipient_name, recipient_phone, is_default) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $template_name, $pickup_location, $delivery_location, $recipient_name, $recipient_phone, $set_default]);
        $success = '地址模板保存成功！';
    }
}

// 处理删除地址模板
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_template'])) {
    $template_id = intval($_POST['template_id']);
    $stmt = $conn->prepare("DELETE FROM address_templates WHERE id = ? AND user_id = ?");
    $stmt->execute([$template_id, $user_id]);
    $success = '地址模板已删除';
}

// 处理设为默认
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['set_default_template'])) {
    $template_id = intval($_POST['template_id']);
    $conn->prepare("UPDATE address_templates SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
    $conn->prepare("UPDATE address_templates SET is_default = 1 WHERE id = ? AND user_id = ?")->execute([$template_id, $user_id]);
    $success = '已设为默认地址';
}

// 处理订单发布
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['publish_order'])) {
    // 调试信息
    error_log('订单发布请求已收到');
    error_log('POST数据: ' . print_r($_POST, true));
    
    $packages = $_POST['packages'] ?? [];
    $pickup_location = $_POST['pickup_location'] ?? '';
    $delivery_location = $_POST['delivery_location'] ?? '';
    $recipient_name = $_POST['recipient_name'] ?? '';
    $recipient_phone = $_POST['recipient_phone'] ?? '';
    $reward = $_POST['reward'] ?? '';
    $remark = $_POST['remark'] ?? '';

    error_log('包裹数据: ' . print_r($packages, true));

    // 验证快递包裹
    $valid_packages = [];
    foreach ($packages as $package) {
        if (!empty($package['courier_company']) && !empty($package['tracking_number'])) {
            $valid_packages[] = $package;
        }
    }

    error_log('有效包裹数量: ' . count($valid_packages));

    if (empty($valid_packages)) {
        $error = '请至少添加一个快递包裹';
        error_log('错误: 没有有效包裹');
    } elseif (empty($pickup_location) || empty($delivery_location) || empty($recipient_name) || empty($reward)) {
        $error = '请填写所有必填字段';
        error_log('错误: 必填字段为空');
    } elseif (!is_numeric($reward) || $reward <= 0) {
        $error = '请输入有效的奖励金额';
        error_log('错误: 奖励金额无效');
    } else {
        try {
            $conn->beginTransaction();
            
            error_log('开始创建订单，用户ID: ' . $user_id);
            
            // 创建订单
            $stmt = $conn->prepare("INSERT INTO orders (user_id, pickup_location, delivery_location, recipient_name, recipient_phone, reward, remark, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
            $stmt->execute([$user_id, $pickup_location, $delivery_location, $recipient_name, $recipient_phone, $reward, $remark]);
            $order_id = $conn->lastInsertId();
            
            error_log('订单创建成功，订单ID: ' . $order_id);
            
            // 添加快递包裹
            $stmt = $conn->prepare("INSERT INTO order_packages (order_id, courier_company, tracking_number) VALUES (?, ?, ?)");
            foreach ($valid_packages as $package) {
                $stmt->execute([$order_id, $package['courier_company'], $package['tracking_number']]);
            }
            
            error_log('包裹添加成功，数量: ' . count($valid_packages));
            
            $conn->commit();
            $success = '订单发布成功！';
            error_log('订单发布成功');
        } catch (Exception $e) {
            $conn->rollBack();
            $error = '发布失败: ' . $e->getMessage();
            error_log('订单发布失败: ' . $e->getMessage());
        }
    }
}

// 获取地址模板列表
$stmt = $conn->prepare("SELECT * FROM address_templates WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->execute([$user_id]);
$address_templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取上次订单数据（用于默认填充）
$stmt = $conn->prepare("SELECT pickup_location, delivery_location, recipient_name, recipient_phone, reward FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$last_order = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<?php include 'includes/header.php'; ?>

<style>
.publish-page {
    max-width: 900px;
    margin: 0 auto;
}

.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 30px 40px;
    color: white;
    margin-bottom: 30px;
}

.page-title {
    font-size: 28px;
    font-weight: 600;
    margin: 0;
}

.page-subtitle {
    opacity: 0.9;
    margin-top: 5px;
}

.template-section {
    background: white;
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    margin-bottom: 25px;
}

.section-title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.template-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 15px;
}

.template-card {
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.3s;
    position: relative;
}

.template-card:hover {
    border-color: #667eea;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
}

.template-card.active {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
}

.template-name {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin-bottom: 10px;
}

.template-info {
    font-size: 13px;
    color: #666;
    line-height: 1.8;
}

.template-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 3px 10px;
    border-radius: 10px;
    font-size: 11px;
}

.template-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #f0f0f0;
}

.template-actions form {
    margin: 0;
}

.btn-template {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-use {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.btn-use:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
}

.btn-delete {
    background: #fff;
    color: #dc3545;
    border: 1px solid #dc3545;
}

.btn-delete:hover {
    background: #dc3545;
    color: white;
}

.btn-default {
    background: #fff;
    color: #667eea;
    border: 1px solid #667eea;
}

.btn-default:hover {
    background: #667eea;
    color: white;
}

.no-template {
    text-align: center;
    padding: 30px;
    color: #999;
}

.form-section {
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    font-size: 14px;
    color: #666;
    margin-bottom: 8px;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s;
    height: 46px;
    box-sizing: border-box;
    vertical-align: middle;
}

select.form-control {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='4' height='5' viewBox='0 0 4 5'%3e%3cpath fill='%23343a40' d='M2 0L0 2h4zm0 5L0 3h4z'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 15px center;
    background-size: 8px 10px;
    padding-right: 35px;
    cursor: pointer;
    line-height: 22px;
}

input.form-control, textarea.form-control {
    line-height: 22px;
}

textarea.form-control {
    height: auto;
    min-height: 80px;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.btn-submit {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.save-template-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
    margin-top: 25px;
}

.save-template-title {
    font-size: 15px;
    font-weight: 600;
    color: #333;
    margin-bottom: 15px;
}

.save-template-row {
    display: flex;
    gap: 15px;
    align-items: center;
}

.save-template-row .form-control {
    flex: 1;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #666;
    cursor: pointer;
}

.alert {
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
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

.packages-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
}

.packages-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.packages-title {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin: 0;
}

.btn-add-package {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-add-package:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.package-item {
    background: white;
    border-radius: 10px;
    margin-bottom: 15px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.package-header {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    padding: 12px 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.package-number {
    font-size: 14px;
    font-weight: 600;
    color: #667eea;
}

.btn-remove-package {
    background: #dc3545;
    color: white;
    border: none;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.btn-remove-package:hover {
    background: #c82333;
    transform: scale(1.1);
}

.package-content {
    padding: 15px;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .template-grid {
        grid-template-columns: 1fr;
    }
    
    .save-template-row {
        flex-direction: column;
    }
}
</style>

<div class="publish-page">
    <div class="page-header">
        <h1 class="page-title">📦 发布订单</h1>
        <p class="page-subtitle">填写快递信息，快速发布代取需求</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if (count($address_templates) > 0): ?>
    <div class="template-section">
        <h3 class="section-title">📍 我的地址模板</h3>
        <div class="template-grid">
            <?php foreach ($address_templates as $template): ?>
                <div class="template-card" onclick="fillTemplate(<?php echo htmlspecialchars(json_encode($template)); ?>)">
                    <?php if ($template['is_default']): ?>
                        <span class="template-badge">默认</span>
                    <?php endif; ?>
                    <div class="template-name"><?php echo htmlspecialchars($template['name']); ?></div>
                    <div class="template-info">
                        <div>📍 取件：<?php echo htmlspecialchars($template['pickup_location']); ?></div>
                        <div>🎯 送达：<?php echo htmlspecialchars($template['delivery_location']); ?></div>
                        <div>👤 收件人：<?php echo htmlspecialchars($template['recipient_name']); ?></div>
                        <div>📞 电话：<?php echo htmlspecialchars($template['recipient_phone']); ?></div>
                    </div>
                    <div class="template-actions">
                        <button type="button" class="btn-template btn-use" onclick="event.stopPropagation(); fillTemplate(<?php echo htmlspecialchars(json_encode($template)); ?>)">一键填写</button>
                        <?php if (!$template['is_default']): ?>
                            <form method="POST" style="display:inline;" onclick="event.stopPropagation();">
                                <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                <button type="submit" name="set_default_template" class="btn-template btn-default">设为默认</button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" style="display:inline;" onclick="event.stopPropagation();">
                            <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                            <button type="submit" name="delete_template" class="btn-template btn-delete" onclick="return confirm('确定删除该地址模板？')">删除</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="form-section">
        <h3 class="section-title">📝 订单信息</h3>
        <form method="POST" action="">
            <!-- 快递包裹区域 -->
            <div class="packages-section">
                <div class="packages-header">
                    <h4 class="packages-title">📦 快递包裹 <span class="text-danger">*</span></h4>
                    <button type="button" class="btn-add-package" onclick="addPackage()">➕ 添加包裹</button>
                </div>
                <div id="packages-container">
                    <div class="package-item" data-index="0">
                        <div class="package-header">
                            <span class="package-number">包裹 1</span>
                            <button type="button" class="btn-remove-package" onclick="removePackage(this)" style="display:none;">✕</button>
                        </div>
                        <div class="package-content">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">快递公司 <span class="text-danger">*</span></label>
                                    <select class="form-control" name="packages[0][courier_company]" required>
                                        <option value="">请选择快递公司</option>
                                        <option value="顺丰">顺丰</option>
                                        <option value="圆通">圆通</option>
                                        <option value="中通">中通</option>
                                        <option value="申通">申通</option>
                                        <option value="韵达">韵达</option>
                                        <option value="EMS">EMS</option>
                                        <option value="京东">京东</option>
                                        <option value="其他">其他</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">快递单号 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="packages[0][tracking_number]" placeholder="请输入快递单号" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">取件地点 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="pickup_location" id="pickup_location" 
                           value="<?php echo htmlspecialchars($last_order['pickup_location'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">送达地点 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="delivery_location" id="delivery_location"
                           value="<?php echo htmlspecialchars($last_order['delivery_location'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">收件人姓名 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="recipient_name" id="recipient_name"
                           value="<?php echo htmlspecialchars($last_order['recipient_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">联系电话</label>
                    <input type="tel" class="form-control" name="recipient_phone" id="recipient_phone"
                           value="<?php echo htmlspecialchars($last_order['recipient_phone'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">奖励金额 (元) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" class="form-control" name="reward"
                           value="<?php echo htmlspecialchars($last_order['reward'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">备注</label>
                    <input type="text" class="form-control" name="remark" placeholder="选填，如：请尽快送达">
                </div>
            </div>

            <button type="submit" name="publish_order" class="btn-submit">🚀 发布订单</button>

            <div class="save-template-section">
                <div class="save-template-title">💾 保存为地址模板</div>
                <div class="save-template-row">
                    <input type="text" class="form-control" name="template_name" placeholder="模板名称，如：宿舍地址、教学楼地址">
                    <label class="checkbox-label">
                        <input type="checkbox" name="set_default">
                        设为默认
                    </label>
                    <button type="submit" name="save_template" class="btn-template btn-use">保存模板</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
let packageIndex = 1;

function addPackage() {
    const container = document.getElementById('packages-container');
    const html = `
        <div class="package-item" data-index="${packageIndex}">
            <div class="package-header">
                <span class="package-number">包裹 ${packageIndex + 1}</span>
                <button type="button" class="btn-remove-package" onclick="removePackage(this)">✕</button>
            </div>
            <div class="package-content">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">快递公司 <span class="text-danger">*</span></label>
                        <select class="form-control" name="packages[${packageIndex}][courier_company]" required>
                            <option value="">请选择快递公司</option>
                            <option value="顺丰">顺丰</option>
                            <option value="圆通">圆通</option>
                            <option value="中通">中通</option>
                            <option value="申通">申通</option>
                            <option value="韵达">韵达</option>
                            <option value="EMS">EMS</option>
                            <option value="京东">京东</option>
                            <option value="其他">其他</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">快递单号 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="packages[${packageIndex}][tracking_number]" placeholder="请输入快递单号" required>
                    </div>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    packageIndex++;
    updateRemoveButtons();
}

function removePackage(btn) {
    const packageItem = btn.closest('.package-item');
    packageItem.remove();
    updatePackageNumbers();
    updateRemoveButtons();
}

function updatePackageNumbers() {
    const packages = document.querySelectorAll('.package-item');
    packages.forEach((pkg, index) => {
        pkg.querySelector('.package-number').textContent = `包裹 ${index + 1}`;
        pkg.setAttribute('data-index', index);
        
        // 更新name属性
        const select = pkg.querySelector('select');
        const input = pkg.querySelector('input[type="text"]');
        select.name = `packages[${index}][courier_company]`;
        input.name = `packages[${index}][tracking_number]`;
    });
    packageIndex = packages.length;
}

function updateRemoveButtons() {
    const packages = document.querySelectorAll('.package-item');
    packages.forEach((pkg, index) => {
        const removeBtn = pkg.querySelector('.btn-remove-package');
        removeBtn.style.display = packages.length > 1 ? 'flex' : 'none';
    });
}

function fillTemplate(template) {
    document.getElementById('pickup_location').value = template.pickup_location;
    document.getElementById('delivery_location').value = template.delivery_location;
    document.getElementById('recipient_name').value = template.recipient_name;
    document.getElementById('recipient_phone').value = template.recipient_phone;
    
    // 高亮选中的模板
    document.querySelectorAll('.template-card').forEach(card => card.classList.remove('active'));
    event.currentTarget.classList.add('active');
    
    // 滚动到表单
    document.querySelector('.form-section').scrollIntoView({ behavior: 'smooth' });
}
</script>

<?php include 'includes/footer.php'; ?>