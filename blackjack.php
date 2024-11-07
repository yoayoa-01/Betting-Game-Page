<?php
session_start();

// ログイン状態をチェック
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.html"); // 未ログインの場合はログインページにリダイレクト
    exit();
}

// JSONファイルのパス
$filename = 'game_data.json';
$email = $_SESSION['email']; // セッションからメールアドレスを取得

// JSONファイルからユーザーデータを読み込む
if (file_exists($filename)) {
    $data = json_decode(file_get_contents($filename), true);
    if (isset($data[$email])) {
        // ユーザーの所持金、累計ベット額、レーキバック金額を取得
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

// ゲームの初期化
if (!isset($_SESSION['deck']) || isset($_POST['reset'])) {
    $_SESSION['deck'] = initialize_deck();
    $_SESSION['player_cards'] = [];
    $_SESSION['dealer_cards'] = [];
    $_SESSION['bet_amount'] = 0; // 初期ベット額を0に設定
    unset($_SESSION['game_over']);
    unset($_SESSION['bet_placed']); // ベットが行われたかどうかのフラグをリセット
}

// デッキを初期化する関数
function initialize_deck() {
    $suits = ['S', 'H', 'D', 'C'];
    $values = [2, 3, 4, 5, 6, 7, 8, 9, 10, 'J', 'Q', 'K', 'A'];
    $deck = [];
    foreach ($suits as $suit) {
        foreach ($values as $value) {
            $deck[] = $suit . $value;
        }
    }
    shuffle($deck);
    return $deck;
}

// 合計を計算する関数
function calculate_total($cards) {
    $total = 0;
    $aces = 0;

    foreach ($cards as $card) {
        $value = substr($card, 1); // カード名の2文字目以降を取得
        if (in_array($value, ['J', 'Q', 'K'])) {
            $total += 10;
        } elseif ($value === 'A') {
            $total += 11;
            $aces++;
        } else {
            $total += (int)$value;
        }
    }

    while ($total > 21 && $aces > 0) {
        $total -= 10;
        $aces--;
    }

    return $total;
}

// ベット額が入力された場合
if (isset($_POST['bet_amount_submit'])) {
    $bet_amount = (int)$_POST['bet_amount'];
    if ($bet_amount > $balance) {
        $result_message = "所持金が不足しています";
    } else {
        $_SESSION['bet_amount'] = $bet_amount;
        $_SESSION['bet_placed'] = true; // ベットが行われたことを示すフラグ

        // プレイヤーとディーラーにカードを配る
        $_SESSION['player_cards'] = [array_pop($_SESSION['deck']), array_pop($_SESSION['deck'])];
        $_SESSION['dealer_cards'] = [array_pop($_SESSION['deck']), array_pop($_SESSION['deck'])];

        // ベット額からレーキバックを計算
        $rakeback = $bet_amount * 0.01 * 0.05;
        $rakeback_amount += $rakeback;

        // 累計ベット額の更新
        $total_bet_amount += $bet_amount;

        // JSONデータの更新
        $data[$email]['rakeback_amount'] = $rakeback_amount;
        $data[$email]['total_bet_amount'] = $total_bet_amount;
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
    }
}

// ヒットボタンが押された場合
if (isset($_POST['hit']) && !isset($_SESSION['game_over'])) {
    if (isset($_SESSION['bet_amount']) && $_SESSION['bet_amount'] > 0) {
        $_SESSION['player_cards'][] = array_pop($_SESSION['deck']);
        $player_total = calculate_total($_SESSION['player_cards']);
        if ($player_total > 21) {
            $result_message = "バースト！あなたの負けです。";
            $balance -= $_SESSION['bet_amount'];
            // 所持金の更新
            $data[$email]['balance'] = $balance;
            file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
            $_SESSION['game_over'] = true;
        }
    } else {
        $result_message = "まずベット額を設定してください。";
    }
}

// スタンドボタンが押された場合
if (isset($_POST['stand']) && !isset($_SESSION['game_over'])) {
    // ディーラーの行動
    while (calculate_total($_SESSION['dealer_cards']) < 17) {
        $_SESSION['dealer_cards'][] = array_pop($_SESSION['deck']);
    }
    $dealer_total = calculate_total($_SESSION['dealer_cards']);
    $player_total = calculate_total($_SESSION['player_cards']);

    if ($dealer_total > 21) {
        $result_message = "ディーラーがバーストしました！あなたの勝ちです。";
        $balance += $_SESSION['bet_amount'];
    } elseif ($player_total > $dealer_total) {
        $result_message = "勝利！おめでとうございます！";
        $balance += $_SESSION['bet_amount'];
    } elseif ($player_total < $dealer_total) {
        $result_message = "残念…あなたの負けです。";
        $balance -= $_SESSION['bet_amount'];
    } else {
        $result_message = "引き分けです！";
    }

    // 所持金の更新
    $data[$email]['balance'] = $balance;
    file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));

    $_SESSION['game_over'] = true;
}

// 各アクション後に合計値を再計算
$player_total = calculate_total($_SESSION['player_cards']);
$dealer_total = calculate_total($_SESSION['dealer_cards']);

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ブラックジャック</title>
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
            margin-bottom: 10px;
        }
        .back-button {
            background-color: #007bff;
            margin-top: 20px;
            width: 100%;
            font-size: 1rem;
            padding: 10px;
        }
        .back-button:hover, button:hover {
            opacity: 0.9;
        }
        .cards {
            display: flex;
            justify-content: center;
            margin-top: 10px;
        }
        .card {
            margin: 0 5px;
        }
        .label {
            margin-top: 10px;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="main-frame">
        <h2>ブラックジャック</h2>

        <?php if (!isset($_SESSION['bet_placed'])): ?>
            <form method="post">
                <div class="input-group">
                    <label for="bet_amount">ベット額を入力:</label>
                    <input type="number" id="bet_amount" name="bet_amount" required>
                </div>
                <button type="submit" name="bet_amount_submit">ベット開始</button>
            </form>
        <?php endif; ?>

        <?php if (isset($_SESSION['bet_placed']) && $_SESSION['bet_placed']): ?>
            <!-- プレイヤーの手札 -->
            <h3>プレイヤーの手札</h3>
            <div class="cards">
                <?php 
                foreach ($_SESSION['player_cards'] as $card) {
                    echo display_card_image($card);
                }
                ?>
            </div>
            <div class="label">合計: <?php echo $player_total; ?></div>

            <!-- ディーラーの手札 -->
            <h3>ディーラーの手札</h3>
            <div class="cards">
                <?php 
                foreach ($_SESSION['dealer_cards'] as $index => $card) {
                    if ($index == 0 && !isset($_SESSION['game_over'])) {
                        echo display_card_image($card);
                        echo display_card_image('back'); // 裏向きカードの画像
                        break;
                    } else {
                        echo display_card_image($card);
                    }
                }
                ?>
            </div>
            <div class="label">合計: <?php echo isset($_SESSION['game_over']) ? $dealer_total : "？"; ?></div>

            <?php if (isset($result_message)): ?>
                <div class="label"><?php echo $result_message; ?></div>
            <?php endif; ?>

            <?php if (!isset($_SESSION['game_over'])): ?>
                <form method="post">
                    <button type="submit" name="hit">ヒット</button>
                    <button type="submit" name="stand">スタンド</button>
                </form>
            <?php else: ?>
                <form method="post">
                    <button type="submit" name="reset" class="back-button">もう一度プレイ</button>
                </form>
            <?php endif; ?>
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

<?php
// カード画像を表示する関数
function display_card_image($card) {
    if ($card === 'back') {
        $image_path = "cards/back.png"; // 裏向きカードの画像
    } else {
        $image_path = "cards/{$card}.png";
    }
    if (file_exists($image_path)) {
        return "<img src='{$image_path}' alt='{$card}' width='60' height='90' class='card' />";
    } else {
        return "<span class='card'>{$card}</span>";
    }
}
?>
