<?php

require_once("./application/libraries/GameParser.php");

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
ini_set("auto_detect_line_endings", true);

class BinFileParser2 extends GameParser {

    static protected $col_names = array("Bin ID", "Method", "Tax", "Size", "GC perc", "Num contigs"
        , "Disparity", "Completeness", "Contamination", "Strain Het");
    protected $col_names_idx = array("Bin ID" => -1, "Method" => -1, "Tax" => -1
        , "Size" => -1, "GC perc" => -1, "Num contigs" => -1, "Disparity" => -1
        , "Completeness" => -1, "Contamination" => -1, "Strain Het" => -1);

    function __construct() {
        $this->samples_cols_match = array();
    }

    protected function get_column_names() {
        return self::$col_names;
    }

    function validate_header($header, $sample) {
        for ($i = 0; $i < sizeof($header); $i++) {
            switch ($header[$i]) {
                case "Bin ID":
                case "Method":
                case "Tax":
                case "Size":
                case "GC perc":
                case "Num contigs":
                case "Disparity":
                case "Completeness":
                case "Contamination":
                case "Strain Het":
                    $this->col_names_idx[$header[$i]] = $i;
                    break;
                default:
                    if (strpos($header[$i], "RPKM") === 0) {
                        $aux_sample = substr($header[$i], 5);
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
        $row = 1;
        $handle = fopen($filename, "rb");
        $num_samples = sizeof($samples);
        $bin_names = array();
        while (($data = fgetcsv($handle, 0, "\t")) !== FALSE) {
            //$data = fgetcsv($handle, 1000, "\t");
            if ($row == 2) { // We ignore the first line, and the second one is the header
                if ($this->validate_header($data, $samples) == FALSE) {
                    return FALSE;
                }
            } elseif ($row > 2) { // data rows
                if (sizeof($data) > 0) {
                    $bin = new Bin();
                    $bin->name = $data[$this->col_names_idx["Bin ID"]];
                    // Validate that the bin name is unique
                    if (isset($bin_names[$bin->name])) {
                        $this->error_message = "Repeated bin name at row " . $row;
                        return FALSE;
                    } else {
                        $bin_names[$bin->name] = TRUE;
                    }
                    $bin->method = trim($data[$this->col_names_idx["Method"]]);
                    $bin->taxonomy = trim($data[$this->col_names_idx["Tax"]]);
                    $bin->size = trim($data[$this->col_names_idx["Size"]]);
                    $bin->gc_per = trim($data[$this->col_names_idx["GC perc"]]);
                    $bin->contig_num = trim($data[$this->col_names_idx["Num contigs"]]);
                    $bin->chimerism = trim($data[$this->col_names_idx["Disparity"]]);
                    $bin->completeness = trim($data[$this->col_names_idx["Completeness"]]);
                    $bin->contamination = trim($data[$this->col_names_idx["Contamination"]]);
                    $bin->strain_het = trim($data[$this->col_names_idx["Strain Het"]]);
                    // Fill abundances in samples
                    $abundances = array();
                    foreach ($this->samples_cols_match as $sample => $col) {
                        $abundances[$sample]["cover"] = trim($data[$col["cover"]]);
                        $abundances[$sample]["norm"] = trim($data[$col["norm"]]);
                    }
                    $bin->abundances = $abundances;

                    $result[] = $bin;
                }
            }
            $row++;
        }
        fclose($handle);

        return $result;
    }

}
