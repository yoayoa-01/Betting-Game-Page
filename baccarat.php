<?php
session_start();

// ログイン状態をチェック
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.html");
    exit();
}

// JSONファイルのパス
$filename = 'game_data.json';
$email = $_SESSION['email'];

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

// ベット額と賭けタイプが入力された場合
if (isset($_POST['bet_submit'])) {
    $bet_amount = (int)$_POST['bet_amount'];
    $bet_type = $_POST['bet_type'];

    if ($bet_amount > $balance) {
        $result_message = "所持金が不足しています";
    } else {
        $_SESSION['bet_amount'] = $bet_amount;

        // 所持金の減少、累計ベット額とレーキバックの更新
        $balance -= $bet_amount;
        $total_bet_amount += $bet_amount;
        $rakeback = $bet_amount * 0.01 * 0.05;
        $rakeback_amount += $rakeback;

        // カードデッキを作成
        $suits = ['H', 'D', 'C', 'S'];
        $values = [2, 3, 4, 5, 6, 7, 8, 9, '10', 'J', 'Q', 'K', 'A'];
        $deck = [];
        foreach ($suits as $suit) {
            foreach ($values as $value) {
                $deck[] = $suit . $value;
            }
        }
        shuffle($deck);

        // プレイヤーとバンカーのカードをランダムに2枚ずつ引く
        $player_cards = [array_pop($deck), array_pop($deck)];
        $banker_cards = [array_pop($deck), array_pop($deck)];

        // バカラの合計値を計算する関数
        function calculate_baccarat_total($cards) {
            $total = 0;
            foreach ($cards as $card) {
                $value = substr($card, 1); // カード名から数値部分を取得
                if (in_array($value, ['J', 'Q', 'K'])) {
                    $total += 0;
                } elseif ($value === 'A') {
                    $total += 1;
                } else {
                    $total += (int)$value;
                }
            }
            return $total % 10; // バカラの合計は10で割った余り
        }

        // プレイヤーとバンカーの合計値を計算
        $player_total = calculate_baccarat_total($player_cards);
        $banker_total = calculate_baccarat_total($banker_cards);

        // 勝利判定
        $win_multiplier = 0;
        if ($bet_type === 'プレイヤー' && $player_total > $banker_total) {
            $win_multiplier = 2;
        } elseif ($bet_type === 'バンカー' && $banker_total > $player_total) {
            $win_multiplier = 1.95;
        } elseif ($bet_type === 'タイ' && $player_total === $banker_total) {
            $win_multiplier = 8;
        }

        // 勝利金の計算
        $winnings = $bet_amount * $win_multiplier;
        $balance += $winnings;

        // JSONデータの更新
        $data[$email]['balance'] = $balance;
        $data[$email]['total_bet_amount'] = $total_bet_amount;
        $data[$email]['rakeback_amount'] = $rakeback_amount;
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));

        // 結果メッセージの表示
        if ($win_multiplier > 0) {
            $result_message = "{$win_multiplier}倍の当たり！ 賞金: " . number_format($winnings, 2) . "円";
        } else {
            $result_message = "残念…はずれです";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>バカラゲーム</title>
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
        .input-group input, .input-group select {
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
        }
        button:hover {
            background-color: #218838;
        }
        .back-button {
            background-color: #007bff;
            margin-top: 20px;
            width: 100%;
            font-size: 1rem;
            padding: 10px;
        }
        .back-button:hover {
            background-color: #0056b3;
        }
        .cards {
            display: flex;
            justify-content: center;
            margin-top: 10px;
        }
        .card {
            margin: 0 5px;
        }
        #result, .label {
            margin-top: 10px;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="main-frame">
        <h2>バカラゲーム</h2>
        <form method="post">
            <div class="input-group">
                <label for="bet_amount">ベット額:</label>
                <input type="number" id="bet_amount" name="bet_amount" required>
            </div>
            <div class="input-group">
                <label for="bet_type">賭けタイプ:</label>
                <select id="bet_type" name="bet_type">
                    <option value="プレイヤー">プレイヤー (2倍)</option>
                    <option value="バンカー">バンカー (1.95倍)</option>
                    <option value="タイ">タイ (8倍)</option>
                </select>
            </div>
            <button type="submit" name="bet_submit">ベット開始</button>
        </form>

        <!-- 結果とカード表示 -->
        <div id="result"><?php echo $result_message ?? ''; ?></div>
        <?php if (isset($player_cards, $banker_cards)): ?>
            <div class="cards">
                <div>
                    <h3>プレイヤー (合計: <?php echo $player_total; ?>)</h3>
                    <?php foreach ($player_cards as $card): ?>
                        <img src="cards/<?php echo $card; ?>.png" alt="<?php echo $card; ?>" class="card" width="60" height="90">
                    <?php endforeach; ?>
                </div>
                <div>
                    <h3>バンカー (合計: <?php echo $banker_total; ?>)</h3>
                    <?php foreach ($banker_cards as $card): ?>
                        <img src="cards/<?php echo $card; ?>.png" alt="<?php echo $card; ?>" class="card" width="60" height="90">
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- ユーザー情報の表示 -->
        <div class="label">所持金: <?php echo number_format($balance, 2); ?>円</div>
        <div class="label">累計ベット額: <?php echo number_format($total_bet_amount, 2); ?>円</div>
        <div class="label">レーキバック: <?php echo number_format($rakeback_amount, 2); ?>円</div>

        <!-- TOPへ戻るボタン -->
        <button class="back-button" onclick="location.href='betting_game_page.php'">TOPへ戻る</button>
    </div>
</body>
</html>
