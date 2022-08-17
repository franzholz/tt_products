#
# Table structure for table 'tt_products'
#
CREATE TABLE tt_products (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage int(11) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(3) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	title tinytext,
	subtitle mediumtext,
	slug varchar(2048),
	keyword mediumtext,
	prod_uid int(11) DEFAULT '0' NOT NULL,
	accessory_uid int(11) DEFAULT '0' NOT NULL,
	related_uid int(11) DEFAULT '0' NOT NULL,
	dam_uid int(11) DEFAULT '0' NOT NULL,
	itemnumber varchar(120) DEFAULT '' NOT NULL,
	ean varchar(48) DEFAULT '' NOT NULL,
	shipping_point varchar(24) DEFAULT '' NOT NULL,
	directcost decimal(19,2) DEFAULT '0.00' NOT NULL,
	price decimal(19,2) DEFAULT '0.00' NOT NULL,
	price2 decimal(19,2) DEFAULT '0.00' NOT NULL,
	discount decimal(19,2) DEFAULT '0.00' NOT NULL,
	discount_disable tinyint(3) unsigned DEFAULT '0' NOT NULL,
	deposit decimal(19,2) DEFAULT '0.00' NOT NULL,
	creditpoints int(11) DEFAULT '0' NOT NULL,
	graduated_price_uid int(11) DEFAULT '0' NOT NULL,
	graduated_price_round tinytext,
	graduated_price_enable int(11) DEFAULT '0' NOT NULL,
	article_uid int(11) DEFAULT '0' NOT NULL,
	note text,
	note2 text,
	note_uid int(11) DEFAULT '0' NOT NULL,
	text_uid int(11) DEFAULT '0' NOT NULL,
	download_type varchar(36) DEFAULT '' NOT NULL,
	download_info mediumtext,
	download_uid int(11) DEFAULT '0' NOT NULL,
	unit varchar(20) DEFAULT '' NOT NULL,
	unit_factor varchar(6) DEFAULT '' NOT NULL,
	image text,
	image_uid int(11) DEFAULT '0' NOT NULL,
	smallimage text,
	smallimage_uid int(11) DEFAULT '0' NOT NULL,
	datasheet text,
	datasheet_uid int(11) DEFAULT '0' NOT NULL,
	www varchar(160) DEFAULT '' NOT NULL,
	category int(11) unsigned DEFAULT '0' NOT NULL,
	syscat int(11) unsigned DEFAULT '0' NOT NULL,
	address int(11) unsigned DEFAULT '0' NOT NULL,
	inStock int(11) DEFAULT '1' NOT NULL,
	basketminquantity decimal(19,2) DEFAULT '0.00' NOT NULL,
	basketmaxquantity decimal(19,2) DEFAULT '0.00' NOT NULL,
	taxcat_id tinyint(3) unsigned DEFAULT '0',
	tax_id tinyint(3) unsigned DEFAULT '0',
	tax decimal(19,2) DEFAULT '0.00' NOT NULL,
	weight decimal(19,6) DEFAULT '0.000000' NOT NULL,
	usebydate int(11) unsigned DEFAULT '0' NOT NULL,
	bulkily int(11) DEFAULT '0' NOT NULL,
	offer int(11) DEFAULT '0' NOT NULL,
	highlight int(11) DEFAULT '0' NOT NULL,
	bargain int(11) DEFAULT '0' NOT NULL,
	color mediumtext,
	color2 mediumtext,
	color3 mediumtext,
	size mediumtext,
	size2 mediumtext,
	size3 mediumtext,
	description mediumtext,
	gradings mediumtext,
	material mediumtext,
	quality mediumtext,
	additional_type varchar(36) DEFAULT '' NOT NULL,
	additional mediumtext,
	special_preparation int(11) DEFAULT '0' NOT NULL,
	shipping decimal(19,2) DEFAULT '0.00' NOT NULL,
	shipping2 decimal(19,2) DEFAULT '0.00' NOT NULL,
	handling decimal(19,2) DEFAULT '0.00' NOT NULL,
	delivery int(11) DEFAULT '0' NOT NULL,
	sellstarttime int(11) unsigned DEFAULT '0' NOT NULL,
	sellendtime int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY comp1 (sorting),
	KEY comp2 (pid,deleted,hidden,starttime,endtime,fe_group),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid)
);


#
# Table structure for table 'tt_products_language'
#
CREATE TABLE tt_products_language (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage int(11) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	title tinytext,
	subtitle mediumtext,
	slug varchar(2048),
	keyword mediumtext,
	itemnumber varchar(120) DEFAULT '' NOT NULL,
	prod_uid int(11) DEFAULT '0' NOT NULL,
	text_uid int(11) DEFAULT '0' NOT NULL,
	note text,
	note2 text,
	unit varchar(20) DEFAULT '' NOT NULL,
	image text,
	image_uid int(11) DEFAULT '0' NOT NULL,
	smallimage text,
	smallimage_uid int(11) DEFAULT '0' NOT NULL,
	datasheet text,
	www varchar(160) DEFAULT '' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY comp1 (sorting),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid)
);


#
# Table structure for table 'tt_products_related_products_products_mm'
#
#
CREATE TABLE tt_products_related_products_products_mm (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	sorting_foreign int(10) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign),
	KEY select01 (uid_local,uid_foreign),
);


#
# Table structure for table 'tt_products_accessory_products_products_mm'
#
#
CREATE TABLE tt_products_accessory_products_products_mm (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	sorting_foreign int(10) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign),
	KEY select01 (uid_local,uid_foreign),
);


#
# Table structure for table 'tt_products_products_dam_mm'
#
#
CREATE TABLE tt_products_products_dam_mm (
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,

	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign),
);


#
# Table structure for table 'tt_products_products_note_pages_mm'
#
#
CREATE TABLE tt_products_products_note_pages_mm (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign),
);


#
# Table structure for table 'tt_products_cat'
#
CREATE TABLE tt_products_cat (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage int(11) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(3) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	starttime int(11) unsigned DEFAULT '0' NOT NULL,
	endtime int(11) unsigned DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	title tinytext,
	subtitle mediumtext,
	slug varchar(2048),
	catid varchar(40) DEFAULT '' NOT NULL,
	keyword mediumtext,
	note text,
	note2 text,
	image text,
	image_uid int(11) DEFAULT '0' NOT NULL,
	sliderimage text,
	sliderimage_uid int(11) DEFAULT '0' NOT NULL,
	email_uid int(11) DEFAULT '0' NOT NULL,
	discount decimal(19,2) DEFAULT '0.00' NOT NULL,
	discount_disable tinyint(3) unsigned DEFAULT '0' NOT NULL,
	highlight int(11) DEFAULT '0' NOT NULL,
	parent_category int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY comp1 (sorting),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid)
);


#
# Table structure for table 'tt_products_cat_language'
#
CREATE TABLE tt_products_cat_language (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage int(11) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	title tinytext,
	subtitle mediumtext,
	slug varchar(2048),
	keyword mediumtext,
	note text,
	note2 text,
	cat_uid int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY comp1 (sorting),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid)
);


#
# Table structure for table 'tt_products_products_mm_articles'
#
CREATE TABLE tt_products_products_mm_articles (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	tablenames varchar(30) DEFAULT '' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	sorting_foreign int(10) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign),
	KEY parent (pid)
);


#
# Table structure for table 'tt_products_articles'
#
CREATE TABLE tt_products_articles (
	uid int(11) DEFAULT '0' NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage int(11) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	title varchar(80) DEFAULT '' NOT NULL,
	subtitle varchar(80) DEFAULT '' NOT NULL,
	slug varchar(2048),
	keyword mediumtext,
	itemnumber varchar(120) DEFAULT '' NOT NULL,
	price decimal(19,2) DEFAULT '0.00' NOT NULL,
	price2 decimal(19,2) DEFAULT '0.00' NOT NULL,
	graduated_config_type varchar(36) DEFAULT '' NOT NULL,
	graduated_config mediumtext,
	graduated_price_uid int(11) DEFAULT '0' NOT NULL,
	graduated_price_round tinytext,
	graduated_price_enable int(11) DEFAULT '0' NOT NULL,
	note text,
	note2 text,
	image text,
	image_uid int(11) DEFAULT '0' NOT NULL,
	smallimage text,
	smallimage_uid int(11) DEFAULT '0' NOT NULL,
	inStock int(11) DEFAULT '1' NOT NULL,
	basketminquantity decimal(19,2) DEFAULT '0.00' NOT NULL,
	basketmaxquantity decimal(19,2) DEFAULT '0.00' NOT NULL,
	weight decimal(19,6) DEFAULT '0.000000' NOT NULL,
	color mediumtext,
	color2 mediumtext,
	color3 mediumtext,
	size mediumtext,
	size2 mediumtext,
	size3 mediumtext,
	description mediumtext,
	gradings mediumtext,
	material mediumtext,
	quality mediumtext,
	config_type varchar(36) DEFAULT '' NOT NULL,
	config mediumtext,
	uid_product int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY comp1 (sorting),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid)
);


#
# Table structure for table 'tt_products_articles_language'
#
CREATE TABLE tt_products_articles_language (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage int(11) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	title varchar(80) DEFAULT '' NOT NULL,
	subtitle varchar(80) DEFAULT '' NOT NULL,
	slug varchar(2048),
	keyword mediumtext,
	article_uid int(11) DEFAULT '0' NOT NULL,
	note text,
	note2 text,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY comp1 (sorting),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid)
);


#
# Table structure for table 'tt_products_gifts'
#
CREATE TABLE tt_products_gifts (
	uid int(11) DEFAULT '0' NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage int(11) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,

	ordernumber int(11) DEFAULT '0' NOT NULL,
	personname varchar(80) DEFAULT '' NOT NULL,
	personemail varchar(80) DEFAULT '' NOT NULL,
	deliveryname varchar(80) DEFAULT '' NOT NULL,
	deliveryemail varchar(80) DEFAULT '' NOT NULL,
	note text,
	amount decimal(19,2) DEFAULT '0.00' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY comp1 (sorting),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid)
);


#
# Table structure for table 'tt_products_gifts_articles_mm'
#
#
CREATE TABLE tt_products_gifts_articles_mm (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	count int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign),
);


#
# Table structure for table 'tt_products_emails'
#
CREATE TABLE tt_products_emails (
	uid int(11) DEFAULT '0' NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage int(11) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	name varchar(80) DEFAULT '' NOT NULL,
	email varchar(80) DEFAULT '' NOT NULL,
	suffix varchar(24) DEFAULT '' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY comp1 (sorting),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid)
);


#
# Table structure for table 'tt_products_texts'
#
CREATE TABLE tt_products_texts (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage int(11) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(3) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	title tinytext,
	marker varchar(255) DEFAULT '' NOT NULL,
	note text,
	parentid int(11) DEFAULT '0' NOT NULL,
	parenttable varchar(30) DEFAULT '' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY comp1 (sorting),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid)
);


#
# Table structure for table 'tt_products_texts_language'
#
CREATE TABLE tt_products_texts_language (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage int(11) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	text_uid int(11) DEFAULT '0' NOT NULL,
	title tinytext,
	note text,
	parentid int(11) DEFAULT '0' NOT NULL,
	parenttable varchar(30) DEFAULT '' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY comp1 (sorting),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid)
);


#
# Table structure for table 'tt_products_products_mm_graduated_price'
#
CREATE TABLE tt_products_mm_graduated_price (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	title tinytext,
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	tablenames varchar(30) DEFAULT '' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	sorting_foreign int(10) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign),
	KEY select01 (uid_local,uid_foreign),
	KEY parent (pid)
);


#
# Table structure for table 'tt_products_attribute_mm_graduated_price'
#
CREATE TABLE tt_products_attribute_mm_graduated_price (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(3) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	title tinytext,
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	tablenames varchar(30) DEFAULT '' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	sorting_foreign int(10) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign),
	KEY select01 (uid_local,uid_foreign),
	KEY parent (pid)
);


#
# Table structure for table 'tt_products_graduated_price'
#
CREATE TABLE tt_products_graduated_price (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(3) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	title tinytext,
	formula text,
	startamount decimal(19,2) DEFAULT '0.00' NOT NULL,
	note text,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY comp1 (sorting)
);


#
# Table structure for table 'tt_products_products_mm_tax_categories'
#
CREATE TABLE tt_products_products_mm_tax_categories (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(3) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	foreignsort int(10) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign),
	KEY sorting (sorting),
	KEY foreignsort (foreignsort),
	KEY parent (pid)
);



#
# Table structure for table 'tt_products_products_mm_downloads'
#
CREATE TABLE tt_products_products_mm_downloads (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	sorting_foreign int(10) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign),
	KEY select01 (uid_local,uid_foreign),
	KEY parent (pid)
);



#
# Table structure for table 'tt_products_downloads'
#
CREATE TABLE tt_products_downloads (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage int(11) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(3) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	title tinytext,
	slug varchar(2048),
	marker varchar(255) DEFAULT '' NOT NULL,
	note text,
	path varchar(255) DEFAULT '' NOT NULL,
	file_uid int(11) DEFAULT '0' NOT NULL,
	edition int(11) DEFAULT '0' NOT NULL,
	author varchar(255) DEFAULT '' NOT NULL,
	price_enable tinyint(3) unsigned DEFAULT '0' NOT NULL,
	price decimal(19,2) DEFAULT '0.00' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY comp1 (sorting),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid)
);


#
# Table structure for table 'sys_file_reference'
#
CREATE TABLE sys_file_reference (
	tx_ttproducts_author varchar(255) DEFAULT '' NOT NULL,
	tx_ttproducts_startpoint varchar(255) DEFAULT '' NOT NULL,
	tx_ttproducts_endpoint varchar(255) DEFAULT '' NOT NULL,
	tx_ttproducts_price_enable tinyint(3) unsigned DEFAULT '0' NOT NULL,
	tx_ttproducts_price decimal(19,2) DEFAULT '0.00' NOT NULL,
);



#
# Table structure for table 'tt_products_downloads_language'
#
CREATE TABLE tt_products_downloads_language (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage int(11) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	parent_uid int(11) DEFAULT '0' NOT NULL,
	title tinytext,
	slug varchar(2048),
	note text,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY comp1 (sorting),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid)
);


#
# Table structure for table 'sys_products_accounts'
#
CREATE TABLE sys_products_accounts (
	uid int(11) DEFAULT '0' NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	owner_name varchar(255) DEFAULT '' NOT NULL,
	iban varchar(24) DEFAULT '' NOT NULL,
	ac_number varchar(255) DEFAULT '' NOT NULL,
	bic varchar(11) DEFAULT '' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);


#
# Table structure for table 'sys_products_cards'
#
CREATE TABLE sys_products_cards (
	uid int(11) DEFAULT '0' NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	owner_name varchar(255) DEFAULT '' NOT NULL,
	cc_number varchar(255) DEFAULT '' NOT NULL,
	cc_type varchar(20) DEFAULT '' NOT NULL,
	cvv2 int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);


#
# Table structure for table 'sys_products_orders'
#
CREATE TABLE sys_products_orders (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	feusers_uid int(11) DEFAULT '0' NOT NULL,
	name varchar(80) DEFAULT '' NOT NULL,
	first_name varchar(50) DEFAULT '' NOT NULL,
	last_name varchar(50) DEFAULT '' NOT NULL,
	slug varchar(2048),
	salutation int(11) DEFAULT '0' NOT NULL,
	company varchar(80) DEFAULT '' NOT NULL,
	vat_id varchar(20) DEFAULT '' NOT NULL,
	address varchar(255) DEFAULT '' NOT NULL,
	house_no varchar(20) DEFAULT '' NOT NULL,
	zip varchar(20) DEFAULT '' NOT NULL,
	city varchar(50) DEFAULT '' NOT NULL,
	country varchar(60) DEFAULT '' NOT NULL,
#	country_code char(3) DEFAULT '' NOT NULL,
	telephone varchar(20) DEFAULT '' NOT NULL,
	email varchar(80) DEFAULT '' NOT NULL,
	fax varchar(20) DEFAULT '' NOT NULL,
	business_partner int(11) DEFAULT '0' NOT NULL,
	organisation_form varchar(2) DEFAULT 'U' NOT NULL,
	payment varchar(80) DEFAULT '' NOT NULL,
	shipping varchar(80) DEFAULT '' NOT NULL,
	amount decimal(19,2) DEFAULT '0.00' NOT NULL,
	tax_mode tinyint(3) DEFAULT '0' NOT NULL,
	pay_mode tinyint(3) DEFAULT '0' NOT NULL,
	email_notify tinyint(4) unsigned DEFAULT '0' NOT NULL,
	tracking_code varchar(64) DEFAULT '' NOT NULL,
	status tinyint(4) unsigned DEFAULT '0' NOT NULL,
	status_log blob,
	orderData mediumblob,
	orderHtml varchar(1) DEFAULT '' NOT NULL,
	creditpoints decimal(10,0) default '0' NOT NULL,
	creditpoints_spended decimal(10,0) default '0' NOT NULL,
	creditpoints_saved decimal(10,0) default '0' NOT NULL,
	creditpoints_gifts decimal(10,0) default '0' NOT NULL,
	agb tinyint(1) DEFAULT '0' NOT NULL,
	desired_date varchar(30) DEFAULT '' NOT NULL,
	desired_time varchar(30) DEFAULT '' NOT NULL,
	client_ip varchar(50) DEFAULT '' NOT NULL,
	note text,
	giftservice text,
	cc_uid int(11) unsigned DEFAULT '0' NOT NULL,
	ac_uid int(11) unsigned DEFAULT '0' NOT NULL,
	foundby int(11) DEFAULT '0' NOT NULL,
	giftcode varchar(80) DEFAULT '' NOT NULL,
	date_of_birth int(11) DEFAULT '0' NOT NULL,
	date_of_payment int(11) DEFAULT '0' NOT NULL,
	date_of_delivery int(11) DEFAULT '0' NOT NULL,
	bill_no varchar(80) DEFAULT '' NOT NULL,
	radio1 int(11) unsigned DEFAULT '0' NOT NULL,
	ordered_products varchar(1) DEFAULT '' NOT NULL,
	product_uid int(11) DEFAULT '0' NOT NULL,
	fal_uid int(11) DEFAULT '0' NOT NULL,
	gained_uid int(11) DEFAULT '0' NOT NULL,
	gained_voucher int(11) DEFAULT '0' NOT NULL,


	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY tracking (tracking_code),
	KEY status (status),
	KEY comp1 (pid,deleted)
);


#
# Table structure for table 'sys_products_orders_mm_tt_products'
#
CREATE TABLE sys_products_orders_mm_tt_products (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	sys_products_orders_qty int(11) unsigned DEFAULT '0' NOT NULL,
	variants mediumtext,
	edit_variants mediumtext,
	fal_variants mediumtext,
	tt_products_articles_uid int(11) DEFAULT '0' NOT NULL,
	tablenames varchar(50) DEFAULT '' NOT NULL,

	PRIMARY KEY (uid),
	KEY tt_products_articles_uid (tt_products_articles_uid),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);




#
# Table structure for table 'sys_products_orders_mm_gained_tt_products'
#
CREATE TABLE sys_products_orders_mm_gained_tt_products (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	quantity int(11) unsigned DEFAULT '0' NOT NULL,
	variants mediumtext,
	edit_variants mediumtext,
	sorting int(10) DEFAULT '0' NOT NULL,
	sorting_foreign int(10) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign),
	KEY select01 (uid_local,uid_foreign),
	KEY parent (pid)
);



#
# Table structure for table 'sys_products_orders_mm_gained_voucher_codes'
#
CREATE TABLE sys_products_orders_mm_gained_voucher_codes (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	sorting_foreign int(10) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign),
	KEY select01 (uid_local,uid_foreign),
	KEY parent (pid)
);



#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users (
	static_info_country char(3) DEFAULT '' NOT NULL,
	zone varchar(45) DEFAULT '' NOT NULL,
	cnum varchar(50) DEFAULT '' NOT NULL,
	tt_products_memoItems tinytext,
	tt_products_memodam tinytext,
	tt_products_discount decimal(19,2) DEFAULT '0.00' NOT NULL,
	tt_products_creditpoints decimal(10,0) DEFAULT '0' NOT NULL,
	tt_products_vouchercode varchar(50) DEFAULT '',
	tt_products_vat varchar(15) DEFAULT '' NOT NULL,
	tt_products_payment_bill int(11) DEFAULT '0' NOT NULL,
	tt_products_business_partner int(11) DEFAULT '0' NOT NULL,
	tt_products_organisation_form varchar(2) DEFAULT 'U' NOT NULL
);


#
# Table structure for table 'sys_products_fe_users_mm_visited_products'
#
#
CREATE TABLE sys_products_fe_users_mm_visited_products (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	qty int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign),
);


#
# Table structure for table 'sys_products_visited_products'
#
#
CREATE TABLE sys_products_visited_products (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	qty int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);


#
# Table structure for table 'pages_language_overlay'
#
CREATE TABLE pages_language_overlay (
	sorting int(11) DEFAULT '0' NOT NULL,
);



### cache tables needed only for TYPO3 4.3 - 4.5

#
# TABLE structure FOR TABLE 'tt_products_cache'
#
CREATE TABLE tt_products_cache (
    id int(11) unsigned NOT NULL auto_increment,
    identifier varchar(250) DEFAULT '' NOT NULL,
    crdate int(11) unsigned DEFAULT '0' NOT NULL,
    content mediumblob,
    lifetime int(11) unsigned DEFAULT '0' NOT NULL,
    PRIMARY KEY (id),
    KEY cache_id (identifier)
) ENGINE=InnoDB;



#
# TABLE structure FOR TABLE 'tt_products_cache_tags'
#
CREATE TABLE tt_products_cache_tags (
    id int(11) unsigned NOT NULL auto_increment,
    identifier varchar(250) DEFAULT '' NOT NULL,
    tag varchar(250) DEFAULT '' NOT NULL,
    PRIMARY KEY (id),
    KEY cache_id (identifier),
    KEY cache_tag (tag)
) ENGINE=InnoDB;
