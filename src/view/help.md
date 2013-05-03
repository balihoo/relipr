Documentation
=============
This document will walk you through a handful of examples that demonstrate how to implement and use the Balihoo List Provider API. These examples are tied into the sample list database hosted on this site. If the database has been altered drastically from its original form, these examples may not yield the expected results.

Definitions
-----------
 - **Recipient List**: A list of customers or prospects to be used in a direct marketing campaign. Throughout this documentation this will be referred to simply as a _list_.
 - **List Provider**: A vendor that provides recipient lists by implementing this List Provider API.
 - **List Consumer**: Balihoo - a company that provides local marketing automation solutions and integrates with List Providers via this API.

* * *

Criteria Collection
-------------------
The API provides a mechanism that allows the List Provider to publish a directory of pre-defined campaign criteria types that it supports. The listings are made available on a per brand/medium basis. For example:

<a href="medium/directmail/brand/acme/criteria" target="if1">/medium/directmail/brand/acme/criteria</a>
(Click link to see sample results)
<a href="medium/directmail/brand/oscorp/criteria" target="if1">/medium/directmail/brand/oscorp/criteria</a>
<iframe name="if1" src="about:blank"></iframe>
Note that the outermost container is an array. The criteria resource returns a list of applicable criteria specifications.

* * *

Single Criteria
---------------
To retrieve an individual criteria specification, append the `critieriaid` to the resource:

<a href="medium/directmail/brand/acme/criteria/12345" target="if2">/medium/directmail/brand/acme/criteria/12345</a>
<a href="medium/directmail/brand/acme/criteria/12456" target="if2">/medium/directmail/brand/acme/criteria/12456</a>
<a href="medium/directmail/brand/oscorp/criteria/osc101" target="if2">/medium/directmail/brand/oscorp/criteria/osc101</a>
<iframe name="if2" src="about:blank"></iframe>

* * *

Affiliate Specific Criteria
---------------------------
In some cases the criteria and options will differ based on wether the campaign is executed across the entire national brand or for a specific affiliate. In the following example, the criteria is specifically for a single affiliate. Notice how the options differ in these examples from the previous examples:
<a href="medium/directmail/brand/acme/affiliate/45/criteria/12456" target="if3">/medium/directmail/brand/acme/affiliate/45/criteria/12456</a>
<a href="medium/directmail/brand/acme/affiliate/32/criteria/12456" target="if3">/medium/directmail/brand/acme/affiliate/32/criteria/12456</a>
<iframe name="if3" src="about:blank"></iframe>

* * *

Create a List Request
-----------------------

<form action="/medium/directmail/brand/oscorp/criteria/osc101/list" method="post" target="iframe1">
	<strong>POST to :</strong> medium/directmail/brand/oscorp/criteria/osc101/list<br/>
	<br/>

	selections:<br/>
	<textarea name="selections" cols="40" rows="9">{
 "affiliates": [75],
 "visitedrange": ["2012-03-01", null],
 "vehicle": ["Ford", "Chevrolet", "Toyota"],
 "mileage": [null, 150000],
 "custloyalty": ["Oil Change", "Oil Change+"]
}</textarea><br/>
	<input type="submit" value="POST"/>
</form>
<iframe name="iframe1" src="about:blank"></iframe>

Calculate List Cost & Count
-----------------------------

Purchase a List
-----------------

Retrieve List Status
--------------------

Cancel a List
-------------

Download a Prepared List
------------------------

Updating Recipient Specific Results
-----------------------------------

