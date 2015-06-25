
<a href='/site/common'>Summary</a>&nbsp;
<a href='/site/admin'>Coins</a>&nbsp;
<a href='/site/user?symbol=BTC'>Users</a>&nbsp;
<a href='/site/worker'>Workers</a>&nbsp;
<a href='/site/version'>Version</a>&nbsp;
<a href='/site/earning'>Earnings</a>&nbsp;
<a href='/site/payments'>Payments</a>&nbsp;

<?php

echo "<a href='/site/coin?id=$coin->id'>$coin->name</a><br>";

//include "current.php";
$this->widget('UniForm');

echo CUFHtml::beginForm();
echo CUFHtml::errorSummary($coin);
echo CUFHtml::openTag('fieldset', array('class'=>'inlineLabels'));

InitMenuTabs('#tabs');

echo "<div id='tabs' '><ul>";
echo "<li><a href='#tabs-1'>General</a></li>";
echo "<li><a href='#tabs-2'>Status</a></li>";
echo "<li><a href='#tabs-4'>Program</a></li>";
echo "</ul><br>";

echo "<div id='tabs-1'>";

echo CUFHtml::openActiveCtrlHolder($coin, 'name');
echo CUFHtml::activeLabelEx($coin, 'name');
echo CUFHtml::activeTextField($coin, 'name', array('maxlength'=>200));
echo "<p class='formHint2'>.</p>";
echo CUFHtml::closeCtrlHolder();

echo CUFHtml::openActiveCtrlHolder($coin, 'symbol');
echo CUFHtml::activeLabelEx($coin, 'symbol');
echo CUFHtml::activeTextField($coin, 'symbol', array('maxlength'=>200));
echo "<p class='formHint2'>.</p>";
echo CUFHtml::closeCtrlHolder();

echo CUFHtml::openActiveCtrlHolder($coin, 'symbol2');
echo CUFHtml::activeLabelEx($coin, 'symbol2');
echo CUFHtml::activeTextField($coin, 'symbol2', array('maxlength'=>200));
echo "<p class='formHint2'>.</p>";
echo CUFHtml::closeCtrlHolder();

echo CUFHtml::openActiveCtrlHolder($coin, 'algo');
echo CUFHtml::activeLabelEx($coin, 'algo');
echo CUFHtml::activeTextField($coin, 'algo', array('maxlength'=>200));
echo "<p class='formHint2'>.</p>";
echo CUFHtml::closeCtrlHolder();

echo CUFHtml::openActiveCtrlHolder($coin, 'image');
echo CUFHtml::activeLabelEx($coin, 'image');
echo CUFHtml::activeTextField($coin, 'image', array('maxlength'=>200));
echo "<p class='formHint2'>.</p>";
echo CUFHtml::closeCtrlHolder();

echo CUFHtml::openActiveCtrlHolder($coin, 'reward');
echo CUFHtml::activeLabelEx($coin, 'reward');
echo CUFHtml::activeTextField($coin, 'reward', array('maxlength'=>200));
echo "<p class='formHint2'>.</p>";
echo CUFHtml::closeCtrlHolder();

echo CUFHtml::openActiveCtrlHolder($coin, 'reward_mul');
echo CUFHtml::activeLabelEx($coin, 'reward_mul');
echo CUFHtml::activeTextField($coin, 'reward_mul', array('maxlength'=>200));
echo "<p class='formHint2'>.</p>";
echo CUFHtml::closeCtrlHolder();

echo CUFHtml::openActiveCtrlHolder($coin, 'block_height');
echo CUFHtml::activeLabelEx($coin, 'block_height');
echo CUFHtml::activeTextField($coin, 'block_height', array('maxlength'=>200));
echo "<p class='formHint2'>.</p>";
echo CUFHtml::closeCtrlHolder();

echo CUFHtml::openActiveCtrlHolder($coin, 'target_height');
echo CUFHtml::activeLabelEx($coin, 'target_height');
echo CUFHtml::activeTextField($coin, 'target_height', array('maxlength'=>200));
echo "<p class='formHint2'>.</p>";
echo CUFHtml::closeCtrlHolder();

echo "</div>";

//////////////////////////////////////////////////////////////////////////////////////////

echo "<div id='tabs-2'>";

echo CUFHtml::openActiveCtrlHolder($coin, 'enable');
echo CUFHtml::activeLabelEx($coin, 'enable');
echo CUFHtml::activeCheckBox($coin, 'enable');
echo "<p class='formHint2'>.</p>";
echo CUFHtml::closeCtrlHolder();

echo CUFHtml::openActiveCtrlHolder($coin, 'auto_ready');
echo CUFHtml::activeLabelEx($coin, 'auto_ready');
echo CUFHtml::activeCheckBox($coin, 'auto_ready');
echo "<p class='formHint2'>.</p>";
echo CUFHtml::closeCtrlHolder();

echo CUFHtml::openActiveCtrlHolder($coin, 'visible');
echo CUFHtml::activeLabelEx($coin, 'visible');
echo CUFHtml::activeCheckBox($coin, 'visible');
echo "<p class='formHint2'>.</p>";
echo CUFHtml::closeCtrlHolder();

echo CUFHtml::openActiveCtrlHolder($coin, 'installed');
echo CUFHtml::activeLabelEx($coin, 'installed');
echo CUFHtml::activeCheckBox($coin, 'installed');
echo "<p class='formHint2'>.</p>";
echo CUFHtml::closeCtrlHolder();

echo CUFHtml::openActiveCtrlHolder($coin, 'auxpow');
echo CUFHtml::activeLabelEx($coin, 'auxpow');
echo CUFHtml::activeCheckBox($coin, 'auxpow');
echo "<p class='formHint2'>.</p>";
echo CUFHtml::closeCtrlHolder();

// echo CUFHtml::openActiveCtrlHolder($coin, 'index_avg');
// echo CUFHtml::activeLabelEx($coin, 'index_avg');
// echo CUFHtml::activeTextField($coin, 'index_avg', array('maxlength'=>200));
// echo "<p class='formHint2'>.</p>";
// echo CUFHtml::closeCtrlHolder();

echo CUFHtml::openActiveCtrlHolder($coin, 'dontsell');
echo CUFHtml::activeLabelEx($coin, 'dontsell');
echo CUFHtml::activeCheckBox($coin, 'dontsell');
echo "<p class='formHint2'>.</p>";
echo CUFHtml::closeCtrlHolder();

echo CUFHtml::openActiveCtrlHolder($coin, 'sellonbid');
echo CUFHtml::activeLabelEx($coin, 'sellonbid');
echo CUFHtml::activeCheckBox($coin, 'sellonbid');
echo "<p class='formHint2'>.</p>";
echo CUFHtml::closeCtrlHolder();

echo CUFHtml::openActiveCtrlHolder($coin, 'txfee');
echo CUFHtml::activeLabelEx($coin, 'txfee');
echo CUFHtml::activeTextField($coin, 'txfee', array('maxlength'=>200));
echo "<p class='formHint2'>.</p>";
echo CUFHtml::closeCtrlHolder();

echo CUFHtml::openActiveCtrlHolder($coin, 'master_wallet');
echo CUFHtml::activeLabelEx($coin, 'master_wallet');
echo CUFHtml::activeTextField($coin, 'master_wallet', array('maxlength'=>200));
echo "<p class='formHint2'>.</p>";
echo CUFHtml::closeCtrlHolder();

echo CUFHtml::openActiveCtrlHolder($coin, 'errors');
echo CUFHtml::activeLabelEx($coin, 'errors');
echo CUFHtml::activeTextField($coin, 'errors', array('maxlength'=>200));
echo "<p class='formHint2'>.</p>";
echo CUFHtml::closeCtrlHolder();

echo "</div>";

//////////////////////////////////////////////////////////////////////////////////////////

echo "<div id='tabs-4'>";

// echo CUFHtml::openActiveCtrlHolder($coin, 'program');
// echo CUFHtml::activeLabelEx($coin, 'program');
// echo CUFHtml::activeTextField($coin, 'program', array('maxlength'=>200));
// echo "<p class='formHint2'>.</p>";
// echo CUFHtml::closeCtrlHolder();

// echo CUFHtml::openActiveCtrlHolder($coin, 'conf_folder');
// echo CUFHtml::activeLabelEx($coin, 'conf_folder');
// echo CUFHtml::activeTextField($coin, 'conf_folder', array('maxlength'=>200));
// echo "<p class='formHint2'>.</p>";
// echo CUFHtml::closeCtrlHolder();

echo CUFHtml::openActiveCtrlHolder($coin, 'rpchost');
echo CUFHtml::activeLabelEx($coin, 'rpchost');
echo CUFHtml::activeTextField($coin, 'rpchost', array('maxlength'=>200));
echo "<p class='formHint2'>.</p>";
echo CUFHtml::closeCtrlHolder();

if(empty($coin->rpcport))
	$coin->rpcport = $coin->id*10;

echo CUFHtml::openActiveCtrlHolder($coin, 'rpcport');
echo CUFHtml::activeLabelEx($coin, 'rpcport');
echo CUFHtml::activeTextField($coin, 'rpcport', array('maxlength'=>200));
echo "<p class='formHint2'>.</p>";
echo CUFHtml::closeCtrlHolder();

if(empty($coin->rpcuser))
	$coin->rpcuser = 'yaamprpc';
	
echo CUFHtml::openActiveCtrlHolder($coin, 'rpcuser');
echo CUFHtml::activeLabelEx($coin, 'rpcuser');
echo CUFHtml::activeTextField($coin, 'rpcuser', array('maxlength'=>200));
echo "<p class='formHint2'>.</p>";
echo CUFHtml::closeCtrlHolder();

if(empty($coin->rpcpasswd))
	$coin->rpcpasswd = 'e7ec005fhdsj3k6po6afbb84545eb24';
	
echo CUFHtml::openActiveCtrlHolder($coin, 'rpcpasswd');
echo CUFHtml::activeLabelEx($coin, 'rpcpasswd');
echo CUFHtml::activeTextField($coin, 'rpcpasswd', array('maxlength'=>200));
echo "<p class='formHint2'>.</p>";
echo CUFHtml::closeCtrlHolder();

echo CUFHtml::openActiveCtrlHolder($coin, 'hassubmitblock');
echo CUFHtml::activeLabelEx($coin, 'hassubmitblock');
echo CUFHtml::activeCheckBox($coin, 'hassubmitblock');
echo "<p class='formHint2'>.</p>";
echo CUFHtml::closeCtrlHolder();

echo CUFHtml::openActiveCtrlHolder($coin, 'txmessage');
echo CUFHtml::activeLabelEx($coin, 'txmessage');
echo CUFHtml::activeCheckBox($coin, 'txmessage');
echo "<p class='formHint2'>.</p>";
echo CUFHtml::closeCtrlHolder();

echo "</div>";

echo "</div>";

echo CUFHtml::closeTag('fieldset');
showSubmitButton($update? 'Save': 'Create');
echo CUFHtml::endForm();



