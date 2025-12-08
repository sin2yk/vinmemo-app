# BYO Concepts for VinMemo

## 1. Analysis of BYO Reference

### Roles
- **Organizer (Admin)**:
  - Identified by `$_SESSION['role'] === 'organizer'`.
  - Has access to `list_admin.php` (full view, edit/delete all).
  - Sees full details of bottles (no masking).
- **Participant (Guest)**:
  - Default role.
  - Sees `list.php` (blind view).
  - "Blind" bottles have restricted fields (Producer, Wine Name, etc.) masked as "???".
  - Cannot edit/delete bottles (except potentially their own, though BYO reference `list.php` doesn't strictly show edit links for own bottles, `register.php` might be different).

### Layout & Navigation
- **Header**:
  - Site Title ("BYOワイン会ボトル登録" -> "VinMemo")
  - User Info: "Logged in as X (Role)"
  - Conditional Link: "Organize View" if organizer.
  - Link: Logout.
- **Nav**:
  - Links to Index, Register, List, Admin List.
- **Bottle List**:
  - Table format.
  - Summary section at top (Total bottles, breakdown by type/price, average ratings).
  - Masking function `mask_if_blind($value, $flag)`.

## 2. Mapping to VinMemo

### Roles in VinMemo
VinMemo uses `event_participants` table (conceptually).
- **Organizer**: `event_participants.role_in_event` = 'organizer'.
- **Guest**: `event_participants.role_in_event` != 'organizer' OR no entry (fallback).
- **Bottle Owner**: `bottle_entries.brought_by_user_id` matches current user.

### Shared Layout
Create `layout/header.php` and `layout/footer.php`.

**`layout/header.php`**:
- Doctype, HTML, Head (CSS `style.css`).
- Body start, Main Container start.
- Header Content:
    - Logo/Title (VinMemo).
    - Navigation: Home, Events, MyPage, Logout.
    - User/Role display (optional, or put in MyPage/Nav).

**`layout/footer.php`**:
- Close container, body, html.
- Copyright/Footer text.

**Pages to update**:
- `index.php` (Login/Landing) - maybe distinct, but could share basics.
- `home.php`
- `events.php`, `events_new.php`, `event_show.php`
- `bottle_new.php`, `bottle_edit.php`
- `mypage.php`

### Organizer vs Participant View (`event_show.php`)
- **Logic**:
    - Fetch event role for current user.
- **Organizer View**:
    - Show Summary Panel (Total bottles, counts by color/type).
    - Show full bottle list with Edit/Delete buttons.
- **Participant View**:
    - Show bottle list.
    - If blind mode is active (future feature? or per bottle?), mask fields. *Note: BYO has blind flags per bottle. VinMemo schema has `is_blind` in `bottle_entries`.*
    - Show "Edit" only for own bottles.
    - Hide "Delete" unless owner.

### Bottle List UI
- **Columns**: Owner, Wine Name, Vintage, Region, Type, Price, Memo.
- **Blind Mode**: Check `is_blind`. If true, mask Producer/Wine/Region/Price/Memo for non-organizers and non-owners.

### Implementation Steps
1.  **Layout**: Create `layout/` files. Refactor all pages to use them.
2.  **Helpers**: Create `helpers.php` for `h()` (escape), `getEventRole($pdo, $evtId, $uid)`.
3.  **Event Show**: Redesign to match BYO `list.php`/`list_admin.php` hybrid.
    - Add logic to determine role.
    - Add Summary section (Organizer only, or public summary?). *BYO shows summary to everyone in `list.php`.* -> **Plan: Show summary to everyone, but maybe more detailed for organizer.**
    - Enhance Table/Card view.
4.  **My Page**: Standardize look with header.
