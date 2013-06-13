<?php

$result = new StdClass();
$result->errors = $this->val->getErrors();
$result->warnings = $this->val->getWarnings();

echo json_encode($result);

