Documentation
=============
This document will walk you through a handful of examples that demonstrate how to implement and use the Balihoo List Provider API. These examples are tied into the sample list database hosted on this site. If the database has been altered drastically from its original form, these examples may not yield the expected results.

Definitions
-----------
 - **Recipient List**: A list of customers or prospects to be used in a direct marketing campaign. Throughout this documentation this will be referred to simply as a _list_.
 - **List Provider**: A vendor that provides recipient lists by implementing this List Provider API.
 - **List Consumer**: Balihoo - a company that provides local marketing automation solutions and integrates with List Providers via this API.

* * *

Criteria Specification Collection
---------------------------------
The API provides a mechanism that allows the List Provider to publish a directory of pre-defined campaign criteria types that it supports. The listings are made available on a per brand/medium basis. For example:

[/medium/directmail/brand/acme/criteria](/medium/directmail/brand/acme/criteria):
<iframe src="/medium/directmail/brand/acme/criteria" width="100%"></iframe>
Note that the outermost container is an array. The criteria resource returns a list of applicable criteria specifications.

####Other examples:
 - [/medium/directmail/brand/oscorp/criteria](/medium/directmail/brand/oscorp/criteria 'view the oscorp criteria collection')

* * *

Individual Criteria Specification
---------------------------------
To retrieve an individual criteria, append the unique `critieriaid` to the criteria resource:
[/medium/directmail/brand/acme/criteria/12456](/medium/directmail/brand/acme/criteria/12456):
<iframe src="/medium/directmail/brand/acme/criteria/12456" width="100%"></iframe>

Create a List Request
-----------------------

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


