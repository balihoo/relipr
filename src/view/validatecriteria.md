Validate Criteria
=================
Use the following form to validate criteria specification JSON.

<form method="POST" target="results">
	<input type="hidden" name="format" value="HTML"/>

	<label for="criteriajson">Either validate the following JSON:</label>
	<textarea name="criteriajson" id="criteriajson" rows="24" cols="60"></textarea>
	<br/><br/>

	<label for="criteriaurl">Or validate the JSON retrieved from the following URL</label>
	<br/>
	<input type="text" size="60" name="criteriaurl" id="criteriaurl"/>
	<br/><br/>

	<input type="submit"/>
</form>

* * *

Validation results:
<iframe id="results"></iframe>
