<?php
$fm_templateControlTypes = array( 'select' => 'fm_templateControlSelect',
										'checkbox' => 'fm_templateControlCheckbox',
										'text' => 'fm_templateControlText'
										);
class fm_templateControlBase{
	
	function getEditor($value, $option){
		$id = $this->getVarId($option);
		return "<input type=\"text\" id=\"".$id."\" name=\"".$id."\" value=\"".$value."\"/>";
	}	
	
	function parseStoredValue($value, $option){
		return $value;
	}
	
	function getVarId($option){
		return 'fm-'.str_replace("\$", "", $option['var']);
	}
	//used for the javascript function that collects values for AJAX save; mostly to accomodate the checkbox type
	function getElementValueAttribute(){ return 'value'; }
}
								
class fm_templateControlSelect extends fm_templateControlBase{
	 function getEditor($value, $option){
	 	$id = $this->getVarId($option);
		$str = "<select  id=\"".$id."\" name=\"".$id."\" >";
		foreach($option['options'] as $k => $v){
			$str.= "<option value=\"".$k."\" ".($k==$value?"selected=\"selected\"":"")."  >".$v."</option>";
		}
		$str.= "</select>";
		return $str;
	 }
}
class fm_templateControlCheckbox extends fm_templateControlBase{
	 function getEditor($value, $option){
	 	$id = $this->getVarId($option);
		return "<input type=\"checkbox\" id=\"".$id."\" name=\"".$id."\" ".($value=="true"||$value=="checked"?"checked=\"checked\"":"")." />";
	 }
	 function parseStoredValue($value, $option){
	 	return ($value == "true");
	 }
	 function getElementValueAttribute(){ return 'checked'; }
}
class fm_templateControlText extends fm_templateControlBase{	 
}
////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////

class fm_template_manager{

////////////////////////////////////////////////////////////////////

var $templatesDir;

var $headers = array('option' => 'option',
				'label' => 'label',
				'description' => 'description',
				'options' => 'options',
				'default' => 'default',
				'template_name' => 'Template Name',
				'template_desc' => 'Template Description',
				'template_type' => 'Template Type'
				);
				
function __construct(){
	$this->templatesDir = dirname(__FILE__).'/templates';
}

function getTemplateAttributes($fileName){
	$file_data = fm_get_file_data($this->templatesDir.'/'.$fileName, $this->headers);
	$templateAtts = array();
	$templateAtts['options'] = array();
	$currentOption = false;
	foreach($file_data as $option){
		switch($option['field']){
			case 'option':
				//closeout the current option if there is one
				if($currentOption !== false) $templateAtts['options'][] = $currentOption;
				
				$currentOption = array();
				$opt = explode(",", $option['value']);
				$currentOption['var'] = trim($opt[0]);
				$currentOption['type'] = trim($opt[1]);
				break;
			case 'label':
				$currentOption['label'] = trim($option['value']);
				break;
			case 'description':
				$currentOption['description'] = trim($option['value']);
				break;
			case 'options':
				eval("\$arr = array(" . trim($option['value']) . ");");
				$currentOption['options'] = $arr;				
				break;
			case 'default':
				$currentOption['default'] = $option['value'];
				break;
			default:
				$templateAtts[$option['field']] = $option['value'];
				break;			
		}	
	}
	
	if($currentOption !== false) $templateAtts['options'][] = $currentOption;
	
	return $templateAtts;
}

function initTemplates(){
	global $fmdb;	
	
	//compare the stored templates with those in the templates directory.  Files that exist in the database but on on disk are re-created on disk; files that exist on disk are all stored in the database.
	$templateFiles = $this->getTemplateList();
	$dbTemplates = $fmdb->getTemplateList();
	
	//load the templates into the database
	foreach($templateFiles as $file => $template){
		$content = file_get_contents($this->templatesDir.'/'.$file);
		$title = $template['template_name'];
		
		$fmdb->storeTemplate($file, $title, $content);		
	}
	
	//replace any 'lost' templates (this is primarily to keep template files across an update)
	foreach($dbTemplates as $dbFile => $dbTemp){
		if(!isset($templateFiles[$dbFile])){
			$templateInfo = $fmdb->getTemplate($dbFile);
			$fp = fopen($this->templatesDir."/".$dbFile, "w");
			fwrite($fp, $templateInfo['content']);
			fclose($fp);
		}
	}	
}

function getTemplateList(){
	$files = $this->getTemplateFiles($this->templatesDir);
	$arr = array();
	foreach($files as $file){
		$arr[$file] = $this->getTemplateAttributes($file);
	}
	return $arr;
}

function getTemplateFilesByType(){
	$templates = $this->getTemplateList();

	$templateList = array();
	$templateList['form'] = array();
	$templateList['email'] = array();
	$templateList['summary'] = array();
	
	foreach($templates as $file=>$temp){
		if(strpos($temp['template_type'], 'form') !== false) $templateList['form'][$file] = $temp['template_name'];
		if(strpos($temp['template_type'], 'email') !== false) $templateList['email'][$file] = $temp['template_name'];
		if(strpos($temp['template_type'], 'summary') !== false) $templateList['summary'][$file] = $temp['template_name'];
	}
	
	return $templateList;	
}

protected function getTemplateFiles($dir){
	if($handle = opendir($dir)){
		$arr = array();
		while(($file = readdir($handle)) !== false){
			if($file != "." && $file != "..")
				$arr[$file] = $file;
		}
		closedir($handle);		
		return $arr;
	}
	return false;
}

function removeTemplate($filename){
	global $fmdb;
	$fullpath = $this->templatesDir.'/'.$filename;
	if(file_exists($fullpath)) unlink($fullpath);
	$fmdb->removeTemplate($filename);
}

////////////////////////////////////////////////////////////////////
}
////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////

function fm_buildTemplateControlTypes($controlTypes){
	$arr = array();
	foreach($controlTypes as $name=>$class){
		$arr[$name] = new $class();
	}
	return $arr;
}
global $fm_template_controls;

$fm_template_controls = fm_buildTemplateControlTypes($fm_templateControlTypes);

?>