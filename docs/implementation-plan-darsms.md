# DarSMS Bulk SMS Implementation Plan

**Project:** KANISA church management system  
**Prepared:** 21 November 2026  
**Status:** Plan only; implementation has not started.

## Goal

Integrate DarSMS for bulk SMS and make it possible to choose a church department (idara) as the audience. The system should retrieve the current members and their phone numbers from the database, send the message through DarSMS, and keep a traceable send record.

## Current project findings

- This is a Laravel application.
- `users.phone` already stores member phone numbers.
- `department_user` already associates users with one or more departments, so one member can belong to multiple idara.
- The dashboard already has an SMS send route and a department-based recipient lookup.
- SMS sending currently uses `App\Services\BeemSmsService` and Beem credentials from `config/services.php`.
- `sms_logs` already stores the department, sender, message, recipient counts, result, and provider response. A recent migration added Beem-specific response naming.

**Contact storage recommendation:** Treat `users.phone` plus `department_user` as the authoritative phone directory instead of creating a second table containing duplicate numbers. This keeps phone updates and department membership in one place. If the church later needs contacts who are not system users, add a separate contacts table and a department-contact pivot as a distinct, optional scope.

## Implementation phases

### 1. DarSMS configuration and provider service

- Add `DARSMS_API_KEY`, `DARSMS_SENDER_ID`, and an optional `DARSMS_BASE_URL` to `.env.example` and `config/services.php`; do not place credentials in source control.
- Implement a DarSMS service using HTTPS JSON requests to `https://dev.darsms.co.tz/api/v1/integrations/sms/send` and the `X-API-Key` header.
- Send `senderId`, `to` (recipient list), and `message` as documented by DarSMS.
- Normalize Tanzanian numbers to `255...`, reject malformed values, and remove duplicate recipients before sending.
- Handle provider errors/timeouts safely; never log the API key. Preserve useful response details and returned `batchId`/`messageIds` in the send record.
- Decide whether to replace Beem entirely or retain a configurable provider switch. The default implementation should avoid unexpected fallback sends to a second paid provider.

### 2. Phone directory and department audience

- Keep `users.phone` as the canonical stored number and `department_user` as the idara assignment source.
- Ensure creating or editing a member saves their number on the user record; department assignments continue to use the existing pivot.
- Recipient lookup for an idara should include eligible members linked to that department, exclude administrative/service accounts, ignore blank or invalid numbers, and deduplicate numbers.
- Show the resolved recipient count before submission so the sender can confirm the audience.
- Consider an SMS opt-out field and filtering it from recipients if the church needs a managed opt-out workflow; do not infer consent from department membership alone.

### 3. Sending flow and access control

- Keep the existing authenticated send flow and department selector.
- Confirm authorization is restricted to users allowed to message the selected department (including existing admin/pastor rules).
- Validate message text and provider-specific length/encoding constraints. Do not assume one SMS segment: DarSMS bills per recipient and message segment.
- Send to the selected department's current eligible numbers and report success/failure clearly to the user.
- Handle an empty audience without making a provider request.

### 4. Send history and delivery tracking

- Generalize Beem-specific `sms_logs` response fields to provider-neutral names, retaining existing historical records through a safe migration.
- Record provider, batch ID, recipient count, sent/queued/failed counts, response metadata, and error details. Avoid storing unnecessary personal data or API secrets.
- Add DarSMS status lookup for returned message IDs if per-recipient delivery reporting is required; represent queued/sent/delivered/failed states accurately rather than treating HTTP acceptance as delivery.
- Keep audit-log entries for who sent a message, to which department, and when.

### 5. Dashboard and operational setup

- Update SMS UI wording and history to identify DarSMS and show the selected idara and recipient count.
- Document the setup steps: create DarSMS API key, configure an approved sender ID, set environment variables, and fund the DarSMS account.
- Keep the existing Beem integration available during transition until DarSMS configuration and a successful controlled send are confirmed.

## Data and migration approach

1. Do not copy existing member numbers into a redundant phone-number table.
2. Preserve `users.phone` and the many-to-many department assignments as the source of truth.
3. Add only the provider-neutral SMS log fields needed for DarSMS and delivery tracking; backfill provider as `beem` for historical rows if that field is introduced.
4. If non-member phone contacts are later required, create a separate contact entity with department membership, normalized unique number, active/opt-out state, and provenance/audit fields.

## Acceptance criteria

- An authorized sender can choose an idara and see the count of eligible phone numbers before sending.
- The recipient list is assembled automatically from current member records and that idara's assignments.
- Missing, malformed, opted-out (if implemented), or duplicate numbers are excluded and accounted for in the result.
- DarSMS receives the documented authenticated request; provider errors are surfaced without exposing credentials.
- Each attempt is recorded with its department, sender, provider, timestamp, and useful delivery/send status.
- Existing Beem-era SMS history and existing member/department data remain intact.

## External API reference

- [DarSMS SMS API integration guide](https://www.darsms.co.tz/blog/sms-api-integration-guide-tanzania) — documents the API key header, send endpoint and payload, batch/message IDs, sender IDs, and status checks.
- API base URL shown in the guide: `https://dev.darsms.co.tz/api/v1`.

## Current scope boundary

This change creates the plan file only. No application code, database schema, credentials, or provider settings have been changed.
