(function () {
	var iframe = document.getElementById('campsflow-registration-iframe');
	if (!iframe) {
		return;
	}

	var allowedOrigin = (typeof CampsflowRegistration !== 'undefined' && CampsflowRegistration.iframeOrigin)
		? CampsflowRegistration.iframeOrigin
		: null;

	window.addEventListener('message', function (event) {
		if (!allowedOrigin || event.origin !== allowedOrigin) {
			return;
		}
		if (!event.data || event.data.type !== 'CF_RESIZE') {
			return;
		}
		var height = parseInt(event.data.height, 10);
		if (isNaN(height) || height < 0) {
			return;
		}
		iframe.style.height = height + 'px';
	});
}());
