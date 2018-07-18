using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;

namespace TileServer.Geo {

    /// <summary>
    /// Math for conversion between Mercator-meters, tile screen pixels, and lat/long.
    /// </summary>
    /// <remarks>
    /// Code taken from NPM library mapbox/sphericalmercator, <seealso cref="https://github.com/mapbox/sphericalmercator"/>.
    /// </remarks>
    public class SphericalMercator {

        public SphericalMercator(int tileSize) {
            TileSize = tileSize;

            // Populate cache
            _cacheBc = new double[CachedZoomLevels];
            _cacheCc = new double[CachedZoomLevels];
            _cacheZc = new double[CachedZoomLevels];
            _cacheAc = new double[CachedZoomLevels];

            for(int d = 0; d < CachedZoomLevels; ++d) {
                double zoomSize = TileSize * Math.Pow(2, d);

                _cacheBc[d] = zoomSize / 360;
                _cacheCc[d] = zoomSize / (2 * Math.PI);
                _cacheZc[d] = zoomSize / 2;
                _cacheAc[d] = zoomSize;
            }
        }

        public int TileSize { get; private set; }

        private readonly double[] _cacheBc, _cacheCc, _cacheZc, _cacheAc;

        private const double
            EPSLN = 1.0e-10,
            D2R = Math.PI / 180,
            R2D = 180 / Math.PI;

        private const int CachedZoomLevels = 30;

        /// <summary>
        /// Convert xyz tile to a lat/long bounding box.
        /// </summary>
        /// <param name="x">Pixels on X axis (longitude).</param>
        /// <param name="y">Pixels on Y axis (latitude)</param>
        /// <param name="zoom">Zoom level.</param>
        public BoundingBox CreateBoundingBox(double x, double y, int zoom) {
            var lowerLeft  = ToLatLong(x * TileSize, (y + 1) * TileSize, zoom);
            var upperRight = ToLatLong((x + 1) * TileSize, y * TileSize, zoom);

            return new BoundingBox(
                lowerLeft.Longitude, lowerLeft.Latitude,
                upperRight.Longitude, upperRight.Latitude
            );
        }

        /// <summary>
        /// Convert screen pixels to lat/long.
        /// </summary>
        /// <param name="x">Pixels on X axis.</param>
        /// <param name="y">Pixels on Y axis.</param>
        /// <param name="zoom">Zoom level.</param>
        public Coordinate ToLatLong(double x, double y, int zoom) {
            if (zoom >= 0 && zoom < CachedZoomLevels - 1) {
                var g = (y - _cacheZc[zoom]) / (-_cacheCc[zoom]);
                return new Coordinate(
                    R2D * (2 * Math.Atan(Math.Exp(g)) - 0.5 * Math.PI),
                    (x - _cacheZc[zoom]) / _cacheBc[zoom]
                );
            }
            else {
                var size = TileSize * Math.Pow(2, zoom);
                var bc = (size / 360);
                var cc = (size / (2 * Math.PI));
                var zc = size / 2;
                var g = (y - zc) / -cc;
                var lon = (x - zc) / bc;
                var lat = R2D * (2 * Math.Atan(Math.Exp(g)) - 0.5 * Math.PI);
                return new Coordinate(lat, lon);
            }
        }

        /// <summary>
        /// Converts lat/long coordinates into screen pixel coordinates.
        /// </summary>
        public (int x, int y) ToPixels(Coordinate coord, int zoom) {
            if (zoom >= 0 && zoom < CachedZoomLevels - 1) {
                var d = _cacheZc[zoom];
                var f = Math.Min(Math.Max(Math.Sin(D2R * coord.Latitude), -0.9999), 0.9999);
                var x = Math.Round(d + coord.Longitude * _cacheBc[zoom]);
                var y = Math.Round(d + 0.5 * Math.Log((1 + f) / (1 - f)) * (-_cacheCc[zoom]));
                if (x > _cacheAc[zoom])
                    x = _cacheAc[zoom];
                if (y > _cacheAc[zoom])
                    y = _cacheAc[zoom];
                return ((int)x, (int)y);
            }
            else {
                var size = TileSize * Math.Pow(2, zoom);
                var d = size / 2;
                var bc = (size / 360);
                var cc = (size / (2 * Math.PI));
                var ac = size;
                var f = Math.Min(Math.Max(Math.Sin(D2R * coord.Latitude), -0.9999), 0.9999);
                var x = d + coord.Longitude * bc;
                var y = d + 0.5 * Math.Log((1 + f) / (1 - f)) * -cc;
                if (x > ac)
                    x = ac;
                if (y > ac)
                    y = ac;
                return ((int)x, (int)y);
            }
        }

    }

}
