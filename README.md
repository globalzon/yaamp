#yaamp

Add your exchange API keys in:

	web/yaamp/core/exchange/*

You need three backend shells (in screen) running these scripts:

	web/main.sh
	web/loop2.sh
	web/block.sh
	
Start one stratum per algo using the run.sh script in the config folder, where the x11.conf is located as in:

	stratumd x11

Your coin's config file needs to blocknotify their corresponding stratum using something like:

	blocknotify=/root/bin/blocknotify yaamp.com:port coinid %s


More instructions comming as needed.

