<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// セッションからメールアドレスを取得
$email = $_SESSION['email'] ?? null;

if (!$email) {
    echo "Email not set in session.";
    exit();
}

// JSONファイルのパス
$filename = 'game_data.json';

// JSONファイルの読み込み
if (file_exists($filename)) {
    $data = json_decode(file_get_contents($filename), true);
    if (isset($data[$email])) {
        // ユーザーデータをセッションに保存
        $_SESSION['balance'] = $data[$email]['balance'] ?? 0;
        $_SESSION['total_bet_amount'] = $data[$email]['total_bet_amount'] ?? 0;
        $_SESSION['rakeback_amount'] = $data[$email]['rakeback_amount'] ?? 0;

        // 変数の初期化
        $balance = $_SESSION['balance'];
        $total_bet_amount = $_SESSION['total_bet_amount'];
        $rakeback_amount = $_SESSION['rakeback_amount'];
        $rank = calculate_rank($total_bet_amount); // ランクを計算
    } else {
        echo "ユーザーデータが見つかりません。";
        exit();
    }
} else {
    echo "データファイルが見つかりません。";
    exit();
}

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

// レーキバック受取処理
if (isset($_POST['claim_rakeback'])) {
    $balance = $_SESSION['balance'] ?? 0; // 所持金をセッションから取得
    $rakeback_amount = $_SESSION['rakeback_amount'] ?? 0; // レーキバックをセッションから取得

    // 所持金にレーキバックを加算
    $balance += $rakeback_amount;
    $rakeback_amount = 0; // レーキバックを0に設定

    // JSONファイルにデータを保存
    $data[$email]['balance'] = $balance;
    $data[$email]['rakeback_amount'] = $rakeback_amount;
    file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));

    // セッションに新しい所持金を保存
    $_SESSION['balance'] = $balance;

    // JSONレスポンスを返す
    header('Content-Type: application/json');
    echo json_encode(["message" => "レーキバックを受け取りました。", "balance" => $balance]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Betting Game Page</title>
    <style>
        /* CSSスタイル */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(to right, #2b5876, #4e4376);
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .main-frame {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            width: 350px;
            text-align: center;
        }
        .label {
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        button {
            background-color: #6a11cb;
            background-image: linear-gradient(to right, #2575fc, #6a11cb);
            color: #fff;
            border: none;
            border-radius: 25px;
            padding: 10px 20px;
            margin-bottom: 10px;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s;
            font-size: 1rem;
        }
        button:hover {
            background-color: #4a148c;
        }
        .green-button {
            background-color: #28a745; /* 緑色 */
            background-image: linear-gradient(to right, #34d058, #28a745);
        }
        .green-button:hover {
            background-color: #218838;
        }
        .progress-bar {
            width: 100%;
            height: 10px; /* 少し細く */
            background-color: #444;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.3);
            margin-top: 10px;
        }
        .progress {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #85e085);
            width: 0; /* 初期幅は0 */
            transition: width 0.05s; /* アニメーション効果 */
            border-radius: 15px;
        }
    </style>
    <script>
        let balance = parseFloat(<?php echo json_encode($balance); ?>);
        if (isNaN(balance)) {
            balance = 0;
        }
        const email = "<?php echo $email; ?>";

        function performWork() {
            const workButton = document.getElementById('work-button');
            const balanceLabel = document.getElementById('balance-label');
            const workResult = document.getElementById('work-result');
            const progressBar = document.getElementById('progress');

            // ボタンを無効化
            workButton.disabled = true;
            progressBar.style.width = '0';

            // プログレスバーを5秒で満たす
            let progress = 0;
            const interval = setInterval(() => {
                progress += 1;
                progressBar.style.width = `${progress}%`;
                if (progress >= 100) {
                    clearInterval(interval);
                    workButton.disabled = false; // ボタンを再び有効化
                }
            }, 50); // 50ミリ秒ごとに更新（5秒で100%に達する）

            // 収入をランダムに決定
            const rand = Math.random();
            let earnings = 0;
            let message = "";

            if (rand < 0.97) {
                earnings = Math.floor(Math.random() * (999 - 100 + 1)) + 100;
                message = `日々の努力が実り、${earnings}円を得ました！`;
            } else if (rand < 0.97 + 0.02) {
                earnings = Math.floor(Math.random() * (2999 - 1000 + 1)) + 1000;
                message = `友人に手を貸し、心温まる${earnings}円を得ました！`;
            } else if (rand < 0.97 + 0.02 + 0.006) {
                earnings = Math.floor(Math.random() * (4999 - 3000 + 1)) + 3000;
                message = `予期せぬボーナスとして${earnings}円を受け取りました！`;
            } else if (rand < 0.97 + 0.02 + 0.006 + 0.001) {
                earnings = Math.floor(Math.random() * (9999 - 5000 + 1)) + 5000;
                message = `大きな成功を収め、${earnings}円の報酬を得ました！`;
            } else if (rand < 0.97 + 0.02 + 0.006 + 0.001 + 0.001) {
                earnings = Math.floor(Math.random() * (49999 - 10000 + 1)) + 10000;
                message = `素晴らしい結果がもたらされ、${earnings}円の収益を得ました！`;
            } else if (rand < 0.97 + 0.02 + 0.006 + 0.001 + 0.001 + 0.001) {
                earnings = Math.floor(Math.random() * (99999 - 50000 + 1)) + 50000;
                message = `努力が報われ、なんと${earnings}円を手に入れました！`;
            } else if (rand < 0.97 + 0.02 + 0.006 + 0.001 + 0.001 + 0.001 + 0.0001) {
                earnings = Math.floor(Math.random() * (200000 - 100000 + 1)) + 100000;
                message = `ビッグチャンスを掴み、驚きの${earnings}円を獲得しました！`;
            } else {
                message = "残念ながら今回は成果がありませんでした…。次回に期待！";
            }

            // 所持金を更新して表示
            balance += earnings;
            balanceLabel.textContent = `所持金: ${balance.toFixed(2)}円`;

            // メッセージを表示
            workResult.textContent = message;

            // JSONファイルに保存するリクエストを送信
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "update_balance.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.send(`email=${encodeURIComponent(email)}&balance=${encodeURIComponent(balance)}`);
        }
        function claimRakeback() {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "betting_game_page.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            document.getElementById('work-result').textContent = response.message;

                            // 数秒後にリロード
                            setTimeout(() => {
                                location.reload();
                            }, 3000); // 3秒後にリロード
                        } else if (response.error) {
                            document.getElementById('work-result').textContent = response.error;
                        }
                    } catch (e) {
                        console.error("Invalid JSON response", e);
                        document.getElementById('work-result').textContent = "エラーが発生しました。";
                    }
                }
            };
            xhr.send("claim_rakeback=true");
        }
    </script>
</head>
<body>
    <div class="main-frame">
        <h2>Betting Game Page</h2>
        <!-- 所持金表示 -->
        <div class="label" id="balance-label">所持金: <?php echo number_format($balance, 2); ?>円</div>
        <!-- 累計ベット額表示 -->
        <div class="label" id="total-bet-label">累計ベット額: <?php echo number_format($total_bet_amount); ?>円</div>
        <!-- ランク表示 -->
        <div class="label" id="rank-label">ランク: <?php echo htmlspecialchars($rank); ?></div>
        <!-- レーキバック金額表示 -->
        <div class="label" id="rakeback-label">レーキバック: <?php echo number_format($rakeback_amount, 2); ?>円</div>
        <!-- レーキバック受け取りボタン -->
        <button onclick="claimRakeback()" style="width: 200px;">レーキバック受取</button>
        <!-- Workボタン -->
        <button id="work-button" onclick="performWork()" style="width: 200px;">Work</button>
        <!-- プログレスバー -->
        <div class="progress-bar">
            <div id="progress" class="progress"></div>
        </div>
        <!-- メッセージ表示ラベル -->
        <div class="label" id="work-result" style="margin-top: 10px; word-wrap: break-word;"></div>
        <!-- ゲームボタン用フレーム -->
        <div class="game-buttons-frame" style="margin-top: 10px; display: flex; flex-wrap: wrap; justify-content: space-between; gap: 5px;">
            <button onclick="location.href='nibuichi.php'" style="width: 48%;">ニブイチ</button>
            <button onclick="location.href='slot.php'" style="width: 48%;">スロット</button>
            <button onclick="location.href='mines.php'" style="width: 48%;">マインズ</button>
            <button onclick="location.href='blackjack.php'" style="width: 48%;">ブラックジャック</button>
            <button onclick="location.href='Sic-Bo.php'" style="width: 48%;">シックボー</button>
            <button onclick="location.href='baccarat.php'" style="width: 48%;">バカラ</button>
        </div>
        <!-- グローバルランキングボタン -->
        <button onclick="location.href='ranking.php'" class="green-button">グローバル所持金ランキング</button>
    </div>
</body>
</html>