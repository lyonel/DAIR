PRAGMA foreign_keys = OFF;
BEGIN TRANSACTION;
DROP TRIGGER close;
DROP TRIGGER reopen;
DROP TRIGGER entry_insert;
DROP TRIGGER entry_update;
DROP TRIGGER log_openclose;
ALTER TABLE entries RENAME TO entries_old;
CREATE TABLE entries(id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, category TEXT COLLATE NOCASE, summary TEXT COLLATE NOCASE, type TEXT NOT NULL COLLATE NOCASE DEFAULT 'risk', strategy TEXT COLLATE NOCASE DEFAULT 'accept', impact INTEGER, probability INTEGER, open INTEGER DEFAULT 1, status  TEXT COLLATE NOCASE DEFAULT 'new', deadline DATE, project TEXT COLLATE NOCASE, timestamp DATE DEFAULT CURRENT_TIMESTAMP, owner TEXT COLLATE NOCASE, flagged INTEGER DEFAULT 0, private INTEGER DEFAULT 0, updated DATE, parentid INTEGER REFERENCES entries ON DELETE SET NULL ON UPDATE CASCADE);
INSERT INTO entries SELECT ROWID,* FROM entries_old;
DROP TABLE entries_old;

ALTER TABLE keywords RENAME TO keywords_old;
CREATE TABLE keywords(keyword TEXT NOT NULL COLLATE NOCASE, entryid INTEGER NOT NULL REFERENCES entries ON DELETE CASCADE ON UPDATE CASCADE);
INSERT INTO keywords SELECT * FROM keywords_old;
DROP TABLE keywords_old;

DROP TRIGGER note_update;
ALTER TABLE notes RENAME TO notes_old;
CREATE TABLE notes(summary TEXT NOT NULL, entryid INTEGER NOT NULL REFERENCES entries ON DELETE CASCADE ON UPDATE CASCADE, timestamp DATE DEFAULT CURRENT_TIMESTAMP, author TEXT COLLATE NOCASE);
INSERT INTO notes SELECT * FROM notes_old;
DROP TABLE notes_old;

CREATE TRIGGER close AFTER UPDATE OF status ON entries WHEN new.status IN ('closed', 'solved')
BEGIN
UPDATE entries SET open=0 WHERE id=OLD.id;
END;
CREATE TRIGGER reopen AFTER UPDATE OF status ON entries WHEN new.status NOT IN ('closed', 'solved')
BEGIN
UPDATE entries SET open=1 WHERE id=OLD.id;
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

REPLACE INTO META VALUES('schema', '2');
COMMIT;
PRAGMA foreign_keys = OFF;
