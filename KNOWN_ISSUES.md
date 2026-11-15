# KNOWN ISSUES & TECHNICAL DEBT TRACKER

## Phase 17 Status: Complete & Verified — No Active Blockers

### Notes & Verifications:
1. **Multi-Tenant Notification Delivery**:
   - Notifications table is strictly partitioned by `tenant_id`. In-app notification queries join on `[tenant_id, notifiable_id]` to guarantee that Tenant A cannot inspect or mark Tenant B's alerts.
2. **Automated Alert Recipient Resolution**:
   - `AutomationService::getAlertRecipients` automatically identifies all active business owners (`is_tenant_owner == true`) and users holding `owner` or `manager` roles, falling back safely to active tenant users to ensure no critical stock or financial warnings are dropped.
3. **External Gateway Security**:
   - SMS and WhatsApp credentials are stored inside `tenants.settings['notifications']`, ensuring external API keys are isolated per business and never hardcoded in source control.
4. **Scheduled Artisan Commands**:
   - `veggitable:daily-summaries` and `veggitable:check-subscriptions` process active tenants safely with proper exception trapping, preventing a failure in one tenant from interrupting processing for others.
5. **No Active Blockers**:
   - All 137 automated tests pass cleanly with 773 assertions across all 17 completed phases. Code conforms strictly to Laravel Pint standards.
