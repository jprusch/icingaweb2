CREATE DOMAIN binary20 AS bytea CONSTRAINT exactly_20_bytes_long CHECK (VALUE IS NULL OR octet_length(VALUE) = 20);

CREATE DOMAIN tinyuint AS smallint CONSTRAINT between_0_and_255 CHECK (VALUE IS NULL OR VALUE BETWEEN 0 AND 255);

CREATE TYPE boolenum AS ENUM ('n', 'y');
CREATE TYPE dashboard_type AS ENUM ('public', 'private', 'shared');

CREATE TABLE "icingaweb_dashboard_owner" (
  "id"        serial,
  "username"  character varying(254) NOT NULL
);

ALTER TABLE ONLY "icingaweb_dashboard_owner"
  ADD CONSTRAINT pk_icingaweb_dashboard_owner
  PRIMARY KEY (
    "id"
);

CREATE UNIQUE INDEX idx_dashboard_user_username
  ON "icingaweb_dashboard_owner"
  USING btree (
    lower((username)::text)
);

CREATE TABLE "icingaweb_dashboard_home" (
  "id"        serial,
  "user_id"   int NOT NULL,
  "name"      character varying(64) NOT NULL,
  "label"     character varying(64) NOT NULL,
  "priority"  tinyuint NOT NULL,
  "type"      dashboard_type DEFAULT 'private',
  "disabled"  boolenum DEFAULT 'n'
);

ALTER TABLE ONLY "icingaweb_dashboard_home"
  ADD CONSTRAINT pk_icingaweb_dashboard_home
  PRIMARY KEY (
    "id"
);

CREATE UNIQUE INDEX idx_dashboard_home
  ON "icingaweb_dashboard_home"
  USING btree (
    user_id,
    lower((name)::text)
);

ALTER TABLE ONLY "icingaweb_dashboard_home"
  ADD CONSTRAINT fk_icingaweb_dashboard_home_owner
  FOREIGN KEY (
    "user_id"
  )
  REFERENCES "icingaweb_dashboard_owner" (
    "id"
) ON UPDATE CASCADE ON DELETE CASCADE;

CREATE TABLE "icingaweb_dashboard" (
  "id"        binary20 NOT NULL,
  "home_id"   int NOT NULL,
  "name"      character varying(64) NOT NULL,
  "label"     character varying(64) NOT NULL,
  "priority"  tinyuint NOT NULL
);

ALTER TABLE "icingaweb_dashboard" ALTER COLUMN "id" SET STORAGE PLAIN;

ALTER TABLE ONLY "icingaweb_dashboard"
  ADD CONSTRAINT pk_icingaweb_dashboard
  PRIMARY KEY (
    "id"
);

ALTER TABLE ONLY "icingaweb_dashboard"
  ADD CONSTRAINT fk_icingaweb_dashboard_home
  FOREIGN KEY (
    "home_id"
  )
  REFERENCES "icingaweb_dashboard_home" (
    "id"
) ON UPDATE CASCADE ON DELETE CASCADE;

CREATE TABLE "icingaweb_dashlet" (
  "id"            binary20 NOT NULL,
  "dashboard_id"  binary20 NOT NULL,
  "name"          character varying(64) NOT NULL,
  "label"         character varying(254) NOT NULL,
  "url"           character varying(2048) NOT NULL,
  "priority"      tinyuint NOT NULL,
  "disabled"      boolenum DEFAULT 'n'
);

ALTER TABLE "icingaweb_dashlet" ALTER COLUMN "id" SET STORAGE PLAIN;
ALTER TABLE "icingaweb_dashlet" ALTER COLUMN "dashboard_id" SET STORAGE PLAIN;

ALTER TABLE ONLY "icingaweb_dashlet"
  ADD CONSTRAINT pk_icingaweb_dashlet
  PRIMARY KEY (
    "id"
);

ALTER TABLE ONLY "icingaweb_dashlet"
  ADD CONSTRAINT fk_icingaweb_dashlet_dashboard
  FOREIGN KEY (
    "dashboard_id"
  )
  REFERENCES "icingaweb_dashboard" (
    "id"
) ON UPDATE CASCADE ON DELETE CASCADE;

CREATE TABLE "icingaweb_module_dashlet" (
  "id"            binary20 NOT NULL,
  "name"          character varying(64) NOT NULL,
  "label"         character varying(64) NOT NULL,
  "module"        character varying(64) NOT NULL,
  "pane"          character varying(64) DEFAULT NULL,
  "url"           character varying(2048) NOT NULL,
  "description"   text DEFAULT NULL,
  "priority"      tinyuint DEFAULT 0
);

ALTER TABLE "icingaweb_module_dashlet" ALTER COLUMN "id" SET STORAGE PLAIN;

ALTER TABLE ONLY "icingaweb_module_dashlet"
  ADD CONSTRAINT pk_icingaweb_module_dashlet
  PRIMARY KEY (
    "id"
);

CREATE INDEX idx_module_dashlet_name
  ON "icingaweb_module_dashlet"
  USING btree (
    lower((name)::text)
);

CREATE INDEX idx_module_dashlet_pane
  ON "icingaweb_module_dashlet"
  USING btree (
    lower((pane)::text)
);

CREATE INDEX idx_module_dashlet_module
  ON "icingaweb_module_dashlet"
  USING btree (
    lower((module)::text)
);

CREATE INDEX idx_module_dashlet_priority
  ON "icingaweb_module_dashlet"
  USING btree (
    "priority"
);

CREATE TABLE "icingaweb_system_dashlet" (
  "dashlet_id"        binary20 NOT NULL,
  "module_dashlet_id" binary20 DEFAULT NULL
);

ALTER TABLE "icingaweb_system_dashlet" ALTER COLUMN "dashlet_id" SET STORAGE PLAIN;
ALTER TABLE "icingaweb_system_dashlet" ALTER COLUMN "module_dashlet_id" SET STORAGE PLAIN;

ALTER TABLE ONLY "icingaweb_system_dashlet"
  ADD CONSTRAINT pk_icingaweb_system_dashlet
  PRIMARY KEY (
    "dashlet_id"
);

ALTER TABLE ONLY "icingaweb_system_dashlet"
  ADD CONSTRAINT fk_icingaweb_system_dashlet_dashlet
  FOREIGN KEY (
    "dashlet_id"
  )
  REFERENCES "icingaweb_dashlet" (
    "id"
) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY "icingaweb_system_dashlet"
  ADD CONSTRAINT fk_icingaweb_system_dashlet_module_dashlet
  FOREIGN KEY (
    "module_dashlet_id"
  )
  REFERENCES "icingaweb_module_dashlet" (
    "id"
) ON UPDATE CASCADE ON DELETE SET NULL;
