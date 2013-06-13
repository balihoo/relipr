<html>
	<head>
		<title>Validation Results</title>
	</head>
	<body>
<?php
	$errorCount = count($this->val->getErrors());
	$warningCount = count($this->val->getWarnings());

	if($errorCount > 0) {
		echo "<h2>Validation failed with $errorCount errors and $warningCount warnings</h2>\n";
		printResults("Errors", $this->val->getErrors());
		if($warningCount > 0)
			printResults("Warnings", $this->val->getWarnings());
	} else if($warningCount > 0) {
		echo "<h2>Validation passed but there were $warningCount warnings</h2>";
		printResults("Warnings", $this->val->getWarnings());
	} else {
		echo "<h2>Validation was successful</h2>";
	}

?>

	<hr/>
	<h3>JSON Data</h3>
	<pre><?=$this->json?></pre>
	</body>
</html>
<?php

function printResults($title, $results) {
	echo "<h3>$title:</h3><ul>\n";
	foreach($results as $result) {
		echo "<li>" . $result . "</li>\n";
	}
	echo "</ul>\n";
}

