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

    // 容量・価格・テーマ適合
    $bottle_size_ml = filter_input(INPUT_POST, 'bottle_size_ml', FILTER_VALIDATE_INT);
    if (!$bottle_size_ml) {
        $bottle_size_ml = 750;
    }

    $est_price_yen = filter_input(INPUT_POST, 'est_price_yen', FILTER_VALIDATE_INT);
    if ($est_price_yen === false) {
        $est_price_yen = null;
    }

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
        'blind_price',
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
    $error = $error ?? 'Event ID is missing.';
}

$page_title = 'VinMemo - New Bottle';
require_once 'layout/header.php';
?>

<header>
    <h1>Register Bottle</h1>
    <?php if ($event_id): ?>
        <a href="event_show.php?id=<?= h($event_id) ?>">← Back to Event</a>
    <?php endif; ?>
</header>

<?php if ($error): ?>
    <div class="error-msg"><?= h($error) ?></div>
<?php endif; ?>

<?php if ($event_id): ?>
    <form action="bottle_new.php" method="post" class="card">
        <input type="hidden" name="event_id" value="<?= h($event_id) ?>">

        <!-- 基本情報 -->
        <div class="form-section">
            <h3>基本情報 / Basic Info</h3>

            <div class="form-row">
                <div class="form-group">
                    <label>お名前（ニックネーム可） / Your Name<span class="required">*</span></label>
                    <input type="text" name="owner_label" required>
                </div>
            </div>

            <!-- ブラインド設定（BYO風だが is_blind 1bitに集約） -->
            <fieldset class="blind-fieldset"
                style="border:1px solid var(--border); padding:10px; border-radius:8px; margin-bottom:15px; background:rgba(0,0,0,0.1);">
                <legend style="color:var(--text-muted);">ブラインド設定（隠したい項目） / Blind settings</legend>
                <label><input type="checkbox" name="blind_producer" value="1"> 生産者を隠す</label><br>
                <label><input type="checkbox" name="blind_wine_name" value="1"> ワイン名を隠す</label><br>
                <label><input type="checkbox" name="blind_vintage" value="1"> ヴィンテージを隠す</label><br>
                <label><input type="checkbox" name="blind_region" value="1"> 産地を隠す</label><br>
                <label><input type="checkbox" name="blind_price" value="1"> 価格帯（参考価格）を隠す</label><br>
                <label><input type="checkbox" name="blind_comment" value="1"> メモ／コメントを隠す</label><br>
            </fieldset>
            <p style="font-size:0.85em; color:var(--text-muted);">
                ※現時点では「どれか1つでもチェック → is_blind=1」で保存されます。<br>
                将来のバージョンでフィールド別ブラインドに拡張予定です。
            </p>

        </div>

        <!-- ワイン情報 -->
        <div class="form-section">
            <h3>ワイン情報 / Wine Info</h3>

            <div class="form-row">
                <div class="form-group">
                    <label>生産者 / Producer<span class="required">*</span></label>
                    <input type="text" name="producer_name" placeholder="例：Emmanuel Rouget" required>
                </div>
                <div class="form-group">
                    <label>ワイン名 / Wine name<span class="required">*</span></label>
                    <input type="text" name="wine_name" placeholder="例：Echezeaux" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>国 / Country</label>
                    <input type="text" name="country" placeholder="例：France">
                </div>
                <div class="form-group">
                    <label>アペラシオン / Appellation</label>
                    <input type="text" name="appellation" placeholder="例：Vosne-Romanée">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>産地 / Region</label>
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
                    <input type="text" name="region_other" placeholder="その他の場合はこちらにご記入ください" style="margin-top:4px;">
                </div>

                <div class="form-group">
                    <label>タイプ / Type</label>
                    <select name="color" required>
                        <option value="sparkling">Sparkling (泡)</option>
                        <option value="white">White (白)</option>
                        <option value="orange">Orange (オレンジ)</option>
                        <option value="rose">Rosé (ロゼ)</option>
                        <option value="red" selected>Red (赤)</option>
                        <option value="sweet">Sweet (甘口)</option>
                        <option value="fortified">Fortified (酒精強化)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- 詳細スペック -->
        <div class="form-section">
            <h3>詳細 / Specs</h3>

            <div class="form-row">
                <div class="form-group">
                    <label>ヴィンテージ / Vintage</label>
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
                    <label>容量(ml) / Size</label>
                    <input type="number" name="bottle_size_ml" value="750" min="30">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>参考価格(円) / Estimated price</label>
                    <input type="number" name="est_price_yen" placeholder="10000" min="0">
                    <small>目安：〜5k=casual, 〜10k=bistro, 〜20k=fine, 〜50k=luxury, それ以上=icon</small>
                </div>
                <div class="form-group">
                    <label>テーマ適合度 / Theme fit (1-5)</label>
                    <select name="theme_fit_score">
                        <option value="">選択してください</option>
                        <option value="1">1：かなりズレているかも</option>
                        <option value="2">2</option>
                        <option value="3" selected>3：まあまあ合っている</option>
                        <option value="4">4</option>
                        <option value="5">5：ドンピシャだと思う</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- メモ -->
        <div class="form-section">
            <h3>メモ / Memo</h3>
            <div class="form-group">
                <textarea name="memo" rows="4" placeholder="コメントやサーブ順の希望など"></textarea>
            </div>
        </div>

        <button type="submit" class="button" style="width:100%;">この内容で登録する</button>
    </form>
<?php else: ?>
    <p>Event information missing.</p>
<?php endif; ?>

<?php require_once 'layout/footer.php'; ?>