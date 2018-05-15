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

--
-- Name: srs_current_to_history(integer); Type: FUNCTION; Schema: public; Owner: crowd4roads_sw
--

CREATE FUNCTION srs_current_to_history(integer) RETURNS void
    LANGUAGE plpgsql STRICT
    AS $_$
DECLARE
  how_many ALIAS FOR $1;
  new_time_frame INT := (SELECT COALESCE(max(time_frame), -1)+1 from history);
BEGIN
  insert into history (ppe, osm_id, quality, the_geom, highway, created_at, time_frame) (select ppe, osm_id, quality, the_geom, highway,  updated_at as created_at, new_time_frame from current limit how_many);
END;
$_$;


ALTER FUNCTION public.srs_current_to_history(integer) OWNER TO crowd4roads_sw;

--
-- Name: FUNCTION srs_current_to_history(integer); Type: COMMENT; Schema: public; Owner: crowd4roads_sw
--

COMMENT ON FUNCTION srs_current_to_history(integer) IS 'values from "current" table and store them in the "history" table, assigning a new time_frame value.
USAGE: SELECT srs_current_to_history(1000000);';


--
-- Name: srs_get_current_vals(geometry, numeric); Type: FUNCTION; Schema: public; Owner: crowd4roads_sw
--

CREATE FUNCTION srs_get_current_vals(the_geom geometry, ppe numeric) RETURNS numeric
    LANGUAGE plpgsql STRICT
    AS $_$
DECLARE
  raw_geom ALIAS FOR $1;
  raw_ppe ALIAS FOR $2;
  new_ppe DECIMAL;
  old_ppe DECIMAL;
BEGIN
  SELECT history.ppe INTO old_ppe FROM history WHERE raw_geom = history.the_geom;

  IF old_ppe IS NULL THEN
    RETURN raw_ppe;
    ELSE
    RETURN (raw_ppe + old_ppe)/2;
  END IF;
END;
$_$;


ALTER FUNCTION public.srs_get_current_vals(the_geom geometry, ppe numeric) OWNER TO crowd4roads_sw;

--
-- Name: FUNCTION srs_get_current_vals(the_geom geometry, ppe numeric); Type: COMMENT; Schema: public; Owner: crowd4roads_sw
--

COMMENT ON FUNCTION srs_get_current_vals(the_geom geometry, ppe numeric) IS 'Returns the new ppe avg value for a specified point.
USAGE: srs_get_current_vals(ST_SetSRID(ST_Point(11.341032355501,44.503379808374),4326),0.0450886305748702)';


--
-- PostgreSQL database dump complete
--

