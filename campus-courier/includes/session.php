<?php
session_save_path(dirname(__DIR__) . '/sessions');
session_start();

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function get_user_role() {
    return $_SESSION['role'] ?? 'user';
}

function is_admin() {
    return get_user_role() === 'admin';
}

function is_courier() {
    return get_user_role() === 'courier';
}

function is_user() {
    return get_user_role() === 'user';
}

function require_role($roles) {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
    if (!in_array(get_user_role(), (array)$roles)) {
        header('Location: index.php');
        exit;
    }
}

function login($user_id, $role = 'user', $username = '') {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['role'] = $role;
    $_SESSION['username'] = $username;
}

function logout() {
    session_destroy();
    header('Location: index.php');
    exit;
}

function get_status_text($status) {
    $status_map = [
        'pending' => '待接单',
        'accepted' => '已接单',
        'picked_up' => '已取件',
        'delivered' => '已送达',
        'completed' => '已完成',
        'cancelled' => '已取消'
    ];
    return $status_map[$status] ?? $status;
}
?>