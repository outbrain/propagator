<?php if (!$has_credentials ) { ?>
	<div class="alert alert-danger" data-warn-missing-credentials="true">
		You have not submitted database credentials. Some further operations might not complete correctly without these.
		<br/>It is strongly suggested that you
	    <a data-link-type="input_credentials" href="<?php echo site_url().'?action=input_credentials' ?>">submit your database credentials now</a>.
	</div>
<?php } ?>
