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
    $selected_number = isset($_POST['selected_number']) ? (int)$_POST['selected_number'] : null;

    if ($bet_amount > $balance) {
        $result_message = "所持金が不足しています";
    } else {
        $_SESSION['bet_amount'] = $bet_amount;

        // 所持金の減少、累計ベット額とレーキバックの更新
        $balance -= $bet_amount;
        $total_bet_amount += $bet_amount;
        $rakeback = $bet_amount * 0.01 * 0.05;
        $rakeback_amount += $rakeback;

        // サイコロの出目をランダムに生成
        $dice1 = rand(1, 6);
        $dice2 = rand(1, 6);
        $dice3 = rand(1, 6);

        // ベットタイプごとの勝利判定
        $total = $dice1 + $dice2 + $dice3;
        $win_multiplier = 0;

        if ($bet_type === '大') {
            if ($total >= 11) {
                $win_multiplier = 2;
            }
        } elseif ($bet_type === '小') {
            if ($total <= 10) {
                $win_multiplier = 2;
            }
        } elseif ($bet_type === 'ダブル' && $selected_number) {
            $matching_dice = array_filter([$dice1, $dice2, $dice3], fn($die) => $die == $selected_number);
            if (count($matching_dice) >= 2) {
                $win_multiplier = 12;
            }
        } elseif ($bet_type === 'エニィトリプル' && $selected_number) {
            if ($dice1 == $selected_number && $dice2 == $selected_number && $dice3 == $selected_number) {
                $win_multiplier = 32;
            }
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
    <title>シックボーゲーム</title>
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
            font-size: 0.9rem;
            font-weight: normal;
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
        #dice_result, #result, .label {
            margin-top: 10px;
            font-size: 1.1rem;
        }
    </style>
    <script>
        function toggleNumberInput() {
            const betType = document.getElementById("bet_type").value;
            const numberInputGroup = document.getElementById("number_input_group");
            numberInputGroup.style.display = (betType === "ダブル" || betType === "エニィトリプル") ? "flex" : "none";
        }
    </script>
</head>
<body>
    <div class="main-frame">
        <h2>シックボーゲーム</h2>
        <form method="post">
            <div class="input-group">
                <label for="bet_amount">ベット額:</label>
                <input type="number" id="bet_amount" name="bet_amount" required>
            </div>
            <div class="input-group">
                <label for="bet_type">賭けタイプ:</label>
                <select id="bet_type" name="bet_type" onchange="toggleNumberInput()">
                    <option value="大">大 (合計11以上)</option>
                    <option value="小">小 (合計10以下)</option>
                    <option value="ダブル">ダブル (ぞろ目)</option>
                    <option value="エニィトリプル">エニィトリプル (3つのぞろ目)</option>
                </select>
            </div>
            <div class="input-group" id="number_input_group" style="display: none;">
                <label for="selected_number">出目を選択:</label>
                <select id="selected_number" name="selected_number">
                    <?php for ($i = 1; $i <= 6; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <button type="submit" name="bet_submit">ベット開始</button>
        </form>

        <!-- 結果とメッセージの表示 -->
        <div id="dice_result"><?php echo isset($dice1) ? "サイコロ: $dice1, $dice2, $dice3" : ""; ?></div>
        <div id="result"><?php echo $result_message ?? ''; ?></div>

        <!-- ユーザー情報の表示 -->
        <div class="label">所持金: <?php echo number_format($balance, 2); ?>円</div>
        <div class="label">累計ベット額: <?php echo number_format($total_bet_amount); ?>円</div>
        <div class="label">レーキバック: <?php echo number_format($rakeback_amount, 2); ?>円</div>

        <!-- TOPへ戻るボタン -->
        <button class="back-button" onclick="location.href='betting_game_page.php'">TOPへ戻る</button>
    </div>
</body>
</html>
