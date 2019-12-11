PGDMP         0                w         
   srs_raw_db    9.6.12    9.6.11 U    �           0    0    ENCODING    ENCODING        SET client_encoding = 'UTF8';
                       false            �           0    0 
   STDSTRINGS 
   STDSTRINGS     (   SET standard_conforming_strings = 'on';
                       false            �           0    0 
   SEARCHPATH 
   SEARCHPATH     8   SELECT pg_catalog.set_config('search_path', '', false);
                       false                        2615    2200    public    SCHEMA        CREATE SCHEMA public;
    DROP SCHEMA public;
             postgres    false            �           0    0    SCHEMA public    COMMENT     6   COMMENT ON SCHEMA public IS 'standard public schema';
                  postgres    false    5            �           1255    273152 V   srs_abc_intersection_exists(bigint, bigint, bigint, public.geometry, integer, integer)    FUNCTION       CREATE FUNCTION public.srs_abc_intersection_exists(bigint, bigint, bigint, public.geometry, integer, integer) RETURNS boolean
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
 m   DROP FUNCTION public.srs_abc_intersection_exists(bigint, bigint, bigint, public.geometry, integer, integer);
       public       crowd4roads_sw    false    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5            �           1255    273153 <   srs_avg_roughness(public.geometry, integer, bigint, integer)    FUNCTION       CREATE FUNCTION public.srs_avg_roughness(the_geom public.geometry, meters integer, osm_id bigint, min_resolution integer) RETURNS SETOF record
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
 y   DROP FUNCTION public.srs_avg_roughness(the_geom public.geometry, meters integer, osm_id bigint, min_resolution integer);
       public       crowd4roads_sw    false    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5            �           1255    638048 E   srs_avg_roughness(public.geometry, integer, bigint, integer, integer)    FUNCTION       CREATE FUNCTION public.srs_avg_roughness(the_geom public.geometry, meters integer, osm_id bigint, min_resolution integer, days integer) RETURNS SETOF record
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
 �   DROP FUNCTION public.srs_avg_roughness(the_geom public.geometry, meters integer, osm_id bigint, min_resolution integer, days integer);
       public       crowd4roads_sw    false    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5            �           1255    273154 P   srs_avg_roughness_prequality(public.geometry, integer, bigint, integer, integer)    FUNCTION     &  CREATE FUNCTION public.srs_avg_roughness_prequality(the_geom public.geometry, meters integer, osm_id bigint, min_resolution integer, days integer) RETURNS SETOF record
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
 �   DROP FUNCTION public.srs_avg_roughness_prequality(the_geom public.geometry, meters integer, osm_id bigint, min_resolution integer, days integer);
       public       crowd4roads_sw    false    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5            �           1255    273155 B   srs_centroid_close_to(public.geometry[], public.geometry, integer)    FUNCTION     m  CREATE FUNCTION public.srs_centroid_close_to(public.geometry[], public.geometry, integer) RETURNS boolean
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
 Y   DROP FUNCTION public.srs_centroid_close_to(public.geometry[], public.geometry, integer);
       public       crowd4roads_sw    false    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5            �           1255    273156    srs_clean_old_tokens(integer)    FUNCTION     �   CREATE FUNCTION public.srs_clean_old_tokens(integer) RETURNS void
    LANGUAGE sql
    AS $_$      delete from "app_token" where "created" < NOW() - INTERVAL '$1 days';
    $_$;
 4   DROP FUNCTION public.srs_clean_old_tokens(integer);
       public       crowd4roads_sw    false    5            �           1255    273157 5   srs_closest_point_on_street(public.geometry, integer)    FUNCTION     -  CREATE FUNCTION public.srs_closest_point_on_street(input_point public.geometry, range integer) RETURNS record
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
 ^   DROP FUNCTION public.srs_closest_point_on_street(input_point public.geometry, range integer);
       public       crowd4roads_sw    false    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5            �           0    0 P   FUNCTION srs_closest_point_on_street(input_point public.geometry, range integer)    COMMENT     �   COMMENT ON FUNCTION public.srs_closest_point_on_street(input_point public.geometry, range integer) IS 'This is a copy of "cloasestpointonstreet" function. NOT TESTED';
            public       crowd4roads_sw    false    1485            �           1255    273158 5   srs_closest_street(public.geometry, double precision)    FUNCTION     �  CREATE FUNCTION public.srs_closest_street(input_point public.geometry, range double precision) RETURNS bigint
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
 ^   DROP FUNCTION public.srs_closest_street(input_point public.geometry, range double precision);
       public       crowd4roads_sw    false    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5            �           1255    273159 ;   srs_distance_from_point(public.geometry[], public.geometry)    FUNCTION     B  CREATE FUNCTION public.srs_distance_from_point(public.geometry[], public.geometry) RETURNS SETOF double precision
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
 R   DROP FUNCTION public.srs_distance_from_point(public.geometry[], public.geometry);
       public       crowd4roads_sw    false    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5            �           1255    273160     srs_get_points_on_track(integer)    FUNCTION     x  CREATE FUNCTION public.srs_get_points_on_track(track_identifier integer) RETURNS TABLE(id integer, line_id bigint, pos public.geometry)
    LANGUAGE plpgsql
    AS $$

  BEGIN
    RETURN QUERY SELECT single_data_id as id, osm_line_id as line_id, position as pos
                 FROM single_data
                 WHERE track_id = track_identifier AND evaluate = 1;

  END
$$;
 H   DROP FUNCTION public.srs_get_points_on_track(track_identifier integer);
       public       crowd4roads_sw    false    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5            �           1255    273161 9   srs_intersection_between(bigint, bigint, public.geometry)    FUNCTION     �  CREATE FUNCTION public.srs_intersection_between(bigint, bigint, public.geometry) RETURNS SETOF public.geometry
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
 P   DROP FUNCTION public.srs_intersection_between(bigint, bigint, public.geometry);
       public       crowd4roads_sw    false    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5            �           1255    273162 C   srs_intersection_exists(bigint, bigint, public.geometry[], integer)    FUNCTION     �  CREATE FUNCTION public.srs_intersection_exists(bigint, bigint, public.geometry[], integer) RETURNS boolean
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
 Z   DROP FUNCTION public.srs_intersection_exists(bigint, bigint, public.geometry[], integer);
       public       crowd4roads_sw    false    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5            �           1255    273163 -   srs_meters_to_line_fraction(integer, integer)    FUNCTION     &  CREATE FUNCTION public.srs_meters_to_line_fraction(geom_identifier integer, meters integer) RETURNS double precision
    LANGUAGE sql
    AS $_$select
      $2/ST_Length(ST_transform(way,4326)::geography) as fraction
      from 
          planet_osm_line
      where 
          osm_id = $1$_$;
 [   DROP FUNCTION public.srs_meters_to_line_fraction(geom_identifier integer, meters integer);
       public       crowd4roads_sw    false    5            �           1255    273164     srs_move_raw_to_history(integer)    FUNCTION       CREATE FUNCTION public.srs_move_raw_to_history(integer) RETURNS void
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
 7   DROP FUNCTION public.srs_move_raw_to_history(integer);
       public       crowd4roads_sw    false    5            �           1255    273165 -   srs_point_projection(public.geometry, bigint)    FUNCTION     �  CREATE FUNCTION public.srs_point_projection(input_point public.geometry, geom_id bigint) RETURNS public.geometry
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
 X   DROP FUNCTION public.srs_point_projection(input_point public.geometry, geom_id bigint);
       public       crowd4roads_sw    false    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5            �           1255    273166 @   srs_points_in_range(public.geometry[], public.geometry, integer)    FUNCTION     o  CREATE FUNCTION public.srs_points_in_range(public.geometry[], public.geometry, integer) RETURNS boolean
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
 W   DROP FUNCTION public.srs_points_in_range(public.geometry[], public.geometry, integer);
       public       crowd4roads_sw    false    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5            �           1255    273167    srs_reset_projections()    FUNCTION     �   CREATE FUNCTION public.srs_reset_projections() RETURNS void
    LANGUAGE sql
    AS $$UPDATE single_data SET projection = NULL, osm_line_id = NULL, evaluate = 1;$$;
 .   DROP FUNCTION public.srs_reset_projections();
       public       crowd4roads_sw    false    5            �           1255    273168 +   srs_road_roughness_values(integer, integer)    FUNCTION     �  CREATE FUNCTION public.srs_road_roughness_values(geom_id integer, meters integer) RETURNS SETOF record
    LANGUAGE plpgsql
    AS $$                                       
                BEGIN
                        RETURN QUERY SELECT result.avg_roughness, result.avg_point FROM roadroughnessvalues(geom_id, meters, meters) as result;  
                    RETURN;
                END;
                
                $$;
 Q   DROP FUNCTION public.srs_road_roughness_values(geom_id integer, meters integer);
       public       crowd4roads_sw    false    5            �           1255    273169 4   srs_road_roughness_values(integer, integer, integer)    FUNCTION     �  CREATE FUNCTION public.srs_road_roughness_values(geom_id integer, meters integer, range integer) RETURNS SETOF record
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
 `   DROP FUNCTION public.srs_road_roughness_values(geom_id integer, meters integer, range integer);
       public       crowd4roads_sw    false    5            �           1255    638049 F   srs_road_roughness_values(integer, integer, integer, integer, integer)    FUNCTION     g  CREATE FUNCTION public.srs_road_roughness_values(geom_id integer, meters integer, range integer, min_resolution integer, days integer) RETURNS SETOF record
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
 �   DROP FUNCTION public.srs_road_roughness_values(geom_id integer, meters integer, range integer, min_resolution integer, days integer);
       public       crowd4roads_sw    false    5            �           1255    273170 Q   srs_road_roughness_values_prequality(integer, integer, integer, integer, integer)    FUNCTION     �  CREATE FUNCTION public.srs_road_roughness_values_prequality(geom_id integer, meters integer, range integer, min_resolution integer, days integer) RETURNS SETOF record
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
 �   DROP FUNCTION public.srs_road_roughness_values_prequality(geom_id integer, meters integer, range integer, min_resolution integer, days integer);
       public       crowd4roads_sw    false    5            �           1255    273171 8   srs_road_roughness_values_sav(integer, integer, integer)    FUNCTION     �  CREATE FUNCTION public.srs_road_roughness_values_sav(geom_id integer, meters integer, range integer) RETURNS SETOF record
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
 d   DROP FUNCTION public.srs_road_roughness_values_sav(geom_id integer, meters integer, range integer);
       public       crowd4roads_sw    false    5            �           1255    273172    srs_text_from_slot(integer)    FUNCTION     Q  CREATE FUNCTION public.srs_text_from_slot(integer) RETURNS text
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
 2   DROP FUNCTION public.srs_text_from_slot(integer);
       public       crowd4roads_sw    false    5            �           1255    273173    srs_touching_points(bigint)    FUNCTION     Y  CREATE FUNCTION public.srs_touching_points(geom_id bigint) RETURNS TABLE(osmid bigint, intersection public.geometry)
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
 :   DROP FUNCTION public.srs_touching_points(geom_id bigint);
       public       crowd4roads_sw    false    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5            �           1255    273174 $   srs_update_data_projections(integer)    FUNCTION     9  CREATE FUNCTION public.srs_update_data_projections(how_many integer) RETURNS SETOF integer
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
 D   DROP FUNCTION public.srs_update_data_projections(how_many integer);
       public       crowd4roads_sw    false    5            �           1255    273175    to_seconds(text)    FUNCTION     z  CREATE FUNCTION public.to_seconds(t text) RETURNS integer
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
 )   DROP FUNCTION public.to_seconds(t text);
       public       crowd4roads_sw    false    5            �            1259    273176    agency    TABLE     b   CREATE TABLE public.agency (
    id integer NOT NULL,
    name character varying(255) NOT NULL
);
    DROP TABLE public.agency;
       public         crowd4roads_sw    false    5            �            1259    273179    agency_id_seq    SEQUENCE     v   CREATE SEQUENCE public.agency_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
 $   DROP SEQUENCE public.agency_id_seq;
       public       crowd4roads_sw    false    209    5            �           0    0    agency_id_seq    SEQUENCE OWNED BY     ?   ALTER SEQUENCE public.agency_id_seq OWNED BY public.agency.id;
            public       crowd4roads_sw    false    210            �            1259    273181    device    TABLE     �   CREATE TABLE public.device (
    id integer NOT NULL,
    agency_id integer NOT NULL,
    name character varying(255) NOT NULL,
    otc character varying(255),
    public_key character varying(800),
    activation_date timestamp without time zone
);
    DROP TABLE public.device;
       public         crowd4roads_sw    false    5            �            1259    273187    device_id_seq    SEQUENCE     v   CREATE SEQUENCE public.device_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
 $   DROP SEQUENCE public.device_id_seq;
       public       crowd4roads_sw    false    211    5            �           0    0    device_id_seq    SEQUENCE OWNED BY     ?   ALTER SEQUENCE public.device_id_seq OWNED BY public.device.id;
            public       crowd4roads_sw    false    212            �            1259    273189    mantova_points    TABLE     �   CREATE TABLE public.mantova_points (
    single_data_id integer,
    projection public.geometry,
    ppe double precision,
    date timestamp without time zone,
    osm_id bigint,
    way_geom public.geometry
);
 "   DROP TABLE public.mantova_points;
       public         crowd4roads_sw    false    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5            �            1259    273195    pilot_boundary    TABLE     T  CREATE TABLE public.pilot_boundary (
    osm_id bigint,
    access text,
    "addr:housename" text,
    "addr:housenumber" text,
    "addr:interpolation" text,
    admin_level text,
    aerialway text,
    aeroway text,
    amenity text,
    area text,
    barrier text,
    bicycle text,
    brand text,
    bridge text,
    boundary text,
    building text,
    construction text,
    covered text,
    culvert text,
    cutting text,
    denomination text,
    disused text,
    embankment text,
    foot text,
    "generator:source" text,
    harbour text,
    highway text,
    historic text,
    horse text,
    intermittent text,
    junction text,
    landuse text,
    layer text,
    leisure text,
    lock text,
    man_made text,
    military text,
    motorcar text,
    name text,
    "natural" text,
    office text,
    oneway text,
    operator text,
    place text,
    population text,
    power text,
    power_source text,
    public_transport text,
    railway text,
    ref text,
    religion text,
    route text,
    service text,
    shop text,
    sport text,
    surface text,
    toll text,
    tourism text,
    "tower:type" text,
    tracktype text,
    tunnel text,
    water text,
    waterway text,
    wetland text,
    width text,
    wood text,
    z_order integer,
    way_area real,
    way public.geometry(Geometry,4326)
);
 "   DROP TABLE public.pilot_boundary;
       public         crowd4roads_sw    false    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5            �            1259    273201    planet_osm_line    TABLE     W  CREATE TABLE public.planet_osm_line (
    osm_id bigint,
    access text,
    "addr:housename" text,
    "addr:housenumber" text,
    "addr:interpolation" text,
    admin_level text,
    aerialway text,
    aeroway text,
    amenity text,
    area text,
    barrier text,
    bicycle text,
    brand text,
    bridge text,
    boundary text,
    building text,
    construction text,
    covered text,
    culvert text,
    cutting text,
    denomination text,
    disused text,
    embankment text,
    foot text,
    "generator:source" text,
    harbour text,
    highway text,
    historic text,
    horse text,
    intermittent text,
    junction text,
    landuse text,
    layer text,
    leisure text,
    lock text,
    man_made text,
    military text,
    motorcar text,
    name text,
    "natural" text,
    office text,
    oneway text,
    operator text,
    place text,
    population text,
    power text,
    power_source text,
    public_transport text,
    railway text,
    ref text,
    religion text,
    route text,
    service text,
    shop text,
    sport text,
    surface text,
    toll text,
    tourism text,
    "tower:type" text,
    tracktype text,
    tunnel text,
    water text,
    waterway text,
    wetland text,
    width text,
    wood text,
    z_order integer,
    way_area real,
    way public.geometry(LineString,4326)
);
 #   DROP TABLE public.planet_osm_line;
       public         crowd4roads_sw    false    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5            �           0    0    TABLE planet_osm_line    ACL     8   GRANT SELECT ON TABLE public.planet_osm_line TO PUBLIC;
            public       crowd4roads_sw    false    215            �            1259    273207    planet_osm_nodes    TABLE     �   CREATE TABLE public.planet_osm_nodes (
    id bigint NOT NULL,
    lat integer NOT NULL,
    lon integer NOT NULL,
    tags text[]
);
 $   DROP TABLE public.planet_osm_nodes;
       public         crowd4roads_sw    false    5            �            1259    273213    planet_osm_point    TABLE     Z  CREATE TABLE public.planet_osm_point (
    osm_id bigint,
    access text,
    "addr:housename" text,
    "addr:housenumber" text,
    "addr:interpolation" text,
    admin_level text,
    aerialway text,
    aeroway text,
    amenity text,
    area text,
    barrier text,
    bicycle text,
    brand text,
    bridge text,
    boundary text,
    building text,
    capital text,
    construction text,
    covered text,
    culvert text,
    cutting text,
    denomination text,
    disused text,
    ele text,
    embankment text,
    foot text,
    "generator:source" text,
    harbour text,
    highway text,
    historic text,
    horse text,
    intermittent text,
    junction text,
    landuse text,
    layer text,
    leisure text,
    lock text,
    man_made text,
    military text,
    motorcar text,
    name text,
    "natural" text,
    office text,
    oneway text,
    operator text,
    place text,
    poi text,
    population text,
    power text,
    power_source text,
    public_transport text,
    railway text,
    ref text,
    religion text,
    route text,
    service text,
    shop text,
    sport text,
    surface text,
    toll text,
    tourism text,
    "tower:type" text,
    tunnel text,
    water text,
    waterway text,
    wetland text,
    width text,
    wood text,
    z_order integer,
    way public.geometry(Point,4326)
);
 $   DROP TABLE public.planet_osm_point;
       public         crowd4roads_sw    false    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5            �           0    0    TABLE planet_osm_point    ACL     9   GRANT SELECT ON TABLE public.planet_osm_point TO PUBLIC;
            public       crowd4roads_sw    false    217            �            1259    273219    planet_osm_polygon    TABLE     X  CREATE TABLE public.planet_osm_polygon (
    osm_id bigint,
    access text,
    "addr:housename" text,
    "addr:housenumber" text,
    "addr:interpolation" text,
    admin_level text,
    aerialway text,
    aeroway text,
    amenity text,
    area text,
    barrier text,
    bicycle text,
    brand text,
    bridge text,
    boundary text,
    building text,
    construction text,
    covered text,
    culvert text,
    cutting text,
    denomination text,
    disused text,
    embankment text,
    foot text,
    "generator:source" text,
    harbour text,
    highway text,
    historic text,
    horse text,
    intermittent text,
    junction text,
    landuse text,
    layer text,
    leisure text,
    lock text,
    man_made text,
    military text,
    motorcar text,
    name text,
    "natural" text,
    office text,
    oneway text,
    operator text,
    place text,
    population text,
    power text,
    power_source text,
    public_transport text,
    railway text,
    ref text,
    religion text,
    route text,
    service text,
    shop text,
    sport text,
    surface text,
    toll text,
    tourism text,
    "tower:type" text,
    tracktype text,
    tunnel text,
    water text,
    waterway text,
    wetland text,
    width text,
    wood text,
    z_order integer,
    way_area real,
    way public.geometry(Geometry,4326)
);
 &   DROP TABLE public.planet_osm_polygon;
       public         crowd4roads_sw    false    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5            �           0    0    TABLE planet_osm_polygon    ACL     ;   GRANT SELECT ON TABLE public.planet_osm_polygon TO PUBLIC;
            public       crowd4roads_sw    false    218            �            1259    273225    planet_osm_rels    TABLE     �   CREATE TABLE public.planet_osm_rels (
    id bigint NOT NULL,
    way_off smallint,
    rel_off smallint,
    parts bigint[],
    members text[],
    tags text[]
);
 #   DROP TABLE public.planet_osm_rels;
       public         crowd4roads_sw    false    5            �            1259    273231    planet_osm_roads    TABLE     X  CREATE TABLE public.planet_osm_roads (
    osm_id bigint,
    access text,
    "addr:housename" text,
    "addr:housenumber" text,
    "addr:interpolation" text,
    admin_level text,
    aerialway text,
    aeroway text,
    amenity text,
    area text,
    barrier text,
    bicycle text,
    brand text,
    bridge text,
    boundary text,
    building text,
    construction text,
    covered text,
    culvert text,
    cutting text,
    denomination text,
    disused text,
    embankment text,
    foot text,
    "generator:source" text,
    harbour text,
    highway text,
    historic text,
    horse text,
    intermittent text,
    junction text,
    landuse text,
    layer text,
    leisure text,
    lock text,
    man_made text,
    military text,
    motorcar text,
    name text,
    "natural" text,
    office text,
    oneway text,
    operator text,
    place text,
    population text,
    power text,
    power_source text,
    public_transport text,
    railway text,
    ref text,
    religion text,
    route text,
    service text,
    shop text,
    sport text,
    surface text,
    toll text,
    tourism text,
    "tower:type" text,
    tracktype text,
    tunnel text,
    water text,
    waterway text,
    wetland text,
    width text,
    wood text,
    z_order integer,
    way_area real,
    way public.geometry(LineString,4326)
);
 $   DROP TABLE public.planet_osm_roads;
       public         crowd4roads_sw    false    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5            �           0    0    TABLE planet_osm_roads    ACL     9   GRANT SELECT ON TABLE public.planet_osm_roads TO PUBLIC;
            public       crowd4roads_sw    false    220            �            1259    273237    planet_osm_ways    TABLE     n   CREATE TABLE public.planet_osm_ways (
    id bigint NOT NULL,
    nodes bigint[] NOT NULL,
    tags text[]
);
 #   DROP TABLE public.planet_osm_ways;
       public         crowd4roads_sw    false    5            �            1259    273243    single_data_old    TABLE     !  CREATE TABLE public.single_data_old (
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
 #   DROP TABLE public.single_data_old;
       public         crowd4roads_sw    false    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5            �           0    0    TABLE single_data_old    ACL     7   GRANT ALL ON TABLE public.single_data_old TO postgres;
            public       crowd4roads_sw    false    222            �            1259    273253    single_data_single _data_id_seq    SEQUENCE     �   CREATE SEQUENCE public."single_data_single _data_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
 8   DROP SEQUENCE public."single_data_single _data_id_seq";
       public       crowd4roads_sw    false    5    222            �           0    0    single_data_single _data_id_seq    SEQUENCE OWNED BY     h   ALTER SEQUENCE public."single_data_single _data_id_seq" OWNED BY public.single_data_old.single_data_id;
            public       crowd4roads_sw    false    223            �            1259    273255    single_data    TABLE     c  CREATE TABLE public.single_data (
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
    DROP TABLE public.single_data;
       public         crowd4roads_sw    false    223    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5            �            1259    273266    track    TABLE     1  CREATE TABLE public.track (
    metadata character varying(1000) NOT NULL,
    device_id integer,
    vehicle_type integer DEFAULT 0,
    anchorage_type integer DEFAULT 0,
    secret character varying(700),
    date timestamp with time zone DEFAULT statement_timestamp(),
    track_id integer NOT NULL
);
    DROP TABLE public.track;
       public         crowd4roads_sw    false    5            �            1259    273275    track_track_id_seq    SEQUENCE     {   CREATE SEQUENCE public.track_track_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
 )   DROP SEQUENCE public.track_track_id_seq;
       public       crowd4roads_sw    false    225    5            �           0    0    track_track_id_seq    SEQUENCE OWNED BY     I   ALTER SEQUENCE public.track_track_id_seq OWNED BY public.track.track_id;
            public       crowd4roads_sw    false    226            '           2604    273277 	   agency id    DEFAULT     f   ALTER TABLE ONLY public.agency ALTER COLUMN id SET DEFAULT nextval('public.agency_id_seq'::regclass);
 8   ALTER TABLE public.agency ALTER COLUMN id DROP DEFAULT;
       public       crowd4roads_sw    false    210    209            (           2604    273278 	   device id    DEFAULT     f   ALTER TABLE ONLY public.device ALTER COLUMN id SET DEFAULT nextval('public.device_id_seq'::regclass);
 8   ALTER TABLE public.device ALTER COLUMN id DROP DEFAULT;
       public       crowd4roads_sw    false    212    211            -           2604    273279    single_data_old single_data_id    DEFAULT     �   ALTER TABLE ONLY public.single_data_old ALTER COLUMN single_data_id SET DEFAULT nextval('public."single_data_single _data_id_seq"'::regclass);
 M   ALTER TABLE public.single_data_old ALTER COLUMN single_data_id DROP DEFAULT;
       public       crowd4roads_sw    false    223    222            6           2604    273280    track track_id    DEFAULT     p   ALTER TABLE ONLY public.track ALTER COLUMN track_id SET DEFAULT nextval('public.track_track_id_seq'::regclass);
 =   ALTER TABLE public.track ALTER COLUMN track_id DROP DEFAULT;
       public       crowd4roads_sw    false    226    225            L           2606    382503    single_data_old SingleData_pkey 
   CONSTRAINT     k   ALTER TABLE ONLY public.single_data_old
    ADD CONSTRAINT "SingleData_pkey" PRIMARY KEY (single_data_id);
 K   ALTER TABLE ONLY public.single_data_old DROP CONSTRAINT "SingleData_pkey";
       public         crowd4roads_sw    false    222    222            8           2606    375479    agency agency_pkey 
   CONSTRAINT     P   ALTER TABLE ONLY public.agency
    ADD CONSTRAINT agency_pkey PRIMARY KEY (id);
 <   ALTER TABLE ONLY public.agency DROP CONSTRAINT agency_pkey;
       public         crowd4roads_sw    false    209    209            :           2606    375481    device device_pkey 
   CONSTRAINT     P   ALTER TABLE ONLY public.device
    ADD CONSTRAINT device_pkey PRIMARY KEY (id);
 <   ALTER TABLE ONLY public.device DROP CONSTRAINT device_pkey;
       public         crowd4roads_sw    false    211    211            >           2606    382288 &   planet_osm_nodes planet_osm_nodes_pkey 
   CONSTRAINT     d   ALTER TABLE ONLY public.planet_osm_nodes
    ADD CONSTRAINT planet_osm_nodes_pkey PRIMARY KEY (id);
 P   ALTER TABLE ONLY public.planet_osm_nodes DROP CONSTRAINT planet_osm_nodes_pkey;
       public         crowd4roads_sw    false    216    216            E           2606    376522 $   planet_osm_rels planet_osm_rels_pkey 
   CONSTRAINT     b   ALTER TABLE ONLY public.planet_osm_rels
    ADD CONSTRAINT planet_osm_rels_pkey PRIMARY KEY (id);
 N   ALTER TABLE ONLY public.planet_osm_rels DROP CONSTRAINT planet_osm_rels_pkey;
       public         crowd4roads_sw    false    219    219            J           2606    376547 $   planet_osm_ways planet_osm_ways_pkey 
   CONSTRAINT     b   ALTER TABLE ONLY public.planet_osm_ways
    ADD CONSTRAINT planet_osm_ways_pkey PRIMARY KEY (id);
 N   ALTER TABLE ONLY public.planet_osm_ways DROP CONSTRAINT planet_osm_ways_pkey;
       public         crowd4roads_sw    false    221    221            N           2606    376572    single_data single_data_v2_pkey 
   CONSTRAINT     i   ALTER TABLE ONLY public.single_data
    ADD CONSTRAINT single_data_v2_pkey PRIMARY KEY (single_data_id);
 I   ALTER TABLE ONLY public.single_data DROP CONSTRAINT single_data_v2_pkey;
       public         crowd4roads_sw    false    224    224            P           2606    382275    track trackv2_pkey 
   CONSTRAINT     V   ALTER TABLE ONLY public.track
    ADD CONSTRAINT trackv2_pkey PRIMARY KEY (track_id);
 <   ALTER TABLE ONLY public.track DROP CONSTRAINT trackv2_pkey;
       public         crowd4roads_sw    false    225    225            ;           1259    376574    planet_osm_line_index    INDEX     O   CREATE INDEX planet_osm_line_index ON public.planet_osm_line USING gist (way);
 )   DROP INDEX public.planet_osm_line_index;
       public         crowd4roads_sw    false    215    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5            <           1259    382149    planet_osm_line_pkey    INDEX     R   CREATE INDEX planet_osm_line_pkey ON public.planet_osm_line USING btree (osm_id);
 (   DROP INDEX public.planet_osm_line_pkey;
       public         crowd4roads_sw    false    215            ?           1259    375484    planet_osm_point_index    INDEX     Q   CREATE INDEX planet_osm_point_index ON public.planet_osm_point USING gist (way);
 *   DROP INDEX public.planet_osm_point_index;
       public         crowd4roads_sw    false    217    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5            @           1259    376508    planet_osm_point_pkey    INDEX     T   CREATE INDEX planet_osm_point_pkey ON public.planet_osm_point USING btree (osm_id);
 )   DROP INDEX public.planet_osm_point_pkey;
       public         crowd4roads_sw    false    217            A           1259    382534    planet_osm_polygon_index    INDEX     U   CREATE INDEX planet_osm_polygon_index ON public.planet_osm_polygon USING gist (way);
 ,   DROP INDEX public.planet_osm_polygon_index;
       public         crowd4roads_sw    false    218    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5            B           1259    382606    planet_osm_polygon_pkey    INDEX     X   CREATE INDEX planet_osm_polygon_pkey ON public.planet_osm_polygon USING btree (osm_id);
 +   DROP INDEX public.planet_osm_polygon_pkey;
       public         crowd4roads_sw    false    218            C           1259    376524    planet_osm_rels_parts    INDEX     f   CREATE INDEX planet_osm_rels_parts ON public.planet_osm_rels USING gin (parts) WITH (fastupdate=off);
 )   DROP INDEX public.planet_osm_rels_parts;
       public         crowd4roads_sw    false    219            F           1259    376529    planet_osm_roads_index    INDEX     Q   CREATE INDEX planet_osm_roads_index ON public.planet_osm_roads USING gist (way);
 *   DROP INDEX public.planet_osm_roads_index;
       public         crowd4roads_sw    false    220    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5    5            G           1259    376544    planet_osm_roads_pkey    INDEX     T   CREATE INDEX planet_osm_roads_pkey ON public.planet_osm_roads USING btree (osm_id);
 )   DROP INDEX public.planet_osm_roads_pkey;
       public         crowd4roads_sw    false    220            H           1259    380847    planet_osm_ways_nodes    INDEX     f   CREATE INDEX planet_osm_ways_nodes ON public.planet_osm_ways USING gin (nodes) WITH (fastupdate=off);
 )   DROP INDEX public.planet_osm_ways_nodes;
       public         crowd4roads_sw    false    221            Q           2606    382277    device device_agency_id_fkey    FK CONSTRAINT     ~   ALTER TABLE ONLY public.device
    ADD CONSTRAINT device_agency_id_fkey FOREIGN KEY (agency_id) REFERENCES public.agency(id);
 F   ALTER TABLE ONLY public.device DROP CONSTRAINT device_agency_id_fkey;
       public       crowd4roads_sw    false    211    3640    209            R           2606    382524 -   single_data_old single_data_old_track_id_fkey    FK CONSTRAINT     �   ALTER TABLE ONLY public.single_data_old
    ADD CONSTRAINT single_data_old_track_id_fkey FOREIGN KEY (track_id) REFERENCES public.track(track_id) ON UPDATE CASCADE ON DELETE SET NULL;
 W   ALTER TABLE ONLY public.single_data_old DROP CONSTRAINT single_data_old_track_id_fkey;
       public       crowd4roads_sw    false    222    225    3664            S           2606    382529 (   single_data single_data_v2_track_id_fkey    FK CONSTRAINT     �   ALTER TABLE ONLY public.single_data
    ADD CONSTRAINT single_data_v2_track_id_fkey FOREIGN KEY (track_id) REFERENCES public.track(track_id) ON UPDATE CASCADE ON DELETE SET NULL;
 R   ALTER TABLE ONLY public.single_data DROP CONSTRAINT single_data_v2_track_id_fkey;
       public       crowd4roads_sw    false    224    3664    225            T           2606    382282    track track_device_id_fkey1    FK CONSTRAINT     �   ALTER TABLE ONLY public.track
    ADD CONSTRAINT track_device_id_fkey1 FOREIGN KEY (device_id) REFERENCES public.device(id) ON UPDATE CASCADE ON DELETE SET NULL;
 E   ALTER TABLE ONLY public.track DROP CONSTRAINT track_device_id_fkey1;
       public       crowd4roads_sw    false    211    3642    225           