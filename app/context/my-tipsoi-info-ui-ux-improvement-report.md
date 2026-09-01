# My Tipsoi Info Dashboard — Navigation & UI/UX Improvement Report

**Date Created:** September 1, 2026  
**Status:** Backlog / Planned for Future Implementation  
**Location:** `app/context/my-tipsoi-info-ui-ux-improvement-report.md`  
**Target View:** `app/views/employee-modules/my-tipsoi-info.php`

---

## 1. Executive Summary

The `My Tipsoi Information` module (`my-tipsoi-info`) serves as the central self-service and managerial hub for all Tipsoi biometric attendance, leave, late excuses, missing punch, and manual entry features.

During feature expansion, all functionalities were added as top-level navigation tabs. This document outlines the current UI/UX audit, identified bottlenecks, and actionable design proposals to be implemented in an upcoming UI refinement sprint.

---

## 2. Current Navigation Structure Audit

Currently, there are **9 top-level horizontal tabs**:

1. 👤 **My Profile** _(Employee general details, approvers list, RFID, shifts)_
2. 📅 **Attendance Data** _(Monthly calendar log, in/out times, working hours, working status)_
3. 📊 **My Leave Balance** _(Leave categories allocation, used days, remaining balances, ledger)_
4. ⏳ **Pending Requests** _(Logged-in user's own submitted applications awaiting approval)_
5. ✅ **Approval Pending** _(Requests submitted by supervisees waiting for this manager's approval)_
6. ➕ **Apply Leave** _(Leave application form with category balance preview & live duration calculator)_
7. ⏰ **Late Request** _(Late arrival excuse submission form & historical log)_
8. 📜 **History** _(Unified historical log for leaves, lates, manual entries, and mobile punches)_
9. 📝 **Manual Entry** _(Manual in/out attendance, device/mobile missing punch, and field visits)_

---

## 3. Identified Problems & Limitations

### 1. Visual Clutter & Horizontal Crowding

- Having 9 tabs in a single linear bar creates significant visual noise.
- On laptops (1366x768, 1440x900) or tablets, the tabs either wrap into multiple awkward lines or overflow horizontally.

### 2. Lack of Semantic Categorization

The tabs currently mix three completely different user intents in one line:

- **Information & History View:** `My Profile`, `Attendance Data`, `My Leave Balance`, `History`
- **Application / Action Forms:** `Apply Leave`, `Late Request`, `Manual Entry`
- **Managerial & Personal Approvals:** `Pending Requests`, `Approval Pending`

### 3. Missing Live Badge Indicators

- Neither `Pending Requests` nor `Approval Pending` display live numerical badges (e.g., `🟠 2` or `🔴 5`). Users cannot immediately know if action is required without clicking each tab.

### 4. Legacy Bootstrap Tab Styling

- The current active tab indicator uses standard square borders, which contrasts with the modern card and shadow design of the rest of the ERP.

---

## 4. Proposed Solutions & Design Proposals

### Proposal 1: Modern Floating Pill Bar with Live Badge Counters (Recommended)

Refactor the tab navigation into an ultra-modern, rounded **Pill Container** with:

- **Segmented Pills:** Soft background colors, smooth hover states, and active pills with subtle drop-shadow.
- **Live Counter Badges:**
  - `Pending Requests` -> `<span class="badge bg-warning text-dark ms-1 rounded-pill">2</span>`
  - `Approval Pending` -> `<span class="badge bg-danger text-white ms-1 rounded-pill">5</span>`
- **Responsive Mobile Touch Scroll:** CSS `flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch;` with hidden scrollbars so the bar never breaks or wraps on smaller screens.

---

### Proposal 2: Categorized 5-Tab Layout with "Quick Action" Dropdown

Condense the 9 tabs into **5 core tabs** and **1 unified Action dropdown button**:

- **Tabs:**
  1. 👤 `My Profile`
  2. 📅 `Attendance & Ledger`
  3. 📊 `Leave Balance`
  4. 📜 `History Log`
  5. 🔔 `Approvals & Requests` _(with sub-tab switch for "My Pending" vs "To Approve")_
- **Right-Aligned Quick Action Button:**
  - `➕ Apply / Request ▾`
    - 🌴 _Apply Leave_
    - ⏰ _Late Excuse_
    - 📝 _Manual Attendance / Missing Punch_
    - 🚗 _Field Visit Log_

---

## 5. Technical Implementation Checklist (For Future Sprint)

- [ ] Update `#tipsoiTabs` container styles in `app/views/employee-modules/my-tipsoi-info.php`.
- [ ] Add live counter badges fetched from `GET /api/employee/tipsoi-pending-approvals`.
- [ ] Add responsive CSS rules for horizontal pill scrolling on mobile/tablet.
- [ ] Test active hash routing (`#profile`, `#attendance`, `#apply-leave`, `#history`, `#manual-entry`).
