CREATE TABLE PAGE_REFERENCES
(
    SOURCE_ID TEXT,
    TARGET_ID TEXT
);

CREATE UNIQUE INDEX PAGE_REFERENCES_IDX ON PAGE_REFERENCES (SOURCE_ID,TARGET_ID);
CREATE INDEX PAGE_REFERENCES_IDX_SOURCE ON PAGE_REFERENCES (SOURCE_ID);
CREATE INDEX PAGE_REFERENCES_IDX_TARGET ON PAGE_REFERENCES (TARGET_ID);








