<?php

/**
 * @copyright  Bright Cloud Studio
 * @author     Bright Cloud Studio
 * @package    Contao Isotope Salsify
 * @license    LGPL-3.0+
 * @see        https://github.com/bright-cloud-studio/contao-isotope-salsify
 */

    /**
     * Step Seven - watch for stalled Salsify Requests
     *
     * Steps five and six only advance a SalsifyRequest once their work is written, so any failure -
     * a timeout, a fatal error, bad data that throws - leaves the request parked on the same status.
     * This step notices that and sends the Notification Center notification chosen on the request.
     *
     * Flow:
     * 1. Load every SalsifyRequest
     * 2. If its status moved since the last run, record the new status and restart the clock
     * 3. Skip it when idle, already alerted, still inside the limit, or no notification is chosen
     * 4. Otherwise send the notification, and flag the stall once an email actually went out
     */

    /** INITS AND INCLUDES - START **/
    use Bcs\Model\SalsifyRequest;
    use Contao\Config;
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
