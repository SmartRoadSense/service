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
-- Name: agency; Type: TABLE; Schema: public; Owner: crowd4roads_sw; Tablespace:
--

CREATE TABLE agency (
    id integer NOT NULL,
    name character varying(255) NOT NULL
);


ALTER TABLE public.agency OWNER TO crowd4roads_sw;

--
-- Name: agency_id_seq; Type: SEQUENCE; Schema: public; Owner: crowd4roads_sw
--

CREATE SEQUENCE agency_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.agency_id_seq OWNER TO crowd4roads_sw;

--
-- Name: agency_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: crowd4roads_sw
--

ALTER SEQUENCE agency_id_seq OWNED BY agency.id;


--
-- Name: device; Type: TABLE; Schema: public; Owner: crowd4roads_sw; Tablespace:
--

CREATE TABLE device (
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

CREATE SEQUENCE device_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.device_id_seq OWNER TO crowd4roads_sw;

--
-- Name: device_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: crowd4roads_sw
--

ALTER SEQUENCE device_id_seq OWNED BY device.id;


--
-- Name: single_data_old; Type: TABLE; Schema: public; Owner: crowd4roads_sw; Tablespace:
--

CREATE TABLE single_data_old (
    single_data_id integer NOT NULL,
    duration bigint DEFAULT 1 NOT NULL,
    position_resolution integer,
    bearing real,
    date timestamp without time zone NOT NULL,
    meta double precision[] NOT NULL,
    osm_line_id bigint,
    projection geometry,
    evaluate smallint DEFAULT 1,
    debug smallint DEFAULT 0,
    projection_fixed smallint DEFAULT 0 NOT NULL,
    ppe double precision,
    speed double precision,
    "position" geometry(Geometry,4326),
    track_id integer
);


ALTER TABLE public.single_data_old OWNER TO crowd4roads_sw;

--
-- Name: single_data_single _data_id_seq; Type: SEQUENCE; Schema: public; Owner: crowd4roads_sw
--

CREATE SEQUENCE "single_data_single _data_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public."single_data_single _data_id_seq" OWNER TO crowd4roads_sw;

--
-- Name: single_data_single _data_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: crowd4roads_sw
--

ALTER SEQUENCE "single_data_single _data_id_seq" OWNED BY single_data_old.single_data_id;


--
-- Name: single_data; Type: TABLE; Schema: public; Owner: crowd4roads_sw; Tablespace:
--

CREATE TABLE single_data (
    single_data_id integer DEFAULT nextval('"single_data_single _data_id_seq"'::regclass) NOT NULL,
    duration bigint DEFAULT 1 NOT NULL,
    position_resolution integer,
    bearing real,
    date timestamp without time zone NOT NULL,
    meta double precision[] NOT NULL,
    osm_line_id bigint,
    projection geometry,
    evaluate smallint DEFAULT 1,
    debug smallint DEFAULT 0,
    projection_fixed smallint DEFAULT 0 NOT NULL,
    ppe double precision,
    speed double precision,
    "position" geometry(Geometry,4326),
    track_id integer
);


ALTER TABLE public.single_data OWNER TO crowd4roads_sw;

--
-- Name: track; Type: TABLE; Schema: public; Owner: crowd4roads_sw; Tablespace:
--

CREATE TABLE track (
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

CREATE SEQUENCE track_track_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.track_track_id_seq OWNER TO crowd4roads_sw;

--
-- Name: track_track_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: crowd4roads_sw
--

ALTER SEQUENCE track_track_id_seq OWNED BY track.track_id;


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: crowd4roads_sw
--

ALTER TABLE ONLY agency ALTER COLUMN id SET DEFAULT nextval('agency_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: crowd4roads_sw
--

ALTER TABLE ONLY device ALTER COLUMN id SET DEFAULT nextval('device_id_seq'::regclass);


--
-- Name: single_data_id; Type: DEFAULT; Schema: public; Owner: crowd4roads_sw
--

ALTER TABLE ONLY single_data_old ALTER COLUMN single_data_id SET DEFAULT nextval('"single_data_single _data_id_seq"'::regclass);


--
-- Name: track_id; Type: DEFAULT; Schema: public; Owner: crowd4roads_sw
--

ALTER TABLE ONLY track ALTER COLUMN track_id SET DEFAULT nextval('track_track_id_seq'::regclass);


--
-- Name: SingleData_pkey; Type: CONSTRAINT; Schema: public; Owner: crowd4roads_sw; Tablespace:
--

ALTER TABLE ONLY single_data_old
    ADD CONSTRAINT "SingleData_pkey" PRIMARY KEY (single_data_id);


--
-- Name: agency_pkey; Type: CONSTRAINT; Schema: public; Owner: crowd4roads_sw; Tablespace:
--

ALTER TABLE ONLY agency
    ADD CONSTRAINT agency_pkey PRIMARY KEY (id);


--
-- Name: device_pkey; Type: CONSTRAINT; Schema: public; Owner: crowd4roads_sw; Tablespace:
--

ALTER TABLE ONLY device
    ADD CONSTRAINT device_pkey PRIMARY KEY (id);


--
-- Name: single_data_v2_pkey; Type: CONSTRAINT; Schema: public; Owner: crowd4roads_sw; Tablespace:
--

ALTER TABLE ONLY single_data
    ADD CONSTRAINT single_data_v2_pkey PRIMARY KEY (single_data_id);


--
-- Name: trackv2_pkey; Type: CONSTRAINT; Schema: public; Owner: crowd4roads_sw; Tablespace:
--

ALTER TABLE ONLY track
    ADD CONSTRAINT trackv2_pkey PRIMARY KEY (track_id);


--
-- Name: device_agency_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: crowd4roads_sw
--

ALTER TABLE ONLY device
    ADD CONSTRAINT device_agency_id_fkey FOREIGN KEY (agency_id) REFERENCES agency(id);


--
-- Name: single_data_old_track_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: crowd4roads_sw
--

ALTER TABLE ONLY single_data_old
    ADD CONSTRAINT single_data_old_track_id_fkey FOREIGN KEY (track_id) REFERENCES track(track_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: single_data_v2_track_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: crowd4roads_sw
--

ALTER TABLE ONLY single_data
    ADD CONSTRAINT single_data_v2_track_id_fkey FOREIGN KEY (track_id) REFERENCES track(track_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: track_device_id_fkey1; Type: FK CONSTRAINT; Schema: public; Owner: crowd4roads_sw
--

ALTER TABLE ONLY track
    ADD CONSTRAINT track_device_id_fkey1 FOREIGN KEY (device_id) REFERENCES device(id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: single_data_old; Type: ACL; Schema: public; Owner: crowd4roads_sw
--

REVOKE ALL ON TABLE single_data_old FROM PUBLIC;
REVOKE ALL ON TABLE single_data_old FROM crowd4roads_sw;
GRANT ALL ON TABLE single_data_old TO crowd4roads_sw;
--GRANT SELECT ON TABLE single_data_old TO srs_ro_user;


--
-- PostgreSQL database dump complete
--

