<?php

namespace Bcs;

use ZipArchive;

use pcrov\JsonReader\JsonReader; // Json streaming library
use Contao\Database;
use Isotope\Model\Attribute;
use Isotope\Model\Attribute\TextField;
use Isotope\Model\AttributeOption;
use Isotope\Interfaces\IsotopeProduct;
use Isotope\Isotope;
use Isotope\Model\Product;

class Hooks
{
    public function generatePage(&$objPageModel, $objLayout, &$objPage)
    {

        // If this is our "Contact - Success" test page
        if($objPageModel->id == 58)
        {
            // Manually open our 'test' file
            $reader = new JsonReader();
            $reader->open("../salsify/product-feed-paper_2024_04_12_16_45_01_UTC.json");





            /*************************/
            /* Unzip our Assets file */
            /*************************/

            $zip = new ZipArchive();
            $zip->open('../salsify/asset-feed-paper_2024_04_12_16_45_01_UTC.zip');

            $folder_date =  date('m_d_y');
            $zip->extractTo('../files/salsify_assets/' . $folder_date);
            $zip->close();

            echo "Zip Folder: " . "/files/salsify_assets/" . $folder_date . "<br>";
            echo "<pre>";
            echo print_r($zip);
            echo "</pre>";
            die();
            
            


            
            /************************/
            /* Process "Attributes" */
            /************************/

            // Read the "attributes" object
            $reader->read("attributes");
            // Store the initial depth so we know when to end
            $depth = $reader->depth();
            // Step to the first element within "attributes"
            $reader->read();

            // Do while there is data to be read
            do
            {

                // Temporarly store our read values
                $attr = $reader->value();

                // Take the id, convert to lowercase, replace spaces with underscores, truncate to max length of 30 characters
                $field_name = substr(str_replace(' ', '_', strtolower($attr["salsify:id"])), 0, 30);
                
                // Try and find an existing version of this Attribute
                $existing_attr = Attribute::findOneBy(['tl_iso_attribute.field_name=?'],[$field_name])->id;
                
                // Create Attribute if it doesn't exist already
                if(!$existing_attr)
                {
                    echo "New: " . $attr["salsify:id"] . "<br>";

                    //$new_attr = new TextField();
                    //$new_attr->tstamp = time();
                    //$new_attr->name = $attr["salsify:id"];
                    //$new_attr->field_name = $field_name;
                    //$new_attr->type = 'textarea';
                    //$new_attr->published = 1;
                    //$new_attr->save();
                    
                    
                }
                // Update if this Attribute doesn't exist yet
                else {
                    echo "Old: " . $attr["salsify:id"] . "<br>";
                }

            } while ($reader->next() && $reader->depth() > $depth); // Read each sibling.







            
            $reader->close();
            
            die();
            
        }
        
    }
}
