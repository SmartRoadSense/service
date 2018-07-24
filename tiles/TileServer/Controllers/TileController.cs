using Microsoft.AspNetCore.Mvc;
using Microsoft.Extensions.Logging;
using Newtonsoft.Json;
using SixLabors.ImageSharp;
using SixLabors.ImageSharp.Formats.Png;
using SixLabors.ImageSharp.PixelFormats;
using SixLabors.ImageSharp.Processing;
using SixLabors.ImageSharp.Processing.Drawing;
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

        private static readonly Rgba32 ShadowColor = new Rgba32(0.04f, 0.02f, 0.1f, 0.6f);

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
            // Convert features from lat/long to tile pixel coordinates
            (int westPx, _) = Mercator.ToPixels(bbox.SouthWest, zoom);
            (_, int northPx) = Mercator.ToPixels(bbox.NorthEast, zoom);
            var pixelFeatures = from f in features
                                let coordPx = Mercator.ToPixels(f.coord, zoom)
                                select ((float)(coordPx.x - westPx), (float)(coordPx.y - northPx), f.ppe);

            float dotRadius = zoom / 2f;

            // Compose image data
            var responseStream = new MemoryStream();
            using (Image<Rgba32> i = new Image<Rgba32>(TileSize, TileSize)) {
                i.Mutate(ctx => {
                    foreach(var f in pixelFeatures) {
                        ctx.Fill(ShadowColor, new EllipsePolygon(f.Item1, f.Item2, dotRadius * 1.2f));
                    }

                    foreach(var f in pixelFeatures) {
                        var ellipse = new EllipsePolygon(f.Item1, f.Item2, dotRadius);
                        ctx.Fill(PpeColorMapper.Map(f.ppe), ellipse);
                    }
                });

                // Write down to stream
                i.Save(responseStream, new PngEncoder());
                responseStream.Position = 0;
            }

            return responseStream;
        }

        [HttpGet("{zoom}/{x}/{y}")]
        public async Task<ActionResult> Get(int zoom, double x, double y) {
            if(zoom < 1 ) {
                return BadRequest();
            }

            _logger.LogInformation("Getting tile {x},{y} zoom {zoom}", x, y, zoom);

            var bbox = Mercator.CreateBoundingBox(x, y, zoom);
            _logger.LogDebug("Tile bounding box [{w},{s},{e},{n}]", bbox.West, bbox.South, bbox.East, bbox.North);

            var bboxOffset = 16 / Math.Pow(2, zoom);
            var bboxExt = bbox.Expand(bboxOffset);

            var features = await FetchFeatures(bboxExt, zoom);
            _logger.LogDebug("Drawing {count} features on tile", features.Count());

            var responseStream = DrawFeatures(bbox, zoom, features);
            return File(responseStream, "image/png");
        }

    }

}
