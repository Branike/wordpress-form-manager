<?php
/* translators: the following are note element settings */

class fm_noteControl extends fm_controlBase{
	public function getTypeName(){ return "note"; }
	
	/* translators: this appears in the 'Add Form Element' menu */
	public function getTypeLabel(){ return __("Note", 'wordpress-form-manager'); }
	
	public function showItem($uniqueName, $itemInfo){ return htmlspecialchars($itemInfo['extra']['content']); }
	
	public function editItem($uniqueName, $itemInfo){ return "<span id=\"{$uniqueName}-edit-value\" >".htmlspecialchars(fm_restrictString($itemInfo['extra']['content'], 25))."</span>"; }
	
	public function getPanelItems($uniqueName, $itemInfo){
		$arr=array();
		$arr[] = new fm_editPanelItemBase($uniqueName, 'label', 'Label', array('value' => $itemInfo['label']));
		$arr[] = new fm_editPanelTextarea($uniqueName, 'content', 'Note', array('value' => $itemInfo['extra']['content'], 'rows'=> 10, 'cols' => 25));
		return $arr;
	}
	
	public function getPanelKeys(){
		return array('label');
	}
	
	public function getShowHideCallbackName(){
		return "fm_note_show_hide";
	}
	
	protected function showExtraScripts(){
		?><script type="text/javascript">
		function fm_note_show_hide(itemID, isDone){
			if(isDone){
				document.getElementById(itemID + '-edit-label').innerHTML = document.getElementById(itemID + '-label').value;
				var noteStr = document.getElementById(itemID + '-content').value.toString();
				if(noteStr.length > 28) noteStr = noteStr.substr(0,25) + "...";
				document.getElementById(itemID + '-edit-value').innerHTML = noteStr;
			}
		}
		</script>
		<?php
	}
	
	public function getPanelScriptOptions(){
		$opt = $this->getPanelScriptOptionDefaults();		
		$opt['extra'] = $this->extraScriptHelper(array('content'=>'content'));		
		
		return $opt;
	}
	public function itemDefaults(){
		$itemInfo = array();
		$itemInfo['label'] = __("New Note", 'wordpress-form-manager');
		$itemInfo['description'] = __("Item Description", 'wordpress-form-manager');
		$itemInfo['extra'] = array('content'=>'');
		$itemInfo['nickname'] = '';
		$itemInfo['required'] = 0;
		$itemInfo['validator'] = "";
		$ItemInfo['validation_msg'] = "";
		$itemInfo['db_type'] = "NONE";
		
		return $itemInfo;
	}
}
?>