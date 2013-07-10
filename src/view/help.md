Documentation
=============
This document will walk you through a handful of examples that demonstrate how to implement and use the Balihoo List Provider API. These examples are tied into the sample list database hosted on this site. If the database has been altered drastically from its original form, these examples may not yield the expected results.

If you are asked for authentication credentials, use the following:
**username**: test
**password**: letmein

This document assumes that the reader already has a full understanding of [Balihoo List Integration](https://docs.google.com/document/d/1QSqS5QpAx4wG7__ZQaLScTmpd-IqiEBJD0Mth9926Wg/edit?usp=sharing "Baliho List Integration"). The source code for this documentation and reference implementation can be found on [github](https://github.com/pauldprice/relipr "Source Code").

To test the example criteria or other criteria please visit the [sandbox criteria utility](http://balihoo-test.s3.amazonaws.com/devSwfs/sandbox.swf "Sandbox Utility")

Definitions
-----------
 - **Recipient List**: A list of customers or prospects to be used in a direct marketing campaign. Throughout this documentation this will be referred to simply as a _list_.
 - **List Provider**: A vendor that provides recipient lists by implementing this List Provider API.
 - **List Consumer**: Balihoo - a company that provides local marketing automation solutions and integrates with List Providers via this API.

* * *

Criteria Collection
-------------------
The API provides a mechanism that allows the List Provider to publish a directory of pre-defined campaign criteria types that it supports. The listings are made available on a per brand/medium basis. For example:

<a href="medium/directmail/brand/acme/criteria" target="if1">medium/directmail/brand/acme/criteria</a>
(Click link to see sample results)
<a href="medium/directmail/brand/oscorp/criteria" target="if1">medium/directmail/brand/oscorp/criteria</a>
<iframe name="if1" src="about:blank"></iframe>
Note that the outermost container is an array. The criteria resource returns a list of applicable criteria specifications.

* * *

Single Criteria
---------------
To retrieve an individual criteria specification, append the `critieriaid` to the resource:

<a href="medium/directmail/brand/acme/criteria/demo" target="if2">medium/directmail/brand/acme/criteria/demo</a>
<a href="medium/directmail/brand/acme/criteria/newmovers" target="if2">medium/directmail/brand/acme/criteria/newmovers</a>
<a href="medium/directmail/brand/oscorp/criteria/carcare" target="if2">medium/directmail/brand/oscorp/criteria/carcare</a>
<a href="medium/directmail/brand/oscorp/criteria/demo" target="if2">medium/directmail/brand/oscorp/criteria/demo</a>
<a href="medium/email/brand/choam/criteria/everything" target="if2">medium/email/brand/choam/criteria/everything</a>
<iframe name="if2" src="about:blank"></iframe>

* * *

Affiliate Specific Criteria
---------------------------
In some cases the criteria and options will differ based on wether the campaign is executed across the entire national brand or for a specific affiliate. In the following example, the criteria is specifically for a single affiliate. Notice how the options differ in these examples from the previous examples:
<a href="medium/directmail/brand/acme/affiliate/45/criteria/demo" target="if3">medium/directmail/brand/acme/affiliate/45/criteria/demo</a>
<a href="medium/directmail/brand/acme/affiliate/32/criteria/demo" target="if3">medium/directmail/brand/acme/affiliate/32/criteria/demo</a>
<a href="medium/directmail/brand/oscorp/affiliate/75/criteria/demo" target="if3">medium/directmail/brand/oscorp/affiliate/75/criteria/demo</a>
<a href="medium/directmail/brand/oscorp/affiliate/77/criteria/demo" target="if3">medium/directmail/brand/oscorp/affiliate/77/criteria/demo</a>
<a href="medium/directmail/brand/oscorp/affiliate/75/criteria/carcare" target="if3">medium/directmail/brand/oscorp/affiliate/75/criteria/carcare</a>
<a href="medium/directmail/brand/oscorp/affiliate/77/criteria/carcare" target="if3">medium/directmail/brand/oscorp/affiliate/77/criteria/carcare</a>
<iframe name="if3" src="about:blank"></iframe>

* * *

Get the Estimated Count
-------------------
During the list selection process a user may be interested in determining how many recipients will match the selected criteria. The estimate resource will return a preliminary count that can be used for this purpose. Note that the `affiliates` property of the filter parameter may be left empty or set to one or more affiliate numbers.

<form method="POST" action="medium/directmail/brand/oscorp/criteria/demo/estimate" target="ifestimate">
	<input type="text" name="filter" value="{}" size="40"/>
	<input type="submit" value="Empty filter"/>
</form>
<form method="POST" action="medium/directmail/brand/oscorp/criteria/demo/estimate" target="ifestimate">
	<input type="text" name="filter" value='{"gender":["m"]}' size="40"/>
	<input type="submit" value="Males only"/>
</form>
<form method="POST" action="medium/directmail/brand/oscorp/criteria/demo/estimate" target="ifestimate">
	<input type="text" name="filter" value='{"gender":["f"]}' size="40"/>
	<input type="submit" value="Females only"/>
</form>
<form method="POST" action="medium/directmail/brand/oscorp/criteria/demo/estimate" target="ifestimate">
	<input type="text" name="filter" value='{"gender":["m"],"affiliates":["75"]}' size="40"/>
	<input type="submit" value="Males for affiliate 75"/>
</form>
<form method="POST" action="medium/directmail/brand/oscorp/criteria/demo/estimate" target="ifestimate">
	<input type="text" name="filter" value='{"gender":["m"],"affiliates":["77"]}' size="40"/>
	<input type="submit" value="Males for affiliate 77"/>
</form>
<form method="POST" action="medium/directmail/brand/oscorp/criteria/demo/estimate" target="ifestimate">
	<input type="text" name="filter" value='{"gender":["m"],"affiliates":["75","77"]}' size="40"/>
	<input type="submit" value="Males for affiliates 75 &amp; 77"/>
</form>
<iframe name="ifestimate" src="about:blank" style="height:50px"></iframe>

* * *

Create a New List
-----------------
A _list_ contains all of the information about a campaign that will enable the List Provider to determine the exact recipients to target for a marketing campaign.

**List Parameters**

 - `criteriaid`: Tells the list provider which criteria specification to use (provided in the URL along with medium & brand).
 - `columns`: Indicates which data columns this campaign will use. The List Provider will use this when constructing the final downloaded list and should only return the specified columns. As in SQL, `*` means all columns.
 - `filter`: The filter indicates to the list provider which criteria selections were made by the affiliate. This is a JSON object where the keys represent `criterionid`s and the values represent the user selected values.
 - `orderinfo`: Information about the order including `OrderID`, `DeliveryDate` and other pertinent information will be passed in this JSON object (key/value pairs)
 - `affiliateinfo`: Similar to `orderinfo` this represents data known by Balihoo that are specific to the affiliate that is using this requested list in their marketing order.
 - `creativeinfo`: Another JSON object that represents all of the field and layer selections that the affiliate made while customizing the creative that is going to be used in this campaign.
 - `requestedcount`: This field indicates the _maximum_ number of recipients that the affiliate wishes to target for this campaign. If the list query results in more recipients that `requestedcount` then the list provider uses an algorithm to reduce the total list size to this requested size.
 - `callback`: The list provider will POST the list object to this URL whenever the list status changes to `Cancelled`, `Final Count` or `List Ready`.

<script>
	function frameload(frm) {
		txt = frm.contentWindow.document.body.innerText;
		if(txt != '') {
			listid = JSON.parse(txt).listid;
			updateListId('a', 'href', listid);
			updateListId('a', 'innerText', listid);
			updateListId('form', 'action', listid);
			updateListId('label', 'innerText', listid);
		}
	}
	function updateListId(tagName, propName, listid) {
		nodeList = document.getElementsByTagName(tagName);
		for (var i =  0; nodeList.length > i; i++) {
			node = nodeList[i];
			if(/list\/[0-9]+/.test(node[propName])) {
				text = node[propName] + "";
				console.log(tagName + "" + text);
				node[propName] = text.replace(/list\/[0-9]+/, 'list/' + listid);
			}
		}
	}
</script>


<form action="medium/directmail/brand/oscorp/criteria/carcare/list" method="post" target="if4">
	<strong>Use this form to create a new list</strong><br/><br/>

	columns<br/> <input type="text" name="columns" size="50" value="*"/><br/>

	filter<br/>
	<textarea name="filter" cols="40" rows="9">{
 "affiliates": ["75"],
 "visitedrange": ["2012-03-01", null],
 "vehicle": ["Ford", "Chevrolet", "Toyota"],
 "mileage": [null, 150000],
 "custloyalty": ["Oil Change", "Oil Change+"]
}</textarea><br/><br/>

	orderinfo<br/>
	<textarea name="orderinfo" cols="40" rows="10">{
	"OrderID":10,
	"AffiliateNumber":"75",
	"DeliveryDate":"2016-12-25",
	"IsAffiliateInitiated":false
}</textarea><br/><br/>

	affiliateinfo<br/>
	<textarea name="affiliateinfo" cols="40" rows="10">{
	"Active": true,
	"CompanyName": "Bob's Corner Store",
	"WebsiteURL": "www.bobscornerstore.example.com",
	"BillingAddress":{
		"AddressLine1":"123 Main St.",
		"AddressLine2":"",
		"City":"Boise",
		"StateProvinceCode":"ID",
		"CountryCode":"US",
		"PostalCode":"83702",
		"Phone":"208-555-5555",
		"Fax":"208-555-5551",
		"Email":"bob@example.com"
	},
	"Segments":[
		{	"Segment":"Default",
			"StartDate":"2000-01-01",
			"EndDate":null
		}
	],
	"Attributes":{
		"_BusinessHours":"M-F 8-5",
		"_Directions":"Corner of Main and Front"
	}
}</textarea><br/><br/>

	creativeinfo<br/>
	<textarea name="creativeinfo" cols="40" rows="10">{
	"fields":{
		"RegularPrice":"$10.99",
		"SalePrice":"$8.99",
		"OfferExpires":"8/15/2013",
		"BackgroundImage":"water"
	},
	"layers":{
		"onestore": "onestore",
		"base": "base",
		"holiday": "holiday"
	},
	"creativedetails":{
		"Name":"5.5 x 8.5 Magnet Mailer",
		"TemplateName":"5.5 x 8.5 Magnet Mailer",
		"User":"bob@example.com",
		"TemplateID":28532,
		"CreativeID":28533
	}
}</textarea><br/><br/>

	requestedcount<br/> <input type="text" name="requestedcount" value="100" size="6"/><br/>

	callback<br/> <input type="text" name="callback" size="40" value="http://requestb.in/rxoo58rx"/><br/>

	<input type="submit" value="POST to:"/>
	medium/directmail/brand/oscorp/criteria/carcare/list<br/>
	<small>Notice: the rest of the examples in this document require `listid` in order to execute successfully. Whenever you create a new list by clicking the POST button, those links will be updated with the new listid</small>
</form>
<iframe name="if4" src="about:blank" onload="frameload(this)"></iframe>

* * *

Retrieve List Details
---------------------
Once a list has been created it can be retrieved with a GET request.

<a href="medium/directmail/brand/oscorp/criteria/carcare/list/0" target="if5">medium/directmail/brand/oscorp/criteria/carcare/list/0</a><br/>
<iframe name="if5" src="about:blank"></iframe>

* * *

Cancel a List
-------------
If the affiliate decides to cancel the campaign, Balihoo will attempt to cancel the list with the List Provided. If the list has already been submitted, the List Provider may choose to cancel the list or respond with `403 - Forbidden`. After the `Final Count` has been made, the list should no longer be cancelable.

<form action="medium/directmail/brand/oscorp/criteria/carcare/list/0/cancel" method="post" target="if7">
	<label>POST to: medium/directmail/brand/oscorp/criteria/carcare/list/0/cancel</label>
	<input type="submit" value="POST"/>
</form>
<iframe name="if7" src="about:blank"></iframe>

* * *

Background Jobs
---------------
The following actions are not normally invoked via the API, but are triggered events that occur while processing and preparing a list. These actions will be invoked by background processing, but for testing you can push the processing along with the following actions.

<form action="/jobs/callback" method="POST" target="ifjob">
	<input type="submit" value="Run Callbacks"/>
	Run any pending callback events. If a list has been cancelled, counted or readied then the event and list will be posted to the provided callback url. If you used the default callback url from this documentation then you will see the callback POST details in the <a href="http://requestb.in/rxoo58rx?inspect">requestb.in bucket</a>.
</form>

<form action="/jobs/count" method="POST" target="ifjob">
	<input type="submit" value="Calculate Counts"/>
	Calculate count and cost for any lists that have been submitted, this will move the list into the 'Final Count' status and will call event callback handlers.
</form>

<form action="/jobs/ready" method="POST" target="ifjob">
	<input type="submit" value="Finalize Lists"/>
	This will move lists from the 'Final Count' status to the 'List Ready' status and will call event callback handlers.
</form>

<iframe name="ifjob" src="about:blank"></iframe>

* * *

Download a Prepared List
------------------------
Once a list has reached the `List Ready` status the list/download link will become available. Click on the link to download the final recipient list.

<a href="medium/directmail/brand/oscorp/criteria/carcare/list/0/download" target="_blank">medium/directmail/brand/oscorp/criteria/carcare/list/0/download</a><br/>

* * *

Updating Campaign Recipient Results
-----------------------------------
After a campaign has executed, Balihoo will collect campaign results. As campaign results are collected Balihoo will POST results to the List Provider.
<form action="medium/directmail/brand/oscorp/criteria/carcare/list/0/result" method="POST" target="ifresult">
	results<br/>
	<textarea name="results" cols="40" rows="5">[
{"type": "delivered", "recipientid": "1234", "timestamp": 1369935877, "detail":"Message delivered"},
{"type": "hardbounce", "recipientid": "2384", "timestamp": 1369992185, "detail": "Unrecognized email address"},
{"type": "click", "recipientid": "5297", "timestamp": 1369935876, "detail": "http://www.example.com/testclick"}
]</textarea><br/><br/>
	<input type="submit" value="Post Results to:"/>
	<label>medium/directmail/brand/oscorp/criteria/carcare/list/0/result</label>
</form>
<iframe name="ifresult" src="about:blank" style="height:100px"></iframe>

