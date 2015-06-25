
function onShowTextEditor(elementid)
{
	$('#'+elementid+'_dialog_div').remove();
	$('body').append(
		'<div id="'+elementid+'_dialog_div" style="padding: 0" />');

	$('#'+elementid+'_dialog_div').dialog(
	{
		title: 'Text Editor',
		autoOpen: false, 
		width: 700, 
		height: 520, 
		minWidth: 560,
		minHeight: 300,
		modal: true,
		
		resize: function(event, ui){textEditorResize(elementid);},
		
		beforeClose: function(event, ui)
		{
			$('#'+elementid).val($('#'+elementid+'_dialog_text').val());
		}
	}).dialogExtend(
	{
		maximize: true,
		dblclick: 'maximize',
		events:
		{
			maximize: function(evt, dlg){textEditorResize(elementid);},
			restore: function(evt, dlg){textEditorResize(elementid);}
		}
	});
	
	$('#'+elementid+'_dialog_div').html(
		'<textarea style="font-family: Courier New;" id="'+elementid+'_dialog_text" name="'+elementid+'_dialog_text"></textarea>');

	$('#'+elementid+'_dialog_div').dialog('open');
	$('#'+elementid+'_dialog_text').val($('#'+elementid).val());
	
	textEditorResize(elementid);
}

function textEditorResize(elementid)
{
	var h = $('#'+elementid+'_dialog_div').parent().height();
	$('#'+elementid+'_dialog_text').css("height", h-37);
	
	var w = $('#'+elementid+'_dialog_div').parent().width();
	$('#'+elementid+'_dialog_text').css("width", w-6);

}


