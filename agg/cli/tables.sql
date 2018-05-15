--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET lock_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

SET search_path = public, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: current; Type: TABLE; Schema: public; Owner: crowd4roads_sw; Tablespace:
--

CREATE TABLE current (
    aggregate_id integer NOT NULL,
    ppe double precision NOT NULL,
    updated_at timestamp without time zone DEFAULT statement_timestamp(),
    osm_id bigint,
    quality double precision,
    the_geom geometry(Geometry,4326),
    highway text
);


ALTER TABLE public.current OWNER TO crowd4roads_sw;

--
-- Name: current_aggregate_id_seq; Type: SEQUENCE; Schema: public; Owner: crowd4roads_sw
--

CREATE SEQUENCE current_aggregate_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.current_aggregate_id_seq OWNER TO crowd4roads_sw;

--
-- Name: current_aggregate_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: crowd4roads_sw
--

ALTER SEQUENCE current_aggregate_id_seq OWNED BY current.aggregate_id;


--
-- Name: history; Type: TABLE; Schema: public; Owner: crowd4roads_sw; Tablespace:
--

CREATE TABLE history (
    aggregate_id integer DEFAULT nextval('current_aggregate_id_seq'::regclass) NOT NULL,
    ppe double precision NOT NULL,
    created_at timestamp without time zone DEFAULT statement_timestamp(),
    osm_id bigint,
    quality double precision,
    the_geom geometry(Geometry,4326),
    highway text,
    time_frame integer DEFAULT 0 NOT NULL
);


ALTER TABLE public.history OWNER TO crowd4roads_sw;

--
-- Name: aggregate_id; Type: DEFAULT; Schema: public; Owner: crowd4roads_sw
--

ALTER TABLE ONLY current ALTER COLUMN aggregate_id SET DEFAULT nextval('current_aggregate_id_seq'::regclass);


--
-- Name: current_pkey; Type: CONSTRAINT; Schema: public; Owner: crowd4roads_sw; Tablespace:
--

ALTER TABLE ONLY current
    ADD CONSTRAINT current_pkey PRIMARY KEY (aggregate_id);


--
-- Name: history_pkey; Type: CONSTRAINT; Schema: public; Owner: crowd4roads_sw; Tablespace:
--

ALTER TABLE ONLY history
    ADD CONSTRAINT history_pkey PRIMARY KEY (aggregate_id);


--
-- Name: the_geom_idx; Type: INDEX; Schema: public; Owner: crowd4roads_sw; Tablespace:
--

CREATE INDEX the_geom_idx ON current USING gist (the_geom);


--
-- Name: current; Type: ACL; Schema: public; Owner: crowd4roads_sw
--

REVOKE ALL ON TABLE current FROM PUBLIC;
REVOKE ALL ON TABLE current FROM crowd4roads_sw;
GRANT ALL ON TABLE current TO crowd4roads_sw;
--GRANT SELECT ON TABLE current TO srs_ro_user;


--
-- Name: history; Type: ACL; Schema: public; Owner: crowd4roads_sw
--

REVOKE ALL ON TABLE history FROM PUBLIC;
REVOKE ALL ON TABLE history FROM crowd4roads_sw;
GRANT ALL ON TABLE history TO crowd4roads_sw;
--GRANT SELECT ON TABLE history TO srs_ro_user;


--
-- Name: count; Type: TABLE; Schema: public; Owner: crowd4roads_sw; Tablespace:
--

CREATE TABLE count (
    raw integer,
    aggregate integer,
    date timestamp without time zone DEFAULT statement_timestamp()
);


ALTER TABLE public.count OWNER TO crowd4roads_sw;

--
-- PostgreSQL database dump complete
--
