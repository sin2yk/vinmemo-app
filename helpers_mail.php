<?php
// helpers_mail.php

/**
 * Send an email with the Edit Link to the guest.
 * Returns true if accepted for delivery, false otherwise.
 * Does NOT throw exceptions; failures are silent to the caller (so UI can proceed).
 */
function sendEditLinkEmail(
    string $to,
    array $event,
    array $bottle,
    string $editUrl,
    bool $isUpdate = false
): bool {
    if (empty($to) || empty($editUrl)) {
        return false;
    }

    // Ensure internal encoding is UTF-8
    if (function_exists('mb_internal_encoding')) {
        mb_internal_encoding("UTF-8");
    }

    // Set language to Japanese for proper header encoding
    if (function_exists('mb_language')) {
        mb_language("Japanese");
    }

    $eventName = $event['name'] ?? $event['title'] ?? ''; // Handle 'name' or 'title' variance
    // Handle event date/start_at naming variance
    $eventDate = $event['start_at'] ?? $event['event_date'] ?? '';

    // Retrieve bottle details
    $ownerLabel = $bottle['owner_label'] ?? 'Guest';
    $producerName = $bottle['producer_name'] ?? '';
    $wineName = $bottle['wine_name'] ?? '';
    $wineFullName = trim($producerName . ' ' . $wineName);

    // Subject
    $subjectPrefix = $isUpdate ? '【VinMemo】ボトル情報更新' : '【VinMemo】ボトル登録完了';
    $subject = $subjectPrefix . 'と編集用リンクのご案内';

    // Body
    $actionStr = $isUpdate ? '更新' : '登録';

    // HEREDOC is cleaner
    $body = <<<EOT
{$ownerLabel} 様

VinMemo にて、以下のボトル情報が{$actionStr}されました。

■ イベント
  名称：{$eventName}
  日時：{$eventDate}

■ ボトル
  持ち主：{$ownerLabel}
  ワイン：{$wineFullName}

このボトルは、次のURLからいつでも編集できます：
{$editUrl}

※このメールは送信専用です。心当たりがない場合は破棄してください。

VinMemo
EOT;

    // From Header
    // TODO: Adjust strictly for production if needed.
    // For localhost/XAMPP, 'From' is important.
    $headers = "From: VinMemo <no-reply@vinmemo.local>\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8";

    // Attempt send
    // Suppress warnings just in case, though mb_send_mail usually returns false on failure.
    try {
        $result = @mb_send_mail($to, $subject, $body, $headers);
        // Debug Log
        error_log("VinMemo Mail Debug: Sending to [{$to}] - Result: " . ($result ? 'TRUE (Accepted)' : 'FALSE (Rejected)'));
        return $result;
    } catch (Throwable $e) {
        // Log error if possible, but do not crash
        error_log("Mail send failed: " . $e->getMessage());
        return false;
    }
}
