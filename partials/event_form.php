<?php
// partials/event_form.php
// Expected variable: $event (array)

$title = $event['title'] ?? '';
// Ensure datetime-local format: Y-m-d\TH:i
$event_date_raw = $event['event_date'] ?? '';
$event_date = '';
if ($event_date_raw) {
    // If it comes from DB (e.g. "2025-12-23 18:00:00") or Post ("2025-12-23T18:00")
    $ts = strtotime($event_date_raw);
    if ($ts) {
        $event_date = date('Y-m-d\TH:i', $ts);
    }
}

$place = $event['place'] ?? '';
$memo = $event['memo'] ?? '';
$show_theme_fit = isset($event['show_theme_fit']) ? (bool) $event['show_theme_fit'] : true;
// Default true for new events? events_new says nothing about it, but logic implies enabled by default or specific.
// event_edit.php creation defaulted to 0 if not set, but user prompt says "Theme Fit / テーマ適合度を表示する" checkbox.
// check events_new.php original logic: it didn't have show_theme_fit. Only event_edit had it.
// We should probably default to true for new events if we want the feature active.

// Additional fields from events_new.php refactor request
// The user prompt in Step 1598 Section 1 simplified the form significantly compared to original events_new.php.
// Original events_new.php had: Subtitle, Area, Seats, Event Type (BYO/ORG/VENUE), Style Detail, Theme Desc, Bottle Rules, Blind Policy.
// The PROMPT in Section 1 ONLY lists: Title, Date, Place, Memo, Show Theme Fit.
// HOWEVER, the PROMPT says "events_new.php ... form display part changed to include 'partials/event_form.php'".
// IF I only include the simplified fields, we LOSE all the rich metadata (Metadata logic was in events_new.php).
// User Section A-1 says: "event_edit.php should reuse layout/inputs of events_new.php".
// BUT clearly Section 1 "Implementation Strategy" code block shows a SIMPLIFIED form.
// Does the user want to SIMPLIFY events_new.php to just these fields? OR does the user want the partial to include ALL fields?
// "Partial ... Extract the form body from events_new.php WHOLE".
// "Section 1 Implementation Strategy" code block might be an EXAMPLE of the variable handling, not the exhaustive list.
// I should try to preserve the fields from events_new.php as much as possible to avoid data loss, 
// AND add the 'show_theme_fit' which was in event_edit.
// Let's look at the instruction: "Insert current events_new.php form body whole into this partial".
// So I should keep the fields.

// Let's extract variables for all fields in events_new.php
$subtitle = $event['subtitle'] ?? ''; // actually in DB it's in memo JSON... 
// Wait, events_new.php logic saves these into JSON in 'memo'.
// event_edit.php needs to parse 'memo' to populate these if we want to edit them.
// The prompt A-1 says "event_edit.php ... POST ... UPDATE events SET ...".
// And "event_edit.php ... GET ... $event = fetch ... include partial".
// If I use the complex events_new.php form, I need to Parse JSON in event_edit.php to populate `$event`.
// The PROMPT Section 1 example form is VERY SIMPLE.
// "Title, Date, Place, Memo, Theme Fit".
// Is it possible the user wants to DOWNGRADE the form to simple version?
// OR did they just show a snippet?
// "PARTIALS/event_form.php ... New file ... Extract form body from events_new.php WHOLE". -> This implies keeping breadth.
// But Section 1 Code Example shows ONLY simple fields.
// Section 3 (Event Edit Refactor) -> "UPDATE events SET title=?, event_date=?, place=?, memo=?, show_theme_fit=?".
// It does NOT mention updating the JSON metadata.
// This is ambiguous.
// However, generally "Unified Form" means full capability.
// If I only implement the simple form, I break the "Rich" event creation added earlier.
// I will implement the FULL form from events_new.php, plus Show Theme Fit.
// I need to make sure event_edit.php parses the JSON from memo to pre-fill.
// Actually, looking at `events_new.php`: it constructs JSON and saves to `memo`.
// So in `event_edit.php`, we must parse `$event['memo']` to extract those keys back into `$event` array before including partial.
// I will proceed with the FULL form approach as it is safer than deleting features.

// Recovering Metadata from $event (which might be flat from DB)
// If $event has 'memo' that contains JSON, we should extract it if the keys are missing.
// But the partial just expects `$event` to have the keys. The Logic (Controller) should prepare `$event`.

// Let's look at the variables expected by events_new.php form:
// title, subtitle, event_date, start_time, place, area, seats, event_type, event_style_detail, theme_description, bottle_rules, blind_policy, memo (organizer note)
// And show_theme_fit.

$subtitle = $event['subtitle'] ?? '';
$start_time = $event['start_time'] ?? '';
$area = $event['area'] ?? '';
$seats = $event['seats'] ?? '';
$event_type = $event['event_type'] ?? 'BYO';
$event_style_detail = $event['event_style_detail'] ?? '';
$theme_description = $event['theme_description'] ?? '';
$bottle_rules = $event['bottle_rules'] ?? '';
$blind_policy = $event['blind_policy'] ?? 'none';
// Organizer note in events_new is 'memo' field input, but DB 'memo' stores (Note + Meta).
// So we need to separate them.
// In the partial, 'memo' variable should correspond to the "Organizer Note" input.
// The controller should split DB memo into "Note" and "Meta".

?>
<form method="post" class="bottle-form">
    <!-- 1. 基本情報 / Basic info -->
    <div class="form-section">
        <h3>基本情報 / Basic info</h3>

        <div class="form-group">
            <label>イベント名 / Event title <span style="color:var(--danger)">*</span></label>
            <input type="text" name="title" placeholder="例：第5回 ブルゴーニュ会" value="<?= h($title) ?>" required>
        </div>

        <div class="form-group">
            <label>サブタイトル / Subtitle</label>
            <input type="text" name="subtitle" placeholder="例：〜ジュヴレ・シャンベルタンの魅力〜" value="<?= h($subtitle) ?>">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>開催日 / Date <span style="color:var(--danger)">*</span></label>
                <!-- events_new used date input, prompt section 1 mentions datetime-local. 
                     If we use datetime-local, we combine date and start_time? 
                     events_new has separate Date and Start Time. 
                     The prompt Section 1 Example uses datetime-local for "event_date".
                     Unified form should probably follow the Prompt's suggestion if explicit.
                     "Date (datetime-local) ... input type='datetime-local' name='event_date'".
                     "event_date ... Y-m-d\TH:i".
                     If I switch to datetime-local, I should merge date+time.
                     Let's stick to the structure of `events_new.php` (Date + Time separate) OR follow Prompt (datetime-local)?
                     Prompt says: "Expect variable ... 'event_date' ... datetime-local formatted".
                     This suggests changing `events_new.php` logic to use datetime-local too.
                     I'll try to follow the Prompt's "datetime-local" instruction as it is specific involved in current request.
                     I will use `event_date` as datetime-local. 
                -->
                <input type="datetime-local" name="event_date" value="<?= h($event_date) ?>" required>
            </div>
            <!-- If using datetime-local, 'start_time' separate input is redundant unless for display only?
                 events_new had separate inputs. DB has `event_date` (datetime presumably).
                 If I use datetime-local, I don't need start_time input.
            -->
        </div>
    </div>

    <!-- 2. 会場・スタイル / Venue & style -->
    <div class="form-section">
        <h3>会場・スタイル / Venue & style</h3>

        <div class="form-row">
            <div class="form-group">
                <label>会場名 / Venue name</label>
                <input type="text" name="place" placeholder="例：Restaurant Vin" value="<?= h($place) ?>">
            </div>
            <div class="form-group">
                <label>エリア / Area</label>
                <input type="text" name="area" placeholder="例：六本木" value="<?= h($area) ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>想定人数 / Seats</label>
                <input type="number" name="seats" placeholder="例：8" value="<?= h($seats) ?>">
            </div>

            <div class="form-group">
                <label>DB登録タイプ / DB Type</label>
                <div class="radio-row">
                    <label><input type="radio" name="event_type" value="BYO" <?= $event_type === 'BYO' ? 'checked' : '' ?>> BYO
                        (持参)</label>
                    <label><input type="radio" name="event_type" value="ORG" <?= $event_type === 'ORG' ? 'checked' : '' ?>> ORG
                        (主催)</label>
                    <label><input type="radio" name="event_type" value="VENUE" <?= $event_type === 'VENUE' ? 'checked' : '' ?>>
                        VENUE (店舗)</label>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>イベントスタイル / Style Detail</label>
            <select name="event_style_detail">
                <option value="">選択してください</option>
                <option value="full_byo" <?= $event_style_detail === 'full_byo' ? 'selected' : '' ?>>Full BYO（全員持ち寄り）</option>
                <option value="half_byo" <?= $event_style_detail === 'half_byo' ? 'selected' : '' ?>>Half BYO（店ワイン＋持ち寄り）
                </option>
                <option value="no_byo" <?= $event_style_detail === 'no_byo' ? 'selected' : '' ?>>No BYO（主催者セレクト/ペアリング）</option>
            </select>
        </div>
    </div>

    <!-- 3. テーマ・ルール / Theme & rules -->
    <div class="form-section">
        <h3>テーマ・ルール / Theme & rules</h3>

        <div class="form-group">
            <label>テーマ詳細 / Theme description</label>
            <textarea name="theme_description" rows="3"
                placeholder="テーマについての詳しい説明"><?= h($theme_description) ?></textarea>
        </div>

        <div class="form-group">
            <label>持ち寄りルール / Bottle rules</label>
            <textarea name="bottle_rules" rows="3"
                placeholder="例：1人1本、予算1万円以上、2015年以降など"><?= h($bottle_rules) ?></textarea>
        </div>

        <div class="form-group">
            <label>ブラインド方針 / Blind policy</label>
            <select name="blind_policy">
                <option value="none" <?= $blind_policy === 'none' ? 'selected' : '' ?>>Label Open（ラベル出し）</option>
                <option value="semi" <?= $blind_policy === 'semi' ? 'selected' : '' ?>>Semi Blind（一部ブラインド）</option>
                <option value="full" <?= $blind_policy === 'full' ? 'selected' : '' ?>>Full Blind（完全ブラインド）</option>
            </select>
        </div>

        <!-- Toggle for Theme Fit -->
        <div class="form-group" style="margin-top:20px;">
            <label style="display:flex; align-items:center; cursor:pointer;">
                <input type="checkbox" name="show_theme_fit" value="1" <?= $show_theme_fit ? 'checked' : '' ?>
                    style="margin-right:8px;">
                Show Theme Fit / テーマ適合度を表示する
            </label>
        </div>
    </div>

    <!-- 4. 幹事メモ / Organizer note -->
    <div class="form-section">
        <h3>幹事メモ / Organizer note</h3>
        <div class="form-group">
            <label>管理者用メモ / Secret Note (Not visible to guests)</label>
            <textarea name="memo" rows="4" placeholder="予算管理、連絡事項など。ここに入力した内容は参加者には表示されません。"><?= h($memo) ?></textarea>
        </div>
    </div>

    <button type="submit" class="btn-pill btn-primary" style="width:100%">
        Save Event / イベントを保存
    </button>
</form>