<?php 
$ci = get_instance();
?>
<script type="text/javascript">
    jQuery(document).ready(function () {
        $("#back").click(function (event) {
            document.location.href = "<?php echo site_url("admin/Home") ?>";
            event.preventDefault();
        });
        $("#create_user").click(function (event) {
            document.location.href = "<?php echo site_url("admin/Users/create") ?>";
            event.preventDefault();
        });
        $("[id^=edit_]").click(function (event) {
            id = $(this).attr('id').substring(5);
            url = "<?= site_url('admin/Users/edit/') ?>/" + id;
            console.log("URL: " + url);
            document.location.href = url;
        });
        $("[id^=delete_]").click(function (event) {
            id = $(this).attr('id').substring(7);
            console.log("Del: " + id);
            showDeleteUserDlg(id);
        });
    });
</script>

<h2 class="section_title">Users</h2>

<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <button id="create_user" class="btn btn-primary btn-sm">Create user</button>
    </div>
</div>
<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <table class="table">
            <thead>
            <th>Name</th>
            <th>Surname</th>
            <th>Email</th>
            <th>Is Admin</th>
            <th>Is Blocked</th>
            <th>&nbsp;</th>
            </thead>
            <tbody>
                <?php if (sizeof($users) == 0) { ?>
                    <tr>
                        <td colspan="4">No users found</td>
                    </tr>
                <?php } ?>
                <?php foreach ($users as $user) { ?>
                    <tr>
                        <td><?= $user->name ?></td>
                        <td><?= $user->surname ?></td>
                        <td><?= $user->email ?></td>
                        <td><?= ($user->type == USER_TYPE_ADMIN) ? 'Yes' : 'No' ?></td>
                        <td><?= ($user->is_blocked == 1) ? 'Yes' : 'No' ?></td>
                        <td><button id="edit_<?= $user->ID ?>">Edit</button>&nbsp;<button id="delete_<?= $user->ID ?>">Delete</button></td>
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
<div class="floating_message hidden" id="loading_message">
    <div id="spinner" class="center-block">
        <img src="<?php echo $ci->config->base_url()?>/resources/images/spinner_game.gif" width="95" height="95"/>
        <div style="margin: 8px;"><h4>Deleting project...</h4></div>
    </div>
</div>			
