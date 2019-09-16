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

--
-- Name: srs_abc_intersection_exists(bigint, bigint, bigint, public.geometry, integer, integer); Type: FUNCTION; Schema: public; Owner: crowd4roads_sw
--

CREATE FUNCTION public.srs_abc_intersection_exists(bigint, bigint, bigint, public.geometry, integer, integer) RETURNS boolean
    LANGUAGE plpgsql STABLE STRICT
    AS $_$
DECLARE
  aroad ALIAS FOR $1;
  broad ALIAS FOR $2;
  croad ALIAS FOR $3;
  pnt ALIAS FOR $4;
  cross_max ALIAS FOR $5;
  pnt_max ALIAS FOR $6;
  cnt INTEGER;
  res BOOLEAN;
BEGIN

   select count(*) from (SELECT * FROM srs_touching_points(broad )) as a, (SELECT * FROM srs_touching_points(broad )) as b where st_distance_sphere(a.intersection, b.intersection) < cross_max AND a.osmid = aroad AND b.osmid = croad AND ST_distance_sphere(ST_centroid(ST_Collect(a.intersection,b.intersection)), pnt ) < pnt_max into cnt;

if cnt < 1 then 
   res := false;
else 
   res := true;
end if;

RETURN res;

END;
$_$;


ALTER FUNCTION public.srs_abc_intersection_exists(bigint, bigint, bigint, public.geometry, integer, integer) OWNER TO crowd4roads_sw;

--
-- Name: srs_avg_roughness(public.geometry, integer, bigint, integer); Type: FUNCTION; Schema: public; Owner: crowd4roads_sw
--

CREATE FUNCTION public.srs_avg_roughness(the_geom public.geometry, meters integer, osm_id bigint, min_resolution integer) RETURNS SETOF record
    LANGUAGE plpgsql
    AS $_$
      DECLARE
        r record;
        meters_radians double precision;
      BEGIN
        meters_radians = $2 / 111000.0;
        FOR r IN
             SELECT
                   AVG(ppe) AS avg_roughness,
                   $1 AS avg_point
                   FROM ( 
                       SELECT 
                             ppe
                             FROM 
                                 single_data AS vals
                             WHERE 
                                  ST_DWithin($1, 
                vals.position, 
                meters_radians)
                                  AND vals.osm_line_id = $3
                                  AND vals.evaluate = 1
                                  AND position_resolution < $4
                   ) AS foo
        LOOP
        RETURN NEXT r;
        END LOOP;
        RETURN;
      END;
  $_$;


ALTER FUNCTION public.srs_avg_roughness(the_geom public.geometry, meters integer, osm_id bigint, min_resolution integer) OWNER TO crowd4roads_sw;

--
-- Name: srs_avg_roughness(public.geometry, integer, bigint, integer, integer); Type: FUNCTION; Schema: public; Owner: crowd4roads_sw
--

CREATE FUNCTION public.srs_avg_roughness(the_geom public.geometry, meters integer, osm_id bigint, min_resolution integer, days integer) RETURNS SETOF record
    LANGUAGE plpgsql
    AS $_$
DECLARE
  r record;
  meters_radians double precision;
BEGIN
  meters_radians = $2 / 111000.0;
  FOR r IN
  SELECT
    AVG(ppe) AS avg_roughness,
    $1 AS avg_point,
    MAX(date) as max_date,
    COUNT(*) as count,
    STDDEV(ppe) as stddev_ppe,
    AVG(occupancy)
  FROM (
         SELECT
           ppe,
           vals.date,
           cast(nullif(metadata::json->>'numberOfPeople', '1') AS float) as occupancy
         FROM
           single_data AS vals left join track  as t on t.track_id = vals.track_id
         WHERE
           ST_Distance_Sphere($1, vals.position) < $2
           AND vals.osm_line_id = $3
           AND vals.evaluate = 1
           AND position_resolution < $4
           AND vals.date > NOW() - ($5 || 'days')::INTERVAL
       ) AS foo
  LOOP
    RETURN NEXT r;
  END LOOP;
  RETURN;
END;
$_$;


ALTER FUNCTION public.srs_avg_roughness(the_geom public.geometry, meters integer, osm_id bigint, min_resolution integer, days integer) OWNER TO crowd4roads_sw;

--
-- Name: srs_avg_roughness_prequality(public.geometry, integer, bigint, integer, integer); Type: FUNCTION; Schema: public; Owner: crowd4roads_sw
--

CREATE FUNCTION public.srs_avg_roughness_prequality(the_geom public.geometry, meters integer, osm_id bigint, min_resolution integer, days integer) RETURNS SETOF record
    LANGUAGE plpgsql
    AS $_$
DECLARE
  r record;
  meters_radians double precision;
BEGIN
  meters_radians = $2 / 111000.0;
  FOR r IN
  SELECT
    AVG(ppe) AS avg_roughness,
    $1 AS avg_point
  FROM (
         SELECT
           ppe
         FROM
           single_data AS vals
         WHERE
           ST_DWithin($1,
                      vals.position,
                      meters_radians)
           AND vals.osm_line_id = $3
           AND vals.evaluate = 1
           AND position_resolution < $4
           AND date > NOW() - ($5 || 'days')::INTERVAL
       ) AS foo
  LOOP
    RETURN NEXT r;
  END LOOP;
  RETURN;
END;
$_$;


ALTER FUNCTION public.srs_avg_roughness_prequality(the_geom public.geometry, meters integer, osm_id bigint, min_resolution integer, days integer) OWNER TO crowd4roads_sw;

--
-- Name: srs_centroid_close_to(public.geometry[], public.geometry, integer); Type: FUNCTION; Schema: public; Owner: crowd4roads_sw
--

CREATE FUNCTION public.srs_centroid_close_to(public.geometry[], public.geometry, integer) RETURNS boolean
    LANGUAGE plpgsql STABLE STRICT
    AS $_$
DECLARE
   pts ALIAS FOR $1;
   inters ALIAS FOR $2;
   thr ALIAS FOR $3;
   res BOOLEAN := false;
BEGIN
   res := (SELECT St_distance_sphere(ST_Centroid(ST_Collect(pts)), inters) < thr);
   return res;
END;
$_$;


ALTER FUNCTION public.srs_centroid_close_to(public.geometry[], public.geometry, integer) OWNER TO crowd4roads_sw;

--
-- Name: srs_clean_old_tokens(integer); Type: FUNCTION; Schema: public; Owner: crowd4roads_sw
--

CREATE FUNCTION public.srs_clean_old_tokens(integer) RETURNS void
    LANGUAGE sql
    AS $_$      delete from "app_token" where "created" < NOW() - INTERVAL '$1 days';
    $_$;


ALTER FUNCTION public.srs_clean_old_tokens(integer) OWNER TO crowd4roads_sw;

--
-- Name: srs_closest_point_on_street(public.geometry, integer); Type: FUNCTION; Schema: public; Owner: crowd4roads_sw
--

CREATE FUNCTION public.srs_closest_point_on_street(input_point public.geometry, range integer) RETURNS record
    LANGUAGE sql
    AS $_$    select
         coalesce(St_Line_Interpolate_Point(
                  Road,
                  ST_Line_Locate_Point(
                        Road,
                        $1
                  )
         ), $1) as Proiection,
         RoadId

from (select
        ST_Transform(way, 4326)
            as Road,
		ST_Distance(
            ST_Transform(way, 4326)::geography,
            CAST($1 AS geography))as Distance,
            osm_id as RoadId
    from
        planet_osm_line
    where
        ST_DWithin(
            ST_Transform(way, 4326)::geography,
            CAST($1 AS geography),
            $2)
    order by
        Distance ASC
    limit 1) as foo

    $_$;


ALTER FUNCTION public.srs_closest_point_on_street(input_point public.geometry, range integer) OWNER TO crowd4roads_sw;

--
-- Name: FUNCTION srs_closest_point_on_street(input_point public.geometry, range integer); Type: COMMENT; Schema: public; Owner: crowd4roads_sw
--

COMMENT ON FUNCTION public.srs_closest_point_on_street(input_point public.geometry, range integer) IS 'This is a copy of "cloasestpointonstreet" function. NOT TESTED';


--
-- Name: srs_closest_street(public.geometry, double precision); Type: FUNCTION; Schema: public; Owner: crowd4roads_sw
--

CREATE FUNCTION public.srs_closest_street(input_point public.geometry, range double precision) RETURNS bigint
    LANGUAGE sql
    AS $_$
      SELECT
        osm_id AS RoadId
      FROM 
        planet_osm_line
      WHERE 
        ST_DWithin(way, $1, $2) AND highway IS NOT NULL AND highway NOT IN ('footway','pedestrian','cycleway','construction', 'raceway', 'steps')
      ORDER BY 
        ST_Distance(way, $1) ASC 
      LIMIT 1    $_$;


ALTER FUNCTION public.srs_closest_street(input_point public.geometry, range double precision) OWNER TO crowd4roads_sw;

--
-- Name: srs_distance_from_point(public.geometry[], public.geometry); Type: FUNCTION; Schema: public; Owner: crowd4roads_sw
--

CREATE FUNCTION public.srs_distance_from_point(public.geometry[], public.geometry) RETURNS SETOF double precision
    LANGUAGE plpgsql STABLE STRICT
    AS $_$
DECLARE
   arr ALIAS FOR $1;
BEGIN
   FOR I IN array_lower(arr, 1) .. array_upper(arr, 1) LOOP
       return next ST_Distance(arr[I], $2);
   END LOOP;
END;
$_$;


ALTER FUNCTION public.srs_distance_from_point(public.geometry[], public.geometry) OWNER TO crowd4roads_sw;

--
-- Name: srs_get_points_on_track(integer); Type: FUNCTION; Schema: public; Owner: crowd4roads_sw
--

CREATE FUNCTION public.srs_get_points_on_track(track_identifier integer) RETURNS TABLE(id integer, line_id bigint, pos public.geometry)
    LANGUAGE plpgsql
    AS $$

  BEGIN
    RETURN QUERY SELECT single_data_id as id, osm_line_id as line_id, position as pos
                 FROM single_data
                 WHERE track_id = track_identifier AND evaluate = 1;

  END
$$;


ALTER FUNCTION public.srs_get_points_on_track(track_identifier integer) OWNER TO crowd4roads_sw;

--
-- Name: srs_intersection_between(bigint, bigint, public.geometry); Type: FUNCTION; Schema: public; Owner: crowd4roads_sw
--

CREATE FUNCTION public.srs_intersection_between(bigint, bigint, public.geometry) RETURNS SETOF public.geometry
    LANGUAGE plpgsql STABLE STRICT
    AS $_$
DECLARE
   ra_id ALIAS FOR $1;
   rb_id ALIAS FOR $2;
   ref_pnt ALIAS FOR $3;
BEGIN
   return query select intersection from srs_touching_points(ra_id) where osmid = rb_id order by         ST_distance(intersection, ref_pnt) limit 1;
END;
$_$;


ALTER FUNCTION public.srs_intersection_between(bigint, bigint, public.geometry) OWNER TO crowd4roads_sw;

--
-- Name: srs_intersection_exists(bigint, bigint, public.geometry[], integer); Type: FUNCTION; Schema: public; Owner: crowd4roads_sw
--

CREATE FUNCTION public.srs_intersection_exists(bigint, bigint, public.geometry[], integer) RETURNS boolean
    LANGUAGE plpgsql STABLE STRICT
    AS $_$
DECLARE
  pts ALIAS FOR $3;
  res geometry;
  ret BOOLEAN := false;
BEGIN

    FOR res IN select srs_intersection_between($1, $2, pts[1])
    LOOP
        ret := ret OR srs_centroid_close_to(pts, res, $4);
    END LOOP;
   RETURN ret;
END;
$_$;


ALTER FUNCTION public.srs_intersection_exists(bigint, bigint, public.geometry[], integer) OWNER TO crowd4roads_sw;

--
-- Name: srs_meters_to_line_fraction(integer, integer); Type: FUNCTION; Schema: public; Owner: crowd4roads_sw
--

CREATE FUNCTION public.srs_meters_to_line_fraction(geom_identifier integer, meters integer) RETURNS double precision
    LANGUAGE sql
    AS $_$select
      $2/ST_Length(ST_transform(way,4326)::geography) as fraction
      from 
          planet_osm_line
      where 
          osm_id = $1$_$;


ALTER FUNCTION public.srs_meters_to_line_fraction(geom_identifier integer, meters integer) OWNER TO crowd4roads_sw;

--
-- Name: srs_move_raw_to_history(integer); Type: FUNCTION; Schema: public; Owner: crowd4roads_sw
--

CREATE FUNCTION public.srs_move_raw_to_history(integer) RETURNS void
    LANGUAGE sql
    AS $_$
WITH inserted_rows(id) AS (
  INSERT INTO single_data_old
    SELECT *
    FROM single_data
    WHERE single_data.date < NOW() - ($1 || ' hours')::INTERVAL
  RETURNING single_data_id)
DELETE FROM single_data
USING inserted_rows
WHERE single_data.single_data_id = inserted_rows.id;
$_$;


ALTER FUNCTION public.srs_move_raw_to_history(integer) OWNER TO crowd4roads_sw;

--
-- Name: srs_point_projection(public.geometry, bigint); Type: FUNCTION; Schema: public; Owner: crowd4roads_sw
--

CREATE FUNCTION public.srs_point_projection(input_point public.geometry, geom_id bigint) RETURNS public.geometry
    LANGUAGE sql
    AS $_$
    
            select 
               St_Line_Interpolate_Point(
                        Road,
                        ST_Line_Locate_Point(
                              Road,
                              $1
                        )
               ) as Proiection

              from (select
                      ST_Transform(way, 4326)
                          as Road, 
                      max(osm_id)
                  from 
                      planet_osm_line
                  where 
                      osm_id = $2
                  group by way 
                  Limit 1) as foo;
     $_$;


ALTER FUNCTION public.srs_point_projection(input_point public.geometry, geom_id bigint) OWNER TO crowd4roads_sw;

--
-- Name: srs_points_in_range(public.geometry[], public.geometry, integer); Type: FUNCTION; Schema: public; Owner: crowd4roads_sw
--

CREATE FUNCTION public.srs_points_in_range(public.geometry[], public.geometry, integer) RETURNS boolean
    LANGUAGE plpgsql STABLE STRICT
    AS $_$
DECLARE
   arr ALIAS FOR $1;
   res BOOLEAN := true;
BEGIN
   FOR I IN array_lower(arr, 1) .. array_upper(arr, 1) LOOP
        res := res AND ST_Distance_Sphere(arr[I], $2) < $3;
   END LOOP;
   RETURN res;
END;
$_$;


ALTER FUNCTION public.srs_points_in_range(public.geometry[], public.geometry, integer) OWNER TO crowd4roads_sw;

--
-- Name: srs_reset_projections(); Type: FUNCTION; Schema: public; Owner: crowd4roads_sw
--

CREATE FUNCTION public.srs_reset_projections() RETURNS void
    LANGUAGE sql
    AS $$UPDATE single_data SET projection = NULL, osm_line_id = NULL, evaluate = 1;$$;


ALTER FUNCTION public.srs_reset_projections() OWNER TO crowd4roads_sw;

--
-- Name: srs_road_roughness_values(integer, integer); Type: FUNCTION; Schema: public; Owner: crowd4roads_sw
--

CREATE FUNCTION public.srs_road_roughness_values(geom_id integer, meters integer) RETURNS SETOF record
    LANGUAGE plpgsql
    AS $$                                       
                BEGIN
                        RETURN QUERY SELECT result.avg_roughness, result.avg_point FROM roadroughnessvalues(geom_id, meters, meters) as result;  
                    RETURN;
                END;
                
                $$;


ALTER FUNCTION public.srs_road_roughness_values(geom_id integer, meters integer) OWNER TO crowd4roads_sw;

--
-- Name: srs_road_roughness_values(integer, integer, integer); Type: FUNCTION; Schema: public; Owner: crowd4roads_sw
--

CREATE FUNCTION public.srs_road_roughness_values(geom_id integer, meters integer, range integer) RETURNS SETOF record
    LANGUAGE plpgsql
    AS $$
    DECLARE
    i float;
    step float;
    curr_road geometry;
  BEGIN
    SELECT srs_meters_to_line_fraction(geom_id, meters) INTO step;
    SELECT way FROM planet_osm_line WHERE osm_id = geom_id LIMIT 1 INTO curr_road;
    i:= 0;
    WHILE (i <= 1)
    LOOP
      -- Get avg here --
      RETURN QUERY SELECT result.avg_roughness, result.avg_point FROM srs_avg_roughness(ST_Line_Interpolate_Point(curr_road, i), range, geom_id, 20) AS result(avg_roughness float, avg_point geometry);
      i := i + step;
    END loop;
    RETURN;
  END;
  $$;


ALTER FUNCTION public.srs_road_roughness_values(geom_id integer, meters integer, range integer) OWNER TO crowd4roads_sw;

--
-- Name: srs_road_roughness_values(integer, integer, integer, integer, integer); Type: FUNCTION; Schema: public; Owner: crowd4roads_sw
--

CREATE FUNCTION public.srs_road_roughness_values(geom_id integer, meters integer, range integer, min_resolution integer, days integer) RETURNS SETOF record
    LANGUAGE plpgsql
    AS $$
DECLARE
  i float;
  step float;
  curr_road geometry;
BEGIN
  SELECT srs_meters_to_line_fraction(geom_id, meters) INTO step;
  SELECT way FROM planet_osm_line WHERE osm_id = geom_id LIMIT 1 INTO curr_road;
  i:= 0;
  WHILE (i <= 1)
  LOOP
    -- Get avg here --
    RETURN QUERY SELECT result.avg_roughness, result.avg_point, result.max_date, result.count, result.stddev_ppe, result.occupancy FROM srs_avg_roughness(ST_Line_Interpolate_Point(curr_road, i), range, geom_id, min_resolution, days) AS result(avg_roughness float, avg_point geometry, max_date timestamp, count bigint, stddev_ppe float, occupancy float);
    i := i + step;
  END loop;
  RETURN;
END;
$$;


ALTER FUNCTION public.srs_road_roughness_values(geom_id integer, meters integer, range integer, min_resolution integer, days integer) OWNER TO crowd4roads_sw;

--
-- Name: srs_road_roughness_values_prequality(integer, integer, integer, integer, integer); Type: FUNCTION; Schema: public; Owner: crowd4roads_sw
--

CREATE FUNCTION public.srs_road_roughness_values_prequality(geom_id integer, meters integer, range integer, min_resolution integer, days integer) RETURNS SETOF record
    LANGUAGE plpgsql
    AS $$
DECLARE
  i float;
  step float;
  curr_road geometry;
BEGIN
  SELECT srs_meters_to_line_fraction(geom_id, meters) INTO step;
  SELECT way FROM planet_osm_line WHERE osm_id = geom_id LIMIT 1 INTO curr_road;
  i:= 0;
  WHILE (i <= 1)
  LOOP
    -- Get avg here --
    RETURN QUERY SELECT result.avg_roughness, result.avg_point FROM srs_avg_roughness(ST_Line_Interpolate_Point(curr_road, i), range, geom_id, min_resolution, days) AS result(avg_roughness float, avg_point geometry);
    i := i + step;
  END loop;
  RETURN;
END;
$$;


ALTER FUNCTION public.srs_road_roughness_values_prequality(geom_id integer, meters integer, range integer, min_resolution integer, days integer) OWNER TO crowd4roads_sw;

--
-- Name: srs_road_roughness_values_sav(integer, integer, integer); Type: FUNCTION; Schema: public; Owner: crowd4roads_sw
--

CREATE FUNCTION public.srs_road_roughness_values_sav(geom_id integer, meters integer, range integer) RETURNS SETOF record
    LANGUAGE plpgsql
    AS $$
    DECLARE
    i float;
    step float;
    curr_road geometry;
  BEGIN
    SELECT MetersToLineFraction(geom_id, meters) INTO step;
    SELECT way FROM planet_osm_line WHERE osm_id = geom_id LIMIT 1 INTO curr_road;
    i:= 0;
    WHILE (i <= 1)
    LOOP
      -- Get avg here --
      RETURN QUERY SELECT result.avg_roughness, result.avg_point FROM AvgRoughness_sav(ST_Line_Interpolate_Point(curr_road, i), range, geom_id) AS result(avg_roughness float, avg_point geometry);
      i := i + step;
    END loop;
    RETURN;
  END;
  $$;


ALTER FUNCTION public.srs_road_roughness_values_sav(geom_id integer, meters integer, range integer) OWNER TO crowd4roads_sw;

--
-- Name: srs_text_from_slot(integer); Type: FUNCTION; Schema: public; Owner: crowd4roads_sw
--

CREATE FUNCTION public.srs_text_from_slot(integer) RETURNS text
    LANGUAGE plpgsql STABLE STRICT
    AS $_$
DECLARE
   slot ALIAS FOR $1;
BEGIN
   IF slot = 0 THEN
      RETURN '1-6'::text;
 ELSIF slot = 1 THEN
      RETURN '7-12'::text;
 ELSIF slot = 2 THEN
    RETURN '13-18'::text;
 ELSE
    RETURN '19-0'::text;
END IF;

END;
$_$;


ALTER FUNCTION public.srs_text_from_slot(integer) OWNER TO crowd4roads_sw;

--
-- Name: srs_touching_points(bigint); Type: FUNCTION; Schema: public; Owner: crowd4roads_sw
--

CREATE FUNCTION public.srs_touching_points(geom_id bigint) RETURNS TABLE(osmid bigint, intersection public.geometry)
    LANGUAGE plpgsql
    AS $_$DECLARE
                touch geometry;
            BEGIN
                touch := (SELECT way FROM planet_osm_line WHERE osm_id = $1 limit 1);

                return query SELECT
                      osm_id as osmid,
                      st_intersection(way, touch) as intersection
                   FROM
                      planet_osm_line
                   WHERE
                      ST_Touches(touch, way);
            END;
            $_$;


ALTER FUNCTION public.srs_touching_points(geom_id bigint) OWNER TO crowd4roads_sw;

--
-- Name: srs_update_data_projections(integer); Type: FUNCTION; Schema: public; Owner: crowd4roads_sw
--

CREATE FUNCTION public.srs_update_data_projections(how_many integer) RETURNS SETOF integer
    LANGUAGE plpgsql
    AS $$
    DECLARE
      r record;
      proj geometry;
      search_radians double precision;
    BEGIN
      -- convert meters to radians for SRID 4326
      search_radians = 200 / 111000.0;

      FOR r IN 
        SELECT single_data_id, position, srs_closest_street(position, search_radians) AS osm_id
        FROM single_data
        WHERE osm_line_id IS NULL AND evaluate = 1 ORDER BY single_data_id
        LIMIT how_many 
      LOOP
        proj := srs_point_projection (r.position, r.osm_id);
        
        UPDATE single_data SET projection = proj, osm_line_id = r.osm_id
        WHERE single_data_id = r.single_data_id;
        RETURN NEXT r.osm_id ;
      END LOOP;
      RETURN;
    END
    $$;


ALTER FUNCTION public.srs_update_data_projections(how_many integer) OWNER TO crowd4roads_sw;

--
-- Name: to_seconds(text); Type: FUNCTION; Schema: public; Owner: crowd4roads_sw
--

CREATE FUNCTION public.to_seconds(t text) RETURNS integer
    LANGUAGE plpgsql
    AS $$ 
DECLARE 
    hs INTEGER;
    ms INTEGER;
    s INTEGER;
BEGIN
    SELECT (EXTRACT( HOUR FROM  t::time) * 60*60) INTO hs; 
    SELECT (EXTRACT (MINUTES FROM t::time) * 60) INTO ms;
    SELECT (EXTRACT (SECONDS from t::time)) INTO s;
    SELECT (hs + ms + s) INTO s;
    RETURN s;
END;
$$;


ALTER FUNCTION public.to_seconds(t text) OWNER TO crowd4roads_sw;

--
-- PostgreSQL database dump complete
--

