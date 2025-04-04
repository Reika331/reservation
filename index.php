<!--index.php-->

<?php
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__); // .envファイルがあるディレクトリを指定
$dotenv->load();                      // .envファイルから環境変数を読み込み

// 通常の環境変数を同じように下記のどの方法でも環境変数を呼び出せます
$host = getenv('DB_HOST');
$user = $_SERVER['DB_USER'];
$password = $_ENV['DB_PASS'];

$dsn = "mysql:host={$host};charset=utf8;";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
];

$this->pdo = new PDO($dsn, $user, $password, $options);
?>

<?php get_header(); ?>
<main role="main">

    <?php include(get_template_directory() . "/inc/component/card-img.php"); ?>

</main>
<?php get_footer(); ?>
