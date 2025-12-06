# VinMemo ER 図 v1.1

```mermaid
flowchart LR
  USERS[users<br/>id, firebase_uid, ...]
  EVENTS[events<br/>id, organizer_user_id, ...]
  PARTICS[event_participants<br/>...]

  USERS --> EVENTS
  EVENTS --> PARTICS


  USERS {
    int id
    string firebase_uid
    string display_name
    string email
    int birth_year
    boolean is_organizer
    string plan
    datetime created_at
    datetime updated_at
  }

  EVENTS {
    int id
    int organizer_user_id
    string public_token
    string name
    string description
    datetime date_time
    string venue_name
    string venue_address
    string theme
    string rules
    string event_style
    decimal fee_per_person
    int expected_bottle_count
    string series_name
    int series_round
    string eligibility_type
    string eligibility_summary
    string dress_code_type
    string dress_code_summary
    int max_participants
    boolean is_public
    datetime created_at
    datetime updated_at
  }

  EVENT_PARTICIPANTS {
    int id
    int event_id
    int user_id
    string guest_email
    string display_name
    string role
    string status
    datetime created_at
    datetime updated_at
  }

  PRODUCERS {
    int id
    string name
    string country
    string region
    string subregion
    string commune_or_village
    string website_url
    boolean is_verified
    datetime created_at
    datetime updated_at
  }

  WINES {
    int id
    int producer_id
    string name_canonical
    string country
    string region
    string appellation
    string color
    string classification_label
    boolean is_grand_cru
    datetime created_at
    datetime updated_at
  }

  BOTTLE_ENTRIES {
    int id
    int event_id
    int participant_id
    int user_id
    string guest_email
    int wine_id
    string wine_label_text
    int vintage
    string type
    string price_band
    int theme_fit_score
    boolean hide_producer
    boolean hide_wine_name
    boolean hide_vintage
    boolean hide_price_band
    string edit_token
    datetime created_at
    datetime updated_at
  }

  BOTTLE_PHOTOS {
    int id
    int bottle_entry_id
    string kind
    string url
    string visibility
    int uploader_user_id
    boolean is_public_approved_by_organizer
    datetime created_at
  }

  SUBSCRIPTION_PLANS {
    int id
    string code
    string name
    string description
  }

```