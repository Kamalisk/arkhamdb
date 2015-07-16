/**
 * hypergeometric distribution module, homemade
 */ 
(function app_hypergeometric(hypergeometric, $) {
	
	var memo = [];
	
	/**
	 * @memberOf hypergeometric
	 */
	hypergeometric.get = function(k, N, K, n) {
		if ( !k || !N || !K || !n ) return 0;
		if ( memo_exists(k, N, K, n) ) {
			return get_memo(k, N, K, n);
		}
		if ( memo_exists(n - k, N, N - K, n) ) {
			return get_memo(n - k, N, N - K, n);
		}
		if ( memo_exists(K - k, N, K, N - n) ) {
			return get_memo(K - k, N, K, N - n);
		}
		if ( memo_exists(k, N, n, K) ) {
			return get_memo(k, N, n, K);
		}
		var d = app.binomial.get(N, n);
		if(d === 0) return 0;
		var r = app.binomial.get(K, k) * app.binomial.get(N - K, n - k) / d;
		memoize(k, N, K, n, r);
		return r;
	}
	
	/**
	 * @memberOf hypergeometric
	 */
	hypergeometric.get_cumul = function(k, N, K, n) {
		var r = 0;
		for(; k <= n; k++) {
			r += hypergeometric.get(k, N, K, n);
		}
		return r;
	}
	
	/**
	 * @memberOf hypergeometric
	 */
	function memo_exists(k, N, K, n) {
		return ( memo[k] != undefined && memo[k][N] != undefined && memo[k][N][K] != undefined && memo[k][N][K][n] != undefined );
	}
	
	/**
	 * @memberOf hypergeometric
	 */
	function get_memo(k, N, K, n) {
		return memo[k][N][K][n];
	}
	
	/**
	 * @memberOf hypergeometric
	 */
	function memoize(k, N, K, n, val) {
		if ( memo[k] === undefined ) {
			memo[k] = [];
		}
		if ( memo[k][N] === undefined ) {
			memo[k][N] = [];
		}
		if ( memo[k][N][K] === undefined ) {
			memo[k][N][K] = [];
		}
		memo[k][N][K][n] = val;
	}
	
})(app.hypergeometric = {}, jQuery);
