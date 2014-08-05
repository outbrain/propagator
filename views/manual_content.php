<div>
    <h2>Manual</h2>
</div>
<div>
    <a name="About" id="About"></a>
    <h3>About</h3>

    <p>
        <strong>Propagator</strong> is a schema & data deployment tool that works on a multi-everything topology:
        <ul>
            <li>Multi-server: push your schema & data changes to multiple instances in parallel</li>
            <li>Multi-role: different servers have different schemas</li>
            <li>Multi-environment: recognizes the differences between development, QA, build & production servers</li>
            <li>Multi-technology: supports MySQL, Hive (Cassandra on the TODO list)</li>
            <li>Multi-user: allows users authenticated and audited access</li>
            <li>Multi-planetary: TODO</li>
        </ul>
        It makes for a centralized deployment control, allowing for tracking, auditing and management of deployed scripts.
    </p>
    <p>
        It answers such questions as "Who added column 'x' to table 't' and when?", "Was that column added to the build & test servers?";
        "It's not there; was there a failure? What was the failure?".
        It provides with: "OK, let's deploy it on all machines"; "There was some error and it's fixed now. Let's deploy again on this paritular instance";
        "We already deployed this manually; so let's just mark it as 'deployed'".
    </p>
    <p>
        <i>Propagator</i> is developed at <a href="http://www.outbrain.com">Outbrain</a> to answer for the difficulty in
        managing schema changes made by dozens of developers on a multi-everything topology in continuous delivery.
    </p>
    <p>
        <i>Propagator</i> is released as open source under the
        <a href="http://www.apache.org/licenses/LICENSE-2.0">Apache 2.0 license</a>.
    </p>
    <p>
        Developed by Shlomi Noach.
    </p>
    <div><img src="img/manual/man_img_deploy_status.png"/></div>
</div>
<div>
    <a name="Download" id="Download"></a>
    <h3>Download</h3>

    <p>
        <i>Propagator</i> is released as open source and is available at: <a href="https://github.com/outbrain/propagator">https://github.com/outbrain/propagator</a>
    </p>
</div>
<div>
    <a name="Install" id="Install"></a>
    <h3>Install</h3>
    <p>
        <i>Propagator</i> is a PHP web app, running on top of Apache and uses MySQL as backend.
    </p>
    <p>
        <i>Propagator</i> is developed and tested on Linux (Ubuntu 12.10 & CentOs 5.9). It will likely run well under BSD and Mac OS/X,
        and possibly on Windows.
    </p>
    <p>
        <h5>Requirements</h5>
        <ul>
            <li>Apache 2</li>
            <li>php >= 5.3</li>
            <li>MySQL >= 5.0</li>
            <li>php modules: php5-mysql. If you wish to fork/develop <i>Propagator</i>, install phpunit.</li>
            <li>Optional: to view instance topologies (see <a href="#Advanced">Advanced DBA actions</a>), install <a href="http://www.percona.com/software/percona-toolkit">Percona Toolkit</a></li>
        </ul>
        Copy the entire <i>Propagator</i> tree under your Apache html directory (e.g. <code>/var/www/html/propagator/</code>)
    </p>

    <p>
        <i>Propagator</i> uses MySQL as backend, where all metadata about your system and topologies is read from (see <a href="#Populate">Populate</a> section)
        and where all deployments, history and status are written. Use the provided <code>install.sql</code> file to generate the schema and tables.
        By default, the schema name is <code>propagator</code>. 
    </p>
    <p>
    	Sample installation method:
<pre class="prettyprint lang-bash">
bash$ mysql -uroot -psecr3t &lt; install.sql 
</pre>
    </p>
    <p>
        Next, you must tell <i>Propagator</i> where the MySQL database backend is, and provide with credentials.
        Update the <code>conf/config.inc.php</code> file in the like of the following:
<pre class="prettyprint lang-php">
$conf['db'] = array(
  'host'	=> '127.0.0.1',
  'port'	=> 3306,
  'db'	=> 'propagator',
  'user'	=> 'the_user',
  'password' => 'the_password'
);</pre>
    </p>
    <a name="Install-Upgrade" id="Install-Upgrade"></a>
    <h5>Upgrade</h5>
    <p>
    	<i>Propagator</i> is evolving, and further development requires schema changes. These are provided as "patches" to the original schema.
    </p>
    <p>
    	In case you are upgrading from a previous installed version, 
    	follow on to install any <code>install-db-migration-*</code> file that is later than your installation.
    	If you're unsure, simply execute any <code>install-db-migration-*</code> file (ordered by name/timestamp) -
    	and ignore any errors. You can do so via the following example: 
<pre class="prettyprint lang-bash">
bash$ mysql -uroot -psecr3t --force propagator &lt; install-db-migration-2014-03-24.sql
</pre>
    </p>
    <p>
        Finally, you will need to populate your database. Read on to understand the <a href="#Concepts">Concepts</a>, then follow
        the instructions under the <a href="#Populate">Populate</a> section.
    </p>
</div>
<div>
    <a name="Concepts" id="Concepts"></a>
    <h3>Concepts: instances, environments, roles & scripts</h3>

    <a name="Concepts-Instances" id="Concepts-Instances"></a>
    <h5>Instances</h5>
        <p>
            Your database <i>instances</i> are where you will be deploying your scripts to. These would be MySQL or Hive
            instances (more types of databases will be supported in the future). If you are familiar with MySQL replication,
            then please note only masters are relevant to <i>Propagator</i>, since changes applied to masters will propagate
            to slaves via normal MySQL replication.
        </p>
        <p>
            An instance is identified by hostname & port. Thus, you can manage multiple instances running on the same machine.
        </p>
    <a name="Concepts-Environments" id="Concepts-Environments"></a>
    <h5>Environments</h5>
        <p>
            Not all instances are created the same. Some are production servers, some are build servers, some QA/Testing
            and some are just development databases.
        </p>
        <p>
            Instances are categorized by the following environments: <code>production</code>, <code>build</code>, <code>qa</code>, <code>dev</code>.
            It is up to you to properly categorize your instances.
        </p>
        <p>
            <i>Propagator</i> can treat different environments in different ways. For example, by default deployments will never execute
            automatically on production instances and will require a manual user action. This is configurable in <code>conf/config.inc.php</code>:
<pre class="prettyprint lang-php">
$conf['instance_type_deployment'] = array(
    'production' 	=> 'manual',
    'build' 		=> 'automatic',
    'qa' 			=> 'automatic',
    'dev' 			=> 'automatic'
);</pre>
    </p>
    <a name="Concepts-Roles" id="Concepts-Roles"></a>
    <h5>Roles</h5>
        <p>
            You may have completely different databases. For example, you might have one "major" MySQL OLTP server (along with a bunch of slaves),
            and a different MySQL server for OLAP queries, which you load hourly/daily. Yet other MySQL instances may be used for company internals,
            and you access your Hadoop cluster via Hive.
        </p>
        <p>
            In the above, the different servers are likely to have completely different schema and functionality. This is where <strong>roles</strong> come in:
            they are your way of categorizing your databases based on functionality.
        </p>
        <p>
            And yet, multiple instances can be associated with a single role. The most obvious example is that your test servers will have identical
            schema to your production servers, in which case they are sharing the same role. Moreover, your test or build servers are likely to only
            contain a small amount of data, and so they can accommodate multiple functions. And so the instance-role mapping is a many-to-many association.
        </p>
    <a name="Concepts-Scripts" id="Concepts-Scripts"></a>
    <h5>Scripts</h5>
        <p>
            A script is a set of queries you wish to deploy (propagate) on your instances. This could be a <code>CREATE TABLE...</code>, an <code>INSERT INTO</code>
            command etc. A script can be composed of multiple queries. See <a href="#Propagate">Propagate</a> section for more on this.
        </p>
</div>
<div>
    <a name="Populate" id="Populate"></a>
    <h3>Populate database</h3>

    <p>
        Post-install <i>Propagator</i>, you must populate some tables which describe your databases layout.
    </p>
    <a name="Populate-Essential" id="Populate-Essential"></a>
    <h5>Essential</h5>
        <p>
            <code>database_instance</code>: this is the listing of all database instances you will wish to deploy scripts on.
            This excludes any replica slaves (since they are merely replaying changes on their masters).
<pre class="prettyprint lang-sql">desc database_instance;
+----------------------+---------------------------------------+------+-----+------------+----------------+
| Field                | Type                                  | Null | Key | Default    | Extra          |
+----------------------+---------------------------------------+------+-----+------------+----------------+
| database_instance_id | int(10) unsigned                      | NO   | PRI | NULL       | auto_increment |
| host                 | varchar(128)                          | NO   | MUL | NULL       |                |
| port                 | smallint(5) unsigned                  | NO   |     | NULL       |                |
| environment          | enum('production','build','qa','dev') | NO   | MUL | production |                |
| description          | varchar(255)                          | NO   |     |            |                |
| is_active            | tinyint(3) unsigned                   | NO   |     | 0          |                |
| is_guinea_pig        | tinyint(3) unsigned                   | NO   |     | 0          |                |
+----------------------+---------------------------------------+------+-----+------------+----------------+
</pre>
            Provide data as follows:
            <ul>
                <li>host: host name or IP for your database instance</li>
                <li>port: public TCP/IP port database listens on (typically <code>3306</code> for MySQL)</li>
                <li>environment: see <a href="#Concepts">Concepts</a></li>
                <li>description: make it meaningful. It's for you and your peers</li>
                <li>is_active: unused at this stage</li>
                <li>is_guinea_pig: "1" if this instance should serve as guinea pig. Guinea pigs are those instances
                    that are deployed first, and at least one guinea pig must deploy successfully for other instances to be deployed.
                    See <a href="#Deploy">Deploy</a></li>
            </ul>
        </p>
        <p>
            <code>database_role</code>: listing of roles.
<pre class="prettyprint lang-sql">desc database_role;
+------------------+----------------------+------+-----+---------+-------+
| Field            | Type                 | Null | Key | Default | Extra |
+------------------+----------------------+------+-----+---------+-------+
| database_role_id | varchar(32)          | NO   | PRI | NULL    |       |
| database_type    | enum('mysql','hive') | NO   |     | mysql   |       |
| description      | varchar(1024)        | YES  |     | NULL    |       |
| is_default       | tinyint(3) unsigned  | YES  |     | 0       |       |
+------------------+----------------------+------+-----+---------+-------+
</pre>
            Provide data as follows:
            <ul>
                <li>database_role_id: a short, meaningful name for your role; this serves as the role's ID. Examples: <code>'oltp'</code>, <code>'dwh'</code></li>
                <li>database_type: currently only 'mysql' and 'hive' are supported</li>
                <li>description: make it meaningful. It's for you and your peers</li>
                <li>is_default: make just one role a default one (though no harm done if you define none or multiple). The default role is
                    auto-selected on the script deployment page.
                </li>
            </ul>
        </p>
        <p>
            <code>database_instance_role</code>: associate instances with roles
<pre class="prettyprint lang-sql">desc database_instance_role;
+----------------------+------------------+------+-----+---------+-------+
| Field                | Type             | Null | Key | Default | Extra |
+----------------------+------------------+------+-----+---------+-------+
| database_instance_id | int(10) unsigned | NO   | PRI | NULL    |       |
| database_role_id     | varchar(32)      | NO   | PRI | NULL    |       |
+----------------------+------------------+------+-----+---------+-------+
</pre>
            <i>Propagator</i> does not provide with CRUD functionality. Populating these tables is on you; use your favorite MySQL GUI or command line.
        </p>
    <a name="Populate-Optional" id="Populate-Optional"></a>
    <h5>Optional</h5>
        <p>
            <code>general_query_mapping</code>: auto-transformation of queries executed on any instance (see <a href="#Mappings">Mappings</a>).
<pre class="prettyprint lang-sql">desc general_query_mapping;
+--------------------------+------------------+------+-----+---------+----------------+
| Field                    | Type             | Null | Key | Default | Extra          |
+--------------------------+------------------+------+-----+---------+----------------+
| general_query_mapping_id | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
| mapping_type             | varchar(32)      | YES  |     | NULL    |                |
| mapping_key              | varchar(4096)    | YES  |     | NULL    |                |
| mapping_value            | varchar(4096)    | NO   |     | NULL    |                |
+--------------------------+------------------+------+-----+---------+----------------+
</pre>
            Provide data as follows:
            <ul>
                <li>mapping_type: Currently supported values are <code>regex</code> and <code>federated</code></li>
                <li>mapping_key: relevant on <code>regex</code> only: pattern to match replace</li>
                <li>mapping_value:
                    on <code>regex</code>: replacement text.
                    On <code>federated</code>: format of <code>CONNECTION</code> clause.
                </li>
            </ul>
        </p>
        <p>
            <code>database_role_query_mapping</code>: auto-transformation of queries executed on any instance associated with specific roles (see <a href="#Mappings">Mappings</a>).
<pre class="prettyprint lang-sql">desc database_role_query_mapping;
+--------------------------------+------------------+------+-----+---------+----------------+
| Field                          | Type             | Null | Key | Default | Extra          |
+--------------------------------+------------------+------+-----+---------+----------------+
| database_role_query_mapping_id | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
| database_role_id               | varchar(32)      | NO   | MUL | NULL    |                |
| mapping_type                   | varchar(32)      | YES  |     | NULL    |                |
| mapping_key                    | varchar(4096)    | YES  |     | NULL    |                |
| mapping_value                  | varchar(4096)    | NO   |     | NULL    |                |
+--------------------------------+------------------+------+-----+---------+----------------+
</pre>
            Provide data as follows:
            <ul>
                <li>database_role_id: role for which this mapping applies</li>
            </ul>
            Other columns same as for <code>general_query_mapping</code>.
        </p>
        <p>
            <code>database_role_query_mapping</code>: auto-transformation of queries executed on specific instances (see <a href="#Mappings">Mappings</a>).
<pre class="prettyprint lang-sql">desc database_instance_query_mapping;
+------------------------------------+------------------+------+-----+---------+----------------+
| Field                              | Type             | Null | Key | Default | Extra          |
+------------------------------------+------------------+------+-----+---------+----------------+
| database_instance_query_mapping_id | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
| database_instance_id               | int(10) unsigned | NO   | MUL | NULL    |                |
| mapping_type                       | varchar(32)      | YES  |     | NULL    |                |
| mapping_key                        | varchar(4096)    | YES  |     | NULL    |                |
| mapping_value                      | varchar(4096)    | NO   |     | NULL    |                |
+------------------------------------+------------------+------+-----+---------+----------------+
</pre>
            Provide data as follows:
            <ul>
                <li>database_instance_id: instance for which this mapping applies</li>
            </ul>
            Other columns same as for <code>general_query_mapping</code>.
        </p>
        <p>
            <code>database_instance_schema_mapping</code>: auto-transformation of schema names executed on specific instances (see <a href="#Mappings">Mappings</a>).
<pre class="prettyprint lang-sql">desc database_instance_schema_mapping;
+-------------------------------------+------------------+------+-----+---------+----------------+
| Field                               | Type             | Null | Key | Default | Extra          |
+-------------------------------------+------------------+------+-----+---------+----------------+
| database_instance_schema_mapping_id | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
| database_instance_id                | int(10) unsigned | NO   | MUL | NULL    |                |
| from_schema                         | varchar(64)      | NO   | MUL | NULL    |                |
| to_schema                           | varchar(64)      | NO   |     | NULL    |                |
+-------------------------------------+------------------+------+-----+---------+----------------+
</pre>
            Provide data as follows:
            <ul>
                <li>database_instance_id: instance for which this mapping applies</li>
                <li>from_schema: name of schema to replace</li>
                <li>to_schema: replacement schema name. May contains '%' wildcards</li>
            </ul>
        </p>
        <p>
            You may map the same <code>from_schema</code> into multiple <code>to_schema</code> values on the same instance, in which case
            a deployment on such instance will execute multiple times, on multiple schemas.
        </p>
        <p>
            Wildcard mapping is also supported. <code>to_schema</code> may take the form of <code>my_wp_%</code> which will
            match any schema name <code>LIKE 'my_wp_%'</code> (do note that '_' is in itself a wildcard. <i>Propagator</i> only considers
            a mapping to be a "wildcard" mapping if it contains the '%' sign).
        </p>
        <p>
            Wildcard mapping requires that <i>Propagator</i> pre-connects to relevant instance before deployment, to gather those
            schema names that match the wildcard.
        </p>
        <p>
            <code>known_deploy_schema</code>: listing of schema names that are presented to the user:
<pre class="prettyprint lang-sql">desc known_deploy_schema;
+------------------------+---------------------+------+-----+---------+----------------+
| Field                  | Type                | Null | Key | Default | Extra          |
+------------------------+---------------------+------+-----+---------+----------------+
| known_deploy_schema_id | int(10) unsigned    | NO   | PRI | NULL    | auto_increment |
| schema_name            | varchar(64)         | NO   | MUL | NULL    |                |
| is_default             | tinyint(3) unsigned | YES  |     | 0       |                |
+------------------------+---------------------+------+-----+---------+----------------+
</pre>
            Provide data as follows:
            <ul>
                <li>schema_name: name of schema (aka database in MySQL jargon)</li>
                <li>is_default: make just one schema a default one (though no harm done if you define none or multiple). The default schema is
                    auto-selected on the script deployment page.
                </li>
            </ul>
            Data in this table has no functional effect other than providing the user with possible schemas the user may wish to deploy a script on.
        </p>
</div>
<div>
    <a name="Propagate" id="Propagate"></a>
    <h3>Propagate scripts</h3>
    <div class="pull-right">
        <div><img src="img/manual/man_img_propagate_role.png"/></div>
        <div><img src="img/manual/man_img_propagate_schema.png"/></div>
    </div>
    <p>
        This is the place to start actual usage of <i>Propagator</i>!
    </p>
    <p>
        Click the <strong>Propagate Script</strong> link on the top navigation bar to deploy/propagate a new script. A script is a set of
        one or more queries to be executed, and the <strong>Propagate Script</strong> submission page allows you to describe what to deploy where.
        You will need to fill in the following:
        <ul>
            <li>
                Role: you must pick from existing set of roles (see <a href="#Concepts-Roles">Concepts-Roles</a>). This will in effect
                determine which instances will be deployed with your script.
            </li>
            <li>
                Schema: you can fill this freely, though you have a pre-defined list of possible schemas via the <code>known_deploy_schema</code>
                (see <a href="#Populate-Optional">Populate-Optional</a>).
                <br/>
                <i>Propagator</i> encourages you to <i>not</i> specify schema name in your script queries, but instead specify the schema separately,
                and issue schema-less queries. For example, you are encouraged <i>not</i> to use <code>ALTER TABLE sakila.actor ...</code> but instead
                specify <code>sakila</code> as the script's schema, then enter <code>ALTER TABLE actor ...</code> in script.
                <br/>
                The above is not enforced and you are free to do as you please. However note the benefits of specifying the schema externally:
                <ul>
                    <li>Queries become generalized</li>
                    <li><i>Propagator</i> will connect to your instances using the specific schema name: this may have beneficial security implications</li>
                    <li>
                        <i>Propagator</i> supports the notion of <a href="#Mappings-Schema">schema mapping</a>: that the same conceptual schema is called differently on different instances.
                        Based on the <code>database_instance_schema_mapping</code> your script will be deployed differently, and automatically so, on different
                        instances.
                    </li>
                </ul>
            </li>
            <li>
                Script: a set of one or more queries to issue. These could be DDL (<code>CREATE</code>, <code>ALTER</code>, <code>DROP</code> etc.)
                or DML (<code>INSERT</code>, <code>DELETE</code>, <code>CALL</code> etc.). (It may also be <code>SELECT</code> queries, though
                <i>Propagator</i> will not return any result set from such queries)
                <br/>
                <i>Propagator</i> discourages issuing the same exact script more than once and warns in such case. It is however allowed.
                <br/>
                Your script's queries might be modified when executed. This is controlled by <a href="#Mappings">Mappings</a>. For example, you
                may wish to transform any <code>CREATE TRIGGER</code> statement to <code>CREATE DEFINER='root'@'localhost' TRIGGER</code>.
            </li>
            <li>Description: make it meaningful. It's for you and your peers.</li>
        </ul>
        You will notice that the Roel and Schema you have entered will be used in the next time you submit a script as
        auto-suggested value. This is to save you some time and errors, and follows the pattern that specific users typically
        deploy on same whereabouts of the database.
    </p>
</div>
<div>
    <a name="Deploy" id="Deploy"></a>
    <h3>Deploy & manage script</h3>
    <p>
        Once you've submitted your script for deployment, you are directed at the view-script page.
        This is where you approve specific or all instances for deployment; follow up on deployment status;
        take action if needed; comment for the benefit of your peers.
    </p>
    <p>
        A quick breakdown of the page:
        <ul>
            <li>
                General script info at the top
            </li>
            <li>
                Followed by <a href="#Deploy-Commenting">comment</a> area (use it!)
            </li>
            <li>
                A query-breakdown of the script is initially hidden (visibility can be toggled)
            </li>
            <li>
                Script deployment <a href="#Deploy-Status">status</a> table -- this is where most of the fun things happen
            </li>
            <li>
                <a href="#Deploy-Additional">Additional actions</a> at the bottom
            </li>
        </ul>
    </p>

    <a name="Deploy-Approving" id="Deploy-Approving"></a>
    <h5>Approving</h5>
    <p>
        You've submitted a script. The script was associated with a role. Based on that role, <i>Propagator</i>
        chose a set of instances where the script would be deployed to.
    </p>
    <p>
        It is now at your hands to deploy the script onto those instances. You may choose to deploy it on all instances
        (which makes sense, they all belong to that role), or you may wish to only deploy onto <strong>Dev</strong> and
        <strong>QA</strong> environment at first, and only later come back and deploy on to <strong>Build</strong> and
        <strong>Production</strong> environments.
    </p>
    <p>
        You will need to <strong>Approve</strong> the relevant instances. You might actually want to skip a specific
        instance for whatever reason, in which case you may <strong>Disapprove</strong> it. Disapproved instances can
        later be Approved again.
    </p>
    <p>
        Don't leave instances in unknown approval state for a long time. Either <strong>Approve</strong> an instance or
        <strong>Disapprove</strong> it. Otherwise, one week later, no one knows <i>why</i> those specific instances
        were not deployed. Was there a specific reason? Did the user just forget about them? By Disapproving an instance
        you're making a clear point that it should not be deployed to.
    </p>


    <a name="Deploy-Status" id="Deploy-Status"></a>
    <h5>Status</h5>
    <div class="pull-right">
        <div><img src="img/manual/man_img_deploy_query.png"/></div>
        <div><img src="img/manual/man_img_deploy_failed.png"/></div>
    </div>
    <p>
        This is the place where you can hopefully sit back, smile and relax, as your script is being deployed on
        approved instances. Alternatively, you can be at your keyboard and mouse, smile and relax as you control
        and diagnose deployment problems.
    </p>
    <p>
        One an instance is approved, deployment should start automatically. Do <a href="#Concepts-Environments">remember</a>
        that by default <code>production</code> instances are not auto-deployed. This is for safety measures -- double
        verification that that <code>ALTER TABLE</code> should indeed be carried away by <i>Propagator</i> (or you
        might choose to do an
        <a href="http://openarkkit.googlecode.com/svn/trunk/openarkkit/doc/html/oak-online-alter-table.html">online operation</a>
        yourself). For such <code>production</code> instances, you will need to click the <strong>Run/Retry</strong>
        button to the right.
    </p>
    <p>
        Whether automatic or manual, once deployment begins, the status table makes for a self-updating dashboard.
        It will refresh periodically and present you with deployment status for all instances.
    </p>
    <p>
        You will notice that <strong>Guinea pig</strong> instances are deployed first. If any instacnes are at all
        defined as <strong>Guinea pig</strong>, <i>Propagator</i> uses them as ... guinea pigs, and only once the
        script is successfully deployed on at least one guinea pig, does <i>Propagator</i> continue to deploy the
        script onto the rest of the instances. It is encouraged that you do define at least once instance per
        environment as <strong>Guinea pig</strong>. See <a href="#Populate-Essential">Populate - Essential</a> for more.
    </p>
    <p>
        While a script is being deployed, and assuming it does not complete immediately, you are able to track its
        status: watch the runtime, current query and overall progress on a per-instance basis.
    </p>
    <p>
        Informative messages are available almost everywhere. Click the <strong>Current</strong> query text to reveal
        the query that is being executed right now. Click the <strong>time</strong> entry link to get complete start-to-end timing.
    </p>
    <p>
        The <strong>Status</strong> message is the most important thing to watch. Hopefully the script will deploy
        successfully, whereas you will get a "passed" confirmation. In case the script fails, you will see a "failed"
        message. Click the "failed" message to find out why: is it a credentials thing? A duplicate key? Other problem?
        The status message is always clickable.
    </p>
    <p>
        The <strong>Mark as Passed</strong> button ("thumb up") marks the script as successful. Maybe you issued the
        query yourself manually beforehand, in which case <i>Propagator</i> may fail to deploy (e.g. "table already exists").
        If you are certain the very same script has been fully deployed (all script queries executed or otherwise applied)
        use this action to override <i>Propagator</i>'s failure or inability to deploy.
    </p>

    <p>
        See also <a href="#Advanced">Advanced DBA actions</a> for deployment actions that can override or resolve script
        deployment issues.
    </p>

    <a name="Deploy-Commenting" id="Deploy-Commenting"></a>
    <h5>Commenting</h5>
    <div class="pull-right">
        <div><img src="img/manual/man_img_deploy_comment.png"/></div>
    </div>
    <p>
        Adding a short comment is possible and encouraged, especial in the case where the script had trouble deploying.
        The comment can be associated with a "OK", "Fixed", "TODO", "Cancelled" label/icon.
    </p>
    <p>
        Submitted comments show up on the script's viewing page (same page where comment was submitted), as well as
        on the <a href="#Reviewing">scripts history</a> page. This allows a supervisor or a guest to easily gather
        information about script history; what went wrong; what was done; etc.
    </p>

    <a name="Deploy-Additional" id="Deploy-Additional"></a>
    <h5>Additional actions</h5>
    <p>
        At bottom tabs, the following actions are made available:
        <ul>
            <li>
                Propagate New: submit a new script identical to current one. This will land you on the <strong>Propagate script</strong
                page with all input fields populated, where you will have the option to change some of the settings.
                <br/>
                This is useful, for example, when you are mistakenly introducing syntax errors to your script. You realize it
                only upon deployment. Editing a scirpt in place is strictly and intentionally impossible in <i>Propagator</i>,
                but you are given the option of submitting a new script in the likes of the old one.
            </li>
            <li>
                Redeploy: a little bit tricky situation is when you add new instances to <i>Propagator</i>, that are not
                up to date with other instances (perhaps by the time you backed-up an instance and deplicated it onto another,
                some deployments were made). The <strong>Redeploy</strong> action simply adds to this deployment those hosts
                that were not previously there. It is then up to you to <strong>Approve</strong> them.
            </li>
        </ul>
    </p>
</div>
<div>
    <a name="Reviewing" id="Reviewing"></a>
    <h3>Reviewing script history</h3>
    <div class="pull-right">
        <div><img src="img/manual/man_img_history_02.png"/></div>
    </div>
    <p>
        You can search, filter and view scripts history.
    </p>
    <p>
        A user's own history is available via <strong>Welcome</strong> -&gt; <strong>My history</strong>.
        If <code>history_visible_to_all</code> is set (see <a href="#Security-Users">Users & privileges</a>), then any user
        can also browse history for all submitted scripts, not just his own. Complete history is available via
        <strong>Welcome</strong> -&gt; <strong>Script history</strong>.
    </p>
    <p>
        Search is available via <strong>Find script</strong> in top navigation bar.
        Type some text to search for in script code, description, role or submitter name.
    </p>
    <p>
        Script listing table allows paging and basic filtering: by same role as a given script, same submitter,
        same schema.
    </p>
    <p>
        Script description is followed by comments, if available.
    </p>
    <p>
        The script listing also provides a quick summary of script status. Remember that a script is deployed on multiple instances.
        The listing indicates how many instances have been deployed successfully, how many have failed, and how many instances in
        total for this deployment.
    </p>
</div>
<div>
    <a name="Security" id="Security"></a>
    <h3>Credentials</h3>
    <p>
        <i>Propagator</i> deploys queries on remote databases. For this, it needs to get credentials on those servers.
        At this stage there are two ways by which such credentials can be provided.
    </p>
    <a name="Security-Authentication" id="Security-Authentication"></a>
    <h5>Authentication</h5>
    <p>
        At this stage, <i>Propagator</i> identifies users via <code>"PHP_AUTH_USER"</code>; this can be provided by
        Apache's <strong>.htaccess</strong>, by connecting to <strong>ldap</strong> server etc.
    </p>
    <p>
        In such case as the above, it is up to you to set Apache authentication. <i>Propagator</i> will simply expect
        a valid entry.
    </p>
    <p>
        Otherwise, you can edit <code>conf/config.inc.php</code> to read:
<pre class="prettyprint lang-php">$conf['default_login'] = 'gromit';</pre>
        In which case, any unidentified user is automatically mapped to <code>gromit</code>. If you're doing a one man show
        as DBA/developer this might be simplest.
    </p>
    <a name="Security-Users" id="Security-Users"></a>
    <h5>Users & privileges</h5>
    <p>
        All users are not equal. There are <i>normal</i> users, and there are <i>DBAs</i>. DBA users have greater privileges
        and broader set of actions (see <a href="#Advanced">Advanced DBA actions</a>).
    </p>
    <p>
        To define who exactly accounts as DBA, edit <code>conf/config.inc.php</code> to read:
<pre class="prettyprint lang-php">$conf['dbas'] = array('gromit', 'penguin');</pre>
        In the above, the <code>gromit</code> and <code>penguin</code> accounts are automatically considered to be DBAs
        (there is no action required for those users, nor special credentials).
    </p>
    <p>
        You will obviously want to have at least one account that is a DBA.
    </p>
    <p>
        Are scripts submitted by Alice visible to Trudy? Edit <code>conf/config.inc.php</code> to read:
<pre class="prettyprint lang-php">$conf['history_visible_to_all'] = true;</pre>
        When true, Trudy can see the entire <a href="#Reviewing">history</a> and search it, as well as seeing
        details for all scripts deployed by other users. DBA users have this privilege implicitly.
    </p>
    
    <a name="Security-TwoPhaseApproval" id="Security-TwoPhaseApproval"></a>
    <h5>Two phase approval</h5>
    <p>
        Enhanced control can be set via <code>conf/config.inc.php</code>:
<pre class="prettyprint lang-php">
$conf['two_step_approval_environments'] = array(
  'production', 
  'build'
);
</pre>
		In the above, any deployment to the two environments <code>production</code>, <code>build</code>
		must be manually approved by a <a href="#Security-Users">DBA</a>. Even after the normal user approves
		a deployment, it hangs until the second approval is submitted.
        </p>
    </div>
<div>
    <a name="Credentials" id="Credentials"></a>
    <h3>Credentials</h3>
    <p>
        <i>Propagator</i> deploys queries on remote databases. For this, it needs to get credentials on those servers.
        At this stage there are two ways by which such credentials can be provided.
    </p>
    <a name="Credentials-Session" id="Credentials-Session"></a>
    <h5>Session</h5>
    <p>
        The users can provide database credentials to <i>Propagator</i>. Credentials will only be stored in the
        user's session, and never be written to file. Click <strong>Welcome</strong> -&gt; <strong>Set credentials</strong>
        to enter your credentials, and <strong>Welcome</strong> -&gt; <strong>Clear credentials</strong> to forcibly
        erase them from session memory.
    </p>
    <p>
        <i>Propagator</i> will let you know if your credentials are required. When entering your credentials,
        <i>Propagator</i> will verify them against one chosen instance.
    </p>
    <p>
        This method requires that all users have database credentials. They will have to have credentials that apply to
        all databases affected by a deployment they are issuing. Either this would be a single credential that applies to
        all instances (in which case this is some super-master account; all databases have this account) or they would
        have to enter and re-enter different credentials and deploy a script again and again, each time with different
        credentials until all instances are covered.
    </p>

    <a name="Credentials-Persistent" id="Credentials-Persistent"></a>
    <h5>Persistent</h5>
    <p>
        In this method the users have no knowledge of credentials, and <i>Propagator</i> manages them all.
        For this to happen, you will need to provide <i>Propagator</i> with plaintext passwords for each instance.
        (each instance may have different credentials).
    </p>
    <p>
        <strong>WARNING</strong>: make sure you understand the implications of storing plaintext passwords in file.
    </p>
    <p>
        To set up credentials, edit <code>conf/config.inc.php</code> to include an external config file where these
        credentials are found. For example, add:
<pre class="prettyprint lang-php">include "/etc/propagator-credentials.conf.php";</pre>
    </p>
    <p>
        Included file would have an entry such as follows:
<pre class="prettyprint lang-php">
$conf['instance_credentials'] = array(
"my.host01.name:3306" => "prop_user:super_duper_password_01",
"my.host02.name:3306" => "prop_user:super_duper_password_02",
"other.host.name:3306" => "prop_user:super_duper_password_99",
);
</pre>
    </p>
    <p>
        Reason for including an external file is that it can be placed in a more restricted directory, with stricter
        access (e.g. <code>chown apache:root ...</code>, <code>chmod 600 ...</code>).
    </p>
    <a name="Credentials-Notes" id="Credentials-Notes"></a>
    <h5>Notes</h5>
    <p>
        When working in persistent-credentials mode <i>Propagator</i> simply assumes all credentials are fine. It will
        not verify credentials against any host. In the case one of the credentials is wrong, it will take a failed
        deployment to reveal the problem. Do note that once the correct credentials are updated <i>Propagator</i>
        automatically picks them up and the submitter of a script may re-run the failed deployment.
    </p>
    <p>
        As consequence of the above, when working in persistent-credentials mode, <i>Propagator</i> will not issue
        a "credentials required" message at any stage.
    </p>
    <p>
        Still in persistent-credentials mode, the user is still allowed to enter credentials via
        <strong>Welcome</strong> -&gt; <strong>Set credentials</strong>. In such case, the user's credentials override
        the persistent credentials.
        This comes handy as you would typically limit the privileges for the persistent credentials (do you really have
        to have <code>SUPER</code> or <code>RELOAD</code> MySQL privileges for automated deployments?). Nevertheless
        a DBA with super-user privileges may set her privileges in session, and gain more power over deployments.
    </p>
    <p>
    	A configuration variable controls whether normal users are indeed allowed to enter their own credentials
    	(this is the default). One may disallow this, and only permit DBAs to enter their own credentials.
    	Edit <code>conf/config.inc.php</code> to read:
<pre class="prettyprint lang-php">$conf['restrict_credentials_input_to_dbas'] = true;</pre>
    </p>
</div>
<div>
    <a name="Advanced" id="Advanced"></a>
    <h3>Advanced DBA actions</h3>
    <div class="pull-right">
        <div><img src="img/manual/man_img_deploy_advanced_actions.png"/></div>
    </div>
    <p>
        The following actions are available to users <a href="#Security-Users">recognized as DBAs</a>:
    </p>
    <a name="Advanced-Deployment" id="Advanced-Deployment"></a>
    <h5>Script deployment</h5>
    <p>
        On script-deployment page, the DBA is able to perform several actions per instance-deployment entry:
        <ul>
            <li>
                Run/retry: same as for a normal user. <i>Propagator</i> attempts to resume the script from the last
                failed position, or just run it completely if it hasn't even started.
            </li>
            <li>
                Run Next Query: execute a single query from the script, and pause immediately after. If the script hasn't
                been started yet then the first query is executed; otherwise the query indicated by the <strong>Current</strong>
                column.
            </li>
            <li>
                Skip One Query: ignore the <strong>Current</strong> query and point to the next one. NOTE: you will be, umm,
                <i>skipping a query</i>. But that's why you are the DBA!
            </li>
            <li>
                Restart Deployment: completely restart the script and run it from the beginning. This could come in handy if
                you made some manual fixes to the database which would allow it to execute after first failing.
            </li>
        </ul>
    </p>
    <a name="Advanced-CRUD" id="Advanced-CRUD"></a>
    <h5>CRUD operations</h5>
    <p>
        Full CRUD operation are not supported. You need to provide <i>Propagator</i>'s backend data using your favorite
        MySQL GUI tool or via commandline.
    </p>
    <p>
        However, <i>Propagator</i> does support a limited set of operations that will make your data maintenance safer:
        <ul>
            <li>
                Duplicate instance: on a database-instance page, bottom tabs, you may create a new instance based on current instance's data.
                In particular, the new instance will use same environment, same roles and same mappings as current instance.
                <br/>
                For example, you might have a couple build servers and you are adding a third one. They should probably be set up almost identically.
            </li>
            <li>
                Rewire instance: on a database-instance page, bottom tabs, you may associate new roles to an instance; disassociate existing roles.
            </li>
            <li>
                Duplicate role: on a database-role page, bottom tabs, you may create a new role based on current role's data.
                In particular, the new role will use same type, same instances and same mappings as current role.
            </li>
            <li>
                Rewire role: on a database-role page, bottom tabs, you may associate new instances to a role; disassociate existing instances.
            </li>
        </ul>
    </p>
    <a name="Advanced-Replication" id="Advanced-Replication"></a>
    <h5>Replication topology</h5>
    <p>
        <i>Propagator</i> manages master servers only, as slave instances are assumed to get updates from their masters.
        As a utility service, and unrelated to <i>Propagator</i>'s job, it allows you to see the replication topology for
        MySQL instances.
    </p>
    <p>
        This is done by invoking <a href="http://www.percona.com/doc/percona-toolkit/2.2/pt-slave-find.html">pt-slave-find</a>, a Perl
        tool being part of Percona Toolkit. You must have <b>pt-slave-find</b> installed and available on the same host where your PHP/Apache
        executes.
    </p>
    <p>
        Note that <b>pt-slave-find</b> requires special credentials such as <code>REPLICATION CLIENT</code>. It is suggested that you
        provide <i>Propagator</i> with super-user privileges through <a href="#Credentials-Session">session credentials</a>.
    </p>
    <p>
        The output from <b>pt-slave-find</b> is a tree-listing of host names, and it can be prettified.
        Edit <code>conf/config.inc.php</code>, and add regular expressions to match host names. Host names will be
        assigned different colors per different matching expressions. For example, you might color hosts differently
        based on the data center they're located in:
<pre class="prettyprint lang-php">$conf['instance_topology_pattern_colorify'] = array (
"/[.]east/",
"/[.]west/",
"/localhost/"
);
</pre>
    </p>

</div>
<div>
    <a name="Mappings" id="Mappings"></a>
    <h3>Mappings</h3>
    <p>
        <i>Propagator</i> supports mapping, or transformations of your queries before deploying on your database instances.
    </p>
    <p>
        Click the <strong>Mappings</strong> link at the top navigation bar to see setup for all mappings.
    </p>
    <a name="Mappings-Schema" id="Mappings-Schema"></a>
    <h5>Schema</h5>
    <p>
        The same conceptual schema may be named differently on different instances. For example, your production <code>webapp</code> schema
        may be named <code>unittest_webapp</code> on your test server.
    </p>
    <p>
        Based on the <code>database_instance_schema_mapping</code> table data, your script will be deployed differently, and automatically so,
        on different instances.
    </p>
    <p>
        You may map a schema name into multiple schemas matching some wildcard. This is useful if you have multiple similar
        schemas on the same database instance, all which have the same set of tables, all which should be affected by the same deployment.
        See <a href="#Populate-Optional">Populate Database - Optional</a> for more on this.
    </p>
    <p>
        If you don't have any such cases where your schemas can be named differently on different servers, you can safely ignore this feature.
    </p>

    <a name="Mappings-Query" id="Mappings-Query"></a>
    <h5>Query</h5>
    <p>
        You may have automatic query transformation for propagated scripts.
        Such transformations can be defined for all queries (via the <code>general_query_mapping</code> table),
        for queries executing on a specific role (via the <code>database_role_query_mapping</code> table),
        or for queries executing against specific instances (via the <code>database_instance_query_mapping</code> table).
    </p>
    <p>
        Two types of query transformations are supported at this time:
        <ul>
            <li>
                federated: transforming creation of <code>FEDERATED</code> tables such that user, password, host, port & schema
                (or subset of the above) are applied differently on different instances.
                <br> An example can be:
<pre class="prettyprint lang-sql">select * from database_instance_query_mapping;
+----------------------+--------------+-------------+-------------------------+
| database_instance_id | mapping_type | mapping_key | mapping_value           |
+----------------------+--------------+-------------+-------------------------+
|                    9 | federated    | NULL        | localhost:3306/myschema |
+----------------------+--------------+-------------+-------------------------+
</pre>
                If your script includes, foe example, a <code>CREATE TABLE ... ENGINE=FEDERATED CONNECTION='super:duper@someserver:3307/'</code>
                query, the query is transformed to <code>CREATE TABLE ... ENGINE=FEDERATED CONNECTION='super:duper@localhost:3306/myschema'</code>
                for the specific instance specified (<code>database_instance_id</code> = 9).
            </li>
            <li>
                regex: a simple regular expression search and replace mechanism. Since <i>Propagator</i> is a PHP application, you must provide
                the regular expression in PHP format. For example, the following:
<pre class="prettyprint lang-sql">select * from database_instance_query_mapping;
+----------------------+--------------+-----------------------+-------------------------------------------+
| database_instance_id | mapping_type | mapping_key           | mapping_value                             |
+----------------------+--------------+-----------------------+-------------------------------------------+
|                    9 | regex        | /CREATE[\s]+TRIGGER/i | CREATE DEFINER='root'@'localhost' TRIGGER |
+----------------------+--------------+-----------------------+-------------------------------------------+
</pre>
                Will implant the <code>DEFINER</code> clause within a <code>CREATE TRIGGER</code> statement in case the user
                forgets to specify it. The above example applies only to a single instance (<code>database_instance_id</code> = 9)
                <br/>
                Similarly, one can specify a query mapping for an entire role:
<pre class="prettyprint lang-sql">select * from database_role_query_mapping;
+------------------+--------------+-------------+---------------+
| database_role_id | mapping_type | mapping_key | mapping_value |
+------------------+--------------+-------------+---------------+
| hive             | regex        | /^(#.+)\n/  |               |
+------------------+--------------+-------------+---------------+
</pre>
                In the above, all scripts issued against instances of the <code>Hive</code> role, are stripped of commented lines
                (which the connector dislikes and will not accept).
            </li>
        </ul>
    </p>
</div>
