
function isInternetExplorer()
{
	if(parseInt(navigator.appVersion)>3 && navigator.appName.indexOf("Microsoft")!=-1)
		return true;
	return false;
}

function isFirefox()
{
	if(parseInt(navigator.appVersion)>3 && navigator.appName=="Netscape")
		return true;
	return false;
}

function getMyWindowWidth()
{
	if(parseInt(navigator.appVersion) > 3)
	{
		if(navigator.appName == "Netscape")
			return window.innerWidth - 16;
		
		if(navigator.appName.indexOf("Microsoft") != -1)
			return document.body.offsetWidth - 20;
	}
}

function getMyWindowHeight()
{
	if(parseInt(navigator.appVersion) > 3)
	{
		if(navigator.appName == "Netscape")
			return window.innerHeight - 16;
		
		if(navigator.appName.indexOf("Microsoft") != -1)
			return document.documentElement.clientHeight;
	}
}

function rgb2hex(rgb)
{
    rgb = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
    
    function hex(x) {
        return ("0" + parseInt(x).toString(16)).slice(-2);
    }
    return "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]);
}

function post_to_url(path, params, method)
{
    method = method || "post"; // Set method to post by default, if not specified.

    // The rest of this code assumes you are not using a library.
    // It can be made less wordy if you use one.
    var form = document.createElement("form");
    form.setAttribute("method", method);
    form.setAttribute("action", path);

    for(var key in params) {
        if(params.hasOwnProperty(key)) {
            var hiddenField = document.createElement("input");
            hiddenField.setAttribute("type", "hidden");
            hiddenField.setAttribute("name", key);
            hiddenField.setAttribute("value", params[key]);

            form.appendChild(hiddenField);
         }
    }

    document.body.appendChild(form);
    form.submit();
}
	
function datetoa2(d)
{
	if(!d) return '';
	
	var table = [
        [' year', 60*60*24*365, 60*60*24*365],
        [' month', 60*60*24*60, 60*60*24*30],
        [' week', 60*60*24*14, 60*60*24*7],
        ['d', 60*60*24*2, 60*60*24],
        ['h', 60*60*2, 60*60],
        ['m', 90, 60],
		['s', 0, 1]];
	
	var e = Math.round(new Date().getTime() / 1000) - d;
	var res = '';
	
	for(var i = 0; i < table.length; i++)
	{
		if(e >= table[i][1])
		{
			res = Math.round(e/table[i][2]) + table[i][0];
			break;
		}
	}

	var date = new Date(d*1000);
	var show = date.format("yyyy-mm-d H:MM");

	if(res=='') res = 'now';
	return "<span title='"+show+"'>"+res+"</span>";
}



