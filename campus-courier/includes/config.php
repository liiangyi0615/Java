<?php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '123456');
define('DB_NAME', 'campus_courier');

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 自动检查并创建必要的表
    ensureTablesExist($conn);
    
} catch(PDOException $e) {
    die("连接失败: " . $e->getMessage());
}

function ensureTablesExist($conn) {
    // 修改 orders 表，让 courier_company 和 tracking_number 允许为空
    try {
        $conn->exec("ALTER TABLE orders MODIFY COLUMN courier_company VARCHAR(50) NULL");
        $conn->exec("ALTER TABLE orders MODIFY COLUMN tracking_number VARCHAR(100) NULL");
    } catch(Exception $e) {
        // 如果字段已经允许为空，会报错，忽略即可
    }
    
    // 检查 users 表是否有 address 字段
    try {
        $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS address VARCHAR(255) COMMENT '常用地址'");
    } catch(Exception $e) {
        // 如果字段已存在，会报错，忽略即可
    }
    
    // 检查 order_packages 表
    $stmt = $conn->query("SHOW TABLES LIKE 'order_packages'");
    if ($stmt->rowCount() == 0) {
        $conn->exec("CREATE TABLE IF NOT EXISTS order_packages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            courier_company VARCHAR(50) NOT NULL COMMENT '快递公司',
            tracking_number VARCHAR(100) NOT NULL COMMENT '快递单号',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // 迁移现有数据
        $stmt = $conn->query("SELECT id, courier_company, tracking_number FROM orders WHERE courier_company IS NOT NULL AND tracking_number IS NOT NULL");
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($orders as $order) {
            $check = $conn->prepare("SELECT id FROM order_packages WHERE order_id = ?");
            $check->execute([$order['id']]);
            
            if ($check->rowCount() == 0) {
                $insert = $conn->prepare("INSERT INTO order_packages (order_id, courier_company, tracking_number) VALUES (?, ?, ?)");
                $insert->execute([$order['id'], $order['courier_company'], $order['tracking_number']]);
            }
        }
    }
    
    // 检查 address_templates 表
    $stmt = $conn->query("SHOW TABLES LIKE 'address_templates'");
    if ($stmt->rowCount() == 0) {
        $conn->exec("CREATE TABLE IF NOT EXISTS address_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            name VARCHAR(50) NOT NULL COMMENT '模板名称',
            pickup_location VARCHAR(255) NOT NULL COMMENT '取件地址',
            delivery_location VARCHAR(255) NOT NULL COMMENT '送达地址',
            recipient_name VARCHAR(50) NOT NULL COMMENT '收件人姓名',
            recipient_phone VARCHAR(20) COMMENT '联系电话',
            is_default TINYINT(1) DEFAULT 0 COMMENT '是否默认',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}
?>