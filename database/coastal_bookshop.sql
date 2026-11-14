-- Coastal Bookshop database
CREATE DATABASE IF NOT EXISTS coastal_bookshop
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE coastal_bookshop;

-- Strong entities

CREATE TABLE Customer (
  CustomerID   VARCHAR(10) PRIMARY KEY,
  Name         VARCHAR(100) NOT NULL,
  Email        VARCHAR(100) NOT NULL UNIQUE,
  Phone        VARCHAR(20),
  Address      VARCHAR(255),
  PasswordHash VARCHAR(255) NOT NULL DEFAULT '',
  IsAdmin      TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE Books (
  BookID    VARCHAR(10) PRIMARY KEY,
  Title     VARCHAR(150) NOT NULL,
  Author    VARCHAR(100) NOT NULL,
  Price     DECIMAL(10,2) NOT NULL CHECK (Price >= 0),
  Category  VARCHAR(50),
  ImageFile VARCHAR(100),
  DateAdded DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE Event (
  EventID   VARCHAR(10) PRIMARY KEY,
  EventName VARCHAR(150) NOT NULL,
  EventDate DATE NOT NULL,
  BookID    VARCHAR(10), 
  FOREIGN KEY (BookID) REFERENCES Books(BookID)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE Cart (
  CartID     VARCHAR(10) PRIMARY KEY,
  CustomerID VARCHAR(10) NOT NULL,
  FOREIGN KEY (CustomerID) REFERENCES Customer(CustomerID)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE Orders (
  OrderID              VARCHAR(10) PRIMARY KEY,
  OrderDate            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  Status               ENUM('Pending','Processing','Shipped','Delivered','Cancelled')
                       NOT NULL DEFAULT 'Pending',
  DeliveryOption       ENUM('Near Colombo','Island wide','Express') NOT NULL,
  PackagingType        ENUM('Standard','Protective') NOT NULL DEFAULT 'Standard',
  DeliveryAddress      VARCHAR(255) NOT NULL,
  DeliveryInstructions VARCHAR(255),
  CustomerID           VARCHAR(10) NOT NULL,
  FOREIGN KEY (CustomerID) REFERENCES Customer(CustomerID)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Weak entities

CREATE TABLE Member (
  CustomerID VARCHAR(10) PRIMARY KEY,
  JoinDate   DATE NOT NULL DEFAULT (CURRENT_DATE),
  FOREIGN KEY (CustomerID) REFERENCES Customer(CustomerID)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE PaymentMethod (
  OrderID    VARCHAR(10),
  MethodType ENUM('Card','Cash on Delivery','Bank Transfer') NOT NULL,
  PRIMARY KEY (OrderID, MethodType),
  FOREIGN KEY (OrderID) REFERENCES Orders(OrderID)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- Order specialization: every order is a Parcel OR a Gift
-- Parcel: an order for the customer (themselves)
CREATE TABLE Parcel (
  OrderID VARCHAR(10) PRIMARY KEY,
  FOREIGN KEY (OrderID) REFERENCES Orders(OrderID)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- Gift: an order for someone else
CREATE TABLE Gift (
  OrderID        VARCHAR(10) PRIMARY KEY,
  RecipientName  VARCHAR(100) NOT NULL,
  RecipientPhone VARCHAR(20),
  Message        VARCHAR(500),
  DecorationType ENUM('Option 1','Option 2','Option 3','Option 4','Option 5','Option 6','Option 7','None') NOT NULL DEFAULT 'None',
  FOREIGN KEY (OrderID) REFERENCES Orders(OrderID)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- Many-to-many relationships

-- Cart has Books
CREATE TABLE Cart_Books (
  CartID   VARCHAR(10),
  BookID   VARCHAR(10),
  Quantity INT NOT NULL DEFAULT 1 CHECK (Quantity > 0),
  PRIMARY KEY (CartID, BookID),
  FOREIGN KEY (CartID) REFERENCES Cart(CartID)
    ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (BookID) REFERENCES Books(BookID)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- Order has Books
CREATE TABLE Order_Books (
  OrderID   VARCHAR(10),
  BookID    VARCHAR(10),
  Quantity  INT NOT NULL DEFAULT 1 CHECK (Quantity > 0),
  UnitPrice DECIMAL(10,2) NOT NULL CHECK (UnitPrice >= 0),
  PRIMARY KEY (OrderID, BookID),
  FOREIGN KEY (OrderID) REFERENCES Orders(OrderID)
    ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (BookID) REFERENCES Books(BookID)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Member has Event
CREATE TABLE Member_Event (
  CustomerID VARCHAR(10),
  EventID    VARCHAR(10),
  PRIMARY KEY (CustomerID, EventID),
  FOREIGN KEY (CustomerID) REFERENCES Member(CustomerID)
    ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (EventID) REFERENCES Event(EventID)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- Customer likes Books
CREATE TABLE IF NOT EXISTS BookLike (
  CustomerID VARCHAR(10),
  BookID     VARCHAR(10),
  LikedAt    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (CustomerID, BookID),
  FOREIGN KEY (CustomerID) REFERENCES Customer(CustomerID)
    ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (BookID) REFERENCES Books(BookID)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
ALTER TABLE Books ADD COLUMN EbookFile VARCHAR(255) NULL AFTER ImageFile;

CREATE TABLE EbookDownload (
  CustomerID   VARCHAR(10) NOT NULL,
  BookID       VARCHAR(10) NOT NULL,
  DownloadedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (CustomerID, BookID),
  FOREIGN KEY (CustomerID) REFERENCES Customer (CustomerID) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (BookID)     REFERENCES Books (BookID)       ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock and offers (Books)

-- Stock           :Copies left
-- DiscountPercent : percentage taken off the price
-- OfferEnds       : last day of the offer. After this date the full price applies.
ALTER TABLE Books
  ADD COLUMN Stock           INT         NOT NULL DEFAULT 10 CHECK (Stock >= 0),
  ADD COLUMN DiscountPercent TINYINT     NOT NULL DEFAULT 0  CHECK (DiscountPercent BETWEEN 0 AND 100),
  ADD COLUMN OfferEnds       DATE        NULL;

-- Gift decoration choices

UPDATE Gift SET DecorationType = 'None';
ALTER TABLE Gift
  MODIFY DecorationType ENUM('Option 1','Option 2','Option 3','Option 4','Option 5','Option 6','Option 7','None')
  NOT NULL DEFAULT 'None';


--Ebooks
-- books have no ebook version.Only members can buy books.

CREATE TABLE Ebook (
    EbookID    VARCHAR(10)   NOT NULL,
    Title      VARCHAR(150)  NOT NULL,
    Author     VARCHAR(100)  NOT NULL,
    Price      DECIMAL(10,2) NOT NULL,
    CoverImage VARCHAR(255)  NULL,      
    FileName   VARCHAR(255)  NOT NULL,   
    PRIMARY KEY (EbookID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--Member buys Ebook 
CREATE TABLE Member_Ebook (
    CustomerID  VARCHAR(10)   NOT NULL,
    EbookID     VARCHAR(10)   NOT NULL,
    PricePaid   DECIMAL(10,2) NOT NULL, 
    PurchasedAt DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (CustomerID, EbookID),
    CONSTRAINT fk_me_member FOREIGN KEY (CustomerID) REFERENCES Member (CustomerID),
    CONSTRAINT fk_me_ebook  FOREIGN KEY (EbookID)    REFERENCES Ebook (EbookID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ebook offers
ALTER TABLE Ebook
  ADD COLUMN IF NOT EXISTS DiscountPercent TINYINT NOT NULL DEFAULT 0
      CHECK (DiscountPercent BETWEEN 0 AND 100),
  ADD COLUMN IF NOT EXISTS OfferEnds DATE NULL;
ALTER TABLE Member
  ADD COLUMN ResignedAt DATETIME NULL DEFAULT NULL;

UPDATE Books SET DiscountPercent = 0 WHERE OfferEnds IS NULL;
ALTER TABLE Books ADD CONSTRAINT chk_books_offer_needs_date
  CHECK (DiscountPercent = 0 OR OfferEnds IS NOT NULL);

UPDATE Ebook SET DiscountPercent = 0 WHERE OfferEnds IS NULL;
ALTER TABLE Ebook ADD CONSTRAINT chk_ebook_offer_needs_date
  CHECK (DiscountPercent = 0 OR OfferEnds IS NOT NULL);
