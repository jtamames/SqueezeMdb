<?php
$ci = get_instance();
?>
<script type="text/javascript">
    jQuery(document).ready(function () {
        $("#back").click(function (event) {
            document.location.href = "<?php echo site_url("admin/Projects") ?>";
            event.preventDefault();
        });
        $("#creation_form").submit(function (event) {
            $("#loading_message").addClass("show").removeClass("hidden");
            return;
        });
    });
</script>

<h2 class="section_title">Create new project</h2>

<?php if (isset($error) && strlen($error) > 0) { ?>
    <div class="row">
        <div class="col-md-offset-3 col-md-6">
            <h3>Errors:</h3>
            <div class="alert alert-danger">
                <?= $error ?>
            </div>
        </div>
    </div>
<?php } ?>
<form id="creation_form" name="creation_form" class="form-horizontal" action="<?=(isset($edit)?site_url('admin/Projects/edit/').$project->ID:site_url('admin/Projects/create'))?>" method="POST" enctype="multipart/form-data">
    <div class="row">
        <div class="col-md-offset-3 col-md-6">
            <!-- general info -->
            <div class="form-group">
                <label for="name" class="col-sm-3 control-label">Name:</label>
                <div class="col-sm-9">
                    <input class="form-control" type="text" name="name" value="<?=(isset($edit)?$project->name:'')?>"/>
                </div>
            </div>
            <div class="form-group">
                <label for="description" class="col-sm-3 control-label">Description: <small>(optional)</small></label>
                <div class="col-sm-9">
                    <textarea class="form-control" rows="4" cols="25" name="description"><?=(isset($edit)?$project->description:'')?></textarea>
                </div>
            </div>
            <!-- data files -->
            <?php if (!isset($edit)) { ?>
            <div class="form-group">
                <label for="sample_file" class="col-sm-3 control-label">Samples file:</label>
                <div class="col-sm-9">
                    <input type="file" class="form-control" name="sample_file" />
                </div>
            </div>
            <div class="form-group">            
                <label for="bin_file" class="col-sm-3 control-label">Bins file:</label>
                <div class="col-sm-9">
                    <input type="file" class="form-control" name="bin_file" />
                </div>
            </div>
            <div class="form-group">
                <label for="contig_file" class="col-sm-3 control-label">Contigs file:</label>
                <div class="col-sm-9">
                    <input type="file" class="form-control" name="contig_file" />
                </div>
            </div>
            <div class="form-group">
                <label for="gene_file" class="col-sm-3 control-label">Genes file:</label>
                <div class="col-sm-9">
                    <input type="file" class="form-control" name="gene_file" />
                </div>
            </div>
            <?php } ?>
            <div class="form-group">
                <label for="seq_file" class="col-sm-3 control-label">Sequences file: <small>(optional)</small></label>
                <div class="col-sm-9">
                    <input type="file" class="form-control" name="seq_file" />
                </div>
            </div>
            <!-- project users -->
            <div class="row section">
                <div class="col-md-12 section">
                    <h4>Project Users</h4>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <table class="table">
                        <thead class="">
                        <th>&nbsp;</th>
                        <th>Name</th>
                        <th>Surname</th>
                        <th>email</th>
                        </thead>
                        <?php foreach ($users as $user) { ?>
                            <tr>
                                <td><input type="checkbox" name="users[]" value="<?= $user->ID ?>" <?=(isset($edit) && isset($project_users[$user->ID])?"checked":"")?>/></td>
                                <td><?= $user->name ?></td>
                                <td><?= $user->surname ?></td>
                                <td><?= $user->email ?></td>
                            </tr>

                        <?php } ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-offset-5 col-md-1">
            <input type="submit" name="submit" id="submit" class="btn btn-primary"/>
        </div>
        <div  class="col-md-offset-1 col-md-2">
            <button class="btn btn-default" id="back" name="back">Back</button>
        </div>
    </div>
</form>
<div class="floating_message hidden" id="loading_message">
    <div id="spinner" class="center-block">
        <img src="<?php echo $ci->config->base_url()?>/resources/images/spinner_game.gif" width="95" height="95"/>
        <div style="margin: 8px;"><h4><?=(isset($edit)?"Updating":"Creating")?> project...</h4></div>
    </div>
</div>			

