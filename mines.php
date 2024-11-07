<?php
// 開発中のみエラーメッセージを表示（公開環境では無効化）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// マインズゲームの処理
$result_message = isset($result_message) ? $result_message : "";
$game_over = false; // ゲームオーバー状態を追跡

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['start_game'])) {
        // ゲーム開始
        $bet_amount = isset($_POST['bet_amount']) ? (int)$_POST['bet_amount'] : 0;
        $mine_count = isset($_POST['mine_count']) ? (int)$_POST['mine_count'] : 0;

        if ($bet_amount <= 0 || $mine_count <= 0) {
            $result_message = "ベット額と爆弾数を正しく入力してください";
        } elseif ($bet_amount > $balance) {
            $result_message = "所持金が不足しています";
        } elseif ($mine_count > 24) { // 5x5 grid has 25 tiles; limit mine_count
            $result_message = "爆弾の数が多すぎます。最大24までです。";
        } else {
            // 所持金からベット額を引き、累計ベット額とレーキバックの更新
            $balance -= $bet_amount;
            $total_bet_amount += $bet_amount;
            $rakeback_amount += $bet_amount * 0.01 * 0.05;

            // マインスゲームの初期設定
            $grid_size = 5;
            if ($mine_count > ($grid_size * $grid_size - 1)) {
                $result_message = "爆弾の数が多すぎます。もう一度入力してください。";
            } else {
                // 爆弾の位置をランダムに選ぶ
                $all_positions = range(0, $grid_size * $grid_size - 1);
                shuffle($all_positions);
                $mines = array_slice($all_positions, 0, $mine_count);

                // セッションにゲーム状態を保存
                $_SESSION['mines_game'] = [
                    'bet_amount' => $bet_amount,
                    'mine_count' => $mine_count,
                    'mines' => $mines,
                    'found_diamonds' => 0,
                    'current_multiplier' => 1.0,
                    'grid_size' => $grid_size,
                    'revealed_tiles' => [] // array of revealed tile indices
                ];

                // JSONファイルにデータを保存
                $data[$email]['balance'] = $balance;
                $data[$email]['total_bet_amount'] = $total_bet_amount;
                $data[$email]['rakeback_amount'] = $rakeback_amount;
                file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));

                $result_message = "ゲームを開始しました。";
            }
        }
    } elseif (isset($_POST['reveal_tile'])) {
        // タイルがクリックされた
        $tile = isset($_POST['reveal_tile']) ? (int)$_POST['reveal_tile'] : -1;

        if (!isset($_SESSION['mines_game'])) {
            $result_message = "ゲームが開始されていません。";
        } else {
            $game = &$_SESSION['mines_game'];

            if (in_array($tile, $game['revealed_tiles'])) {
                $result_message = "このタイルは既に開かれています。";
            } else {
                $game['revealed_tiles'][] = $tile;

                if (in_array($tile, $game['mines'])) {
                    // 爆弾に当たった
                    $result_message = "爆弾に当たりました…ゲームオーバー！";
                    // ゲーム終了処理
                    unset($_SESSION['mines_game']);
                    $game_over = true;
                } else {
                    // ダイヤモンドを見つけた
                    $game['found_diamonds'] += 1;
                    $game['current_multiplier'] = 1.0 + 0.075 * $game['found_diamonds'] * (1 + 0.05 * $game['mine_count']);

                    $result_message = "ダイヤモンドを見つけました！ 現在の倍率: " . number_format($game['current_multiplier'], 2);
                }

                // JSONファイルにデータを保存
                $data[$email]['balance'] = $balance;
                $data[$email]['total_bet_amount'] = $total_bet_amount;
                $data[$email]['rakeback_amount'] = $rakeback_amount;
                file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
            }
        }
    } elseif (isset($_POST['cashout'])) {
        // キャッシュアウト処理
        if (!isset($_SESSION['mines_game'])) {
            $result_message = "ゲームが開始されていません。";
        } else {
            $game = &$_SESSION['mines_game'];
            $bet_amount = $game['bet_amount'];
            $current_multiplier = $game['current_multiplier'];
            $winnings = $bet_amount * $current_multiplier;

            $balance += $winnings;
            $result_message = "キャッシュアウト成功！獲得額: " . number_format($winnings, 2) . "円";

            // ゲーム終了処理
            unset($_SESSION['mines_game']);

            // JSONファイルにデータを保存
            $data[$email]['balance'] = $balance;
            $data[$email]['total_bet_amount'] = $total_bet_amount;
            $data[$email]['rakeback_amount'] = $rakeback_amount;
            file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
        }
    } elseif (isset($_POST['restart_game'])) {
        // ゲームリスタート
        if (!isset($_SESSION['mines_game'])) {
            $result_message = "ゲームが開始されていません。";
        } else {
            unset($_SESSION['mines_game']);
            $result_message = "ゲームをリスタートします。";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>マインズ</title>
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
        .cashout-button {
            background-color: #ffc107;
            color: #000;
        }
        .back-button, .restart-button {
            background-color: #007bff;
            margin-top: 10px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(5, 50px);
            grid-gap: 5px;
            justify-content: center;
            margin-bottom: 10px;
        }
        .grid button {
            width: 50px;
            height: 50px;
            background-color: #6a11cb;
            background-image: linear-gradient(to right, #2575fc, #6a11cb);
            border: none;
            border-radius: 5px;
            color: #fff;
            font-size: 18px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .grid button.revealed {
            background-color: #28a745;
            cursor: default;
        }
        .grid button.mine {
            background-color: #dc3545;
        }
        .label {
            margin-top: 10px;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="main-frame">
        <h2>マインズ</h2>
        <form method="post">
            <div class="input-group">
                <label for="bet_amount">ベット額を入力:</label>
                <input type="number" id="bet_amount" name="bet_amount" required>
            </div>
            <div class="input-group">
                <label for="mine_count">爆弾の数を入力:</label>
                <input type="number" id="mine_count" name="mine_count" required>
            </div>
            <button type="submit" name="start_game" class="start-button">ゲームを開始</button>
        </form>

        <?php if (!empty($result_message)): ?>
            <div class="label"><?php echo htmlspecialchars($result_message); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['mines_game'])): ?>
            <form method="post">
                <div class="grid">
                    <?php
                    $game = $_SESSION['mines_game'];
                    $grid_size = $game['grid_size'];
                    for ($i = 0; $i < $grid_size * $grid_size; $i++) {
                        $revealed = in_array($i, $game['revealed_tiles']);
                        $is_mine = in_array($i, $game['mines']);
                        $button_class = '';
                        $button_text = '';

                        if ($revealed) {
                            if ($is_mine) {
                                $button_class = 'mine';
                                $button_text = '💣';
                            } else {
                                $button_class = 'revealed';
                                $button_text = '💎';
                            }
                        }

                        echo "<button type='submit' name='reveal_tile' value='$i' class='$button_class'>$button_text</button>";
                    }
                    ?>
                </div>
            </form>

            <?php if ($game['found_diamonds'] > 0): ?>
                <form method="post">
                    <button type="submit" name="cashout" class="cashout-button" onclick="return confirm('キャッシュアウトしますか？');">キャッシュアウト</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>

        <div class="label">所持金: <?php echo number_format($balance, 2); ?>円</div>
        <div class="label">累計ベット額: <?php echo number_format($total_bet_amount); ?>円</div>
        <div class="label">レーキバック: <?php echo number_format($rakeback_amount, 2); ?>円</div>

        <!-- TOPへ戻るボタン -->
        <button class="back-button" onclick="location.href='betting_game_page.php'">TOPへ戻る</button>
    </div>
</body>
</html>
