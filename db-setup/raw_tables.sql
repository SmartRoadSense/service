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

ALTER TABLE ONLY public.track DROP CONSTRAINT track_device_id_fkey1;
ALTER TABLE ONLY public.single_data DROP CONSTRAINT single_data_v2_track_id_fkey;
ALTER TABLE ONLY public.single_data_old DROP CONSTRAINT single_data_old_track_id_fkey;
ALTER TABLE ONLY public.device DROP CONSTRAINT device_agency_id_fkey;
ALTER TABLE ONLY public.track DROP CONSTRAINT trackv2_pkey;
ALTER TABLE ONLY public.single_data DROP CONSTRAINT single_data_v2_pkey;
ALTER TABLE ONLY public.device DROP CONSTRAINT device_pkey;
ALTER TABLE ONLY public.agency DROP CONSTRAINT agency_pkey;
ALTER TABLE ONLY public.single_data_old DROP CONSTRAINT "SingleData_pkey";
ALTER TABLE public.track ALTER COLUMN track_id DROP DEFAULT;
ALTER TABLE public.single_data_old ALTER COLUMN single_data_id DROP DEFAULT;
ALTER TABLE public.device ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.agency ALTER COLUMN id DROP DEFAULT;
DROP SEQUENCE public.track_track_id_seq;
DROP TABLE public.track;
DROP TABLE public.single_data;
DROP SEQUENCE public."single_data_single _data_id_seq";
DROP TABLE public.single_data_old;
DROP SEQUENCE public.device_id_seq;
DROP TABLE public.device;
DROP SEQUENCE public.agency_id_seq;
DROP TABLE public.agency;
SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: agency; Type: TABLE; Schema: public; Owner: crowd4roads_sw
--

CREATE TABLE public.agency (
    id integer NOT NULL,
    name character varying(255) NOT NULL
);


ALTER TABLE public.agency OWNER TO crowd4roads_sw;

--
-- Name: agency_id_seq; Type: SEQUENCE; Schema: public; Owner: crowd4roads_sw
--

CREATE SEQUENCE public.agency_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.agency_id_seq OWNER TO crowd4roads_sw;

--
-- Name: agency_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: crowd4roads_sw
--

ALTER SEQUENCE public.agency_id_seq OWNED BY public.agency.id;


--
-- Name: device; Type: TABLE; Schema: public; Owner: crowd4roads_sw
--

CREATE TABLE public.device (
    id integer NOT NULL,
    agency_id integer NOT NULL,
    name character varying(255) NOT NULL,
    otc character varying(255),
    public_key character varying(800),
    activation_date timestamp without time zone
);


ALTER TABLE public.device OWNER TO crowd4roads_sw;

--
-- Name: device_id_seq; Type: SEQUENCE; Schema: public; Owner: crowd4roads_sw
--

CREATE SEQUENCE public.device_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.device_id_seq OWNER TO crowd4roads_sw;

--
-- Name: device_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: crowd4roads_sw
--

ALTER SEQUENCE public.device_id_seq OWNED BY public.device.id;


--
-- Name: single_data_old; Type: TABLE; Schema: public; Owner: crowd4roads_sw
--

CREATE TABLE public.single_data_old (
    single_data_id integer NOT NULL,
    duration bigint DEFAULT 1 NOT NULL,
    position_resolution integer,
    bearing real,
    date timestamp without time zone NOT NULL,
    meta double precision[] NOT NULL,
    osm_line_id bigint,
    projection public.geometry,
    evaluate smallint DEFAULT 1,
    debug smallint DEFAULT 0,
    projection_fixed smallint DEFAULT 0 NOT NULL,
    ppe double precision,
    speed double precision,
    "position" public.geometry(Geometry,4326),
    track_id integer
);


ALTER TABLE public.single_data_old OWNER TO crowd4roads_sw;

--
-- Name: single_data_single _data_id_seq; Type: SEQUENCE; Schema: public; Owner: crowd4roads_sw
--

CREATE SEQUENCE public."single_data_single _data_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public."single_data_single _data_id_seq" OWNER TO crowd4roads_sw;

--
-- Name: single_data_single _data_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: crowd4roads_sw
--

ALTER SEQUENCE public."single_data_single _data_id_seq" OWNED BY public.single_data_old.single_data_id;


--
-- Name: single_data; Type: TABLE; Schema: public; Owner: crowd4roads_sw
--

CREATE TABLE public.single_data (
    single_data_id integer DEFAULT nextval('public."single_data_single _data_id_seq"'::regclass) NOT NULL,
    duration bigint DEFAULT 1 NOT NULL,
    position_resolution integer,
    bearing real,
    date timestamp without time zone NOT NULL,
    meta double precision[] NOT NULL,
    osm_line_id bigint,
    projection public.geometry,
    evaluate smallint DEFAULT 1,
    debug smallint DEFAULT 0,
    projection_fixed smallint DEFAULT 0 NOT NULL,
    ppe double precision,
    speed double precision,
    "position" public.geometry(Geometry,4326),
    track_id integer
);


ALTER TABLE public.single_data OWNER TO crowd4roads_sw;

--
-- Name: track; Type: TABLE; Schema: public; Owner: crowd4roads_sw
--

CREATE TABLE public.track (
    metadata character varying(1000) NOT NULL,
    device_id integer,
    vehicle_type integer DEFAULT 0,
    anchorage_type integer DEFAULT 0,
    secret character varying(700),
    date timestamp with time zone DEFAULT statement_timestamp(),
    track_id integer NOT NULL
);


ALTER TABLE public.track OWNER TO crowd4roads_sw;

--
-- Name: track_track_id_seq; Type: SEQUENCE; Schema: public; Owner: crowd4roads_sw
--

CREATE SEQUENCE public.track_track_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.track_track_id_seq OWNER TO crowd4roads_sw;

--
-- Name: track_track_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: crowd4roads_sw
--

ALTER SEQUENCE public.track_track_id_seq OWNED BY public.track.track_id;


--
-- Name: agency id; Type: DEFAULT; Schema: public; Owner: crowd4roads_sw
--

ALTER TABLE ONLY public.agency ALTER COLUMN id SET DEFAULT nextval('public.agency_id_seq'::regclass);


--
-- Name: device id; Type: DEFAULT; Schema: public; Owner: crowd4roads_sw
--

ALTER TABLE ONLY public.device ALTER COLUMN id SET DEFAULT nextval('public.device_id_seq'::regclass);


--
-- Name: single_data_old single_data_id; Type: DEFAULT; Schema: public; Owner: crowd4roads_sw
--

ALTER TABLE ONLY public.single_data_old ALTER COLUMN single_data_id SET DEFAULT nextval('public."single_data_single _data_id_seq"'::regclass);


--
-- Name: track track_id; Type: DEFAULT; Schema: public; Owner: crowd4roads_sw
--

ALTER TABLE ONLY public.track ALTER COLUMN track_id SET DEFAULT nextval('public.track_track_id_seq'::regclass);


--
-- Name: single_data_old SingleData_pkey; Type: CONSTRAINT; Schema: public; Owner: crowd4roads_sw
--

ALTER TABLE ONLY public.single_data_old
    ADD CONSTRAINT "SingleData_pkey" PRIMARY KEY (single_data_id);


--
-- Name: agency agency_pkey; Type: CONSTRAINT; Schema: public; Owner: crowd4roads_sw
--

ALTER TABLE ONLY public.agency
    ADD CONSTRAINT agency_pkey PRIMARY KEY (id);


--
-- Name: device device_pkey; Type: CONSTRAINT; Schema: public; Owner: crowd4roads_sw
--

ALTER TABLE ONLY public.device
    ADD CONSTRAINT device_pkey PRIMARY KEY (id);


--
-- Name: single_data single_data_v2_pkey; Type: CONSTRAINT; Schema: public; Owner: crowd4roads_sw
--

ALTER TABLE ONLY public.single_data
    ADD CONSTRAINT single_data_v2_pkey PRIMARY KEY (single_data_id);


--
-- Name: track trackv2_pkey; Type: CONSTRAINT; Schema: public; Owner: crowd4roads_sw
--

ALTER TABLE ONLY public.track
    ADD CONSTRAINT trackv2_pkey PRIMARY KEY (track_id);


--
-- Name: device device_agency_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: crowd4roads_sw
--

ALTER TABLE ONLY public.device
    ADD CONSTRAINT device_agency_id_fkey FOREIGN KEY (agency_id) REFERENCES public.agency(id);


--
-- Name: single_data_old single_data_old_track_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: crowd4roads_sw
--

ALTER TABLE ONLY public.single_data_old
    ADD CONSTRAINT single_data_old_track_id_fkey FOREIGN KEY (track_id) REFERENCES public.track(track_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: single_data single_data_v2_track_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: crowd4roads_sw
--

ALTER TABLE ONLY public.single_data
    ADD CONSTRAINT single_data_v2_track_id_fkey FOREIGN KEY (track_id) REFERENCES public.track(track_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: track track_device_id_fkey1; Type: FK CONSTRAINT; Schema: public; Owner: crowd4roads_sw
--

ALTER TABLE ONLY public.track
    ADD CONSTRAINT track_device_id_fkey1 FOREIGN KEY (device_id) REFERENCES public.device(id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: TABLE single_data_old; Type: ACL; Schema: public; Owner: crowd4roads_sw
--

GRANT ALL ON TABLE public.single_data_old TO postgres;


--
-- PostgreSQL database dump complete
--

