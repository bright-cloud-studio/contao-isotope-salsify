<?php

namespace Bcs\Backend;

use Google;
use Contao\System;
use Contao\MemberModel;


class CronJobs extends System
{
    
    // If there are X $days_before the end of the month then send reminder emails to all psychologists
    public function sendReminderEmails(): void
    {
        
    }
}
