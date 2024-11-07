<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ログイン状態のチェック
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.html");
    exit();
}

$current_username = $_SESSION['username'] ?? ''; // ログイン中のユーザー名を取得

// ランク計算関数
function calculate_rank($total_bet_amount) {
    $ranks = [
        ["ダイヤモンド4", 50000000000],
        ["ダイヤモンド3", 25000000000],
        ["ダイヤモンド2", 10000000000],
        ["ダイヤモンド", 2500000000],
        ["プラチナ6", 1000000000],
        ["プラチナ5", 500000000],
        ["プラチナ4", 250000000],
        ["プラチナ3", 100000000],
        ["プラチナ2", 50000000],
        ["プラチナ", 25000000],
        ["ゴールド", 10000000],
        ["シルバー", 5000000],
        ["ブロンズ", 1000000]
    ];

    foreach ($ranks as $rank) {
        if ($total_bet_amount >= $rank[1]) {
            return $rank[0];
        }
    }
    return "未ランク";
}

// JSONファイルの読み込み
$filename = 'game_data.json';
if (!file_exists($filename)) {
    die("データファイルが見つかりません。");
}

$data = json_decode(file_get_contents($filename), true);
if (!$data) {
    die("データの読み込みに失敗しました。");
}

// ランキングデータの作成
$ranking_data = [];
foreach ($data as $user_data) {
    $rank = calculate_rank($user_data['total_bet_amount']);
    $ranking_data[] = [
        'username' => $user_data['username'],
        'rank' => $rank,
        'balance' => $user_data['balance']
    ];
}

// ランクと所持金で昇順ソート
usort($ranking_data, function($a, $b) {
    return [$a['rank'], $b['balance']] <=> [$b['rank'], $a['balance']];
});
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>グローバル所持金ランキング</title>
    <style>
        /* 全体のスタイル */
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(to right, #2b5876, #4e4376);
            color: #fff;
        }
        .main-frame {
            text-align: center;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 10px;
            width: 350px;
            box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.2);
        }
        h2 {
            margin-bottom: 20px;
        }
        .ranking-item {
            margin: 10px 0;
            font-size: 1.1rem;
        }
        .ranking-item.bold {
            font-weight: bold;
        }
        .back-button {
            background-color: #007bff;
            margin-top: 20px;
            width: 100%;
            font-size: 1rem;
            padding: 10px;
            border: none;
            border-radius: 5px;
            color: #fff;
            cursor: pointer;
        }
        .back-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="main-frame">
        <h2>グローバル所持金ランキング (TOP 10)</h2>

        <?php
        // TOP 10の表示
        for ($i = 0; $i < min(10, count($ranking_data)); $i++) {
            $rank = $i + 1;
            $is_bold = $ranking_data[$i]['username'] === $current_username ? "bold" : "";
            echo "<div class='ranking-item {$is_bold}'>{$rank}位: {$ranking_data[$i]['username']} - {$ranking_data[$i]['rank']} - " . number_format($ranking_data[$i]['balance'], 2) . "円</div>";
        }

        // 11位以降の表示
        if (count($ranking_data) > 10) {
            echo "<h3>11位以降</h3>";
            for ($i = 10; $i < count($ranking_data); $i++) {
                $rank = $i + 1;
                $is_bold = $ranking_data[$i]['username'] === $current_username ? "bold" : "";
                echo "<div class='ranking-item {$is_bold}'>{$rank}位: {$ranking_data[$i]['username']} - {$ranking_data[$i]['rank']} - " . number_format($ranking_data[$i]['balance'], 2) . "円</div>";
            }
        }
        ?>

        <!-- TOPへ戻るボタン -->
        <button class="back-button" onclick="location.href='betting_game_page.php'">TOPへ戻る</button>
    </div>
</body>
</html>
