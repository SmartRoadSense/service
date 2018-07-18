using SixLabors.ImageSharp.PixelFormats;

namespace TileServer {

    public static class PpeColorMapper {

        private readonly static Rgba32[] _colors = {
            new Rgba32(0  , 128, 0),
            new Rgba32(138, 205, 0),
            new Rgba32(157, 215, 0),
            new Rgba32(201, 236, 0),
            new Rgba32(255, 255, 0),
            new Rgba32(255, 215, 0),
            new Rgba32(255, 184, 0),
            new Rgba32(255, 160, 0),
            new Rgba32(255, 140, 0),
            new Rgba32(255,   0, 0)
        };

        private readonly static double[] _thresholds = {
            0.0811,
            0.2058,
            0.3824,
            0.5861,
            0.8311,
            1.1207,
            1.4040,
            1.6392,
            1.7985
        };

        /// <summary>
        /// Gets the matching color for a given PPE value.
        /// </summary>
        public static Rgba32 Map(double ppe) {
            for (int i = 0; i < _thresholds.Length; ++i) {
                if (ppe <= _thresholds[i]) {
                    return _colors[i];
                }
            }

            return _colors[_colors.Length - 1];
        }

    }

}
