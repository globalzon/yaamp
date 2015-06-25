<?php
/**
 * Uni-Form widget to add needed css and javascript files on page
 * 
 * @author Alexander Hramov
 * @link http://www.hramov.info
 * @version 0.1
 */
class UniForm extends CWidget
{
	public function init()
	{
		echo CHtml::cssFile('/yaamp/ui/css/uni-form.css');
	//	echo CHtml::scriptFile('sansspace/ui/js/uni-form.jquery.js');
	}
	
	public function run()
	{
		CHtml::$requiredCss = '';
		CHtml::$afterRequiredLabel='';
		CHtml::$beforeRequiredLabel='<em>*</em> '; 
		CHtml::$errorSummaryCss = 'errorMsg';
	}
}

