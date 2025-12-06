# VinMemo Architecture v1

VinMemo（ワインメモリー / VinuMemory）の全体構成メモ。

```mermaid
graph TB

  %% ===== Users =====
  subgraph Users["利用者（Users）"]
    Guest["ゲスト参加者\n招待URLから参加"]
    Member["登録ユーザー\nワイン履歴・ランキング"]
    Organizer["幹事 Organizer\nイベント作成・招待"]
    Admin["管理者 Admin\n運営・全体管理"]
  end

  %% ===== VinMemo App =====
  subgraph App["VinMemo Webアプリ\nPHP on Sakura VPS"]
    Front["フロントエンド UI\nブラウザ (PC / スマホ)"]
    API["バックエンド API\nPHP"]
  end

  %% ===== External Services =====
  subgraph External["外部サービス"]
    Firebase["Firebase Auth\n認証・ユーザー管理"]
    Stripe["Stripe\nサブスク決済"]
  end

  %% ===== Database =====
  subgraph DB["MySQL (VinMemo DB)"]
    UsersT["users\nユーザー"]
    EventsT["events\nワイン会イベント"]
    EntriesT["bottle_entries\n会で開けたボトル"]
    WinesT["wines\nワインマスタ"]
    ProducersT["producers\n生産者マスタ"]
    CellarT["cellar_moves\nセラー入出庫ログ"]
    PlansT["plans\n料金プラン"]
    SubsT["user_subscriptions\nユーザー契約"]
  end

  %% ===== Existing Excel Assets =====
  Excel["既存Excel\nセラー出庫・過去ワイン会リスト"]

  %% ユーザー → アプリ
  Guest --> Front
  Member --> Front
  Organizer --> Front
  Admin --> Front

  %% フロント ↔ バックエンド
  Front --> API
  API --> Front

  %% アプリ ↔ 外部サービス
  Front --> Firebase
  API --> Firebase
  API --> Stripe

  %% アプリ ↔ DB
  API --> DB

  %% DB内の関係（ざっくり）
  UsersT --> EventsT
  EventsT --> EntriesT
  EntriesT --> WinesT
  WinesT --> ProducersT
  UsersT --> SubsT
  PlansT --> SubsT
  CellarT --> WinesT
  CellarT --> EventsT

  %% Excel → DB への移行
  Excel --> CellarT

  ```
