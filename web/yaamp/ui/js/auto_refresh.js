
var auto_delay = 60000;
var auto_max_time = 3600000;
var auto_start_time = new Date().getTime();

$(function()
{
	auto_page_refresh();
});

function auto_page_resume()
{
	auto_start_time = new Date().getTime();
	$('#resume_update_button').hide();

	auto_page_refresh();
}

function auto_page_refresh()
{
	page_refresh();
	
	var now_time = new Date().getTime();
	if(now_time > auto_start_time + auto_max_time)
	{
		$('#resume_update_button').show();
		document.title = 'yaamp.com';
	}
	
	else
		setTimeout(auto_page_refresh, auto_delay);
}

