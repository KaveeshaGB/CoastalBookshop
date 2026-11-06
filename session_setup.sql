-- Book club (sessions, messages, likes)
-- Run this ONCE, AFTER coastal_bookshop.sql (and setup_additions.sql).
USE coastal_bookshop;

-- ---------------------------------------------------------
-- Event = a book club session
-- HostID    : the member who started it
-- StartTime / EndTime : when the session runs (messages can be sent and edited only in this time)
-- Status    : the host can end a session early by setting this to 'Ended'
-- IsPublic  : 1 = everyone can see the session in the list, 0 = only members can see it
-- (EventDate stays, and is filled with the date of StartTime)
-- ---------------------------------------------------------
ALTER TABLE Event
  ADD COLUMN HostID    VARCHAR(10) NULL AFTER BookID,
  ADD COLUMN StartTime DATETIME NULL,
  ADD COLUMN EndTime   DATETIME NULL,
  ADD COLUMN Status    ENUM('Active','Ended') NOT NULL DEFAULT 'Active',
  ADD COLUMN IsPublic  TINYINT(1) NOT NULL DEFAULT 1,
  ADD CONSTRAINT fk_event_host FOREIGN KEY (HostID) REFERENCES Member(CustomerID)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT chk_event_times CHECK (StartTime IS NULL OR EndTime IS NULL OR EndTime > StartTime);

-- Member_Event = a member joined a session. LeftAt is filled when the member leaves.
ALTER TABLE Member_Event
  ADD COLUMN LeftAt DATETIME NULL;

-- ---------------------------------------------------------
-- Weak entity: Message depends on Event (Event has Messages)
-- A message is identified by its event + its own MessageID (M001, M002 ... inside each event)
-- ---------------------------------------------------------
CREATE TABLE Message (
  EventID    VARCHAR(10),
  MessageID  VARCHAR(10),
  CustomerID VARCHAR(10) NOT NULL,                    -- the member who wrote it
  Body       VARCHAR(1000) NOT NULL,
  SentAt     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  EditedAt   DATETIME NULL,
  PRIMARY KEY (EventID, MessageID),
  FOREIGN KEY (EventID) REFERENCES Event(EventID)
    ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (CustomerID) REFERENCES Member(CustomerID)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Many-to-many: Member likes Message (one like per member per message)
-- ---------------------------------------------------------
CREATE TABLE Message_Like (
  EventID    VARCHAR(10),
  MessageID  VARCHAR(10),
  CustomerID VARCHAR(10),
  PRIMARY KEY (EventID, MessageID, CustomerID),
  FOREIGN KEY (EventID, MessageID) REFERENCES Message(EventID, MessageID)
    ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (CustomerID) REFERENCES Member(CustomerID)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Derived attributes (calculated, not stored)
-- ---------------------------------------------------------

-- Number of members currently in a session
-- SELECT COUNT(*) FROM Member_Event WHERE EventID = 'E001' AND LeftAt IS NULL;

-- Number of likes on one message
-- SELECT COUNT(*) FROM Message_Like WHERE EventID = 'E001' AND MessageID = 'M001';
