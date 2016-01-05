<!DOCTYPE html>
<html>
	<head>
		<title>Uptime | <?php echo $title?></title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
                <!-- Latest compiled and minified CSS -->
                <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7"            crossorigin="anonymous">
		<link href="<?php echo $template; ?>css/custom.css" rel="stylesheet">
		<style>
			body { padding-top: 0px; }
			@media (max-width: 979px) {
  				body { padding-top: 0px; }
			}
		</style>
	</head>
<body>
	<nav class="navbar navbar-default navbar-static-top">
	  	<div class="container">
		    <a class="navbar-brand" href="index.php">ServerStatus Live</a><a class="navbar-brand" style="float:right;" href="outages.php">Outages</a>
		</div>
	</nav>

	<div class="container content">
		<div class="col-sm-12">
			<table class="table table-striped table-condensed">
			<?php echo $sTable; ?>
			</table>
		</div>
	</div>
	
	<div class="container">
		<p style="text-align: center; font-size: 10px;">We are showing <?php echo $rtype; ?> resources.<br /><a href="https://www.qwdsa.com/converse/threads/serverstatus-rebuild.43/">ServerStatus</a> by <a href="http://www.mojeda.com">Michael Ojeda</a> and <a href="http://www.cameronmunroe.com/">Cameron Munroe</a></p>
	</div>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
        <!-- Latest compiled and minified JavaScript -->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
	<?php echo $sJavascript; ?>
</body>
</html>
