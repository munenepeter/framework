CREATE TABLE accounts (
    id     INT AUTO_INCREMENT PRIMARY KEY,
    name   VARCHAR (50)    NOT NULL,
    amount DECIMAL (19, 4) NOT NULL
);

DELETE from accounts where id IS NULL;
INSERT INTO accounts(id, name,amount)
VALUES(1,'John',25000),
      (2,'Mary',95000);