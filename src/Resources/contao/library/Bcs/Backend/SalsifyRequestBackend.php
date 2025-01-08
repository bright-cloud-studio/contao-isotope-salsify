<?php



namespace Bcs\Backend;

use Contao\Backend;
use Contao\Image;
use Contao\Input;
use Contao\DataContainer;
use Contao\StringUtil;

use Bcs\Model\Transaction;
use Bcs\Model\Assignment;
use Bcs\Model\Student;

class AssignmentBackend extends Backend
{
    // Create 'Invoice' DCAs for Psychologists
    public function createAssignment(DataContainer $dc) {
        
        // do nothing if we havent saved this record
        if (!$dc->activeRecord)
		{
			return;
		}
		

        // If we have not yet created the Invoices for this request
        if($dc->activeRecord->student != '') {

            $student = Student::findOneBy('id', $dc->activeRecord->student);
            
            $assignment = Assignment::findOneBy('id', $dc->activeRecord->id);
            $assignment->lasid = $student->lasid;
            $assignment->sasid = $student->sasid;
            $assignment->save();

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
		$this->Database->prepare("UPDATE tl_assignment SET tstamp=". time() .", published='" . ($blnVisible ? 1 : '') . "' WHERE id=?")->execute($intId);
		//$this->log('A new version of record "tl_assignment.id='.$intId.'" has been created'.$this->getParentEntries('tl_listing', $intId), __METHOD__, TL_GENERAL);
	}
	
	public function exportListings()
	{
		$objListing = Transactions::findAll();
		$strDelimiter = ',';
	
		if ($objListing) {
			$strFilename = "Assignments_" .(date('Y-m-d_Hi')) ."csv";
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

		$objAlias = $this->Database->prepare("SELECT id FROM tl_assignment WHERE id=? OR alias=?")->execute($dc->id, $varValue);

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

    // Get Psychologists as select menu
    public function getPsychologistsShared(DataContainer $dc) { 

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

    // Get Districts as select menu
    public function getDistricts(DataContainer $dc) { 

        // Hold the psys
        $districts = array();
        
        $districts = $districts + array('0' => 'Select a District');
        
        // Use the DB to grab all of our enabled members, aka our psychologists
		$this->import('Database');
		$result = $this->Database->prepare("SELECT * FROM tl_district WHERE published=1 ORDER BY district_name ASC")->execute();
		while($result->next())
		{
            // Add ti array with ID as the value and firstname lastname as the label
            $districts = $districts + array($result->id => $result->district_name);   
		}

		return $districts;
	}

    // Get Schools as select menu
    public function getSchools(DataContainer $dc) { 
    
        $schools = array();
        
        if($dc->activeRecord->district != '') {
            
            $schools = $schools + array('0' => 'Select a District');
            
            // Use the DB to grab all of our enabled members, aka our psychologists
    		$this->import('Database');
    		
    		$result = $this->Database->prepare("SELECT * FROM tl_school WHERE pid=" . $dc->activeRecord->district . "  ORDER BY school_name ASC")->execute();
    		while($result->next())
    		{
                // Add ti array with ID as the value and firstname lastname as the label
                $schools = $schools + array($result->id => $result->school_name);
    		}
    		return $schools;
        }
        $schools = $schools + array('0' => 'First, Select a District');
        return $schools;
		
	}

    // Get Services as select menu
    public function getServices(DataContainer $dc) { 

        // Hold the psys
        $services = array();

        $services = $services + array('0' => 'Select a Service');

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

    // Get Students as select menu
    public function getStudents(DataContainer $dc) { 
    
        // Hold the psys
        $students = array();

        if($dc->activeRecord->district != '') {
            
            $students = $students + array('0' => 'Select a Student');
            
            // Use the DB to grab all of our enabled members, aka our psychologists
    		$this->import('Database');
    		$result = $this->Database->prepare("SELECT * FROM tl_student WHERE district=".$dc->activeRecord->district . " ORDER BY name ASC")->execute();
    		while($result->next())
    		{
                // Add ti array with ID as the value and firstname lastname as the label
                $students = $students + array($result->id => $result->name);   
    		}
    
    		return $students;
        }
        $students = $students + array('0' => 'First, Select a District');
        return $students;
    
	}








     // Get Initial - Re-eval as select menu
    public function getInitialReeval(DataContainer $dc) { 
    
        // Hold the psys
        $students = array();
        
        $students = $students + array('0' => 'Select type of Eval');

        $students = $students + array('initial' => 'Initial');
        $students = $students + array('initial_504' => 'Initial 504');

        $students = $students + array('re_eval' => 'Re-eval');
        $students = $students + array('re_eval_504' => 'Re-eval 504');

        $students = $students + array('extended' => 'Extended Eval');
        $students = $students + array('independent' => 'Independent Eval');

        $students = $students + array('other' => 'Other');
        
        return $students;
    
	}
















    
    
}
