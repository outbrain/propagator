

<div id="side-nav" class="col-sm-2 hidden-xs hidden-sm hidden-print affix">

    <ul class="nav nav-pills nav-stacked ">
        <li><a href="#About">About</a></li>
        <li><a href="#Download">Download</a></li>
        <li><a href="#Install">Install</a>
            <ul class="nav nav-pills nav-stacked">
                <li><a href="#Install-Upgrade">Upgrade</a></li>
            </ul>
        </li>
        <li><a href="#Concepts">Concepts</a>
            <ul class="nav nav-pills nav-stacked">
                <li><a href="#Concepts-Instances">Instances</a></li>
                <li><a href="#Concepts-Environments">Environments</a></li>
                <li><a href="#Concepts-Roles">Roles</a></li>
                <li><a href="#Concepts-Scripts">Scripts</a></li>
            </ul>
        </li>
        <li><a href="#Populate">Populate database</a>
            <ul class="nav nav-pills nav-stacked">
                <li><a href="#Populate-Essential">Essential</a></li>
                <li><a href="#Populate-Optional">Optional</a></li>
            </ul>
        </li>
        <li><a href="#Propagate">Propagate script</a></li>
        <li><a href="#Deploy">Deploy & manage script</a>
            <ul class="nav nav-pills nav-stacked">
                <li><a href="#Deploy-Approving">Approving</a></li>
                <li><a href="#Deploy-Status">Status</a></li>
                <li><a href="#Deploy-Commenting">Commenting</a></li>
                <li><a href="#Deploy-Additional">Additional actions</a></li>
            </ul>
        </li>
        <li><a href="#Reviewing">Reviewing script history</a></li>
        <li><a href="#Security">Security</a>
            <ul class="nav nav-pills nav-stacked">
                <li><a href="#Security-Authentication">Authentication</a></li>
                <li><a href="#Security-Users">Users & privileges</a></li>
				<li><a href="#Security-TwoPhaseApproval">Two phase approval</a></li>
            </ul>
        </li>
        <li><a href="#Credentials">Credentials</a>
            <ul class="nav nav-pills nav-stacked">
                <li><a href="#Credentials-Session">Session</a></li>
                <li><a href="#Credentials-Persistent">Persistent</a></li>
                <li><a href="#Credentials-Notes">Notes</a></li>
            </ul>
        </li>
        <li><a href="#Advanced">Advanced DBA actions</a>
            <ul class="nav nav-pills nav-stacked">
                <li><a href="#Advanced-Deployment">Script deployment</a></li>
                <li><a href="#Advanced-CRUD">CRUD operations</a></li>
                <li><a href="#Advanced-Replication">Replication topology</a></li>
            </ul>
        </li>
        <li><a href="#Mappings">Mappings</a>
            <ul class="nav nav-pills nav-stacked">
                <li><a href="#Mappings-Schema">Schema</a></li>
                <li><a href="#Mappings-Query">Query</a></li>
            </ul>
        </li>
   </ul>

   <a href="#" id="toggle_manual_images">Toggle images</a>

</div>

<div id="manual-content" class="col-xs-12 col-sm-12 col-md-10">
    <?php {
        $this->view('manual_content', array());
    } ?>
</div>

<script language="javascript">
    $(document).ready(function(){
        $("body").scrollspy({target: "#side-nav", offset:50});
        $("#toggle_manual_images").click(function() {
            $("#manual-content img").toggle();
            return false;
        });
    });
</script>
