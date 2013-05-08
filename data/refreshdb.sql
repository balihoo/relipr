.bail ON
.separator ","
PRAGMA foreign_keys = ON;

select 'Refreshing database...';

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

create index idx_recipient_affiliateid on recipient(affiliateid);
create index idx_recipient_brandkey on recipient(brandkey);
create index idx_recipient_birthdate on recipient(birthdate);
create index idx_recipient_age on recipient(age);
create index idx_recipient_gender on recipient(gender);
create index idx_recipient_income on recipient(income);
create index idx_recipient_email on recipient(email);
create index idx_recipient_emailsubscribed on recipient(emailsubscribed);
create index idx_recipient_emailunsubscribed on recipient(emailunsubscribed);
create index idx_recipient_emailbounced on recipient(emailbounced);
create index idx_recipient_city on recipient(city);
create index idx_recipient_state on recipient(state);
create index idx_recipient_zip on recipient(zip);
create index idx_recipient_movedin on recipient(movedin);
create index idx_recipient_year on recipient(year);
create index idx_recipient_make on recipient(make);
create index idx_recipient_model on recipient(model);
create index idx_recipient_lastvisit on recipient(lastvisit);
create index idx_recipient_lastspendamount on recipient(lastspendamount);
create index idx_recipient_mileage on recipient(mileage);
create index idx_recipient_loyaltyprogram on recipient(loyaltyprogram);
create index idx_recipient_ecareclub on recipient(ecareclub);
create index idx_recipient_householdmembers on recipient(householdmembers);
create index idx_recipient_haschildren on recipient(haschildren);
create index idx_recipient_catowner on recipient(catowner);
create index idx_recipient_dogowner on recipient(dogowner);
create index idx_recipient_petowner on recipient(petowner);

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

-- create the list status table
create table status(
	status varchar(10) primary key not null
);

insert into status(status)
select 'New' status
union select 'Submitted'
union select 'Final Count'
union select 'List Ready'
union select 'Canceled'
;

select 'Created status table';

-- create the list table
create table list(
	listid integer primary key autoincrement not null,
	brandkey varchar(10) not null,
	criteriaid varchar(20) not null,
	medium varchar(20) not null,
	filter varchar(2000) not null,
	columns varchar(1000) not null,
	requestedcount integer not null,
	count integer null,
	isestimate integer null,
	cost float null,
	status varchar(10) not null,
	callback varchar(255) null,
	inserted datetime not null,
	submitted datetime null,
	canceled datetime null,
	cancelnotified datetime null,
	counted datetime null,
	countnotified datetime null,
	readied datetime null,
	readynotified datetime null,
	callbackfailures integer default 0,
	foreign key(brandkey) references brand(brandkey),
	foreign key(status) references status(status)
);
select 'Created list table';

select 'Refresh complete';

