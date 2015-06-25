#yaamp

Add your exchange API keys in:

	web/yaamp/core/exchange/*

You need three backend shells (in screen) running these scripts:

	web/main.sh
	web/loop2.sh
	web/block.sh
	
Start one stratum per algo using the run.sh script in the config folder, where the x11.conf is located as in:

	stratumd x11


More instructions comming as needed.

