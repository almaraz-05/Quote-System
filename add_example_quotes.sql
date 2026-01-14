-- QUOTE 1: Status = 'open', Associate = Zoe, Customer = IBM
INSERT INTO quote (associate_id, date_created, customer_id, customer_email, status, discount, quote_price)
VALUES (1, '2025-07-01', 1, 'sales@ibm.com', 'open', 5.00, 9500.00);

INSERT INTO line_item (quote_id, description, price) VALUES
(1, 'Server Rack Repair', 5000.00),
(1, 'Network Optimization', 5000.00);

INSERT INTO secret_note (quote_id, description) VALUES
(1, 'IBM wants expedited delivery');

-- QUOTE 2: Status = 'finalized', Associate = Mr. Krabs, Customer = Alcatel
INSERT INTO quote (associate_id, date_created, customer_id, customer_email, status, discount, quote_price)
VALUES (2, '2025-07-10', 3, 'contact@alcatel.com', 'finalized', 10.00, 13500.00);

INSERT INTO line_item (quote_id, description, price) VALUES
(2, 'Switch Repair', 4000.00),
(2, 'Fiber Installation', 11000.00);

INSERT INTO secret_note (quote_id, description) VALUES
(2, 'Customer requested full logs'),
(2, 'Tight deadline – 2 weeks');

-- QUOTE 3: Status = 'sanctioned', Associate = Zoe, Customer = Insight Technologies
INSERT INTO quote (associate_id, date_created, customer_id, customer_email, status, discount, quote_price)
VALUES (1, '2025-07-15', 4, 'insight@itg.com', 'sanctioned', 0.00, 20000.00);

INSERT INTO line_item (quote_id, description, price) VALUES
(3, 'Full Plant Rewire', 20000.00);

-- QUOTE 4: Status = 'ordered', Associate = Mr. Krabs, Customer = Bell South
INSERT INTO quote (associate_id, date_created, customer_id, customer_email, status, discount, quote_price)
VALUES (2, '2025-07-20', 6, 'orders@bellsouth.com', 'ordered', 15.00, 25500.00);

INSERT INTO line_item (quote_id, description, price) VALUES
(4, 'Core Plant Equipment Upgrade', 30000.00);

INSERT INTO secret_note (quote_id, description) VALUES
(4, 'Confirmed budget approval by finance'),
(4, 'Expected shipping by August 5th');

-- QUOTE 5: Status = 'open', Associate = Mr. Krabs, Customer = Rational Software
INSERT INTO quote (associate_id, date_created, customer_id, customer_email, status, discount, quote_price)
VALUES (2, '2025-07-28', 5, 'rational@ibm.com', 'open', 7.00, 12000.00);

INSERT INTO line_item (quote_id, description, price) VALUES
(5, 'Tooling Setup', 5000.00),
(5, 'Code Quality Audit', 7000.00);
