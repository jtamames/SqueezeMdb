<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
ini_set("auto_detect_line_endings", true);

class SampleFileParser {
    
    static protected $col_names = array("Sample"/*, "Location", "Latitude"
        , "Longitude", "Altitude", "Sampling date", "Extraction", "Sequencing method"
        ,"Total amount of sequence (Gb)"*/
     );
     
    
    protected $sample_props_cols;

    protected $error_message;

    public function get_error() {
        return $this->error_message;
    }

    protected function get_column_names() {
        return self::$col_names;
    }

    protected function validate_header($header, $sample) {
        $col_names = $this->get_column_names();
        $num_fixed_cols = sizeof($col_names);
        if ($num_fixed_cols > sizeof($header)) {
            $this->error_message = "Wrong number of columns";
            return FALSE;
        }
        // Validate fixed name columns
        $sample_cols = 0;
        for ($i = 0; $i < sizeof($header); $i++) {
            if ($i < $num_fixed_cols) {
                if ($header[$i] != $col_names[$i]) {
                    $this->error_message = "Wrong header: Expecting ".$col_names[$i];
                    return FALSE;
                }
            } else {
                $this->sample_props_cols[$header[$i]] = $i;
            }
        }
        $this->error_message = "";
        return TRUE;
    }
    
    public function parse($filename) {
        $samples = array();
        $handle = fopen($filename, "rb");
        $header_read = FALSE;
        $this->row = 0;
        $col_num = sizeof(self::$col_names);
        while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {
            $this->row++;
            if (!$header_read && (sizeof($data) > 0) && (strpos($data[0], "#") !== 0)) { // The first line is the header
                $header_read = TRUE;
                $this->validate_header($data, $samples);
                $col_num += sizeof($this->sample_props_cols);
            }
            elseif ($header_read) {
                if (sizeof($data) != $col_num) {
                    $this->error_message = "Missing columns at row {$this->row}";
                    return FALSE;
                }
                $sample = new Sample();
                $sample->name = trim($data[0]);
                $sample->properties = array();
                foreach ($this->sample_props_cols as $prop => $col) {
                    $sample->properties[] =["property"=>$prop, "value"=>$data[$col]];
                }
                $samples[] = $sample;
            }
        }
        return $samples;
    }
}
