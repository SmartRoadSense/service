--
-- PostgreSQL database dump
--

-- Dumped from database version 9.6.15
-- Dumped by pg_dump version 9.6.11

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET client_min_messages = warning;
SET row_security = off;

DROP INDEX public.the_geom_idx;
ALTER TABLE ONLY public.history DROP CONSTRAINT history_pkey;
ALTER TABLE ONLY public.current DROP CONSTRAINT current_pkey;
ALTER TABLE public.current ALTER COLUMN aggregate_id DROP DEFAULT;
DROP TABLE public.history;
DROP SEQUENCE public.current_aggregate_id_seq;
DROP TABLE public.current;
DROP TABLE public.count;
SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: count; Type: TABLE; Schema: public; Owner: crowd4roads_sw
--

CREATE TABLE public.count (
    raw integer,
    aggregate integer,
    date timestamp without time zone DEFAULT statement_timestamp()
);


ALTER TABLE public.count OWNER TO crowd4roads_sw;

--
-- Name: current; Type: TABLE; Schema: public; Owner: crowd4roads_sw
--

CREATE TABLE public.current (
    aggregate_id integer NOT NULL,
    ppe double precision NOT NULL,
    updated_at timestamp without time zone DEFAULT statement_timestamp(),
    osm_id bigint,
    quality double precision,
    the_geom public.geometry(Geometry,4326),
    highway text,
    stddev double precision,
    count integer,
    last_count integer,
    last_ppe double precision,
    last_stddev double precision,
    occupancy double precision
);


ALTER TABLE public.current OWNER TO crowd4roads_sw;

--
-- Name: current_aggregate_id_seq; Type: SEQUENCE; Schema: public; Owner: crowd4roads_sw
--

CREATE SEQUENCE public.current_aggregate_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.current_aggregate_id_seq OWNER TO crowd4roads_sw;

--
-- Name: current_aggregate_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: crowd4roads_sw
--

ALTER SEQUENCE public.current_aggregate_id_seq OWNED BY public.current.aggregate_id;


--
-- Name: history; Type: TABLE; Schema: public; Owner: crowd4roads_sw
--

CREATE TABLE public.history (
    aggregate_id integer DEFAULT nextval('public.current_aggregate_id_seq'::regclass) NOT NULL,
    ppe double precision NOT NULL,
    created_at timestamp without time zone DEFAULT statement_timestamp(),
    osm_id bigint,
    quality double precision,
    the_geom public.geometry(Geometry,4326),
    highway text,
    time_frame integer DEFAULT 0 NOT NULL,
    count integer,
    stddev double precision,
    last_count integer,
    last_ppe double precision,
    last_stddev double precision,
    occupancy double precision
);


ALTER TABLE public.history OWNER TO crowd4roads_sw;

--
-- Name: current aggregate_id; Type: DEFAULT; Schema: public; Owner: crowd4roads_sw
--

ALTER TABLE ONLY public.current ALTER COLUMN aggregate_id SET DEFAULT nextval('public.current_aggregate_id_seq'::regclass);


--
-- Name: current current_pkey; Type: CONSTRAINT; Schema: public; Owner: crowd4roads_sw
--

ALTER TABLE ONLY public.current
    ADD CONSTRAINT current_pkey PRIMARY KEY (aggregate_id);


--
-- Name: history history_pkey; Type: CONSTRAINT; Schema: public; Owner: crowd4roads_sw
--

ALTER TABLE ONLY public.history
    ADD CONSTRAINT history_pkey PRIMARY KEY (aggregate_id);


--
-- Name: the_geom_idx; Type: INDEX; Schema: public; Owner: crowd4roads_sw
--

CREATE INDEX the_geom_idx ON public.current USING gist (the_geom);


--
-- PostgreSQL database dump complete
--

