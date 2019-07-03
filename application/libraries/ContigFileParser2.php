<?php

require_once("./application/libraries/GameParser.php");

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
ini_set("auto_detect_line_endings", true);

class ContigFileParser2 extends GameParser {

    protected $row;
    static protected $col_names = array("Contig ID", "Tax", "Disparity", "GC perc", "Length", "Num genes", "Bin ID");
    protected $col_names_idx = array("Contig ID" => -1, "Tax" => -1, "Disparity" => -1
        , "GC perc" => -1, "Length" => -1, "Num genes" => -1, "Bin ID" => -1);
    private $methods;

    function __construct() {
        $this->samples_cols_match = array();
        $this->methods = array();
    }

    protected function get_column_names() {
        return self::$col_names;
    }

    function validate_header($header, $sample) {
        for ($i = 0; $i < sizeof($header); $i++) {
            switch ($header[$i]) {
                case "Contig ID":
                case "Bin ID":
                case "Tax":
                case "Length":
                case "GC perc":
                case "Num genes":
                case "Disparity":
                    $this->col_names_idx[$header[$i]] = $i;
                    break;
                default:
                    if (strpos($header[$i], "TPM") === 0) {
                        $aux_sample = substr($header[$i], 4);
                        if (!isset($sample[$aux_sample]) || $sample[$aux_sample] == NULL) {
                            $this->error_message = "Wrong header: Unknown sample name '{$aux_sample}' in column {$i}";
                            return FALSE;
                        } else {
                            $this->samples_cols_match[$aux_sample]["norm"] = $i;
                        }
                    } elseif (strpos($header[$i], "Coverage") === 0) {
                        $aux_sample = substr($header[$i], 9);
                        if (!isset($sample[$aux_sample]) || $sample[$aux_sample] == NULL) {
                            $this->error_message = "Wrong header: Unknown sample name '{$aux_sample}' in column {$i}";
                            return FALSE;
                        } else {
                            $this->samples_cols_match[$aux_sample]["cover"] = $i;
                        }
                    }
                    break;
            }
        }
        // Check that the header has all the mandatory columns
        foreach ($this->col_names_idx as $column => $index) {
            if ($index < 0) {
                $this->error_message = "Wrong header: Missing column '{$column}'";
                return FALSE;
            }
        }
        return TRUE;
    }

    function parse($filename, $samples) {
        $ci = get_instance();
        $result = array();
        $this->row = 1;
        $handle = fopen($filename, "rb");
        $num_samples = sizeof($samples);
        while (($data = fgetcsv($handle, 0, "\t")) !== FALSE) {
            if ($this->row == 1) { // The first line is the header
                if ($this->validate_header($data, $samples) == FALSE) {
                    return FALSE;
                }
            } elseif ($this->row > 1) { // data rows
                if (sizeof($data) > 0) {
                    $contig = new Contig();
                    $contig->name = $data[$this->col_names_idx["Contig ID"]];
                    $contig->taxonomy = $data[$this->col_names_idx["Tax"]];
                    $contig->chimerism = $data[$this->col_names_idx["Disparity"]];
                    $contig->gc_per = $data[$this->col_names_idx["GC perc"]];
                    $contig->size = $data[$this->col_names_idx["Length"]];
                    $contig->genes_num = $data[$this->col_names_idx["Num genes"]];
                    // TODO: parse bins x methods
                    $bins = array();
                    if (isset($data[$this->col_names_idx["Bin ID"]]) && sizeof($data[$this->col_names_idx["Bin ID"]]) > 0) {
                        $aux_meth = json_decode($data[$this->col_names_idx["Bin ID"]], TRUE);
                        $aux_meth = $aux_meth["\"Bins\""];
                        $bins = array();
                        for ($i = 0; $i < sizeof($aux_meth); $i++) {
                            foreach ($aux_meth[$i] as $method => $bin) {
                                $bins[$bin] = $method;
                            }
                        }
                    }
                    $contig->bins = $bins;

                    // Fill abundances in samples
                    $abundances = array();
                    foreach ($this->samples_cols_match as $sample => $col) {
                        $abundances[$sample]["cover"] = trim($data[$col["cover"]]);
                        $abundances[$sample]["norm"] = trim($data[$col["norm"]]);
                    }
                    $contig->abundances = $abundances;

                    $result[] = $contig;
                }
            }
            $this->row = $this->row + 1;
        }

        return $result;
    }

    function parse_header($filehandle, $samples) {
        $this->row = 0;
        while (($data = fgetcsv($filehandle, 0, "\t")) !== FALSE) {
            $this->row++;
            if ((sizeof($data) > 0) && (strpos($data[0], "#") !== 0)) { // The first line is the header
                return $this->validate_header($data, $samples);
            }
        }
        $this->error_message = "Error parsing contig file: no header found";
        return FALSE;
    }

    function parse_data($filehandle, $samples, $num_rows) {
        $result = array();
        $num_samples = sizeof($samples);
        $ci = get_instance();
        // Check the file handle
        if (!isset($filehandle) || $filehandle == NULL) {
            log_message("error", "Error parsing Contig file: Invalid file  handle");
            $this->error_message = "Error parsing Contig file: Invalid file  handle";
            return FALSE;
        }
        // prior to invoke this method, the parse_header method has to be invoked
        if (!isset($this->samples_cols_match) || $this->samples_cols_match == NULL || sizeof($this->samples_cols_match) == 0) {
            log_message("error", "Error parsing Contig file: Header not parsed");
            $this->error_message = "Error parsing Contig file: Header not parsed";
            return FALSE;
        }
        $j = 0;
        while ($j < $num_rows && (($data = fgetcsv($filehandle, 1000, "\t")) !== FALSE)) {
            if (sizeof($data) > 0) {
                $contig = new Contig();
                $contig->name = $data[$this->col_names_idx["Contig ID"]];
                // Validate that the contig name is unique
                if (isset($contig_names[$contig->name])) {
                    $this->error_message = "Repeated contig name at row " . $this->row;
                    return FALSE;
                } else {
                    $contig_names[$contig->name] = TRUE;
                }
                $contig = new Contig();
                $contig->name = $data[$this->col_names_idx["Contig ID"]];
                $contig->taxonomy = $data[$this->col_names_idx["Tax"]];
                $contig->chimerism = $data[$this->col_names_idx["Disparity"]];
                $contig->gc_per = $data[$this->col_names_idx["GC perc"]];
                $contig->size = $data[$this->col_names_idx["Length"]];
                $contig->genes_num = $data[$this->col_names_idx["Num genes"]];
                // TODO: parse bins x methods
                $bins = array();
                if (isset($data[$this->col_names_idx["Bin ID"]]) && sizeof($data[$this->col_names_idx["Bin ID"]]) > 0) {
                    $aux_meth = json_decode($data[$this->col_names_idx["Bin ID"]], TRUE);
                    $aux_meth = $aux_meth["Bins"];
                    $bins = array();
                    for ($i = 0; $i < sizeof($aux_meth); $i++) {
                        foreach ($aux_meth[$i] as $method => $bin) {
                            $bins[$bin] = $method;
                        }
                    }
                }
                $contig->bins = $bins;
                // Fill abundances in samples
                $abundances = array();
                foreach ($this->samples_cols_match as $sample => $col) {
                    $abundances[$sample]["cover"] = trim($data[$col["cover"]]);
                    $abundances[$sample]["norm"] = trim($data[$col["norm"]]);
                }
                $contig->abundances = $abundances;

                $result[] = $contig;
            }
            $j++;
            $this->row++;
        }

        return $result;
    }

}
