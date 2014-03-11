<?php if(!empty($database_roles)) { ?>
    <table class="table table-striped table-bordered table-condensed">
        <thead>
            <tr>
                <th>Role id</th>
                <th>Type</th>
                <th>Description</th>
            </tr>
        </thead>
        <?php foreach($database_roles as $database_role) { ?>
            <tr>
                <td><a href="<?php echo site_url()."?action=database_role&database_role_id=".$database_role["database_role_id"]; ?>"><?php echo $database_role["database_role_id"]; ?></a></td>
                <td><?php echo $database_role["database_type"]; ?></td>
                <td><?php echo $database_role["description"]; ?></td>
            </tr>
        <?php } ?>
    </table>
<?php } ?>
