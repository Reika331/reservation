<?php
get_header();
?>
    <div class="background2">
        <div class="eyecatch">
            <h2>Vacancy<span>空室情報</span></h2>
        </div>
        <main role="main" class="main2">
            <section class="reservation">
                <?php
                require_once get_template_directory() . '/vendor/autoload.php';

                ini_set('display_errors', 1);
                error_reporting(E_ALL);

                use Google\Client;
                use Google\Service\Sheets;

                $credentialsPath = get_template_directory() . '/credentials.json';
                if (!file_exists($credentialsPath)) {
                    die("Error: credentials.json が見つかりません！");
                }

                $spreadsheetId = '1UeTwSIO0jvHZ68cVsb0pPwoQrrmHdXN88xpmT_QrG7Q';
                $range = 'Sheet1!A:J';

                try {
                    $client = new Client();
                    $client->setAuthConfig($credentialsPath);
                    $client->setScopes([Sheets::SPREADSHEETS_READONLY]);

                    $service = new Sheets($client);
                    $response = $service->spreadsheets_values->get($spreadsheetId, $range);
                    $values = $response->getValues();

                    if (empty($values)) {
                        echo 'データが見つかりません。';
                    } else {
                        $availability = [];
                        $currentDate = null;

                        foreach ($values as $index => $row) {
                            if ($index === 0) continue; // ヘッダーはスキップ

                            if (!empty($row[0])) {
                                $currentDate = $row[0];
                                $availability[$currentDate] = ["total" => 4, "occupied" => 0];
                            }

                            // 部屋の予約状況を確認（C列～H列に1つでもデータがあれば予約済み）
                            $roomData = array_slice($row, 2, 6);
                            $isRoomOccupied = array_filter($roomData, fn($data) => !empty($data));

                            // 予約がある場合、occupied のカウントを増やす
                            if (!empty($isRoomOccupied)) {
                                $availability[$currentDate]["occupied"]++;
                            }
                        }
                    }
                } catch (Exception $e) {
                    echo 'エラー: ' . $e->getMessage();
                    exit;
                }

                // 📅 URLパラメータから「年・月」を取得（デフォルトは現在の年月）
                $year = isset($_GET['year']) ? (int)$_GET['year'] : date("Y");
                $month = isset($_GET['month']) ? (int)$_GET['month'] : date("m");

                // 📌 前月・次月のリンクを作成
                $prevMonth = $month - 1;
                $nextMonth = $month + 1;
                $prevYear = $year;
                $nextYear = $year;

                if ($prevMonth == 0) {
                    $prevMonth = 12;
                    $prevYear--;
                }
                if ($nextMonth == 13) {
                    $nextMonth = 1;
                    $nextYear++;
                }

                // 📌 月の最初の日の曜日と日数を取得
                $firstDayOfMonth = date("w", mktime(0, 0, 0, $month, 1, $year));
                $daysInMonth = date("t", mktime(0, 0, 0, $month, 1, $year));

                // 📌 カレンダーの表示
                echo "<style>
    .calendar-container { text-align: center; max-width: 500px; margin: auto; }
    .calendar-header { display: flex; justify-content: space-between; align-items: center; padding: 10px; font-size: 20px; }
    .calendar { display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; text-align: center; }
    .day-header { font-weight: bold; padding: 5px; background: #f4f4f4; }
    .day-cell { padding: 10px; border: 1px solid #ddd; min-height: 50px; }
    .empty { background: #f9f9f9; }
    .nav-button { padding: 5px 10px; text-decoration: none; background: #007BFF; color: white; border-radius: 5px; }
</style>";

                echo "<div class='calendar-container'>";
                echo "<div class='calendar-header'>";
                echo "<a href='?year={$prevYear}&month={$prevMonth}' class='nav-button'>◀ 前月</a>";
                echo "<span>{$yegitar}年 {$month}月</span>";
                echo "<a href='?year={$nextYear}&month={$nextMonth}' class='nav-button'>次月 ▶</a>";
                echo "</div>";

                echo "<div class='calendar'>";
                echo "<div class='day-header'>日</div>";
                echo "<div class='day-header'>月</div>";
                echo "<div class='day-header'>火</div>";
                echo "<div class='day-header'>水</div>";
                echo "<div class='day-header'>木</div>";
                echo "<div class='day-header'>金</div>";
                echo "<div class='day-header'>土</div>";

                // 📌 空白セル（1日が始まるまで埋める）
                for ($i = 0; $i < $firstDayOfMonth; $i++) {
                    echo "<div class='day-cell empty'></div>";
                }

                // 📌 日付セルを表示
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $dateKey = sprintf("%04d/%02d/%02d", $year, $month, $day);
                    $status = "⚪︎"; // デフォルトは「空部屋あり」

                    // 予約情報に基づく空室状況の判定
                    if (isset($availability[$dateKey])) {
                        $occupied = $availability[$dateKey]["occupied"];
                        if ($occupied < 4) {
                            $status = "⚪︎"; // 空部屋あり
                        } elseif ($occupied == 3) {
                            $status = "△"; // 残りわずか
                        } elseif ($occupied == 4) {
                            $status = "×"; // 空部屋なし
                        }
                    }

                    echo "<div class='day-cell'>{$day}<br>{$status}</div>";
                }

                echo "</div>"; // カレンダー閉じる
                echo "</div>"; // コンテナ閉じる
                ?>

                <div class="remarks">
                    <ul>
                        <li>○<span>：</span>空部屋あり</li>
                        <li>△<span>：</span>残りわずか</li>
                        <li>×<span>：</span>空部屋なし</li>
                    </ul>
                    <p>※1部屋<span>：</span>1~4名</p>
                </div>

            </section>

        </main>
    </div>

<?php get_footer(); ?>