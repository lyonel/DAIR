CREATE TABLE META(name TEXT COLLATE NOCASE PRIMARY KEY, value TEXT);
CREATE TABLE entries(title TEXT NOT NULL, category TEXT COLLATE NOCASE, summary TEXT COLLATE NOCASE, type TEXT NOT NULL COLLATE NOCASE DEFAULT 'risk', strategy TEXT COLLATE NOCASE DEFAULT 'accept', impact INTEGER, probability INTEGER, open INTEGER DEFAULT 1, status  TEXT COLLATE NOCASE DEFAULT 'new', deadline DATE, project TEXT COLLATE NOCASE, timestamp DATE DEFAULT CURRENT_TIMESTAMP, owner TEXT COLLATE NOCASE, flagged INTEGER DEFAULT 0, private INTEGER DEFAULT 0, updated DATE, parentid INTEGER);
CREATE TABLE keywords(keyword TEXT NOT NULL COLLATE NOCASE, entryid INTEGER NOT NULL);
CREATE TABLE notes(summary TEXT NOT NULL, entryid INTEGER NOT NULL, timestamp DATE DEFAULT CURRENT_TIMESTAMP, author TEXT COLLATE NOCASE);
CREATE VIEW persons AS SELECT owner AS person,ROWID AS entryid FROM entries UNION SELECT author AS person,entryid FROM notes;
CREATE VIEW tags AS SELECT keyword AS tag,entryid FROM keywords UNION SELECT category AS tag,ROWID AS entryid FROM entries UNION SELECT project AS tag,ROWID AS entryid FROM entries UNION SELECT 'overdue' AS tag,ROWID AS entryid FROM entries WHERE open AND (DATE(deadline)<=DATE('now','localtime')) UNION SELECT 'due this month' AS tag,ROWID AS entryid FROM entries WHERE open AND (DATE(deadline)>=DATE('now', 'localtime', 'start of month')) AND (DATE(deadline)<DATE('now', 'localtime', 'start of month', '+1 month')) UNION SELECT 'due this week' AS tag,ROWID AS entryid FROM entries WHERE open AND (DATE(deadline)>=DATE('now', 'localtime', 'weekday 0', '-7 day')) AND (DATE(deadline)<DATE('now', 'localtime', 'weekday 0')) UNION SELECT 'due next week' AS tag,ROWID AS entryid FROM entries WHERE open AND (DATE(deadline)>=DATE('now', 'localtime', 'weekday 0')) AND (DATE(deadline)<DATE('now', 'localtime', 'weekday 0', '+7 day')) UNION SELECT 'no owner'AS tag,ROWID AS entryid FROM entries WHERE open AND owner IS NULL;
CREATE TRIGGER close AFTER UPDATE OF status ON entries WHEN new.status IN ('closed', 'solved')
BEGIN
UPDATE entries SET open=0 WHERE ROWID=old.ROWID;
INSERT INTO notes (summary, entryid) VALUES ('closed', old.ROWID);
END;
CREATE TRIGGER entry_delete AFTER DELETE ON entries
BEGIN
DELETE FROM keywords WHERE entryid=old.ROWID;
DELETE FROM notes WHERE entryid=old.ROWID;
END;
CREATE TRIGGER entry_insert AFTER INSERT ON entries BEGIN
UPDATE entries SET updated=DATETIME('now') WHERE ROWID=new.parentid;
END;
CREATE TRIGGER entry_update AFTER UPDATE ON entries BEGIN
UPDATE entries SET updated=DATETIME('now') WHERE ROWID=old.ROWID;
UPDATE entries SET updated=DATETIME('now') WHERE ROWID=new.parentid;
END;
CREATE TRIGGER note_update AFTER INSERT ON notes BEGIN
UPDATE entries SET updated=DATETIME('now') WHERE ROWID=new.entryid;
END;
CREATE TRIGGER reopen AFTER UPDATE OF status ON entries WHEN new.status NOT IN ('closed', 'solved')
BEGIN
UPDATE entries SET open=1 WHERE ROWID=old.ROWID;
INSERT INTO notes (summary, entryid) VALUES ('reopened', old.ROWID);
END;
