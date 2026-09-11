# Bright Cloud Studios Contao Isotope Salsify
Integrates Salsify into Contao and Isotope

## Stall notifications

Step seven (`7.notify.stalled.requests.php`) watches every Salsify Request and sends an email through Notification Center when one sits on the same step for more than 30 minutes. That is the sign a step failed partway through and keeps retrying.

Requires Notification Center 1.x (`terminal42/notification_center`), which Isotope already installs.

### Setup

1. Run the database update (Contao Manager, the install tool, or `vendor/bin/contao-console contao:migrate`) to add the new `tl_salsify_request` fields.
2. In Notification Center, create a notification of type **Salsify → Salsify Request stalled** and give it an email message. Available tokens: `##request_id##`, `##request_name##`, `##request_status##`, `##request_file##`, `##stalled_minutes##`, `##admin_email##`.
3. In the Cron Scheduler, add a job that runs every minute:
   `vendor/bright-cloud-studio/contao-isotope-salsify/src/Resources/public/php/7.notify.stalled.requests.php`
4. On each Salsify Request, choose that notification under **Stall Notification**. Leave it empty to turn alerts off for that request.

The 30-minute limit is `$stallSeconds` at the top of the step seven script.
