<html>
	<head>
		<title>List Provider API</title>
		<style>
			ul {
				font-family:monospace;
			}
		</style>
	</head>
	<body>
		<h2>ReLiPr Console</h2>

		<h3>Links</h3>
		<ul>
			<li><a href="docs">Live API Documentation</a></li>
			<li><a href="https://github.com/pauldprice/relipr">Source code &amp; install instructions</a></li>
			<li><a href="https://docs.google.com/document/d/1QSqS5QpAx4wG7__ZQaLScTmpd-IqiEBJD0Mth9926Wg/edit?usp=sharing">Detailed Documentation &amp; API Specification</a></li>
		</ul>

		<hr/>
		<h3>Configuration checklist:</h3>
		<ul>
<?php
foreach($this->check as $label => $pass) {
	echo "<li>\n";
	if($pass)
		echo '<font color="blue">PASSED</font>';
	else
		echo '<font color="red">FAILED</font>';
	echo ': ' . htmlentities($label);
	echo "</li>\n";
}
echo "</ul>\n";
?>

		<hr/>
		<h3>Synchronize Source Data File</h3>
		The source data file contains all of the sample recipient data in CSV format.<br/>
		The default list is stored on Amazon S3, you can download it from 
			<a href="http://balihoo.test.s3.amazonaws.com/tmp/sample.csv">here</a>.<br/>
		If a new source file is uploaded to S3 you can refresh this server's copy.<br/>

		<form method="POST" action="admin/refreshsourcedata">
			<br/>Pull source file from the following public URL</br>
			<input type="text" name="path" size="50" value="http://balihoo.test.s3.amazonaws.com/tmp/sample.csv"/><br/>
			<input type="submit" value="Refresh source data file"/>
		</form>

		<hr/>
		<h3>Refresh Database</h3>
		This will drop the sample database and rebuild it from scratch using the source data file.<br/>
		If you do this, you will lose all changes - including any lists that have been created.

		<form method="POST" action="admin/refreshdatabase">
			<input type="submit" value="Refresh database"/>
		</form>


	</body>
</html>

