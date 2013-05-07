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

<a href="medium/directmail/brand/acme/criteria/demo" target="if2">/medium/directmail/brand/acme/criteria/demo</a>
<a href="medium/directmail/brand/acme/criteria/newmovers" target="if2">/medium/directmail/brand/acme/criteria/newmovers</a>
<a href="medium/directmail/brand/oscorp/criteria/carcare" target="if2">/medium/directmail/brand/oscorp/criteria/carcare</a>
<a href="medium/directmail/brand/oscorp/criteria/demo" target="if2">/medium/directmail/brand/oscorp/criteria/demo</a>
<iframe name="if2" src="about:blank"></iframe>

* * *

Affiliate Specific Criteria
---------------------------
In some cases the criteria and options will differ based on wether the campaign is executed across the entire national brand or for a specific affiliate. In the following example, the criteria is specifically for a single affiliate. Notice how the options differ in these examples from the previous examples:
<a href="medium/directmail/brand/acme/affiliate/45/criteria/demo" target="if3">/medium/directmail/brand/acme/affiliate/45/criteria/demo</a>
<a href="medium/directmail/brand/acme/affiliate/32/criteria/demo" target="if3">/medium/directmail/brand/acme/affiliate/32/criteria/demo</a>
<a href="medium/directmail/brand/oscorp/affiliate/75/criteria/demo" target="if3">/medium/directmail/brand/oscorp/affiliate/75/criteria/demo</a>
<a href="medium/directmail/brand/oscorp/affiliate/77/criteria/demo" target="if3">/medium/directmail/brand/oscorp/affiliate/77/criteria/demo</a>
<a href="medium/directmail/brand/oscorp/affiliate/75/criteria/carcare" target="if3">/medium/directmail/brand/oscorp/affiliate/75/criteria/carcare</a>
<a href="medium/directmail/brand/oscorp/affiliate/77/criteria/carcare" target="if3">/medium/directmail/brand/oscorp/affiliate/77/criteria/carcare</a>
<iframe name="if3" src="about:blank"></iframe>

* * *

Create a New List
-----------------

<form action="/medium/directmail/brand/oscorp/criteria/carcare/list" method="post" target="if4">
	<strong>POST to :</strong> medium/directmail/brand/oscorp/criteria/carcare/list<br/>
	<br/>

	columns<br/> <input type="text" name="columns" size="50" value="*"/><br/>

	filter<br/>
	<textarea name="filter" cols="40" rows="9">{
 "affiliates": [75],
 "visitedrange": ["2012-03-01", null],
 "vehicle": ["Ford", "Chevrolet", "Toyota"],
 "mileage": [null, 150000],
 "custloyalty": ["Oil Change", "Oil Change+"]
}</textarea><br/><br/>

	requestedcount<br/> <input type="text" name="requestedcount" value="100"/><br/>
	<input type="submit" value="POST"/>
</form>
<script>
	function frameload(frm) {
		txt = frm.contentWindow.document.body.innerText;
		if(txt != '') {
			listid = JSON.parse(txt).listid;
			links = document.getElementsByTagName('a');
			for (var i =  0; links.length > i; i++) {
				link = links[i];
				if(/list\/[0-9]+$/.test(link.href)) {
					link.href = link.href.replace(/[0-9]+$/, listid);
					link.innerText = link.innerText.replace(/[0-9]+$/, listid);
				}
			};
		}
	}
</script>
<iframe name="if4" src="about:blank" onload="frameload(this)"></iframe>

* * *

Retrieve List Details
---------------------
<a href="/medium/directmail/brand/oscorp/criteria/carcare/list/0" target="if5">/medium/directmail/brand/oscorp/criteria/carcare/list/0</a><br/>
<iframe name="if5" src="about:blank"></iframe>

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

