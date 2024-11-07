<?php
session_start();

// ログイン状態をチェック
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.html"); // 未ログインの場合はログインページにリダイレクト
    exit();
}

// JSONファイルのパス
$filename = 'game_data.json';
$email = $_SESSION['email'];

// JSONファイルからユーザーデータを読み込む
if (file_exists($filename)) {
    $data = json_decode(file_get_contents($filename), true);
    if (isset($data[$email])) {
        $balance = $data[$email]['balance'];
        $total_bet_amount = $data[$email]['total_bet_amount'];
        $rakeback_amount = $data[$email]['rakeback_amount'];
    } else {
        echo "ユーザーデータが見つかりません。";
        exit();
    }
} else {
    echo "データファイルが見つかりません。";
    exit();
}

$result_message = "";
$emoji_result = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bet_amount = isset($_POST['bet_amount']) ? (int)$_POST['bet_amount'] : 0;

    if ($bet_amount > $balance) {
        $result_message = "所持金が不足しています";
    } else {
        // 累計ベット額の加算
        $total_bet_amount += $bet_amount;

        // レーキバック計算
        $rakeback = $bet_amount * 0.01 * 0.05;
        $rakeback_amount += $rakeback;

        // スロットのリール結果
        $symbols = ["🍒", "🍋", "🔔", "⭐", "💎"];
        $result = [$symbols[array_rand($symbols)], $symbols[array_rand($symbols)], $symbols[array_rand($symbols)]];

        // 結果を表示用に格納
        $emoji_result = implode(' | ', $result);

        // 配当倍率
        $multipliers = ["🍒" => 3, "🍋" => 5, "🔔" => 7, "⭐" => 10, "💎" => 77];

        // 揃った場合の処理
        if ($result[0] === $result[1] && $result[1] === $result[2]) {
            $winnings = $bet_amount * $multipliers[$result[0]];
            $balance += $winnings;
            $result_message = "おめでとう！{$result[0]}が揃って{$winnings}円を獲得しました！";
        } else {
            $balance -= $bet_amount;
            $result_message = "残念…{$bet_amount}円を失いました";
        }

        // JSONファイルにデータを保存
        $data[$email]['balance'] = $balance;
        $data[$email]['total_bet_amount'] = $total_bet_amount;
        $data[$email]['rakeback_amount'] = $rakeback_amount;
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));

        // セッションに更新されたデータを保存
        $_SESSION['balance'] = $balance;
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>スロット</title>
    <style>
        /* 全体のスタイル設定 */
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
        .input-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 15px 0;
        }
        .input-group label {
            flex: 1;
            margin-right: 10px;
            font-size: 0.9rem; /* 文字サイズを小さく */
            font-weight: normal; /* 太字を解除 */
        }
        .input-group input {
            flex: 2;
            padding: 10px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
        }
        button {
            background-color: #28a745;
            color: #fff;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            width: 100%;
            margin-bottom: 10px;
        }
        button:hover {
            opacity: 0.9;
        }
        .back-button {
            background-color: #007bff;
            margin-top: 10px;
        }
        .label, .result {
            margin-top: 10px;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="main-frame">
        <h2>スロット</h2>
        <form method="post">
            <div class="input-group">
                <label for="bet_amount">ベット額を入力:</label>
                <input type="number" id="bet_amount" name="bet_amount" required>
            </div>
            <button type="submit">スロットを回す</button>
        </form>

        <!-- 結果表示エリア -->
        <?php if (!empty($emoji_result)): ?>
            <div class="result" style="font-size: 24px;"><?php echo $emoji_result; ?></div>
        <?php endif; ?>

        <?php if (!empty($result_message)): ?>
            <div class="label"><?php echo $result_message; ?></div>
        <?php endif; ?>

        <!-- ユーザー情報の表示 -->
        <div class="label">所持金: <?php echo number_format($balance, 2); ?>円</div>
        <div class="label">累計ベット額: <?php echo number_format($total_bet_amount); ?>円</div>
        <div class="label">レーキバック: <?php echo number_format($rakeback_amount, 2); ?>円</div>

        <!-- TOPへ戻るボタン -->
        <button class="back-button" onclick="location.href='betting_game_page.php'">TOPへ戻る</button>
    </div>
</body>
</html>
