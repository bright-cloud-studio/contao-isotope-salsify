<?php

/**
 * @copyright  Bright Cloud Studio
 * @author     Bright Cloud Studio
 * @package    Contao Isotope Salsify
 * @license    LGPL-3.0+
 * @see        https://github.com/bright-cloud-studio/contao-isotope-salsify
 */

    /**
     * Step Seven - Salsify Request notifications
     *
     * Sends the two Notification Center notifications chosen on each SalsifyRequest:
     * - Completed: step six stamps a request when its import finishes, and this step reports it with
     *   the file and product counts read back from the database
     * - Stalled: steps five and six only advance a request once their work is written, so any failure -
     *   a timeout, a fatal error, bad data that throws - leaves it parked on the same status. This step
     *   notices when one sits still for too long
     *
     * Flow:
     * 1. Load every SalsifyRequest
     * 2. If step six stamped a finished import that hasn't been reported, send the completed report
     * 3. If its status moved since the last run, record the new status and restart the stall clock
     * 4. Skip the stall check when idle, already alerted, still inside the limit, or no notification is chosen
     * 5. Otherwise send the stalled alert, and flag the stall once an email actually went out
     */

    /** INITS AND INCLUDES - START **/
    use Bcs\Model\SalsifyProduct;
    use Bcs\Model\SalsifyRequest;
    use Contao\Config;
    use Contao\Database;
    use Contao\Date;
    use Contao\StringUtil;
    use NotificationCenter\Model\Notification;

    // How long a SalsifyRequest may sit on one step before it counts as stalled. A healthy import
    // moves every minute or so on a minutely cron, so this leaves plenty of headroom
    $stallSeconds = 30 * 60;

    $debugMode = true;
    if($debugMode)
        $log = fopen($_SERVER['DOCUMENT_ROOT'] . '/../salsify_logs/step_seven_'.date('m_d_y').'.txt', "a+") or die("Unable to open file!");
    /** INITS AND INCLUDES - STOP **/


    // Compare each SalsifyRequest's status against what we saw last run
    $salsifyRequests = SalsifyRequest::findAll();
    if($salsifyRequests) {
        foreach($salsifyRequests as $sr) {

            // Report a finished import that step six stamped and we haven't sent yet. This runs first because
            // the stall check below skips idle requests, and a finished import is always back to idle
            if($sr->completed_tstamp && !$sr->completed_notified) {
                $notification = Notification::findByPk($sr->completed_notification);
                if($notification !== null) {
                    debugStepSeven($debugMode, $log, "[SalsifyRequest ID: " . $sr->id . "] Import completed, sending Notification ID: " . $notification->id);

                    $sent = $notification->send(buildCompletedTokens($sr));

                    // Same rule as the stall alert - only flag it once an email actually went out
                    if(in_array(true, $sent, true)) {
                        $sr->completed_notified = '1';
                        $sr->save();
                    } else {
                        debugStepSeven($debugMode, $log, "\tNothing was sent, check that the notification has an email message set up");
                    }
                }
            }

            // Status moved since last run, so the pipeline is healthy - restart the clock
            if($sr->stall_status != $sr->status) {
                debugStepSeven($debugMode, $log, "[SalsifyRequest ID: " . $sr->id . "] Status moved from '" . $sr->stall_status . "' to '" . $sr->status . "', restarting the stall timer");

                $sr->stall_status = $sr->status;
                $sr->stall_tstamp = time();
                $sr->stall_notified = '';
                $sr->save();
                continue;
            }

            // Skip requests that are idle between imports or have already been alerted on
            if($sr->status == 'awaiting_new_file' || $sr->stall_notified)
                continue;

            // Skip requests still inside the limit
            $stalledFor = time() - (int) $sr->stall_tstamp;
            if($stalledFor < $stallSeconds)
                continue;

            // Skip requests with no notification chosen - alerts are off for them
            $notification = Notification::findByPk($sr->notification);
            if($notification === null)
                continue;

            debugStepSeven($debugMode, $log, "[SalsifyRequest ID: " . $sr->id . "] STALLED on '" . $sr->status . "' for " . intdiv($stalledFor, 60) . " minutes, sending Notification ID: " . $notification->id);

            $sent = $notification->send(array
            (
                'request_id'      => $sr->id,
                'request_name'    => $sr->request_name,
                'request_status'  => $sr->status,
                'request_file'    => $sr->file_url,
                'stalled_minutes' => intdiv($stalledFor, 60),
                'admin_email'     => Config::get('adminEmail')
            ));

            // Only flag the stall once an email actually went out. A notification with no message set up
            // yet keeps retrying each run instead of silently swallowing the only alert
            if(in_array(true, $sent, true)) {
                $sr->stall_notified = '1';
                $sr->save();
            } else {
                debugStepSeven($debugMode, $log, "\tNothing was sent, check that the notification has an email message set up");
            }
        }
    }

    // Close our log file
    if($debugMode)
        fclose($log);


    /** HELPER FUNCTIONS **/
    function debugStepSeven($debugMode, $log, $message) {
        // File only, unlike the other steps - echoing would make BugBuster log every run as 'failed'
        if($debugMode)
            fwrite($log, $message . "\n");
    }

    // Gather the file and product counts for a finished import. Everything is read back from the database,
    // so the report shows what actually landed rather than what the steps meant to do
    function buildCompletedTokens($sr) {

        // Salsify side - everything in the file, and what the publish key switched on
        $salsifyProducts = SalsifyProduct::countBy('pid', $sr->id);
        $salsifyPublished = SalsifyProduct::countBy(array('pid=?', "published='1'"), array($sr->id));

        // Published products step five had to skip - it only creates products that have a category page
        $salsifySkipped = SalsifyProduct::countBy(array('pid=?', "published='1'", "(category_page IS NULL OR category_page='')"), array($sr->id));

        // Isotope side - split what step five wrote into singles, variant parents and variants
        $singles = $parents = $variants = 0;
        $ids = array_map('intval', StringUtil::deserialize($sr->generated_isotope_products, true));
        if($ids) {
            $isotopeProducts = Database::getInstance()->query("SELECT pid, sku FROM tl_iso_product WHERE id IN (" . implode(',', $ids) . ")");
            while($isotopeProducts->next()) {
                if($isotopeProducts->pid > 0)
                    $variants++;
                else if(substr($isotopeProducts->sku, -7) == '_parent')
                    $parents++;
                else
                    $singles++;
            }
        }

        return array
        (
            'request_id'          => $sr->id,
            'request_name'        => $sr->request_name,
            'request_file'        => $sr->file_url,
            'import_completed'    => Date::parse(Config::get('datimFormat'), $sr->completed_tstamp),
            'import_minutes'      => intdiv((int) $sr->completed_tstamp - (int) $sr->file_date, 60),
            'salsify_products'    => $salsifyProducts,
            'salsify_published'   => $salsifyPublished,
            'salsify_unpublished' => $salsifyProducts - $salsifyPublished,
            'salsify_skipped'     => $salsifySkipped,
            'isotope_products'    => $singles + $parents + $variants,
            'isotope_singles'     => $singles,
            'isotope_parents'     => $parents,
            'isotope_variants'    => $variants,
            'admin_email'         => Config::get('adminEmail')
        );
    }
