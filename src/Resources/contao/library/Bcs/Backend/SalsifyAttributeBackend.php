<?php

namespace Bcs\Backend;

use Contao\Backend;
use Contao\BackendTemplate;
use Contao\Image;
use Contao\Input;
use Contao\DataContainer;
use Contao\StringUtil;
use Contao\System;

use Bcs\Model\Transaction;
use Bcs\Model\Assignment;
use Bcs\Model\District;
use Bcs\Model\School;
use Bcs\Model\Service;
use Bcs\Model\Student;


class TransactionBackend extends Backend
{
    // Create 'Invoice' DCAs for Psychologists
    public function createTransaction(DataContainer $dc) {
        
        // do nothing if we havent saved this record
        if (!$dc->activeRecord)
		{
			return;
		}
		

        // If we have not yet created the Invoices for this request
        if($dc->activeRecord->pid != '') {
            
            $assignment = Assignment::findOneBy('id', $dc->activeRecord->pid);
            $student = Student::findOneBy('id', $assignment->student);

            $transaction = Transaction::findOneBy('id', $dc->activeRecord->id);
            $transaction->lasid = $student->lasid;
            $transaction->sasid = $student->sasid;
            $transaction->save();

        }

    }
  
	public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
	{
        if (strlen(Input::get('tid')))
		{
			$this->toggleVisibility(Input::get('tid'), (Input::get('state') == 1), (@func_get_arg(12) ?: null));
			$this->redirect($this->getReferer());
		}

		$href .= '&amp;tid='.$row['id'].'&amp;state='.($row['published'] ? '' : 1);

		if (!$row['published'])
		{
			$icon = 'invisible.gif';
		}

		return '<a href="'.$this->addToUrl($href).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
	}	
	

	public function toggleVisibility($intId, $blnVisible, DataContainer $dc=null)
	{
		// Trigger the save_callback
		if (is_array($GLOBALS['TL_DCA']['tl_listing']['fields']['published']['save_callback']))
		{
			foreach ($GLOBALS['TL_DCA']['tl_listing']['fields']['published']['save_callback'] as $callback)
			{
				if (is_array($callback))
				{
					$this->import($callback[0]);
					$blnVisible = $this->$callback[0]->$callback[1]($blnVisible, ($dc ?: $this));
				}
				elseif (is_callable($callback))
				{
					$blnVisible = $callback($blnVisible, ($dc ?: $this));
				}
			}
		}

		// Update the database
		$this->Database->prepare("UPDATE tl_transaction SET tstamp=". time() .", published='" . ($blnVisible ? 1 : '') . "' WHERE id=?")->execute($intId);
		System::getContainer()->get('monolog.logger.contao.cron')->info('A new version of record "tl_transactions.id='.$intId.'" has been created'.$this->getParentEntries('tl_listing', $intId));
	}
	
	public function exportListings()
	{
		$objListing = Transactions::findAll();
		$strDelimiter = ',';
	
		if ($objListing) {
			$strFilename = "Transactions_" .(date('Y-m-d_Hi')) ."csv";
			$tmpFile = fopen('php://memory', 'w');
			
			$count = 0;
			while($objListing->next()) {
				$row = $objListing->row();
				if ($count == 0) {
					$arrColumns = array();
					foreach ($row as $key => $value) {
						$arrColumns[] = $key;
					}
					fputcsv($tmpFile, $arrColumns, $strDelimiter);
				}
				$count ++;
				fputcsv($tmpFile, $row, $strDelimiter);
			}
			
			fseek($tmpFile, 0);
			
			header('Content-Type: text/csv');
			header('Content-Disposition: attachment; filename="' . $strFilename . '";');
			fpassthru($tmpFile);
			exit();
		} else {
			return "Nothing to export";
		}
	}
	
	public function generateAlias($varValue, DataContainer $dc)
	{
		$autoAlias = false;
		
		// Generate an alias if there is none
		if ($varValue == '')
		{
			$autoAlias = true;
			$varValue = standardize(StringUtil::restoreBasicEntities($dc->activeRecord->name));
		}

		$objAlias = $this->Database->prepare("SELECT id FROM tl_transaction WHERE id=? OR alias=?")->execute($dc->id, $varValue);

		// Check whether the page alias exists
		if ($objAlias->numRows > 1)
		{
			if (!$autoAlias)
			{
				throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
			}

			$varValue .= '-' . $dc->id;
		}

		return $varValue;
	}



    // Get Psychologists as select menu
    public function getPsychologists(DataContainer $dc) { 

        // Hold the psys
        $psychologists = array();

        // Use the DB to grab all of our enabled members, aka our psychologists
		$this->import('Database');
		$result = $this->Database->prepare("SELECT * FROM tl_member WHERE disable=0 ORDER BY firstname ASC")->execute();
		while($result->next())
		{
            // Add ti array with ID as the value and firstname lastname as the label
            $psychologists = $psychologists + array($result->id => ($result->firstname . " " . $result->lastname));   
		}

		return $psychologists;
	}
    

    // Get Services as select menu
    public function getServices(DataContainer $dc) { 

        // Hold the psys
        $services = array();

        // Use the DB to grab all of our enabled members, aka our psychologists
		$this->import('Database');
		$result = $this->Database->prepare("SELECT * FROM tl_service WHERE published=1 ORDER BY name ASC")->execute();
		while($result->next())
		{
            // Add ti array with ID as the value and firstname lastname as the label
            $services = $services + array($result->service_code => $result->name);   
		}

		return $services;
	}






    public function getAssignmentDetails(DataContainer $dc)
    {
        // Stores our template values
        $assignment_details = [];

        // Get our Assignment
        $assignment = Assignment::findOneBy('id', $dc->activeRecord->pid);
        $assignment_details['assignment_id']['label'] = "Assignment ID";
        $assignment_details['assignment_id']['value'] = $assignment->id;
        
        // Date Created
        $assignment_details['date_created']['label'] = "Date Created";
        $assignment_details['date_created']['value'] = $assignment->date_created;
        
        // Date 30 Days
        $assignment_details['date_30_day']['label'] = "Date 30 Days";
        $assignment_details['date_30_day']['value'] = $assignment->date_30_day;
        
        // Date 45 Days
        $assignment_details['date_45_day']['label'] = "Date 45 Days";
        $assignment_details['date_45_day']['value'] = $assignment->date_45_day;
        
        // District
        $district = District::findOneBy('id', $assignment->district);
        $assignment_details['district']['label'] = "District";
        $assignment_details['district']['value'] = $district->district_name;
        
        // School
        $school = School::findOneBy('id', $assignment->school);
        $assignment_details['school']['label'] = "School";
        $assignment_details['school']['value'] = $school->school_name;
        
        // Student
        $student = Student::findOneBy('id', $assignment->student);
        $assignment_details['student']['label'] = "Student";
        $assignment_details['student']['value'] = $student->name;
        
        // Initial / Re-eval
        $assignment_details['initial_reeval']['label'] = "Initial / Re-Eval";
        $assignment_details['initial_reeval']['value'] = $this->getInitialReeval($assignment->initial_reeval);
        
        // Type of Testing
        $service = Service::findOneBy('id', $assignment->type_of_testing);
        $assignment_details['type_of_testing']['label'] = "Type of Testing";
        $assignment_details['type_of_testing']['value'] = $service->name;
        
        // Testing Date
        $assignment_details['testing_date']['label'] = "Testing Date";
        $assignment_details['testing_date']['value'] = $assignment->testing_date;
        
        // Meeting Required
        $assignment_details['meeting_required']['label'] = "Meeting Required";
        $assignment_details['meeting_required']['value'] = $this->getYesNo($assignment->meeting_required);
        
        // Meeting Date
        $assignment_details['meeting_date']['label'] = "Meeting Date";
        $assignment_details['meeting_date']['value'] = $assignment->meeting_date;
        
        // Contact Info - Parent
        $assignment_details['contact_info_parent']['label'] = "Contact Info - Parent";
        $assignment_details['contact_info_parent']['value'] = $assignment->contact_info_parent;
        
        // Contact Info - Teacher
        $assignment_details['contact_info_teacher']['label'] = "Contact Info - Teacher";
        $assignment_details['contact_info_teacher']['value'] = $assignment->contact_info_teacher;
        
        // Team Chair
        $assignment_details['team_chair']['label'] = "Team Chair";
        $assignment_details['team_chair']['value'] = $assignment->team_chair;
        
        // Email
        $assignment_details['email']['label'] = "Email";
        $assignment_details['email']['value'] = $assignment->email;
        
        // Report Submitted
        $assignment_details['report_submitted']['label'] = "Report Submitted";
        $assignment_details['report_submitted']['value'] = $this->getYesNo($assignment->report_submitted);
        
        $template = new BackendTemplate('be_transaction_show_assignment');
        $template->assignment_details = $assignment_details;

        return $template->parse();
    }
    
    public function getInitialReeval($value) {
        switch ($value)
		{
			case 'initial':
                return 'Initial';
				break;
			case 'initial_504':
                return 'Initial 504';
				break;
			case 're_eval':
                return 'Re-eval';
				break;
			case 're_eval_504':
                return 'Re-eval 504';
				break;
			case 'extended':
                return 'Extended Eval';
				break;
			case 'independent':
                return 'Independent Eval';
				break;
			case 'other':
                return 'Other';
				break;
			default:
				return 'Not Selected';
				break;
		}
    }
    
    public function getYesNo($value) {
        switch ($value)
		{
			case 'yes':
                return 'Yes';
				break;
			case 'no':
                return 'No';
				break;
			default:
				return 'Not Selected';
				break;
		}
    }

}
