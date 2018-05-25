var express = require('express');
var app = express();
var pg = require('pg');
var util = require('util');
var mcache = require('memory-cache');
var define = require("node-constants")(exports);
require('dotenv').load();

const config = {
    host: process.env.AGG_DB_HOST,
    user: process.env.AGG_DB_USER,
    database: 'srs_agg_db',
    password: process.env.AGG_DB_PASS,
    port: 5432
};

// pool takes the object above as parameter
const pool = new pg.Pool(config);

if(!process.env.AGG_DB_HOST) {
    console.error('Environmental variables not set!');
    process.exit(1);
}

define({
        AREA_QUERY: "SELECT ST_Area(ST_MakeEnvelope (%s, %s, %s, %s, 4326)::geography)/1000000000 as area;",
        BBOX_QUERY: "SELECT aggregate_id, ppe, st_asgeojson(the_geom) as geom, osm_id, highway, updated_at FROM current WHERE the_geom && ST_MakeEnvelope (%s, %s, %s, %s, 4326);"
});

define('LONG_CACHE_DURATION', process.env.SRS_LONG_CACHE || 30);
define('SHORT_CACHE_DURATION', process.env.SRS_SHORT_CACHE || 10);
define('PORT', process.env.SRS_PORT || 8080);

var cache = function(duration) {
  return function (req, res, next) {
    let key = '__express__' + req.originalUrl || req.url
    let cachedBody = mcache.get(key)
    if(cachedBody){
        res.setHeader('content-type', 'text/json');
        res.send(cachedBody)
        return
    }else{
        res.sendResponse = res.send
        res.send = function(body) {
            mcache.put(key, body, duration * 1000);
            res.setHeader('content-type', 'text/json');
            res.sendResponse(body)
        }
        next()
    }
  }
}

app.get('/cc', function (req, res) {
    mcache.clear();
    res.json({"cache entries" : mcache.size()});
})

var isFloat = function(value){
    var num = parseFloat(value);
    return  (!isNaN(num) && num > 0);
}

app.get('/bb/:xmin/:ymin/:xmax/:ymax', cache(exports.LONG_CACHE_DURATION), function (req, res) {
    var now = new Date().toISOString();
    const results = [];

    console.log(exports.AREA_QUERY,req.params.xmin,req.params.ymin,req.params.xmax,req.params.ymax);
    console.log(exports.BBOX_QUERY,req.params.xmin,req.params.ymin,req.params.xmax,req.params.ymax);

    if(!isFloat(req.params.xmin) || !isFloat(req.params.ymin) ||
        !isFloat(req.params.xmax) || !isFloat(req.params.ymax)){
            res.status(400).json({
                datetime: now,
                success:false,
                bbox: {
                    xmin: req.params.xmin,
                    ymin: req.params.ymin,
                    xmax: req.params.xmax,
                    ymax: req.params.ymax
                },
                error: "Wrong parameters"
            });
    }

    pool.connect(function(err, client, done) {
        // Handle connection errors
        if(err) {
          done();
          console.log(err);
          return res.status(500).json({success: false, data: err});
        }

        const checkarea = client.query(util.format(exports.AREA_QUERY,req.params.xmin,req.params.ymin,req.params.xmax,req.params.ymax),
            function (err, result) {
                done();

                console.log(result);

                if(err) {
                    done();
                    console.log(err);
                    return res.status(500).json({
                        datetime: now,
                        success: false,
                        bbox: {
                            xmin: req.params.xmin,
                            ymin: req.params.ymin,
                            xmax: req.params.xmax,
                            ymax: req.params.ymax
                        },
                        error: "Server error"});
                }

                if(parseFloat(result.rows[0]['area']) <= 2.0){
                    // SQL Query > Select Data
                    const query = client.query(util.format(exports.BBOX_QUERY,req.params.xmin,req.params.ymin,req.params.xmax,req.params.ymax),
                        function (err, result) {
                            done();

                            if(err) {
                                done();
                                console.log(err);
                                return res.status(500).json({
                                    datetime: now,
                                    success: false,
                                    bbox: {
                                        xmin: req.params.xmin,
                                        ymin: req.params.ymin,
                                        xmax: req.params.xmax,
                                        ymax: req.params.ymax
                                    },
                                    error: "Server error"});
                            }

                            result.rows.map(function(row){
                                row.geom = JSON.parse(row.geom);
                            });

                            res.json({
                                datetime: now,
                                success:true,
                                bbox: {
                                    xmin: req.params.xmin,
                                    ymin: req.params.ymin,
                                    xmax: req.params.xmax,
                                    ymax: req.params.ymax
                                },
                                data: result.rows
                            });

                    });
                } else {
                    res.status(400).json({
                        datetime: now,
                        success:false,
                        bbox: {
                            xmin: req.params.xmin,
                            ymin: req.params.ymin,
                            xmax: req.params.xmax,
                            ymax: req.params.ymax
                        },
                        error: "Bounding box too big (> 2000 square kilometers)"
                    });
                }
            });
    });
})

app.use(function (req, res) {
  res.status(404).send('') //not found
})

var checkDBConnection = function() {
    pool.connect((err, client, done) => {
        if(err) {
          done();
            console.log(err);
            throw err;
        }
    })
}

// Start server and listen on http://localhost:8080/
var server = app.listen(exports.PORT, function () {
    var host = server.address().address
    var port = server.address().port

    console.log("server listening at http://%s:%s", host, port)

    // checking DB connection
    checkDBConnection();
});
