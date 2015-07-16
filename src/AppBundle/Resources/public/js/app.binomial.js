/**
 * binomial coefficient module, shamelessly ripped from https://github.com/pboyer/binomial.js
 */
(function app_binomial(binomial, $) {
	
	var memo = [];
	
	/**
	 * @memberOf binomial
	 */
	binomial.get = function(n, k) {
		if (k === 0) {
			return 1;
		}
		if (n === 0 || k > n) {
			return 0;
		}
		if (k > n - k) {
        	k = n - k;
        }
		if ( memo_exists(n,k) ) {
			return get_memo(n,k);
		}
	    var r = 1,
	    	n_o = n;
	    for (var d=1; d <= k; d++) {
	    	if ( memo_exists(n_o, d) ) {
	    		n--;
	    		r = get_memo(n_o, d);
	    		continue;
	    	}
			r *= n--;
	  		r /= d;
	  		memoize(n_o, d, r);
	    }
	    return r;
	};
	
	function memo_exists(n, k) {
		return ( memo[n] != undefined && memo[n][k] != undefined );
	}
	
	function get_memo(n, k) {
		return memo[n][k];
	}
	
	function memoize(n, k, val) {
		if ( memo[n] === undefined ) {
			memo[n] = [];
		}
		memo[n][k] = val;
	}
	
})(app.binomial = {}, jQuery);
