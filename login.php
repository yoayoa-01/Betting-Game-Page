<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // フォームからのデータを取得
    $email = $_POST['email'];
    $password = $_POST['password'];

    // JSONファイルのパス
    $filename = 'game_data.json';

    // JSONファイルの読み込み
    if (file_exists($filename)) {
        $data = json_decode(file_get_contents($filename), true);
    } else {
        echo "データファイルが見つかりません。";
        exit();
    }

    // ユーザーデータの確認
    if (isset($data[$email])) {
        $user = $data[$email];

        // パスワードの検証
        if (password_verify($password, $user['password'])) {
            // セッションにユーザーデータを保存
            $_SESSION['loggedin'] = true;
            $_SESSION['email'] = $email; // ここでセッションにメールアドレスを保存
            $_SESSION['username'] = $user['username'];

            // メインページにリダイレクト
            header("Location: betting_game_page.php");
            exit();
        } else {
            echo "パスワードが間違っています。";
        }
    } else {
        echo "ユーザーが見つかりません。";
    }
}
?>
