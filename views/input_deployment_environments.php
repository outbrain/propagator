				
<div class="col-lg-6">
	<input type="checkbox" name="deployment_environment[]" value="production" id="deployment_environment_production">
	<label for="deployment_environment_production" class="fancy_check">
		<div class="env_production">P</div><div><b>Production</b><br/>affects production, build & QA/test servers</div>
	</label>
</div>
<div class="col-lg-6">
	<input type="checkbox" name="deployment_environment[]" value="test" id="deployment_environment_test">
	<label for="deployment_environment_test" class="fancy_check">
		<div class="env_test">T</div><div><b>Test</b><br/>affects QA/test servers only</div>
	</label>
</div>
<div class="col-lg-6">
	<input type="checkbox" name="deployment_environment[]" value="dev" id="deployment_environment_dev">
	<label for="deployment_environment_dev" class="fancy_check">
		<div class="env_dev">D</div><div><b>Dev</b><br/>affects only those dev servers you choose</div>
	</label>
</div>
