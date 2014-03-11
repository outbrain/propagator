<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Outbrain propagator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <link rel="shortcut icon" href="img/favicon.ico" type="image/x-icon" />

    <!-- Le styles -->
  	<script src="js/jquery-1.8.2.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
  	<script src="js/bootbox.min.js"></script>
  	<script src="js/propagator.js"></script>
	<link href="css/bootstrap.css" rel="stylesheet">
  	<link href="css/bootstrap-patches.css" rel="stylesheet">
  	<link href="css/propagator.css" rel="stylesheet">
	    
    <style>
	pre.prettyprint { font-size: 90%; !important; }
	.nowrap { white-space: pre; overflow: scroll; }
    </style>
  
    <!-- typahead -->
    <script src="js/bootstrap-typeahead.js"></script>
    
    <!-- google pretty print -->
    <link href="css/prettify.css" rel="stylesheet" type="text/css">
    <script src="js/prettify.js" type="text/javascript"></script>
    <script src="js/lang-sql.js" type="text/javascript"></script>

    <script src="js/chosen.jquery.min.js"></script>
    <link href="css/chosen.css" media="screen" rel="stylesheet" type="text/css">

    <style>
      body {
        padding-top: 60px; /* 60px to make the container go all the way to the bottom of the topbar */
      }
    </style>
    <link href="css/bootstrap-responsive.css" rel="stylesheet">

  <body>
  <div class="container">
		<?php if (isset($message)) { ?>
      		<div class="alert alert-message"><?php echo $message; ?></div>
		<?php } ?>
  		<?php if (isset($error_message)) { ?>
      		<div class="alert alert-error"><?php echo $error_message; ?></div>
		<?php } ?>
    