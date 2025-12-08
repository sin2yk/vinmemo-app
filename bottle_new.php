<?php
require_once 'db_connect.php';
session_start();

// イベントID（GET で渡される前提）
$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 基本情報
    $owner_label = trim($_POST['owner_label'] ?? '');
    $wine_name = trim($_POST['wine_name'] ?? '');
    $producer_name = trim($_POST['producer_name'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $region_other = trim($_POST['region_other'] ?? '');
    $appellation = trim($_POST['appellation'] ?? '');
    $color = $_POST['color'] ?? 'red';

    // ヴィンテージ（BYO風セレクト → INT / NULL へマッピング）
    $vintage_raw = $_POST['vintage'] ?? '';
    if ($vintage_raw === '' || $vintage_raw === 'NV') {
        $vintage = null;
    } elseif ($vintage_raw === '1970_or_earlier') {
        // DB 上は 1970 を「1970年以前」の代表値として保存
        $vintage = 1970;
    } else {
        $vintage = filter_var($vintage_raw, FILTER_VALIDATE_INT);
        if ($vintage === false) {
            $vintage = null;
        }
    }

    // 容量：radio + select から bottle_size_ml を決定
    $bottle_size_type = $_POST['bottle_size_type'] ?? 'bottle';
    $bottle_size_other = $_POST['bottle_size_other'] ?? '';

    if ($bottle_size_type === 'bottle') {
        $bottle_size_ml = 750;
    } elseif ($bottle_size_type === 'magnum') {
        $bottle_size_ml = 1500;
    } else {
        // other
        switch ($bottle_size_other) {
            case 'demi':
                $bottle_size_ml = 375;
                break;
            case 'clavelin':
                $bottle_size_ml = 620;
                break;
            case 'jeroboam':
                $bottle_size_ml = 3000;
                break;
            case 'other_custom':
                $custom_ml = filter_input(INPUT_POST, 'bottle_size_custom', FILTER_VALIDATE_INT);
                $bottle_size_ml = $custom_ml && $custom_ml > 0 ? $custom_ml : 750;
                break;
            default:
                $bottle_size_ml = 750;
                break;
        }
    }

    // 参考価格：ラジオ（casual〜icon）→ est_price_yen に代表値として保存
    $price_band = $_POST['price_band'] ?? '';

    switch ($price_band) {
        case 'casual':  // 〜5千円
            $est_price_yen = 5000;
            break;
        case 'bistro':  // 〜1万円
            $est_price_yen = 10000;
            break;
        case 'fine':    // 〜2万円
            $est_price_yen = 20000;
            break;
        case 'luxury':  // 〜5万円
            $est_price_yen = 50000;
            break;
        case 'icon':    // それ以上
            $est_price_yen = 100000;
            break;
        default:
            $est_price_yen = null;
            break;
    }

    // テーマ適合度：ラジオ 1〜5（視覚上は 5→1 の順で並べる）
    $theme_fit_score = filter_input(INPUT_POST, 'theme_fit_score', FILTER_VALIDATE_INT);
    if ($theme_fit_score === false) {
        $theme_fit_score = null;
    }

    // ブラインド設定（複数チェックボックス → is_blind 1bitに集約）
    $blind_keys = [
        'blind_producer',
        'blind_wine_name',
        'blind_vintage',
        'blind_region',
        'blind_country',
        'blind_appellation',
        'blind_price',
        'blind_theme_fit', // Added for consistency
        'blind_comment',
    ];
    $is_blind = 0;
    foreach ($blind_keys as $key) {
        if (!empty($_POST[$key])) {
            $is_blind = 1;
            break;
        }
    }

    $memo = trim($_POST['memo'] ?? '');

    // 最低限のバリデーション
    if (!$event_id || $owner_label === '' || $wine_name === '' || $producer_name === '') {
        $error = '必須項目（お名前・生産者・ワイン名・イベントID）が不足しています。';
    } else {
        try {
            // region_other をマージ（スキーマは region だけなのでまとめて保存）
            $region_to_save = $region;
            if ($region === 'Other') {
                $region_to_save = $region_other !== '' ? $region_other : 'Other';
            }

            // ログインユーザー情報（将来のMYボトル表示用）
            $brought_by_user_id = $_SESSION['user_id'] ?? null;
            $brought_by_type = $brought_by_user_id ? 'guest' : null;

            $sql = 'INSERT INTO bottle_entries (
                        event_id,
                        owner_label,
                        wine_name,
                        producer_name,
                        country,
                        region,
                        appellation,
                        color,
                        vintage,
                        bottle_size_ml,
                        est_price_yen,
                        theme_fit_score,
                        is_blind,
                        memo,
                        brought_by_type,
                        brought_by_user_id,
                        created_at,
                        updated_at
                    ) VALUES (
                        :event_id,
                        :owner_label,
                        :wine_name,
                        :producer_name,
                        :country,
                        :region,
                        :appellation,
                        :color,
                        :vintage,
                        :bottle_size_ml,
                        :est_price_yen,
                        :theme_fit_score,
                        :is_blind,
                        :memo,
                        :brought_by_type,
                        :brought_by_user_id,
                        NOW(),
                        NOW()
                    )';

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':event_id', $event_id, PDO::PARAM_INT);
            $stmt->bindValue(':owner_label', $owner_label, PDO::PARAM_STR);
            $stmt->bindValue(':wine_name', $wine_name, PDO::PARAM_STR);
            $stmt->bindValue(':producer_name', $producer_name, PDO::PARAM_STR);
            $stmt->bindValue(':country', $country, PDO::PARAM_STR);
            $stmt->bindValue(':region', $region_to_save, PDO::PARAM_STR);
            $stmt->bindValue(':appellation', $appellation, PDO::PARAM_STR);
            $stmt->bindValue(':color', $color, PDO::PARAM_STR);

            if ($vintage === null) {
                $stmt->bindValue(':vintage', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':vintage', $vintage, PDO::PARAM_INT);
            }

            $stmt->bindValue(':bottle_size_ml', $bottle_size_ml, PDO::PARAM_INT);

            if ($est_price_yen === null) {
                $stmt->bindValue(':est_price_yen', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':est_price_yen', $est_price_yen, PDO::PARAM_INT);
            }

            if ($theme_fit_score === null) {
                $stmt->bindValue(':theme_fit_score', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':theme_fit_score', $theme_fit_score, PDO::PARAM_INT);
            }

            $stmt->bindValue(':is_blind', $is_blind, PDO::PARAM_INT);
            $stmt->bindValue(':memo', $memo, PDO::PARAM_STR);

            if ($brought_by_type === null) {
                $stmt->bindValue(':brought_by_type', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':brought_by_type', $brought_by_type, PDO::PARAM_STR);
            }

            if ($brought_by_user_id === null) {
                $stmt->bindValue(':brought_by_user_id', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':brought_by_user_id', $brought_by_user_id, PDO::PARAM_INT);
            }

            $stmt->execute();

            header('Location: event_show.php?id=' . $event_id);
            exit;
        } catch (PDOException $e) {
            $error = '登録中にエラーが発生しました: ' . $e->getMessage();
        }
    }
}

// GET で event_id 無しで来た場合の簡易防御
if (!$event_id) {
    $error = $error ?? 'イベントIDが指定されていません。';
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VinMemo - ボトル登録</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container bottle-page">
        <header class="page-header">
            <h1>ボトルを登録</h1>
            <?php if ($event_id): ?>
                <a class="back-link" href="event_show.php?id=<?= htmlspecialchars($event_id, ENT_QUOTES, 'UTF-8') ?>">←
                    イベントに戻る</a>
            <?php endif; ?>
        </header>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($event_id): ?>
            <form action="bottle_new.php" method="post" class="bottle-form">
                <input type="hidden" name="event_id" value="<?= htmlspecialchars($event_id, ENT_QUOTES, 'UTF-8') ?>">

                <p class="blind-note">
                    ※ このフォームでは、項目ごとに「ブラインド(非公開)」チェックを入れることで、
                    当日の表示からその情報を隠すことができます。
                </p>

                <!-- 基本情報 -->
                <div class="form-section">
                    <h3>基本情報 / Basic Info</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <div class="label-row">
                                <span>お名前（ニックネーム可） / Your Name<span class="required">*</span></span>
                                <!-- 名前をブラインドする意味は薄いのでチェックなし -->
                            </div>
                            <input type="text" name="owner_label" required>
                        </div>
                    </div>
                </div>

                <!-- ワイン情報 -->
                <div class="form-section">
                    <h3>ワイン情報 / Wine Info</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <div class="label-row">
                                <span>生産者 / Producer<span class="required">*</span></span>
                                <label class="blind-inline">
                                    <input type="checkbox" name="blind_producer" value="1"> ブラインド
                                </label>
                            </div>
                            <input type="text" name="producer_name" placeholder="例：Emmanuel Rouget" required>
                        </div>
                        <div class="form-group">
                            <div class="label-row">
                                <span>ワイン名 / Wine name<span class="required">*</span></span>
                                <label class="blind-inline">
                                    <input type="checkbox" name="blind_wine_name" value="1"> ブラインド
                                </label>
                            </div>
                            <input type="text" name="wine_name" placeholder="例：Echezeaux" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <!-- Country -->
                        <div class="form-group">
                            <div class="label-row">
                                <span>国 / Country</span>
                                <label class="blind-inline">
                                    <input type="checkbox" name="blind_country" value="1"> ブラインド
                                </label>
                            </div>
                            <input type="text" name="country" placeholder="France / USA / Japan など">
                        </div>

                        <!-- Appellation -->
                        <div class="form-group">
                            <div class="label-row">
                                <span>アペラシオン / Appellation</span>
                                <label class="blind-inline">
                                    <input type="checkbox" name="blind_appellation" value="1"> ブラインド
                                </label>
                            </div>
                            <input type="text" name="appellation" placeholder="Meursault, Pauillac など">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <div class="label-row">
                                <span>産地 / Region</span>
                                <label class="blind-inline">
                                    <input type="checkbox" name="blind_region" value="1"> ブラインド
                                </label>
                            </div>
                            <select name="region">
                                <option value="">選択してください</option>
                                <option value="Bourgogne">ブルゴーニュ</option>
                                <option value="Champagne">シャンパーニュ</option>
                                <option value="Bordeaux">ボルドー</option>
                                <option value="Rhône">ローヌ</option>
                                <option value="Loire">ロワール</option>
                                <option value="Alsace">アルザス</option>
                                <option value="Germany">ドイツ</option>
                                <option value="Italy">イタリア</option>
                                <option value="Spain">スペイン</option>
                                <option value="California">カリフォルニア</option>
                                <option value="Other">その他</option>
                            </select>
                            <input type="text" name="region_other" placeholder="その他の場合はこちらにご記入ください"
                                class="input-inline-top">
                        </div>

                        <div class="form-group">
                            <div class="label-row">
                                <span>タイプ / Type</span>
                            </div>
                            <div class="radio-row">
                                <label><input type="radio" name="color" value="sparkling"> 泡</label>
                                <label><input type="radio" name="color" value="white"> 白</label>
                                <label><input type="radio" name="color" value="orange"> オレンジ</label>
                                <label><input type="radio" name="color" value="rose"> ロゼ</label>
                                <label><input type="radio" name="color" value="red" checked> 赤</label>
                                <label><input type="radio" name="color" value="sweet"> 甘口</label>
                                <label><input type="radio" name="color" value="fortified"> 酒精強化</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 詳細スペック -->
                <div class="form-section">
                    <h3>詳細 / Specs</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <div class="label-row">
                                <span>ヴィンテージ / Vintage</span>
                                <label class="blind-inline">
                                    <input type="checkbox" name="blind_vintage" value="1"> ブラインド
                                </label>
                            </div>
                            <select name="vintage">
                                <option value="">選択してください</option>
                                <option value="NV">NV（ノン・ヴィンテージ）</option>
                                <option value="1970_or_earlier">1970年以前</option>
                                <?php
                                $currentYear = (int) date('Y');
                                for ($y = $currentYear; $y >= 1971; $y--): ?>
                                    <option value="<?= $y ?>"><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                            <small>※DB上は数値として保存されます（1970年以前は「1970」扱い）。</small>
                        </div>

                        <div class="form-group">
                            <div class="label-row">
                                <span>容量 / Bottle size</span>
                            </div>

                            <div class="radio-row">
                                <label><input type="radio" name="bottle_size_type" value="bottle" checked> Bottle
                                    (750ml)</label>
                                <label><input type="radio" name="bottle_size_type" value="magnum"> Magnum (1500ml)</label>
                                <label><input type="radio" name="bottle_size_type" value="other"> Other</label>
                            </div>

                            <div class="other-size-row">
                                <select name="bottle_size_other">
                                    <option value="">選択してください</option>
                                    <option value="demi">Demi (375ml)</option>
                                    <option value="clavelin">Clavelin (620ml)</option>
                                    <option value="jeroboam">Jeroboam (3000ml)</option>
                                    <option value="other_custom">その他（ml指定）</option>
                                </select>
                                <input type="number" name="bottle_size_custom" placeholder="例：500 (ml)" min="30"
                                    class="input-inline-top">
                            </div>
                            <small>※未指定の場合は 750ml として扱います。</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <div class="label-row">
                                <span>参考価格帯 / Price band</span>
                                <label class="blind-inline">
                                    <input type="checkbox" name="blind_price" value="1"> ブラインド
                                </label>
                            </div>
                            <div class="radio-row">
                                <label><input type="radio" name="price_band" value="casual"> casual（〜5千円）</label>
                                <label><input type="radio" name="price_band" value="bistro"> bistro（〜1万円）</label>
                                <label><input type="radio" name="price_band" value="fine"> fine（〜2万円）</label>
                                <label><input type="radio" name="price_band" value="luxury"> luxury（〜5万円）</label>
                                <label><input type="radio" name="price_band" value="icon"> icon（それ以上）</label>
                            </div>
                            <small>※DB上はそれぞれの帯の代表値（5k/10k/20k/50k/100k）として保存します。</small>
                        </div>

                        <div class="form-group">
                            <div class="label-row">
                                <span>テーマ適合度 / Theme fit</span>
                                <label class="blind-inline">
                                    <input type="checkbox" name="blind_theme_fit" value="1"> ブラインド
                                </label>
                            </div>
                            <div class="radio-row">
                                <label><input type="radio" name="theme_fit_score" value="5"> 5：ドンピシャ</label>
                                <label><input type="radio" name="theme_fit_score" value="4"> 4：かなり合う</label>
                                <label><input type="radio" name="theme_fit_score" value="3" checked> 3：まあまあ</label>
                                <label><input type="radio" name="theme_fit_score" value="2"> 2：ややズレ</label>
                                <label><input type="radio" name="theme_fit_score" value="1"> 1：かなりズレ</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- メモ -->
                <div class="form-section">
                    <h3>メモ / Memo</h3>
                    <div class="form-group">
                        <div class="label-row">
                            <span>メモ / Notes</span>
                            <label class="blind-inline">
                                <input type="checkbox" name="blind_comment" value="1"> ブラインド
                            </label>
                        </div>
                        <textarea name="memo" rows="4" placeholder="コメントやサーブ順の希望など"></textarea>
                    </div>
                </div>

                <button type="submit" class="button" style="width:100%;">この内容で登録する</button>
            </form>
        <?php else: ?>
            <p>イベント情報が取得できませんでした。</p>
        <?php endif; ?>
    </div>

    <script>
        // ブラインドチェックされた項目を視覚的に強調
        (function () {
            function updateBlindStyles() {
                const blindChecks = document.querySelectorAll('.blind-inline input[type="checkbox"]');
                blindChecks.forEach(function (cb) {
                    const group = cb.closest('.form-group');
                    if (!group) return;
                    if (cb.checked) {
                        group.classList.add('is-blind');
                    } else {
                        group.classList.remove('is-blind');
                    }
                });
            }

            document.addEventListener('change', function (e) {
                if (e.target.matches('.blind-inline input[type="checkbox"]')) {
                    updateBlindStyles();
                }
            });

            // 初期状態の反映（編集画面にも流用できるように）
            document.addEventListener('DOMContentLoaded', updateBlindStyles);
        })();
    </script>
</body>

</html>