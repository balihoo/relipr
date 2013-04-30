.bail ON
.separator ","

select 'Refreshing database...';

-- create the list table
create table list(
	listid integer primary key autoincrement not null,
	selections varchar(2000) not null,
	requestedCount integer not null,
	count integer null,
	isEstimate integer null,
	cost float null,
	status varchar not null
);
select 'Created list table';
	
-- create the recipient table
create table recipient(
	recipientid integer primary key not null,
	affiliateid integer not null,
	brandkey varchar(10) not null,
	firstname varchar(32) not null,
	lastname varchar(32) not null,
	birthdate date not null,
	age integer not null,
	gender char(1) not null,
	income integer not null,
	email varchar(70) not null,
	emailsubscribed date,
	emailunsubscribed date,
	emailbounced date,
	address varchar(50) not null,
	city varchar(30) not null,
	state char(2) not null,
	zip char(5) not null,
	movedin date,
	phone char(12) not null,
	mobile char(12) not null,
	year integer null,
	make varchar(25) not null,
	model varchar(30) not null,
	lastvisit date not null,
	lastspendamount integer not null,
	mileage integer not null,
	loyaltyprogram varchar(20) not null,
	ecareclub integer not null,
	householdmembers integer not null,
	haschildren integer not null,
	catowner integer not null,
	dogowner integer not null,
	petowner integer not null
);
select 'Created recipient table';

-- Load the recipient table with values from the csv file
select 'Populating the recipient table';
.import "sample.csv" recipient

select 'Loaded ' || count(*) || ' recipients from sample.csv' from recipient;

-- Create the brand table
create table brand(
	brandkey varchar(10) primary key not null
);
insert into brand (brandkey) select distinct brandkey from recipient;
select 'Loaded ' || count(*) || ' brands' from brand;

select 'Refresh complete';

