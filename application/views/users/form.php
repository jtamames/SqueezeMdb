<script type="text/javascript">
    jQuery(document).ready(function () {
        $("#back").click(function (event) {
            document.location.href = "<?php echo site_url("admin/Users") ?>";
            event.preventDefault();
        });
    });
</script>
<?php
if (isset($user) && $user != NULL) {
    $action = 'edit/' . $user->ID;
    ?>
    <h2 class="section_title">Edit user</h2>
<?php
} else {
    $action = 'create';
    ?>
    <h2 class="section_title">Create new user</h2>
<?php } ?>

<?php
$error = validation_errors();
if (isset($error) && strlen($error) > 0) {
    ?>
    <div class="row">
        <div class="col-md-offset-3 col-md-6">
            <h3>Errors:</h3>
            <div class="alert alert-danger">
    <?= $error ?>
            </div>
        </div>
    </div>
<?php } ?>
<form id="new_user_form" name="new_user_form" class="form-horizontal" action="<?= site_url('admin/Users/' . $action) ?>" method="POST">
    <div class="row">
        <div class="col-md-offset-3 col-md-6">
            <input type="hidden" name="id" value="<?= (isset($user) ? $user->ID : '') ?>"/><br />
            <!-- general info -->
            <div class="form-group">
                <label for="name" class="col-md-3 control-label">Name:</label>
                <div class="col-md-9">
                    <input type="text" class="form-control" name="name" value="<?= (isset($user) ? $user->name : '') ?>"/>
                </div>
            </div>
            <div class="form-group">
                <label for="surname" class="col-md-3 control-label">Surname:</label>
                <div class="col-md-9">
                    <input type="text" class="form-control" name="surname" value="<?= (isset($user) ? $user->surname : '') ?>"/>
                </div>
            </div>
            <div class="form-group">
                <label for="email" class="col-sm-3 control-label">Email:</label>
                <div class="col-sm-9">
                    <input type="text" class="form-control" name="email" value="<?= (isset($user) ? $user->email : '') ?>"/>
                </div>
            </div>
<?php if (!isset($user) || $user == NULL) { ?>
                <div class="form-group">
                    <label for="password" class="col-sm-3 control-label">Password:</label>
                    <div class="col-sm-9">
                        <input type="password" class="form-control" name="password" value=""/>
                    </div>
                </div>
                <div class="form-group">
                    <label for="passconf" class="col-md-3 control-label">Password confirmation:</label>
                    <div class="col-md-9">
                        <input type="password" class="form-control" name="passconf" value=""/>
                    </div>
                </div>
<?php } ?>
            <div class="form-group">
                <label for="user_type" class="col-sm-3 control-label">User type:</label>
                <div class="col-sm-9">
                    <select class="form-control" name="type">
                        <option value="<?= USER_TYPE_ADMIN ?>" <?= ((isset($user) ? $user->type : '') == USER_TYPE_ADMIN) ? 'selected' : '' ?>>Administrator</option>
                        <option value="<?= USER_TYPE_USER ?>" <?= ((isset($user) ? $user->type : '') == USER_TYPE_USER) ? 'selected' : '' ?>>User</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <?php
    $offset = ((isset($user) && $user != NULL)?4:5);
    ?>
    <div class="row">
        <div class="col-md-offset-<?=$offset?> col-md-1">
             <input type="submit" name="submit" id="submit" class="btn btn-primary"/>
        </div>
    <?php if (isset($user) && $user != NULL) { ?>
        <div class="col-md-2">
            <button class="btn btn-default btn-info" id="change_pass" name="change_pass">Change Password</button>
        </div>
        <div class="col-md-2">
            <button class="btn btn-default btn-info" id="block" name="block">Block</button>
        </div>
<?php } ?>
        <div  class="col-md-2">
            <button class="btn btn-default" id="back" name="back">Back</button>
        </div>
    </div>
</form>