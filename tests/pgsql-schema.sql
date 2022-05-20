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
    c_varchar5 varchar(5) NOT NULL,
    c_varchar25 varchar(25) DEFAULT NULL,
    c_varchar255 varchar NOT NULL,
    c_date date DEFAULT NULL,
    c_time time DEFAULT NULL,
    c_datetime timestamp DEFAULT NULL,
    c_timestamp timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    c_text text,
    c_enum some_enum NOT NULL,
    c_bit255 bit(255) NOT NULL,
    c_bit25 bit(25) DEFAULT NULL,
    c_bit bit(7) DEFAULT NULL,
    c_int int NOT NULL,
    c_smallint smallint NOT NULL,
    c_bigint bigint NOT NULL,
    c_float float NOT NULL,
    c_boolean boolean NOT NULL,
    c_json json NOT NULL,
    c_json_nullable json,
    c_jsonb jsonb NOT NULL,
    c_jsonb_nullable jsonb
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
