<?php

class Projects extends MY_Admin_Controller {

    function __construct() {
        parent::__construct();
        $this->load->library('session');
    }

    function index() {
        $this->load->model('Project_model');

        $data['projects'] = $this->Project_model->getProjects();
        $data['section'] = 'projects';
        $info = $this->session->flashdata('info');
        if (isset($info)) {
            $data['info'] = $info;
        }
        $this->load->view('templates/header', $data);
        $this->load->view('projects/list', $data);
        $this->load->view('templates/footer', $data);
    }

    function show_project_form($project_id = NULL) {
        $this->load->model("User_model");
        $this->load->model("Project_model");
        $data = array(
            "users" => $this->User_model->get_users(USER_TYPE_USER),
            "section" => 'projects'
        );
        if ($project_id != NULL) {
            $project = $this->Project_model->get_project($project_id);
            $p_users = $this->Project_model->get_project_users($project_id);
            // transform into an associative array
            $users_of_project = array();
            if (isset($p_users) && $p_users != NULL) {
                foreach ($p_users as $pu) {
                    $users_of_project[$pu["game_user_ID"]] = TRUE;
                }
            }
            $data["project"] = $project;
            $data["project_users"] = $users_of_project;
            $data['edit'] = TRUE;
        }
        $this->load->view('templates/header');
        $this->load->view('projects/create', $data);
        $this->load->view('templates/footer');
    }

    function create() {
        $this->load->helper('form');
        $this->load->model("Project_model");
        $data = array();

        $name = $this->input->post("name");
        log_message("DEBUG", "::::::::> Name: $name");
        $description = $this->input->post("description");
        $error_msg = "";
        if (!isset($name) || strlen($name) == 0) {
            $error_msg = "Project name field is mandatory";
        } else {
            if ($this->Project_model->exists_project($name)) {
                $error_msg = "Project name already exists";
            } else {
                if ($_FILES["sample_file"]['name'] == "") {
                    $error_msg = "Sample file is mandatory";
                } else {
                    if ($_FILES["contig_file"]['name'] == "") {
                        $error_msg = "Contig file is mandatory";
                    } else {
                        if ($_FILES["gene_file"]['name'] == "") {
                            $error_msg = "Gene file is mandatory";
                        }
                    }
                }
            }
        }
        // Validate project users asignation
        $users_ids = $this->input->post("users");
        log_message("DEBUG", "::::::::> ".sizeof($users_ids));
        if (!isset($users_ids) || sizeof($users_ids) == 0) {
            $error_msg = "Assign at least one user to the project";
        }
        $t0 = time();
        if (strlen($error_msg) > 0) {
            $this->process_error(NULL, $error_msg);
            return;
        }
        // Create project
        $project_id = $this->Project_model->insert_project($name, $description);
        log_message('debug', "::::::> Inserted project");

        $dir = $this->config->item('upload_dir');
        $config['upload_path'] = $dir;
        $config['allowed_types'] = '*';
        #$config['max_size'] = 500000;
        $this->load->library('upload', $config);

        // Load and validation of Samples file
        if (!$this->upload->do_upload('sample_file')) {
            $this->process_error($project_id, $this->upload->display_errors());
            return;
        }
        $data = $this->upload->data();
        $this->load->library("SampleFileParser");
        // Parse file
        $parser = new SampleFileParser();
        $samples = $parser->parse($data['full_path']);

        $view_data['num_samples'] = sizeof($samples);
        // insert samples
        $sample_ids = $this->Project_model->insert_samples($project_id, $samples, 1);
        $t1 = time();
        log_message('debug', "::::::> Inserted samples in " . ($t1 - $t0) . " segs");

        $no_bins = TRUE;
        // Load and validation of Bins file, if there is a bin file
        if ($_FILES["bin_file"]['name'] <> "") {
            if ($this->upload->do_upload('bin_file')) {
                $data = $this->upload->data();
                $this->load->library("BinFileParser2");
                // Parse file
                $parser = new BinFileParser2();
                $result = $parser->parse($data['full_path'], $sample_ids);
                if ($result === FALSE) {
                    $this->process_error($project_id, "Error in bin file: " . $parser->get_error());
                    return;
                }
                // Insert into database
                $view_data['num_bins'] = sizeof($result);
                $no_bins = FALSE;
                $this->Project_model->insert_bins($project_id, $sample_ids, $result);
            }
        } else {
            $view_data['num_bins'] = 0;
        }
        // Create empty bin
        $empty_bin_id = $this->Project_model->create_empty_bin($project_id, $sample_ids);

        $t2 = time();
        log_message('debug', "::::::> Inserted bins in " . ($t2 - $t1) . " segs");

        // Load and validation of contig file
        if (!$this->upload->do_upload('contig_file')) {
            $this->process_error($project_id, $this->upload->display_errors());
            return;
        }

        // Upload contig file
        $data = $this->upload->data();
        $this->load->library("ContigFileParser2");
        $parser = new ContigFileParser2();
        // Contig files are really big, so we parse and insert the data in chunks*/
        $batch_size = 2500;
        $handle = fopen($data['full_path'], "rb");
        // Parse the header
        if (($parser->parse_header($handle, $sample_ids) === FALSE)) {
            $this->process_error($project_id, $parser->get_error());
            return;
        }
        // Read file chunks until we reach the end or some error is detected
        $num_contigs = 0;
        $k = 0;
        do {
            $contigs = null;
            unset($contigs);
            $contigs = $parser->parse_data($handle, $sample_ids, $batch_size);
            if ($contigs === FALSE) {
                $this->process_error($project_id, $parser->get_error());
                return;
            } else {
                $num_contigs += sizeof($contigs);
                $this->Project_model->insert_contigs($project_id, $sample_ids, $contigs, $empty_bin_id, $no_bins);
                $k += sizeof($contigs);
                log_message("debug", "::::::> Inserted {$k} records...");
            }
        } while (($contigs !== FALSE) && sizeof($contigs) == $batch_size);

        $contigs = NULL;
        unset($contigs);
        $view_data['num_contigs'] = $num_contigs;
        $t3 = time();
        log_message('debug', "::::::> Inserted contigs in " . ($t3 - $t2) . " segs");
        fclose($handle);

        // Load and validation of Gene file
        // upload file
        if (!$this->upload->do_upload('gene_file')) {
            $this->process_error($project_id, $this->upload->display_errors());
            return;
        }
        // Parse file
        $data = $this->upload->data();
        $this->load->library("GeneFileParser2");
        $parser = new GeneFileParser2();
        // Gene files are really big, even bigger than conting files so we parse and insert the data in chunks
        $gene_handle = fopen($data['full_path'], "rb");
        // Parse the header
        if (($parser->parse_header($gene_handle, $sample_ids) === FALSE)) {
            $this->process_error($project_id, $parser->get_error());
            return;
        }
        // Parse data
        $num_genes = 0;
        $k = 0;
        $gene_cache = array();
        //$batch_size = 100;
        do {
            $genes = null;
            unset($genes);
            $genes = $parser->parse_data($gene_handle, $sample_ids, $batch_size);
            if ($genes === FALSE) {
                $this->process_error($project_id, $parser->get_error());
                return;
            } else {
                $num_genes += sizeof($genes);
                $this->Project_model->insert_genes_batch($project_id, $sample_ids, $genes, $gene_cache);
                //$this->Project_model->insert_genes_ext($project_id, $sample_ids, $genes);
                $k += sizeof($genes);
                log_message("debug", "::::::> Inserted {$k} records...");
                log_message("debug", "::::::> Gene cache size ".sizeof($gene_cache));
            }
        } while (($genes !== FALSE) && sizeof($genes) == $batch_size);

        $t5 = time();

        $genes = NULL;
        unset($genes);
        // Check if we have to insert sequences
        if ($_FILES["seq_file"]['name'] <> "") {
            if (!$this->upload->do_upload('seq_file')) {
                $this->process_error($project_id, $this->upload->display_errors());
                return;
            }
            // Parse file
            $data = $this->upload->data();
            $this->load->library("SequenceFileParser");
            $parser = new SequenceFileParser();
            // Gene files are really big, even bigger than conting files so we parse and generate a file 
            // to insert the data via LOAD DATA INFILE
            $seq_handle = fopen($data['full_path'], "rb");
            // Open the file we are going to use to upload the data
            $infile_name = $this->config->item('upload_dir').rand(0,1000000)."_seq.txt";
            $infile = fopen($infile_name, "wb");
            if (($parser->parse_header($seq_handle) === FALSE)) {
                $this->process_error($project_id, $parser->get_error());
                return;
            }
            // Parse data
            $k = 0;
            $batch_size = 8000;
            log_message("debug", "::::::> Gene cache size2 ".sizeof($gene_cache));
            do {
                $seqs = null;
                unset($seqs);
                $seqs = $parser->parse_data($seq_handle, $batch_size);
                if ($seqs === FALSE) {
                    $this->process_error($project_id, $parser->get_error());
                    return;
                } else {
                    $this->Project_model->prepare_sequence_infile($project_id,$infile, $seqs, $gene_cache);
                    $k += sizeof($seqs);
                    log_message("DEBUG",">>>>>>>>>>>>> Prepared $k seqs");
                }
            } while (($seqs !== FALSE) && sizeof($seqs) == $batch_size);
            log_message("DEBUG",">>>>>>>>>>>>> TRAZA 8");
            fclose($infile);
            // Load infile
            $this->Project_model->load_sequence_infile($infile_name);
            log_message("DEBUG",">>>>>>>>>>>>> TRAZA 9");
            // Delete aux load file
            unlink($infile_name);
            
        }
        $view_data['num_genes'] = $num_genes;
        $t4 = time();
        log_message('debug', "::::::> Inserted genes in " . ($t5 - $t3) . " segs");
        log_message('debug', "::::::> Inserted seqs in " . ($t4 - $t5) . " segs");
        $view_data['time'] = ($t4 - $t0);
        fclose($gene_handle);
        $view_data['section'] = "projects";
        // Assign users to the project
        $this->Project_model->assign_project_users($project_id, $this->input->post("users"));

        $this->load->view('templates/header');
        $this->load->view('projects/success', $view_data);
        $this->load->view('templates/footer');
    }

    function unique_name($name) {
        $result = TRUE;
        // Validates user credentials
        if ($this->Project_model->exists_project($name)) {
            $this->form_validation->set_message('unique_name', "Project name already exists");
            $result = FALSE;
        }

        return $result;
    }

    function process_error($project_id, $message, $edit = FALSE) {
        log_message('debug', $message);
        // clean up every thing: remove all the inserted data before
        if (isset($project_id) && $project_id != NULL) {
            if ($edit == TRUE) {
                $this->Project_model->delete_sequence($project_id);
            } else {
                $this->Project_model->delete_project($project_id);
            }
        }
        $data = array(
            "users" => $this->User_model->get_users(USER_TYPE_USER),
            'error' => $message,
            "section" => 'projects'
        );
        if ($edit == TRUE) {
            $data["edit"] = TRUE;
        }
        $this->load->view('templates/header');
        $this->load->view('projects/create', $data);
        $this->load->view('templates/footer');

        return;
    }

    private function get_dummy_samples() {
        $this->load->library("entities/Sample");
        $array_name = array("IC625022", "IC5253", "IC4403", "IC440022", "IC4253", "IC425022", "IC3253", "IC325022", "IC2253", "IC225022", "IC18253", "IC182520", "IC1825022", "IC15253", "IC152520", "IC1525022", "IC13253", "IC1253", "IC125022", "IC10403", "IC1040022", "IC10253", "IC102520", "IC1025022");
        $samples = array();

        foreach ($array_name as $name) {
            $sam = new Sample();
            $sam->name = $name;
            $samples[] = $sam;
        }

        return $samples;
    }

    function delete($project_id) {
        $this->load->model("Project_model");
        $this->Project_model->delete_project(intval($project_id));
        redirect("admin/Projects");
    }

    function edit($project_id) {
        $this->load->helper('form');
        $this->load->model("Project_model");
        $data = array();

        $name = $this->input->post("name");
        $description = $this->input->post("description");
        $error_msg = "";
        if (!isset($name) || strlen($name) == 0) {
            $error_msg = "Project name field is mandatory";
        } else {
            if ($this->Project_model->exists_project($name, $project_id)) {
                $error_msg = "Project name already exists";
            }
        }
        // Validate project users asignation
        $users_ids = $this->input->post("users");
        if (!isset($users_ids) || sizeof($users_ids) == 0) {
            $error_msg = "Assign at least one user to the project";
        }
        $t0 = time();
        if (strlen($error_msg) > 0) {
            $this->process_error(NULL, $error_msg);
            return;
        }
        $this->Project_model->update_project($project_id, $name, $description);
        // Check if we have to insert sequences
        if ($_FILES["seq_file"]['name'] <> "") {
            // If we already have sequences, we raise an error
            if (!$this->Project_model->has_sequences($project_id)) {
                $dir = $this->config->item('upload_dir');
                $config['upload_path'] = $dir;
                $config['allowed_types'] = '*';
                #$config['max_size'] = 500000;
                $this->load->library('upload', $config);

                if (!$this->upload->do_upload('seq_file')) {
                    $this->process_error($project_id, $this->upload->display_errors());
                    return;
                }
                // Parse file
                $data = $this->upload->data();
                $this->load->library("SequenceFileParser");
                $parser = new SequenceFileParser();
                // Gene files are really big, even bigger than conting files so we parse and insert the data in chunks
                $seq_handle = fopen($data['full_path'], "rb");
                if (($parser->parse_header($seq_handle) === FALSE)) {
                    $this->process_error($project_id, $parser->get_error());
                }
                // Parse data
                $k = 0;
                $batch_size = 2500;
                do {
                    $seqs = null;
                    unset($seqs);
                    $seqs = $parser->parse_data($seq_handle, $batch_size);
                    if ($seqs === FALSE) {
                        $this->process_error($project_id, $parser->get_error());
                    } else {
                        $this->Project_model->insert_sequences_batch($project_id, $seqs);
                        $k += sizeof($seqs);
                        log_message("debug", "::::::> Inserted {$k} records...");
                    }
                } while (($seqs !== FALSE) && sizeof($seqs) == $batch_size);
            }
        }
        // Update users assigned to the project
        $this->Project_model->update_project_users($project_id, $users_ids);

        $this->session->set_flashdata("info", "Project {$name} updated sucessfuly");
        redirect("admin/Projects/index");
    }

}
