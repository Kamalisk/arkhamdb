(function() {
  'use strict';
  var collator = Intl && Intl.Collator && new Intl.Collator("generic", { sensitivity: "base" });

  /**
   * Extend an Object with another Object's properties.
   *
   * The source objects are specified as additional arguments.
   *
   * @param dst Object the object to extend.
   *
   * @return Object the final object.
   */
  var _extend = function(dst) {
    var sources = Array.prototype.slice.call(arguments, 1);
    for (var i=0; i<sources.length; ++i) {
      var src = sources[i];
      for (var p in src) {
        if (src.hasOwnProperty(p)) dst[p] = src[p];
      }
    }
    return dst;
  };

  /**
   * Based on the algorithm at http://en.wikipedia.org/wiki/Levenshtein_distance.
   */
  var Levenshtein = {
    /**
     * Calculate levenshtein distance of the two strings.
     *
     * @param str1 String the first string.
     * @param str2 String the second string.
     * @return Integer the levenshtein distance (0 and above).
     */
    get: function(str1, str2) {
      // base cases
      if (str1 === str2) return 0;
      if (str1.length === 0) return str2.length;
      if (str2.length === 0) return str1.length;

      // two rows
      var prevRow  = new Array(str2.length + 1),
          curCol, nextCol, i, j, tmp, comp;

      // initialise previous row
      for (i=0; i<prevRow.length; ++i) {
        prevRow[i] = i;
      }

      // calculate current row distance from previous row
      for (i=0; i<str1.length; ++i) {
        nextCol = i + 1;

        for (j=0; j<str2.length; ++j) {
          curCol = nextCol;

          // substution
		  nextCol = prevRow[j] + ( ( collator ? (collator.compare(str1.charAt(i), str2.charAt(j)) === 0) : (str1.charAt(i) === str2.charAt(j)) ) ? 0 : 1 );
		  
          // insertion
          tmp = curCol + 1;
          if (nextCol > tmp) {
            nextCol = tmp;
          }
          // deletion
          tmp = prevRow[j + 1] + 1;
          if (nextCol > tmp) {
            nextCol = tmp;
          }

          // copy current col value into previous (in preparation for next iteration)
          prevRow[j] = curCol;
        }

        // copy last col value into previous (in preparation for next iteration)
        prevRow[j] = nextCol;
      }

      return nextCol;
    },
  };

  // amd
  if (typeof define !== "undefined" && define !== null && define.amd) {
    define(function() {
      return Levenshtein;
    });
  }
  // commonjs
  else if (typeof module !== "undefined" && module !== null) {
    module.exports = Levenshtein;
  }
  // web worker
  else if (typeof self !== "undefined" && typeof self.postMessage === 'function' && typeof self.importScripts === 'function') {
    self.Levenshtein = Levenshtein;
  }
  // browser main thread
  else if (typeof window !== "undefined" && window !== null) {
    window.Levenshtein = Levenshtein;
  }
}());

