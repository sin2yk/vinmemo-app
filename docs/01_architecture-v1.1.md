# VinMemo Architecture v1

VinMemo（ワインメモリー / VinuMemory）の全体構成メモ。

```mermaid
flowchart LR
  subgraph WebApp["VinuMemory Webフロント"]
    EL[Event Landing / Join<br/>参加登録フォーム]
    BE[Bottle Entry Form<br/>持ち寄りワイン登録]
    WL[WineList View<br/>ワインリスト表示]
    MP[My Page<br/>自分のイベント＆ボトル履歴]
  end

  subgraph Backend["VinuMemory API"]
    API_EVENTS[Events API]
    API_PARTICIPANTS[Participants API]
    API_BOTTLES[Bottle Entries API]
  end

  subgraph Auth["Firebase Auth"]
    AUTH[Login / User Identity]
  end

  subgraph DB["VinuMemory DB"]
    T_USERS[(users)]
    T_EVENTS[(events)]
    T_PARTS[(event_participants)]
    T_BOTTLES[(bottle_entries)]
  end

  EL --> API_EVENTS
  BE --> API_BOTTLES
  WL --> API_BOTTLES
  MP --> API_EVENTS
  MP --> API_BOTTLES

  API_EVENTS --> T_EVENTS
  API_PARTICIPANTS --> T_PARTS
  API_BOTTLES --> T_BOTTLES
  API_BOTTLES --> T_USERS
  API_EVENTS --> T_USERS

  WebApp --> AUTH
  AUTH --> T_USERS

  ```
