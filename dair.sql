CREATE TABLE META(name TEXT COLLATE NOCASE PRIMARY KEY, value TEXT);
CREATE TABLE entries(id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, category TEXT COLLATE NOCASE, summary TEXT COLLATE NOCASE, type TEXT NOT NULL COLLATE NOCASE DEFAULT 'risk', strategy TEXT COLLATE NOCASE DEFAULT 'accept', impact INTEGER, probability INTEGER, open INTEGER DEFAULT 1, status  TEXT COLLATE NOCASE DEFAULT 'new', deadline DATE, project TEXT COLLATE NOCASE, timestamp DATE DEFAULT CURRENT_TIMESTAMP, owner TEXT COLLATE NOCASE, flagged INTEGER DEFAULT 0, private INTEGER DEFAULT 0, updated DATE, parentid INTEGER REFERENCES entries ON DELETE SET NULL ON UPDATE CASCADE);
CREATE TABLE keywords(keyword TEXT NOT NULL COLLATE NOCASE, entryid INTEGER NOT NULL REFERENCES entries ON DELETE CASCADE ON UPDATE CASCADE);
CREATE TABLE notes(summary TEXT NOT NULL, entryid INTEGER NOT NULL REFERENCES entries ON DELETE CASCADE ON UPDATE CASCADE, timestamp DATE DEFAULT CURRENT_TIMESTAMP, author TEXT COLLATE NOCASE);
CREATE VIEW deadlines AS SELECT ' overdue' AS tag,id AS entryid FROM entries WHERE open AND (DATE(deadline)<=DATE('now','localtime')) UNION SELECT ' due this month' AS tag,id AS entryid FROM entries WHERE open AND (DATE(deadline)>=DATE('now', 'localtime', 'start of month')) AND (DATE(deadline)<DATE('now', 'localtime', 'start of month', '+1 month')) UNION SELECT ' due  this week' AS tag,id AS entryid FROM entries WHERE open AND (DATE(deadline)>=DATE('now', 'localtime', 'weekday 0', '-7 day')) AND (DATE(deadline)<DATE('now', 'localtime', 'weekday 0')) UNION SELECT ' due next week' AS tag,id AS entryid FROM entries WHERE open AND (DATE(deadline)>=DATE('now', 'localtime', 'weekday 0')) AND (DATE(deadline)<DATE('now', 'localtime', 'weekday 0', '+7 day'));
CREATE VIEW persons AS SELECT owner AS person,id AS entryid FROM entries UNION SELECT author AS person,entryid FROM notes UNION SELECT ' no owner'AS tag,id AS entryid FROM entries WHERE open AND owner IS NULL;
CREATE VIEW tags AS SELECT keyword AS tag,entryid FROM keywords UNION SELECT category AS tag,id AS entryid FROM entries UNION SELECT project AS tag,id AS entryid FROM entries UNION SELECT ' flagged',id AS entryid FROM entries WHERE flagged UNION SELECT * FROM deadlines;
CREATE TRIGGER close AFTER UPDATE OF status ON entries WHEN new.status IN ('closed', 'solved')
BEGIN
UPDATE entries SET open=0 WHERE id=OLD.id;
END;
CREATE TRIGGER entry_insert AFTER INSERT ON entries BEGIN
UPDATE entries SET updated=DATETIME('now') WHERE id=new.parentid;
END;
CREATE TRIGGER entry_update AFTER UPDATE ON entries BEGIN
UPDATE entries SET updated=DATETIME('now') WHERE id=OLD.id;
END;
CREATE TRIGGER log_openclose AFTER UPDATE OF open ON entries WHEN NEW.open!=OLD.open
BEGIN
INSERT INTO notes (summary, entryid) VALUES (NEW.status, OLD.id);
END;
CREATE TRIGGER note_update AFTER INSERT ON notes BEGIN
UPDATE entries SET updated=DATETIME('now') WHERE id=NEW.entryid;
END;
CREATE TRIGGER reopen AFTER UPDATE OF status ON entries WHEN new.status NOT IN ('closed', 'solved')
BEGIN
UPDATE entries SET open=1 WHERE id=OLD.id;
END;
