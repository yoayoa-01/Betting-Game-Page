<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    // パスワードを暗号化（実際のアプリケーションで推奨）
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // JSONファイルの読み込み
    $filename = 'game_data.json';
    if (file_exists($filename)) {
        $data = json_decode(file_get_contents($filename), true);
    } else {
        $data = []; // ファイルが存在しない場合、新しい配列を作成
    }

    // 登録されているメールアドレスの重複チェック
    if (isset($data[$email])) {
        echo "すでに登録されてるメールアドレスです。";
    } else {
        // 初期データの設定
        $user_data = [
            'username' => $username,
            'balance' => 0, // 所持金の初期値
            'total_bet_amount' => 0, // 累計ベット額の初期値
            'rakeback_amount' => 0, // レーキバック金額の初期値
            'password' => $hashed_password, // 暗号化されたパスワード
            'rank' => '未ランク' // 初期ランクの設定
        ];

        // 新規ユーザーを追加
        $data[$email] = $user_data;

        // JSONファイルに保存
        if (file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT)) !== false) {
            echo "登録が成功しました！ようこそ、" . htmlspecialchars($username) . "さん。";

            // 5秒後にログインページにリダイレクト
            echo "<meta http-equiv='refresh' content='5;url=login.html'>";

            // 手動で戻るリンク
            echo "<p>自動的にリダイレクトされない場合は、<a href='login.html'>こちらをクリック</a>してログインページに移動してください。</p>";
        } else {
            echo "ファイルへの書き込みに失敗しました。もう一度試してください。";
        }
    }
}
?>
