(function () {
	class Moments {
		#callbacks = {};
		#isReady = false;
		
		constructor()
		{
			this._isReady = false;
			
			if (document.readyState === 'complete' || document.readyState === 'interactive') {
				this.#triggerCallbacks('ready');
			} else {
				document.addEventListener(
					'DOMContentLoaded',
					() => this.#triggerCallbacks('ready')
				);
			}
		}
		
		#triggerCallbacks(callbackType, ...params)
		{
			this.#isReady = true;
			if (this.#callbacks[callbackType] !== undefined) {
				this.#callbacks[callbackType].forEach(cb => cb(...params));
			}
		}
		
		onReady(callback)
		{
			if (this.#isReady) {
				callback();
			} else {
				if (this.#callbacks.ready == undefined) {
					this.#callbacks.ready = [];
				}
				this.#callbacks.ready.push(callback);
			}
		}
		
		ajax(url, callback, onerror = null, post = null)
		{
			var r = new XMLHttpRequest();
			var processedUntil = 0;
			
			r.addEventListener(
				'readystatechange',
				e => {
					if (r.readyState == XMLHttpRequest.LOADING || r.readyState == XMLHttpRequest.DONE) {
						var cur = r.responseText.substring(processedUntil);
						var parsed;
						
						try {
							parsed = JSON.parse(cur);
						} catch (e) {
							parsed = null;
						}
						if (parsed) {
							processedUntil += cur.length;
							callback(parsed);
							return;
						}
						
						var n = 1;
						while (n < cur.length && n > 0) {
							n = cur.indexOf('}', n);
							try {
								parsed = JSON.parse(cur.substring(0, n + 1));
							} catch (e) {
								parsed = null;
							}
							if (parsed) {
								processedUntil += n;
								cur = cur.substring(n + 1);
								n = 1;
								callback(parsed);
							} else {
								n++;
							}
						}
					}
				}
			);
			r.addEventListener(
				'error',
				e => {
					if (onerror) {
						onerror(e, r.responseText);
					}
				}
			);
			if (post !== null) {
				r.open('POST', url);
				r.setRequestHeader('Content-Type', 'application/json; charset=UTF-8');
				r.send(JSON.stringify(post));
			} else {
				r.open('GET', url);
				r.send();
			}
		}
	}
	
	window.moments = new Moments();
})();
