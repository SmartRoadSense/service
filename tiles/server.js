'use strict';
let cluster = require('cluster');


if (cluster.isMaster) {

  const CPUS = require('os').cpus();

  CPUS.forEach(function() {
    cluster.fork();
  });

  cluster.on('exit', function(worker) {
    console.log(`worker ${worker.process.pid} died`);
  });

} else if (cluster.isWorker) {

  let Canvas = require('canvas');
  let http = require('http');
  let SphericalMercator = require('sphericalmercator');
  let app = require('express')();

  // Server port

  const PORT = process.env.PORT || 8000;

  // SRS API hostname and port

  const SRS_HOST = process.env.SRS_HOST || 'web';
  const SRS_PORT = process.env.SRS_PORT || 8080;

  // ppe values related to color classes

  const COLOR_FIRST_STEP = 0.6;
  const COLOR_SECOND_STEP = 2;
  const COLOR_THIRD_STEP = 4;

  // NOTE: sizes are expressed in number of pixels

  const TILE_LENGTH = 256;
  const CIRCLE_RADIUS = 4;

  app.get('/api/v1/tiles/:zoom/:x/:y/:mark?/:all?', function(req, res) {

    let mark = req.params.mark
    let include_old_data = req.params.all
    let coords = {x: req.params.x, y: req.params.y};
    let zoom = req.params.zoom;

    console.log(coords);
    console.log(`zoom = ${zoom}`);

    let merc = new SphericalMercator({size: TILE_LENGTH});
    let bbox = merc.bbox(coords.x, coords.y, zoom);

    let bboxOffset = 16 / Math.pow(2, zoom);
    console.log(`offset = ${bboxOffset}`);

    // extended bbox used for SRS query
    let bboxExt = [
      bbox[0] - bboxOffset,
      bbox[1] - bboxOffset,
      bbox[2] + bboxOffset,
      bbox[3] + bboxOffset
    ];

	var path_str = `/ws/?bbox=${bboxExt}&zoom_level=${zoom}`

	if (mark) {
        path_str += `&mark=${mark}`
    }

	if (include_old_data) {
        path_str += `&all=1`
    }

    let params = {
      host: SRS_HOST,
      port: SRS_PORT,
      path: path_str
    };

    console.log(params);

    let data = '';

    http.get(params, function(resp) {

      resp.on('data', function(chunk) {
        data += chunk;
      });

      resp.on('end', function() {
        console.log('End GET');
        let values = JSON.parse(data);
        console.log(`elements: ${values.features.length}`);

        let canvas = Canvas.createCanvas(TILE_LENGTH, TILE_LENGTH);
        let context = canvas.getContext('2d');

        // absolute pixel position of the border box NE and SW vertexes
        let sw = merc.px([bbox[0], bbox[1]], zoom);
        let ne = merc.px([bbox[2], bbox[3]], zoom);

        for (let feature of values.features) {

          // average street quality indicator
          let ppe = feature.ppe;

          let red = 0, green = 0, blue = 0;
          let light = 255, pitch;

          if (ppe <= COLOR_FIRST_STEP) {
            red = 1 / COLOR_FIRST_STEP * ppe;
            green = 1;
            light = 127 + 128 * (1 / COLOR_FIRST_STEP) * ppe;
          }

          else if (ppe > COLOR_FIRST_STEP && ppe < COLOR_SECOND_STEP) {
            pitch = COLOR_SECOND_STEP - COLOR_FIRST_STEP;
            red = 1;
            green = -1 / pitch * ppe + COLOR_SECOND_STEP / pitch;
          }

          else if (ppe < COLOR_THIRD_STEP) {
            pitch = COLOR_THIRD_STEP - COLOR_SECOND_STEP;
            red = 1;
            blue = Math.min(1, (ppe - pitch) / pitch);
          }

          red = Math.floor(red * 255);
          green = Math.floor(green * light);
          blue = Math.floor(blue * 255);

          let color = `rgba(${red}, ${green}, ${blue}, 0.9)`;

          let lon = feature.geometry.coordinates[0];
          let lat = feature.geometry.coordinates[1];

          // absolute pixel position of the feature
          let absPos = merc.px([lon, lat], zoom);

          // position of the point inside the tile
          let relPos = [
            absPos[0] - sw[0],
            absPos[1] - ne[1]
          ];

          context.beginPath();
          context.fillStyle = color;
          context.arc(relPos[0], relPos[1], CIRCLE_RADIUS, 0, Math.PI*2);
          context.closePath();
          context.fill();
        }

        // set the Content-type header
        res.type('png');

        let stream = canvas.createPNGStream();
        let chunks = [];

        stream.on('data', function(chunk) {
          chunks.push(chunk);
        });

        stream.on('end', function() {
          let buf = Buffer.concat(chunks);
          res.send(buf);
        });

      });

    }).on('error', function(e) {
      console.log(`Got error: ${e.message}`);
    });
  });

  app.listen(PORT);
  console.log(`Server running on port ${PORT}`);
}
