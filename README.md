# Bright Cloud Studios Contao Isotope Salsify
Integrates Salsify into Contao and Isotope

## Notifications

Step seven (`7.notify.stalled.requests.php`) sends two Notification Center notifications, each chosen per Salsify Request:

- **Completed** - sent when a Salsify Request finishes importing a file, with the file name and product counts.
- **Stalled** - sent when a Salsify Request sits on the same step for more than 30 minutes. That is the sign a step failed partway through and keeps retrying.

Requires Notification Center 1.x (`terminal42/notification_center`), which Isotope already installs.

### Setup

1. Run the database update (Contao Manager, the install tool, or `vendor/bin/contao-console contao:migrate`) to add the new `tl_salsify_request` fields.
2. In Notification Center, create a notification of type **Salsify Request completed** and/or **Salsify Request stalled**. Give each a published email message, with a language that has **Fallback** ticked.
3. In the Cron Scheduler, add a job that runs every minute:
   `vendor/bright-cloud-studio/contao-isotope-salsify/src/Resources/public/php/7.notify.stalled.requests.php`
4. On each Salsify Request, choose the notifications under **Completed Notification** and **Stall Notification**. Leave either empty to turn it off for that request.

The 30-minute stall limit is `$stallSeconds` at the top of the step seven script.

### Completed tokens

- `##request_id##`, `##request_name##`, `##request_file##` - which request, and the Salsify file it imported
- `##import_completed##` - when the import finished
- `##import_minutes##` - minutes from the file arriving to the import finishing
- `##salsify_products##`, `##salsify_published##`, `##salsify_unpublished##` - products in the file, and how the publish key split them
- `##salsify_skipped##` - published products that couldn't be created because they have no category page
- `##isotope_products##`, `##isotope_singles##`, `##isotope_parents##`, `##isotope_variants##` - Isotope products written, and how they split
- `##admin_email##` - the System Settings address, for the address fields

### Stalled tokens

- `##request_id##`, `##request_name##`, `##request_status##`, `##request_file##`, `##stalled_minutes##`, `##admin_email##`
