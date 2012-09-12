<?php
/********************************************************************

   EasyMenu
   ========
   
   Easy to use popup-bar library.
   
   Version: 1.0
   Date 20040831
   
   easymenu.inc

Copyright (C) 2004  Joan Miquel Torres Rigo

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

********************************************************************/

/*
 * Requirements & instructions:
 * ===========================
 * 
 * Use  easymenu class to declare menu object variables, define the menu options
 * for each menu whith the option() method (different popups are automatically
 * created as needed every time you use new menu name).
 *
 * You can optionally pass title text to the constructor in declaration and
 * a second parameter which if is true, the menu bar will be fixed instead of
 * threated as text.
 *
 * Example:
 *
 * $myMenu = new easymenu("This is the title", true);
 *
 * For now there is no way to specify the menu coordinates or width (width is
 * always 100%). For this reason you probably don't want to declare more than 
 * one fixed menus because they will fit in the same place. If you need to do
 * that you can improve this library and send me your patch. For now I don't
 * need this feature and then I didn't implemented.
 *
 * Finally, render menu bar with html() method (this doesn't output anything. Code is
 * is returned as string. Use 'echo' stament to insert it to your html document.
 *
 * EasyMenu needs a few css2 and javascript code to work. This code is automatically
 * included in the html() method output, but you can previously get it separated by
 * the css() and js() methods. If you do so this code will not be returned in the
 * html() call.
 *
 * Also you must call easyinit() function from your onLoad() body function to proper
 * initialize EasyMenu popups.
 *
 * NOTE that if you use more than one easymenu instance, you must completely define 
 * all your menus before calling js() (or html() instead) method of any one because
 * javascript code is common for all and js() need to know about all existent menus
 * to correctly build easyinit() javascript function.
 *
 * See easydemo.php source code to better understand how it works.
 *
 */

define (EASY_PREFFIX, 'EasyMenu');

class easymenu {

	var $appname = '';
	var $anchor; // position css2 atribute for the menubar.
	var $popup = array();
	var $pid;
	var $submenus = array();
	var $html_code, $jscode;
	var $rendered = false;
	var $jsout = false;
	var $cssout = false;
	var $menuid;
	var $pcount = 0;
	var $def_target; // Default target for menu option links.

	// Colors:
	var $background				= '#D0D0D0';
	var $foreground				= '#6E5579';
	var $popup_title_bg			= '#E0E0E0';
	var $popup_title_fg			= '#010101';
	var $unselected_item_bg		= '#eeeeee';
	var $unselected_item_fg		= '#333333';
	var $selected_item_bg		= '#333333';
	var $selected_item_fg		= '#eeeeee';
	var $title_item_bg			= '#cccccc';
	var $title_item_fg			= '#333333';
	var $popup_shadow				= '#999999';
	// Changing this default values if you want
	// must be done before rendering the easymenu object.

	// Dimensions:
	var $menu_height = '1em';
	var $text_size = '1em';
	
	function css_render() {/*{{{*/
		$out = '';
		// Screen:
		$out .= "<style type=\"text/css\" media=\"screen\">\n";
		// Container bar:
		$out .= "#$this->menuid {width:100%; position:$this->anchor; height:{$this->menu_height};background-color:$this->background;top:0px;padding-bottom:3px;font-size:{$this->text_size};}\n";
		// Title area:
		$out .= "#$this->menuid .EMmenubar_title {text-align:right; background-color:none; color:$this->foreground;}\n";
		// Whole popup box:
		$out .= "#$this->menuid .EMpopup {position:relative; top:0px; float:left; height:{$this->menu_height}; margin-left:2px;}\n";
		// Popup title:
		$out .= "#$this->menuid .EMpopup_title {background-color:$this->popup_title_bg; color:$this->popup_title_fg;font-weight:bold;}\n";
		// Popup body:
		$out .= "#$this->menuid .EMpopup_body {display:block;border-right:$this->popup_shadow solid 2px;border-bottom:$this->popup_shadow solid 2px;z-index:10;position:relative}\n";
		// Popup option entry:
		$out .= "#$this->menuid .EMpopup_body a div {background-color:$this->unselected_item_bg; color:$this->unselected_item_fg; width:100% height:2em;}\n";
		$out .= "#$this->menuid .EMpopup_body a:hover div {background-color:$this->selected_item_bg; color:$this->selected_item_fg; text-decoration:none;}\n";
		$out .= "#$this->menuid * a:hover,link,visited * {text-decoration:none}\n";
		// Popup separator entry:
		$out .= "#$this->menuid .EMentry_sep {height:1px; background-color:$this->unselected_item_fg}\n";
		// Popup title entry:
		$out .= "#$this->menuid .EMentry_tit {height:{$this->menu_height}; background-color:$this->title_item_bg; color:$this->title_item_fg;}\n";
		$out .= "</style>\n";
		
		// Print:
		$out .= "<style type=\"text/css\" media=\"print\">\n";
			$out .= "#$this->menuid, #$this->menuid * {display:none;}\n";
		$out .= "</style>\n";
		
		return $out;
	}/*}}}*/
	
	function js_render() {/*{{{*/
		
		$out = "<script language=\"JavaScript\">\n";
		$out .= "<!--\n";
		$out .= "function easyhide(id) {\n";
		$out .= "	if (document.layers) {\n";
		$out .= "		document.layers[id].visibility = 'hide';\n";
		$out .= "	} else if (document.all) {\n";
		$out .= "		document.all[id].style.visibility = 'hidden';\n";
		$out .= "	} else if (document.getElementById) {\n";
		$out .= "		document.getElementById(id).style.visibility = 'hidden';\n";
		$out .= "	}\n";
		$out .= "}\n";
		
		$out .= "function easyunhide(id) {\n";
		$out .= "	if (document.currenteasymenuid) {\n";
		$out .= "		easyhide(document.currenteasymenuid);\n";
		$out .= "	}\n";
		$out .= "	document.currenteasymenuid = id;\n";
		$out .= "	if (document.layers) {\n";
		$out .= "		document.layers[id].visibility = 'show';\n";
		$out .= "	} else if (document.all) {\n";
		$out .= "		document.all[id].style.visibility = 'visible';\n";
		$out .= "	} else if (document.getElementById) {\n";
		$out .= "		document.getElementById(id).style.visibility = 'visible';\n";
		$out .= "	}\n";
		$out .= "}\n";
		
		$out .= "function easyinvert(id) {\n";
		$out .= "	if (document.currenteasymenuid == id) {\n";
		$out .= "		easyhide(id);\n";
		$out .= "	} else {\n";
		$out .= "		easyunhide(id);\n";
		$out .= "	}\n";
		$out .= "}\n";
		
		$out .= "function easyinit() {\n";
		for($i = 0; $i < $this->pcount; $i++) {
			$popid = sprintf(EASY_PREFFIX . "_" . "%02u", $i);
			$out .= "easyhide('$popid');\n";
		};
		$out .= "}\n";
		$out .= "-->\n";
		$out .= "</script>\n";
		
		return $out;
	}/*}}}*/
	
	function popup_render($title, $options) {/*{{{*/
			$popid = $this->pid[$title];
			$out = "<div class=\"EMpopup\"\n";
			$out .= " onMouseOver=\"easyunhide('$popid');\"";
			$out .= " onMouseOut=\"easyhide('$popid');\"";
///			$out .= " onClick=\"easyinvert('$popid')\"";
			$out .= ">\n";
			$out .= "	<a";
			if (strlen ($options['url'])) {
				$out .= " href=\"{$options['url']}\"";
				$options['options'] && $out .= " " . $options['options'];
				if (
					false !== $this->def_target
					&& ! preg_match ('/\btarget\s*=/i', $options['options'])
				) {
					$out .= " target=\"{$this->def_target}\"";
				};
			};
			$out .= ">\n";
			$out .= "	<div class=\"EMpopup_title\">$title&nbsp;&nbsp;</div>\n";
			$out .= "	</a>\n";
			
			// Popup body:
			$out .= "	<div id=\"$popid\" class=\"EMpopup_body\">\n";
			foreach (
				$options
				as $i => $entry
			) if (
				is_numeric ($i) // Ignore popup parameters.
			) {
				switch($entry['type']) {
				case 'option':
					if (is_array ($entry['options'])) {
						extract ($entry['options'], EXTR_PREFIX_ALL, 'opt');
					} else {
						$opt_anchor = $entry['options'];
					};
					$out .= "	<a ";
					$js = '';
					@ list ($prot, $data) = explode (':', $entry['url'], 2);
					if (strtolower(trim($prot)) == 'javascript') {
						$js .= $data;
					} else {
						$out .= "href=\"" . $entry['url'] . "\"";
					};
					$out .= " onClick=\"easyhide('$popid');{$js}\"";
					@ $opt_anchor && $out .= " " . $opt_anchor;
					if (
						false !== $this->def_target
						&& ! preg_match ('/\btarget\s*=/i', $entry['options'])
					) {
						$out .= " target=\"{$this->def_target}\"";
					};
					$out .= "><div";
					@ $opt_div && $out .= " " . $opt_div;
					$out .= ">" . $entry['name'] . "&nbsp;</div></a>\n";
					break;
				case 'title':
					$out .= "<div class=\"EMentry_tit\">" . $entry['name'] . "</div>\n";
					break;
				case 'separator':
					$out .= "<div class=\"EMentry_sep\"></div>\n";
					break;
				case 'submenu':
					echo "EASYmenu ERROR: Submenus not implemented yet.<br>\n";
					///$out .= $this->popup_render($entry['name'], $this->popup[$entry['url']]);
				default:
				};
			};
			$out .= "	</div>\n";
			
			$out .= "</div>\n";
			
			return $out;
	}/*}}}*/

	function html_render() {/*{{{*/
		
		$out = "<div id=\"$this->menuid\">\n";
		foreach($this->popup as $title => $options) {
			if (!$this->submenus[$title]) {
				$out .= $this->popup_render($title, $options);
			};
		};
		$out .= "<div class=\"EMmenubar_title\">";
		$out .= $this->appname;
		$out .= "</div>\n";
		$out .= "</div>\n";
		return $out;
	}/*}}}*/
	
	function render() {/*{{{*/
		if (! $this->rendered) {
			$this->html_code = $this->html_render();
			$this->js_code = $this->js_render();
			$this->css_code = $this->css_render();
			$this->rendered = true;
		};
	}/*}}}*/


	// Public functions:
	// ================
	
	function easymenu( // Constructor. /*{{{*/
		$appname = '',
		$fixed_anchor = false,
		$default_target = false,
		$menuid = false
	) {
	
		$this->anchor = $fixed_anchor ? 'fixed' : 'inherit';
		$this->menuid = $menuid ? $menuid : EASY_PREFFIX . rand(); // Not tecnically secure but acceptable unique identifier.
		$this->appname = $appname;
		$this->def_target = $default_target;
		
		if (! isset($GLOBALS['EasyMenu_GLOBAL_DATA'])) {
			$GLOBALS['EasyMenu_GLOBAL_DATA'] = array();
			
			$this->globaldata =& $GLOBALS['EasyMenu_GLOBAL_DATA'];
		
			// Unique JavaScript code rendering for all EasyMenus.
			$this->globaldata['jsout'] = $this->jsout;
			
			// Unique popup count for ids:
			$this->globaldata['pcount'] = $this->pcount;
			
		} else {
			$this->globaldata =& $GLOBALS['EasyMenu_GLOBAL_DATA'];
		};
		
		$this->jsout =& $this->globaldata['jsout'];
		$this->pcount =& $this->globaldata['pcount'];
		
		

	}/*}}}*/
	
	function option($pname, $oname = '', $url = '', $options = '') { // Menu builder:/*{{{*/
		$pname = htmlentities($pname);
		
		if (
			is_null ($oname) // null $oname => Menu title url
		) {
			$this->popup[$pname]['url'] = $url;
			$this->popup[$pname]['options'] = $options;
			return;
		};

		$otype = $oname ? ($url ? (($url[0] == '@') ? 'submenu' : 'option') :'title') : 'separator';
		$oname = htmlentities($oname);
		if ($url[0] == '@') { // Strip @ mark.
			$url = substr($url, 1, strlen($url) - 1);
		};
		$url = htmlentities($url);
		
		$this->popup[$pname][] = array (
			'name'		=> $oname,
			'type'		=> $otype,
			'url'		=> $url,
			'options'	=> $options
		);
		
		// Popup id assignment.
		if (! isset($this->pid[$pname])) {
			$this->pid[$pname] = sprintf(EASY_PREFFIX . "_" . "%02u", $this->pcount++);
		};
		
		// '@menuname' urls are reserved for future implementation of submenus:
		if ($otype == 'submenu') $this->submenus[$url] = 1; // Mark as a submenu.
	}/*}}}*/
	
	function css() { // CSS code getting method:/*{{{*/
		if (!$this->rendered) $this->render();
		$this->cssout = true;
		return $this->css_code;
	}/*}}}*/
	
	function js() { // JavaScript code getting method:/*{{{*/
		if (!$this->rendered) $this->render();
		$this->jsout = true;
		return $this->js_code;
	}/*}}}*/
	
	function html() { // Html code getting method:/*{{{*/
		$out = '';

		if (!$this->jsout) $out .= $this->js();
		if (!$this->cssout) $out .= $this->css();
		// Now oject is yet rendered.
		$out .= $this->html_code;
		return $out;
	}/*}}}*/
	
}
?>
