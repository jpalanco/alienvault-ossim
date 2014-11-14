// -------------------------------------------------------------------
// markItUp!
// -------------------------------------------------------------------
// Copyright (C) 2008 Jay Salvat
// http://markitup.jaysalvat.com/
// -------------------------------------------------------------------
// Mediawiki Wiki tags example
// -------------------------------------------------------------------
// Feel free to add more tags
// -------------------------------------------------------------------

<?php 
	require_once 'av_init.php';
	
	$sintax = new KDB_Sintax();
	
	
	//Functions List
	$funcs     = array_keys($sintax->_operations_elements);
	$funcs_txt = '';
	$funcs_aux = array();
	$i         = 1;
	foreach($funcs as $f)
	{
		$funcs_aux[] = "{name:'$f',   openWith:'$f', className:'col1-$i' }";
		$i++;
	}
	$funcs_txt = implode(',', $funcs_aux);
	
	
	//Vars List
	$vars     = array_keys($sintax->_variable_list);
	$vars_txt = '';
	$vars_aux = array();
	$i        = 1;
	foreach($vars as $v)
	{
		$vars_aux[] = "{name:'$v',   openWith:'$v', className:'col1-$i' }";
		$i++;
	}
	$vars_txt = implode(',', $vars_aux);
	
?>

mySettings = {
	previewParserPath:	'../js/markitup/wikiparser.php', // path to your Wiki parser
	previewInWindow: 'width=800, height=600, resizable=yes, scrollbars=yes',
	markupSet: [
		{name:'Heading 1', key:'1', openWith:'== ', closeWith:' ==', placeHolder:'Your title here...' },
		{name:'Heading 2', key:'2', openWith:'=== ', closeWith:' ===', placeHolder:'Your title here...' },
		{name:'Heading 3', key:'3', openWith:'==== ', closeWith:' ====', placeHolder:'Your title here...' },
		{separator:'---------------' },		
		{name:'Bold', key:'B', openWith:"'''", closeWith:"'''"}, 
		{name:'Italic', key:'I', openWith:"''", closeWith:"''"}, 
		{name:'Stroke through', key:'S', openWith:'<strike> ', closeWith:' </strike>'}, 
		{separator:'---------------' },
		{name:'Bulleted list', openWith:'(!(* |!|*)!)'}, 
		{name:'Numeric list', openWith:'(!(# |!|#)!)'}, 
		{separator:'---------------' },
		{name:'Quotes', openWith:'(!(> |!|>)!)', placeHolder:''},
		{name:'Code', openWith:'<pre> ', closeWith:' </pre>'}, 
		{separator:'---------------' },
		{name:'Conditions', className:'conds', dropMenu: [
            {name:'IF',   openWith:"{ IF }\n", closeWith:"\n{ ENDIF }\n",       className:"col1-1" },
            {name:'ELSE',   openWith:"{ ELSE }\n", closeWith:"\n{ ENDELSE }\n", className:"col2-1" },
            ]
        },
		{name:'Variables', className:'vars', dropMenu: [
			<?php echo $vars_txt ?>            
            ]
        },
		{name:'Functions', className:'funcs', dropMenu: [
			<?php echo $funcs_txt ?>            
            ]
        },		
		{separator:'---------------' }, 
		{name:'Preview', call:'preview', className:'preview'}
	]
}