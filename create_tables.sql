drop table if exists line_item;
drop table if exists secret_note;
drop table if exists quote;
drop table if exists customers;
drop table if exists sales_associate;

CREATE TABLE sales_associate (
    associate_id int auto_increment PRIMARY KEY,
    name varchar(50) NOT NULL,
    address varchar(50),
    userid varchar(50) NOT NULL UNIQUE,
    password varchar(50) NOT NULL,
    accumulated_commission decimal(10,2) DEFAULT 0.00
);

CREATE TABLE quote (
    quote_id int auto_increment PRIMARY KEY,
    associate_id int NOT NULL,
    date_created date NOT NULL,
    processing_date date,
    customer_id int NOT NULL,
    customer_email varchar(50),
    status enum('open', 'finalized', 'sanctioned', 'ordered') DEFAULT 'open',
    discount decimal(10,2) DEFAULT 0.00,
    is_percent BOOLEAN NOT NULL DEFAULT TRUE,
    final_discount decimal(10,2) DEFAULT 0.00,
    quote_price decimal(10,2) DEFAULT 0.00,
    
    FOREIGN KEY (associate_id) REFERENCES sales_associate(associate_id)
);

CREATE TABLE line_item (
    quote_id int NOT NULL,
    line_item_id int auto_increment PRIMARY KEY,
    description varchar(50),
    price decimal(10,2) NOT NULL,

    FOREIGN KEY (quote_id) REFERENCES quote(quote_id)
);

CREATE TABLE secret_note (
    quote_id int NOT NULL,
    note_id int auto_increment PRIMARY KEY,
    description varchar(50),

    FOREIGN KEY (quote_id) REFERENCES quote(quote_id)
);

INSERT INTO sales_associate (name, address, userid, password) VALUES ('Zoe Zuzzio', '123 Apple Lane', 'zoezuzzio', 'password');
INSERT INTO sales_associate (name, address, userid, password) VALUES ('Mr. Krabs', '3541 Anchor Way', 'krabbypatty', 'money');