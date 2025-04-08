<!--index.php-->
<?php

//必要なライブラリの読み込み
require_once 'vendor/autoload.php';
use Dotenv\Dotenv;

//環境変数の読み込み
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

//エラーメッセージ表示の設定
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 環境変数から取得
$credentialsPath = $_ENV['GOOGLE_CREDENTIALS_PATH'];
$spreadsheetId = $_ENV['GOOGLE_SPREADSHEET_ID'];
$range = $_ENV['GOOGLE_SPREADSHEET_RANGE'];

// 認証ファイルの存在チェック
if (!file_exists($credentialsPath)) {
    die("Error: credentials.json が見つかりません！");
}

//Google Sheets API の設定
use Google\Client;
use Google\Service\Sheets;

$client = new Client();
$client->setApplicationName('Google Sheets API PHP');
$client->setScopes([Sheets::SPREADSHEETS_READONLY]);
$client->setAuthConfig($credentialsPath);

$service = new Sheets($client);

try {
//    スプレッドシートデータの取得
    $response = $service->spreadsheets_values->get($spreadsheetId, $range);
    $values = $response->getValues();

    if (empty($values)) {
        echo 'データが見つかりません。';
        exit;
    }
//    空室情報の処理
    $availability = [];
    $currentDate = null;

    // 行を4つずつループ（1行は日付、次の3行は部屋タイプA～D）
    for ($i = 1; $i < count($values); $i += 4) {
        $dateRow = $values[$i];  // 日付行
        $date = $dateRow[0] ?? ''; // 日付列（最初の列）

        if (!empty($date) && strtotime($date)) {
            $currentDate = date('Y/m/d', strtotime($date));  // 日付を取得
        }

        if (!$currentDate) {
            continue; // 日付が見つからない場合はスキップ
        }

        $emptyCount = 0;

        // 各部屋（タイプA〜D）の空欄確認
        for ($j = 0; $j < 4; $j++) {
            $rowIndex = $i + $j;  // ループ内のインデックス（タイプA〜D）
            if (!isset($values[$rowIndex][2]) || trim($values[$rowIndex][2]) === '') {
                $emptyCount++;
            }
        }

        // 空欄の数によって状態を設定
        if ($emptyCount >= 2) {
            $status = '○';  // 空欄2個以上 → 空室多数
        } elseif ($emptyCount === 1) {
            $status = '△';  // 空欄1個 → 残り1室
        } else {
            $status = '×';  // 空欄なし → 満室
        }

        $availability[$currentDate] = $status;
    }

    // URLパラメータから年月取得
    $year = isset($_GET['year']) ? (int)$_GET['year'] : date("Y");
    $month = isset($_GET['month']) ? (int)$_GET['month'] : date("m");

    if ($month < 1 || $month > 12) {
        $month = date("m");
    }

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

    $firstDayOfMonth = date("w", mktime(0, 0, 0, $month, 1, $year));
    $daysInMonth = date("t", mktime(0, 0, 0, $month, 1, $year));

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
    echo "<span>{$year}年 {$month}月</span>";
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

    for ($i = 0; $i < $firstDayOfMonth; $i++) {
        echo "<div class='day-cell empty'></div>";
    }

    for ($day = 1; $day <= $daysInMonth; $day++) {
        $dateKey = sprintf("%04d/%02d/%02d", $year, $month, $day);
        $status = isset($availability[$dateKey]) ? $availability[$dateKey] : '';
        echo "<div class='day-cell'>{$day}<br>{$status}</div>";
    }

    echo "</div>";
    echo "</div>";

} catch (Exception $e) {
    echo 'エラー: ' . $e->getMessage();
}
?>
