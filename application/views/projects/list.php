<?php
$ci = get_instance();
$user = $ci->session->userdata('user');
$user_type = $ci->session->userdata('user_type');
$isLoggedIn = $ci->session->userdata('isLoggedIn');
?>
<script type="text/javascript">
    jQuery(document).ready(function () {
        $("#back").click(function (event) {
            document.location.href = "<?php echo site_url(($user_type==USER_TYPE_ADMIN)?"admin/Home":"user/Home")?>";
            event.preventDefault();
        });
        $("#create_project").click(function (event) {
            document.location.href = "<?php echo site_url("admin/Projects/show_project_form") ?>";
            event.preventDefault();
        });
        $("[id^=edit_]").click(function (event) {
            id = $(this).attr('id').substring(5);
            url = "<?= site_url('admin/Projects/show_project_form/') ?>/" + id;
            console.log("URL: " + url);
            document.location.href = url;
        });
        $("[id^=delete_]").click(function (event) {
            id = $(this).attr('id').substring(7);
            showDeleteProjectDlg(id);
        });
    });
</script>

<h2 class="section_title">Projects</h2>
<?php if (isset($info)) {?>
    <div class="row">
        <div class="col-md-offset-3 col-md-6">
            <div class="alert alert-success">
                <?=$info?>
            </div>
        </div>
    </div>
<?php } ?>
<div class="row">
    <div class="col-md-8 col-md-offset-2">
    <?php if ($user_type == USER_TYPE_ADMIN) {?>
        <button id="create_project" class="btn btn-primary btn-sm">Create project</button>
    <?php } ?>
        <table class="table">
            <thead>
            <th>Project Name</th>
            <th>Creation date </th>
            <th>Description</th>
            <?php if ($user_type == USER_TYPE_ADMIN) {?>
            <th>&nbsp;</th>
            <?php } ?>
            </thead>
            <tbody>
                <?php if (sizeof($projects) == 0) { ?>
                    <tr>
                        <td colspan="4">No projects found</td>
                    </tr>
                <?php } ?>
                <?php foreach ($projects as $project) { ?>
                    <tr>
                        <td><a href="<?= site_url('projects/search/' . $project->ID) ?>"><?= $project->name ?></a></td>
                        <td><?= $project->creation_date ?>&nbsp;</td>
                        <td><?= $project->description ?>&nbsp;</td>
                <?php if ($user_type == USER_TYPE_ADMIN) {?>
                        <td>
                            <button id="edit_<?= $project->ID ?>" type="button" class="btn btn-default" aria-label="Edit">Edit</button>&nbsp;
                            <button id="delete_<?= $project->ID ?>" type="button" class="btn btn-default" aria-label="Delete">Delete</button>
                        </td>
                <?php } ?>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<div class="row">
    <div class="col-md-2 col-md-offset-5">
            <button id="back" class="btn btn-default">Back</button>        
    </div>
</div>
