
/**
 * TODO
 * 
 * @param HTMLElement container
 * @param boolean setFocus
 */
function PoxyBrowser (container, config) {
	
	// Check parameter
	if (!(container instanceof HTMLElement)) {
		throw "Invalid argument: " + container + " (expected: HTMLElement)";
	}
	
	// Default configuration
	this.config = {
		script_url:			'',
		url_var_name:			'q',
		base64_encode:			false,
		rotate13:				false,
		iframe_change_delay:	500
	};
	
	// Apply given configuration
	if (typeof config == 'object') {
		for (key in config) {
			this.config[key] = config[key];
		}
	}
	
	// Solve script_url
	var tmp = document.createElement('a');
	tmp.href = this.config.script_url;
	this.config.script_url = tmp.href;
	
	// Save container
	this.container = container;
	
	// Initialize the listener pattern
	this.initListener();
	
	// Create components container
	this.ui = {};
	
};

/**
 * TODO
 * 
 * @param string key
 * @return mixed|undefined
 */
PoxyBrowser.prototype.getConfig = function (key) {
	return this.config[key];
};

/**
 * TODO
 */
PoxyBrowser.prototype.setup = function (setFocus) {
	
	// Event before
	if (!this.trigger('beforeBrowserInitialized', [this])) {
		return;
	}
	
	// Set up styles
	this.container.classList.add('poxy2-browser');
	
	// Create UI components
	this.createUI(this.container);
	
	// Create navigation buttons
	this.createNavigationButtons();
	
	// Bind UI events
	this.initUIBehavior();
	
	// Clean given container, and append the browser table
	if (this.trigger('beforeUIAttached', [this, this.container])) {
		this.container.innerHTML = '';
		this.container.appendChild(this.ui.table);
		this.trigger('afterUIAttached', [this, this.container]);
	}
	
	// Focus the address field
	if (setFocus !== false && this.trigger('beforeAddressBarFocused', [this])) {
		this.ui.addressfield.focus();
		this.trigger('afterAddressBarFocused', [this]);
	}
	
	// Event after
	this.trigger('afterBrowserInitialized', [this]);
	
}

/**
 * TODO
 * 
 * @var string[]
 */
PoxyBrowser.prototype.i18n = {
	"AddressField.Placeholder": "Enter a website address",
	"Error.InvalidURL": "Invalid URL: %url%"
};

/**
 * TODO
 * 
 * @param string code
 * @return string
 */
PoxyBrowser.prototype.getLocal = function (code) {
	return this.i18n[code];
};

/**
 * TODO
 * 
 * @param string url
 * @param boolean proxify Default is TRUE
 */
PoxyBrowser.prototype.setURL = function (url, proxify) {

	// Event before
	if (!this.trigger('beforeURLChanged', [this, url, proxify])) {
		return;
	}
	
	// Update address bar
	this.ui.addressfield.value = url;
	
	// Proxify URL
	if (proxify !== false) {
		url = this.proxifyURL(url);
	}
	
	// Change iframe's URL
	this.ui.frame.src = url;
	
	// Trigger a stateChange event
	this.trigger('onStateChanged', [this, 'loading']);
	
	// Event after
	this.trigger('afterURLChanged', [this, url, proxify]);
	
};

/**
 * TODO
 * 
 * @return string|null
 */
PoxyBrowser.prototype.getURL = function () {
	// TODO
};

/**
 * TODO
 */
PoxyBrowser.prototype.setInfo = function (data, status) {
	
	// Set infobar content
	if (data instanceof HTMLElement) {
		// Clear info container
		this.ui.bottombar.innerHTML = '';
		// Append element
		this.ui.bottombar.appendChild(data);
	}
	else {
		// Cast data as a string, and fill in bottom bar with
		this.ui.bottombar.innerHTML = data + "";
	}
	
	// Bar style
	if (status == 'warn' || status == 'error') {
		this.ui.bottombar.className = status;
	}
	else {
		this.ui.bottombar.className = 'info';
	}

	// Display
	this.ui.bottom.classList.add('active');
	
};

/**
 * TODO
 * 
 * @return HTMLElement[]
 */
PoxyBrowser.prototype.getUIComponents = function () {
	return this.ui;
};

/**
 * TODO
 */
PoxyBrowser.prototype.createUI = function () {
	
	// Event before
	if (!this.trigger('beforeUIComponentsCreated', [this])) {
		return;
	}
	
	// Create table
	this.ui.table = document.createElement('table');
	
	// Create top bar
	this.ui.topbar = document.createElement('tr');
	this.ui.topbar.className = 'poxy2-topbar';

	// Create navigation container
	this.ui.nav = document.createElement('td');
	this.ui.nav.setAttribute('width', '1%');
	
	// Create address bar container
	this.ui.addressbar = document.createElement('td');
	this.ui.addressbar.setAttribute('width', '*');
	
	// Create address bar field
	this.ui.addressfield = document.createElement('input');
	this.ui.addressfield.className = 'poxy2-addressbar';
	this.ui.addressfield.setAttribute('type', 'text');
	this.ui.addressfield.setAttribute('placeholder', this.i18n["AddressField.Placeholder"]);
	
	// Create option container
	this.ui.options = document.createElement('td');
	this.ui.options.setAttribute('width', '1%');
	
	// Create view area
	this.ui.viewport = document.createElement('tr');
	this.ui.viewport.className = 'poxy2-viewport';
	
	// Create view container
	this.ui.view = document.createElement('td');
	this.ui.view.setAttribute('colspan', '3');
	
	// Create view iframe
	this.ui.frame = document.createElement('iframe');
	this.ui.frame.setAttribute('src', '');
	this.ui.frame.setAttribute('allowtransparency', 'false');
	
	// Create bottom area
	this.ui.bottom = document.createElement('tr');
	this.ui.bottom.className = 'poxy2-infobar';
	
	// Create bottom container
	this.ui.bottombar = document.createElement('td');
	this.ui.bottombar.setAttribute('colspan', '3');
	
	// Assembly (top)
	this.ui.topbar.appendChild(this.ui.nav);
	this.ui.addressbar.appendChild(this.ui.addressfield);
	this.ui.topbar.appendChild(this.ui.addressbar);
	this.ui.topbar.appendChild(this.ui.options);
	this.ui.table.appendChild(this.ui.topbar);
	
	// Assembly (body)
	this.ui.view.appendChild(this.ui.frame);
	this.ui.viewport.appendChild(this.ui.view);
	this.ui.table.appendChild(this.ui.viewport);
	
	// Assembly (bottom)
	this.ui.bottom.appendChild(this.ui.bottombar);
	this.ui.table.appendChild(this.ui.bottom);
	
	// Event after
	this.trigger('afterUIComponentsCreated', [this]);
	
};

/**
 * TODO
 */
PoxyBrowser.prototype.createNavigationButtons = function () {

	// Create components container
	this.ui.navbuttons = {};
	
	// Event before
	if (!this.trigger('beforeNavigationButtonsCreated', [this])) {
		return;
	}
	
	// Refresh button
	this.ui.navbuttons.refresh = document.createElement('a');
	this.ui.navbuttons.refresh.className = 'poxy2-bt poxy2-bt-refresh';
	this.ui.navbuttons.refresh.innerHTML = 'Refresh';
	
	// Assembly
	this.ui.nav.appendChild(this.ui.navbuttons.refresh);
	
	// Event before
	this.trigger('afterNavigationButtonsCreated', [this]);
	
};

/**
 * TODO
 */
PoxyBrowser.prototype.initUIBehavior = function () {
	
	// Event before
	if (!this.trigger('beforeUIBehaviorBound', [this])) {
		return;
	}
	
	// Keep a reference to this object
	var browser = this;
	
	// Submit address on enter key hit
	this.ui.addressfield.onkeyup = function (e) {
		// Enter key
		if (e.keyCode === 13) {
			browser.setURL(this.value);
		}
	};
	
	// Iframe changes monitoring thread
	this.lastURL = null;
	this.iframeChangesThread = window.setInterval(function () {
		var frame = browser.ui.frame.contentWindow;
		if (!frame || !frame.location) {
			return;
		}
		if (browser.lastURL != null && frame.location.href != browser.lastURL) {
			browser.trigger('onStateChanged', [browser, 'loading']);
			browser.trigger('onPageChanged', [browser, frame.location.href]);
		}
		browser.lastURL = frame.location.href;
	}, this.config.iframe_change_delay);
	
	// Iframe changes using onLoad event
	this.ui.frame.onload = function () {
		browser.trigger('onStateChanged', [browser, 'ready']);
		browser.trigger('onBodyLoaded', [browser]);
		if (browser.lastURL != null && browser.ui.frame.contentWindow.location.href != browser.lastURL) {
			browser.lastURL = browser.ui.frame.contentWindow.location.href;
			browser.trigger('onPageChanged', [browser, browser.lastURL]);
		}
		else {
			browser.lastURL = browser.ui.frame.contentWindow.location.href;
		}
	};
	
	// Refresh/Stop button behavior
	this.bind('onStateChanged', function (browser, state) {
		if (state === 'loading') {
			browser.ui.navbuttons.refresh.classList.add('stop');
		}
		else {
			browser.ui.navbuttons.refresh.classList.remove('stop');
		}
	});
	
	// Synchronize the address bar with the iframe location
	this.bind('onPageChanged', function (browser, url) {
		// Decode URL
		var url = browser.unproxifyURL(url);
		// Update address bar
		browser.ui.addressfield.value = url; 
	});
	
	// Behavior of the refresh/close button
	this.ui.navbuttons.refresh.onclick = function () {
		
		// Stop
		if (this.classList.contains('stop')) {
			if (typeof browser.ui.frame.stop == "function") {
				// Firefox & webkit
				browser.ui.frame.stop();
				browser.trigger('onStateChanged', [browser, 'stopped']);
			}
			else {
				// IE
				browser.ui.frame.contentWindow.document.execCommand('Stop');
				browser.trigger('onStateChanged', [browser, 'stopped']);
			}
		}
		
		// Refresh
		else {
			browser.trigger('onStateChanged', [browser, 'loading']);
			browser.ui.frame.contentWindow.location = browser.ui.frame.contentWindow.location;
		}

	};
	
	// Event after
	this.trigger('beforeUIBehaviorBound', [this]);
	
};

/**
 * TODO
 * 
 * @param string url
 * @return string|null
 */
PoxyBrowser.prototype.unproxifyURL = function (url) {
	
	// Invalid argument
	if (typeof url != "string") {
		return null;
	}
	
	// This URL is not proxyfied, return as this
	if (url.substr(0, this.config.script_url.length) != this.config.script_url) {
		return url;
	}
	
	// Extract encoded URL part 
	var encoded = getQuerystring(url, this.config.url_var_name);
	
	// Encoded using rotate 13
	if (this.config.rotate13) {
		return str_rot13(encoded);
	}
	
	// Encoded using base64
	if (this.config.base64_encode) {
		return base64_decode(encoded);
	}
	
	// Not encoded
	return encoded;
	
};

/**
 * TODO
 * 
 * @return string|null
 */
PoxyBrowser.prototype.proxifyURL = function (url) {

	// Rotate 13 encoding
	if (this.config.rotate13) {
		url = str_rot13(url);
	}
	// Base 64 encoding
	else if (this.config.base64_encode) {
		url = base64_encode(url);
	}
	
	// Rewrite URL
	return this.config.script_url
		+ (url.indexOf('?') < 0 ? '?' : '&')
		+ this.config.url_var_name
		+ '='
		+ encodeURIComponent(url);

};

/**
 * TODO
 * 
 * @param boolean removeUI TRUE by default.
 */
PoxyBrowser.prototype.destroy = function (removeUI) {
	
	// Remove listeners references
	this.listeners = [];
	
	// Detach UI components
	if (removeUI !== false) {
		this.container.innerHTML = '';
		this.container = null;
	}
	
	// Remove components references
	this.ui = {};
	
	// Disable iframe changes monitoring
	if (this.iframeChangesThread) {
		window.clearInterval(this.iframeChangesThread);
	}
	
};

/**
 * Escape html special chars.
 * 
 * @param string unsafe
 * @return string
 */
function escapeHtml (unsafe) {
	return unsafe
		.replace(/&/g, "&amp;")
		.replace(/</g, "&lt;")
		.replace(/>/g, "&gt;")
		.replace(/"/g, "&quot;")
		.replace(/'/g, "&#039;");
}

/**
 * Extract a variable from a query string.
 * 
 * @param string url
 * @param string key
 * @return string
 */
function getQuerystring (url, key) { 
	var a = document.createElement('a');
	a.href = url;
	//var search = unescape(a.search);
	var search = a.search;
	if (search == "") {
	    return "";
	}
	search = search.substr(1);
	var params = search.split("&");
	for (var i = 0; i < params.length; i++) {
	    var pairs = params[i].split("=");
	    if (pairs[0] == key) {
	    	//return pairs[1];
	    	return unescape(pairs[1]);
	    }
	}
	return "";
}

/**
 * Perform the rot13 transform on a string
 * 
 * Performs the ROT13 encoding on the str argument and returns the resulting string.
 * 
 * The ROT13 encoding simply shifts every letter by 13 places in the alphabet while leaving
 * non-alpha characters untouched. Encoding and decoding are done by the same function,
 * passing an encoded string as argument will return the original version.
 * 
 * @param string str
 * @return string
 * @see http://phpjs.org/functions/str_rot13
 */
function str_rot13 (str) {
    return (str + '').replace(/[a-z]/gi, function (s) {
        return String.fromCharCode(s.charCodeAt(0) + (s.toLowerCase() < 'n' ? 13 : -13));
    });
}

/**
 * Encodes data with MIME base64
 * 
 * Encodes the given data with base64. This encoding is designed to make binary data survive
 * transport through transport layers that are not 8-bit clean, such as mail bodies.
 * 
 * Base64-encoded data takes about 33% more space than the original data.
 * 
 * @param string data
 * @return string
 * @see http://phpjs.org/functions/base64_encode
 */
function base64_encode (data) {

    var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
    var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
        ac = 0,
        enc = "",
        tmp_arr = [];

    if (!data) {
        return data;
    }

    do { // pack three octets into four hexets
        o1 = data.charCodeAt(i++);
        o2 = data.charCodeAt(i++);
        o3 = data.charCodeAt(i++);

        bits = o1 << 16 | o2 << 8 | o3;

        h1 = bits >> 18 & 0x3f;
        h2 = bits >> 12 & 0x3f;
        h3 = bits >> 6 & 0x3f;
        h4 = bits & 0x3f;

        // use hexets to index into b64, and append result to encoded string
        tmp_arr[ac++] = b64.charAt(h1) + b64.charAt(h2) + b64.charAt(h3) + b64.charAt(h4);
    } while (i < data.length);

    enc = tmp_arr.join('');
    
    var r = data.length % 3;
    
    return (r ? enc.slice(0, r - 3) : enc) + '==='.slice(r || 3);

}

/**
 * Decodes data encoded with MIME base64.
 * 
 * @param string data
 * @return string
 * @see http://phpjs.org/functions/base64_decode
 */
function base64_decode (data) {

    var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
    var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
        ac = 0,
        dec = "",
        tmp_arr = [];

    if (!data) {
        return data;
    }

    data += '';

    do { // unpack four hexets into three octets using index points in b64
        h1 = b64.indexOf(data.charAt(i++));
        h2 = b64.indexOf(data.charAt(i++));
        h3 = b64.indexOf(data.charAt(i++));
        h4 = b64.indexOf(data.charAt(i++));

        bits = h1 << 18 | h2 << 12 | h3 << 6 | h4;

        o1 = bits >> 16 & 0xff;
        o2 = bits >> 8 & 0xff;
        o3 = bits & 0xff;

        if (h3 == 64) {
            tmp_arr[ac++] = String.fromCharCode(o1);
        } else if (h4 == 64) {
            tmp_arr[ac++] = String.fromCharCode(o1, o2);
        } else {
            tmp_arr[ac++] = String.fromCharCode(o1, o2, o3);
        }
    } while (i < data.length);

    dec = tmp_arr.join('');

    return dec;
}

/**
 * Implements the listener (observer) design pattern on a given class-prototype.
 * 
 * @param prototype proto
 * @return void
 */
function enableListenerPattern (proto) {
	
	/**
	 * Initialize the listener pattern.
	 */
	proto.initListener = function () {
		this.listeners = [];
	};
	
	/**
	 * Attach a handler to an event for the element.
	 * 
	 * @param string event A string containing an event name
	 * @param function callback
	 * @return this
	 */
	proto.bind = function (event, callback) {
		
		// Add the listener
		this.listeners.push({
			e: event,
			c: callback
		});
		
		// Return the same object itself
		return this;
		
	};
	
	/**
	 * Remove a previously-attached event handler from the element.
	 * 
	 * @param string event A string containing an event name
	 * @param function callback The function that is to be no longer executed.
	 * @return this
	 */
	proto.unbind = function (event, callback) {
		
		// Remove a callback
		// Usage: .unbind(function)
		if (typeof event == "function") {
			this.listeners = this.listeners.filter(function (el) {
				if (el && el.c !== event) {
					return el;
				}
			});
		}
		
		// Remove a callback for a given event
		// Usage: .unbind(string, function)
		if (event && callback) {
			this.listeners = this.listeners.filter(function (el) {
				if (el && !(el.e === event && el.c === callback)) {
					return el;
				}
			});
		}
		
		// Remove all callbacks for a given event
		// Usage: .unbind(string)
		else if (event) {
			this.listeners = this.listeners.filter(function (el) {
				if (el && el.e !== event) {
					return el;
				}
			});
		}
		
		// Return the same object itself
		return this;

	};
	
	/**
	 * Execute all handlers and behaviors attached to the element for the given event name.
	 * 
	 * @param string event A string containing an event name
	 * @param mxied[] data Additional parameters to pass along to the event handler.
	 * @return boolean
	 */
	proto.trigger = function (event, data) {
		
		// Fix invalid data
		data = data instanceof Array ? data : (data ? [data] : []);
		
		// Append event name
		data.push(event);
		
		// Fetch listeners
		this.listeners.every(function (el) {
			if (el.e === event || el.e === '*') {
				// Run callback
				if (el.c.apply(el.c, data) === false) {
					// Stop propagation
					return false;
				}
			}
			// Continue
			return true;
		});

		// Return the same object itself
		return true;
		
	};

}

// Make PoxyBrowser class implements the observer design pattern
enableListenerPattern(PoxyBrowser.prototype);