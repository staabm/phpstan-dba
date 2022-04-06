CREATE DATABASE phpstan_dba;

CREATE TABLE ada (
    adaid smallint NOT NULL,
    gesperrt int NOT NULL DEFAULT 0,
    email varchar(100) NOT NULL DEFAULT '',
    freigabe1u1 smallint NOT NULL
);

ALTER TABLE ada ADD PRIMARY KEY (adaid);

CREATE TABLE ak (
    akid int NOT NULL DEFAULT 0,
    eladaid int DEFAULT NULL,
    eadavk decimal(12,2) NOT NULL
);

ALTER TABLE ak
    ADD PRIMARY KEY (akid);

CREATE TYPE some_enum AS ENUM ('small','medium','large');
CREATE TABLE typemix (
    pid SERIAL,
    c_char5 varchar(5) NOT NULL,
    c_varchar255 varchar(255) NOT NULL,
    c_varchar25 varchar(25) DEFAULT NULL,
    c_varbinary255 varchar NOT NULL,
    c_varbinary25 varchar DEFAULT NULL,
    c_date date DEFAULT NULL,
    c_time time DEFAULT NULL,
    c_datetime timestamp DEFAULT NULL,
    c_timestamp timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    c_year date DEFAULT NULL,
    c_tiny_text varchar DEFAULT NULL,
    c_medium_text varchar DEFAULT NULL,
    c_text text,
    c_long_text text,
    c_enum some_enum NOT NULL,
    c_set varchar NOT NULL,


--     CBIT not tested now
--     c_bit255 bit(255) NOT NULL,
--     c_bit25 bit(25) DEFAULT NULL,
    c_bit bit(7) DEFAULT NULL,
-- integer types included in tests however some types do not exist in psql
    c_int int NOT NULL,
    c_tinyint smallint NOT NULL,
    c_smallint smallint NOT NULL,
    c_mediumint smallint NOT NULL,
    c_bigint bigint NOT NULL,
    c_double float NOT NULL,
    c_real float NOT NULL,
    c_float float NOT NULL,
    c_boolean boolean NOT NULL,

--     NOT TESTED
--     c_bytea bytea NOT NULL,

-- TESTED but types do not exist in psql
    c_blob varchar NOT NULL,
    c_tinyblob varchar NOT NULL,
    c_mediumblog varchar NOT NULL,
    c_longblob varchar NOT NULL,
    c_unsigned_tinyint smallint NOT NULL,
    c_unsigned_int int NOT NULL,
    c_unsigned_smallint smallint NOT NULL,
    c_unsigned_mediumint smallint NOT NULL,
    c_unsigned_bigint bigint NOT NULL
--
);

ALTER TABLE typemix
    ADD PRIMARY KEY (pid);

CREATE TABLE cmsdomain (
    id SERIAL,
    cmsdomainid int NOT NULL,
    url varchar(255) NOT NULL,
    standard integer NOT NULL
);

ALTER TABLE cmsdomain
    ADD PRIMARY KEY (id);

CREATE INDEX ON cmsdomain(cmsdomainid);
CREATE INDEX ON cmsdomain(url);
