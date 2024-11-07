<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// メールアドレスと新しいバランスを取得
$email = $_POST['email'] ?? null;
$new_balance = floatval($_POST['balance'] ?? 0);

$filename = 'game_data.json';
if (file_exists($filename)) {
    $data = json_decode(file_get_contents($filename), true);
    if (isset($data[$email])) {
        // 所持金を更新
        $data[$email]['balance'] = $new_balance;

        // JSONファイルにデータを保存
        if (file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT)) !== false) {
            echo json_encode(["message" => "Balance updated successfully."]);
        } else {
            echo json_encode(["error" => "Failed to update the JSON file."]);
        }
    } else {
        echo json_encode(["error" => "User not found."]);
    }
} else {
    echo json_encode(["error" => "Data file not found."]);
}
?>
