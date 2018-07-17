using Microsoft.AspNetCore.Mvc;
using Microsoft.Extensions.Logging;
using Newtonsoft.Json;
using SixLabors.ImageSharp;
using SixLabors.ImageSharp.Formats.Png;
using SixLabors.ImageSharp.PixelFormats;
using SixLabors.ImageSharp.Processing;
using SixLabors.ImageSharp.Processing.Drawing;
using SixLabors.ImageSharp.Processing.Text;
using SixLabors.Shapes;
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Net.Http;
using System.Threading.Tasks;
using TileServer.Geo;

namespace TileServer.Controllers {

    [Route("api/srs-tiles/")]
    public class TileController : Controller {

        public static HttpClient Client = new HttpClient();

        protected static SphericalMercator Mercator = new SphericalMercator(TileSize);

        public TileController(ILogger<TileController> logger) {
            _logger = logger;
        }

        private readonly ILogger _logger;

        public const int TileSize = 256;

        private async Task<IEnumerable<(Coordinate coord, double ppe)>> FetchFeatures(BoundingBox bbox, int zoom) {
            string url = string.Format(
                System.Globalization.CultureInfo.InvariantCulture,
                "http://{0}:{1}/ws/?bbox={2},{3},{4},{5}&zoom_level={6}",
                Environment.GetEnvironmentVariable("WEB_HOST"),
                Environment.GetEnvironmentVariable("WEB_PORT"),
                bbox.West,
                bbox.South,
                bbox.East,
                bbox.North,
                zoom
            );
            _logger.LogDebug("HTTP request {url}", url);

            var response = await Client.GetAsync(url);
            response.EnsureSuccessStatusCode();

            var responseStream = await response.Content.ReadAsStreamAsync();
            using (var reader = new StreamReader(responseStream)) {
                using (var jsonReader = new JsonTextReader(reader)) {
                    var serializer = new JsonSerializer();
                    var decodedResponse = serializer.Deserialize<DataModel.BoundingBoxResponse>(jsonReader);

                    return from f in decodedResponse.Features
                           select (f.Geometry.ToGeoCoordinate(), f.PPE);
                }
            }
        }

        private Stream DrawFeatures(BoundingBox bbox, int zoom, IEnumerable<(Coordinate coord, double ppe)> features) {
            _logger.LogDebug("Geo bounds {0},{1},{2},{3}", bbox.SouthWest.Latitude, bbox.SouthWest.Longitude, bbox.NorthEast.Latitude, bbox.NorthEast.Longitude);

            var swPx = Mercator.ToPixels(bbox.SouthWest, zoom);
            var nePx = Mercator.ToPixels(bbox.NorthEast, zoom);
            _logger.LogDebug("Tile bounds {w},{s},{e},{n} px", swPx.x, swPx.y, nePx.x, nePx.y);

            var responseStream = new MemoryStream();
            using (Image<Rgba32> i = new Image<Rgba32>(TileSize, TileSize)) {
                i.Mutate(ctx => {
                    foreach(var f in features) {
                        var coordPx = Mercator.ToPixels(f.coord, zoom);
                        var featX = (float)(coordPx.x - swPx.x);
                        var featY = (float)(coordPx.y - nePx.y);

                        _logger.LogDebug("Feature {x},{y} => {pxx},{pxy} => {featX},{featY}", f.coord.Latitude, f.coord.Longitude, coordPx.x, coordPx.y, featX, featY);

                        var ellipse = new EllipsePolygon(featX, featY, zoom / 2f);
                        ctx.Fill(Rgba32.Red, ellipse);
                    }
                });

                // Write image to stream
                i.Save(responseStream, new PngEncoder());
                responseStream.Position = 0;
            }

            return responseStream;
        }

        [HttpGet("{zoom}/{x}/{y}")]
        public async Task<ActionResult> Get(int zoom, double x, double y) {
            _logger.LogInformation("Getting tile {x},{y} zoom {zoom}", x, y, zoom);

            var bbox = Mercator.CreateBoundingBox(x, y, zoom);
            _logger.LogDebug("Bounding box [{w},{s},{e},{n}]", bbox.West, bbox.South, bbox.East, bbox.North);

            // TODO: expand bounding box with offset to get all points

            var features = await FetchFeatures(bbox, zoom);
            _logger.LogInformation("Drawing {count} features on tile", features.Count());

            var responseStream = DrawFeatures(bbox, zoom, features);
            return File(responseStream, "image/png");
        }

    }

}
