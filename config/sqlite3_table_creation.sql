create table sheets
(
  id integer primary key autoincrement,
  uploaded_file_name varchar(200),
  uploaded_date DATETIME not null default current_timestamp
)
;

create index sheets__when_uploaded
  on sheets (uploaded_date)
;

create table accounts
(
  id integer primary key autoincrement,
  sheet_id integer NOT NULL ,
  email VARCHAR(250) not null,
  first_name VARCHAR(250) default null,
  last_name VARCHAR(250) default null,
  company VARCHAR(250) default null,
  function VARCHAR(250) default null,
  mobile_phone VARCHAR(250) default null,
  office_phone VARCHAR(250) default null,
  address_line_1 VARCHAR(250) default null,
  address_line_2 VARCHAR(250) default null,
  city VARCHAR(250) default null,
  postal_code VARCHAR(250) default null,
  website VARCHAR(250) default null,
  twitter VARCHAR(250) default null,
  facebook VARCHAR(250) default null,
  linkedin VARCHAR(250) default null,
  FOREIGN KEY(sheet_id) REFERENCES sheets(id)
)
;

create index accounts__email
  on accounts (email)
;