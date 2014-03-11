<td>
    <a href="<?php echo site_url()."?action=database_instance&database_instance_id=".$database_instance["database_instance_id"]; ?>"><?php echo $database_instance["database_instance_id"]; ?></a>
    <?php if ($database_instance["is_guinea_pig"]) {?>
        <div class="pull-right">
            <a href="#" class="" data-live-popover="true" data-toggle="popover" data-html="true" data-placement="auto" title="Guinea pigs" data-content="
                <p>Guinea pig instances are deployed first.
                <p>At least one guinea pig deployment must pass in order for non-guinea pigs deployments to begin.">
                <img src="img/icons/guinea_pig.gif" alt="Guinea pig">
            </a>
        </div>
    <?php } ?>
</td>
<td>
    <a href="<?php echo site_url()."?action=database_instance&database_instance_id=".$database_instance["database_instance_id"]; ?>"><?php echo $database_instance["host"]; ?></a>
    <?php if ($database_instance["description"]) {?>
        <div class="pull-right">
            <a href="#" class="" data-toggle="popover" data-html="true" data-placement="auto"
                data-content="<?php echo $database_instance["description"]; ?>">
                <span class="glyphicon glyphicon-info-sign"></span>
            </a>
        </div>
    <?php } ?>
</td>
<td><?php echo $database_instance["port"]; ?></td>
<td class="instance-environment instance-environment-<?php echo $database_instance["environment"]; ?>"><?php echo $database_instance["environment"]; ?></td>
