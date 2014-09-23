<?php 
$v_id = array();
$v_form_steps = 0;
function v_quickform($titel,$elements,$option=array())
{
	return v_field('<div class="v-form">'.v_form($titel,$elements,$option).'</div>',$titel);
}

function addMapApi()
{
	addHead('
		<script src="http://www.google.com/jsapi"></script>
		<script type="text/javascript" src="http://google-maps-utility-library-v3.googlecode.com/svn/tags/markerclusterer/1.0/src/markerclusterer.js"></script>
		<script type="text/javascript">
			google.load(\'maps\', \'3\', {
				other_params: \'sensor=false\'
			});
		</script>
	');
}

function v_scroller($content,$width='232')
{
	if(isMob())
	{
		return $content;
	}
	else
	{
		$id = id('scroller');
		addJs('$("#'.$id.'").tinyscrollbar();');
	
		return '
			<div id="'.$id.'" class="scroller">
			    <div class="scrollbar"><div class="track"><div class="thumb"><div class="end"></div></div></div></div>
			    <div class="viewport">
		        	<div class="overview" style="width:'.$width.'px">
						'.$content.'
			        </div>
			    </div>
			</div>';
	}
}

function v_activeSwitcher($table,$field_id,$active)
{
	$id = id('activeSwitch');
	
	/*
	addJs('
		$("#'.$id.'").buttonset().change(function(){			
			showLoader();
			$.ajax({
				url: "xhr.php?f=activeSwitch",
				data:{t:"'.$table.'",id:"'.$field_id.'",value:$("#'.$id.' input:checked").attr("value")},
				method:"get",
				complete:function(){
					hideLoader();
				}
			});
		});		
	');
	*/
	
	addJs('
		$("#'.$id.' input").switchButton({
			labels_placement: "right",
			on_label: "'.s('on_label').'",
			off_label: "'.s('off_label').'",
			on_callback: function(){
				showLoader();
				$.ajax({
					url: "xhr.php?f=activeSwitch",
					data:{t:"'.$table.'",id:"'.$field_id.'",value:1},
					method:"get",
					complete:function(){
						hideLoader();
					}
				});
			},
            off_callback:function(){
				showLoader();
				$.ajax({
					url: "xhr.php?f=activeSwitch",
					data:{t:"'.$table.'",id:"'.$field_id.'",value:0},
					method:"get",
					complete:function(){
						hideLoader();
					}
				});
			}
		});
	');
	
	$onck = ' checked="checked"';
	if($active == 0)
	{
		$onck = '';
	}
	
	return '
			<div id="'.$id.'">
				<input'.$onck.' type="checkbox" name="'.$id.'" id="'.$id.'-on" value="1" />
			</div>';
	
}

function v_bezirkChildChooser($id,$options = array())
{
	global $db;

	addJsFunc('
	var u_current_bezirk_type = 0;
	function u_printChildBezirke(element)
	{
			val = element.value + "";
			
			part = val.split(":");
			
			parent = part[0];
			
			u_current_bezirk_type = part[1];
			
			if(parent == -1)
			{
				$("#'.$id.'").val("");
				return false;
			}

			if(parent == -2)
			{
				$("#'.$id.'-notAvail").fadeIn();
			}
			
			
			$("#'.$id.'").val(element.value);
			
			el = $(element);
		
			if(el.next().next().next().next().next().hasClass("childChanger"))
			{
				el.next().next().next().next().next().remove();
			}
			if(el.next().next().next().next().hasClass("childChanger"))
			{
				el.next().next().next().next().remove();
			}
			if(el.next().next().next().hasClass("childChanger"))
			{
				el.next().next().next().remove();
			}
			if(el.next().next().hasClass("childChanger"))
			{
				el.next().next().remove();
			}
			if(el.next().hasClass("childChanger"))
			{
				el.next().remove();
			}
		
			$("#xv-childbezirk-"+parent).remove();
			
			
			showLoader();
			$.ajax({
					dataType:"json",
					url:"xhr.php?f=childBezirke&parent=" + parent,
					success : function(data){
						if(data.status == 1)
						{
							
							$("#'.$id.'-childs-"+parent).remove();
							$("#'.$id.'-wrapper").append(data.html);
							//$("#'.$id.'").val("");
							
							//$("select.childChanger").last().append(\'<option style="font-weight:bold;" value="-2">- Meine Region ist nicht dabei -</option>\');
							
						}
						else
						{
							
						}
					},
					complete: function(){
						hideLoader();
					}
			});
	}');

	addJs('u_printChildBezirke({value:"0:0"});');

	return '<div id="'.$id.'-wrapper"></div><input type="hidden" name="'.$id.'" id="'.$id.'" value="0" />';
}

function v_clustermap($id,$option = array())
{
	$id = id($id);
	
	//addScript('js/data.json');
	//addScript('http://maps.google.com/maps/api/js?sensor=false');
	
	addHead('
		<script src="http://www.google.com/jsapi"></script>
		<script src="xhr.php?f=jsonBoth" type="text/javascript"></script>
		<script type="text/javascript" src="http://google-maps-utility-library-v3.googlecode.com/svn/tags/markerclusterer/1.0/src/markerclusterer.js"></script>
		<script type="text/javascript">
			google.load(\'maps\', \'3\', {
				other_params: \'sensor=false\'
			});
		</script>
	');
	
	if(isset($option['latLng']) && !empty($option['latLng']['lat']))
	{
		$zoom = 13;
		$lat = $option['latLng']['lat'];
		$lon = $option['latLng']['lon'];
	}
	else
	{
		$zoom = 6;
		$lat = '51.303145';
		$lon = '10.235595';
	}
	
	$center_marker = '';
	if(isset($option['center']))
	{
		if(!empty($option['center']['lat']))
		{
			$lat = $option['center']['lat'];
			$lon = $option['center']['lon'];
		}
		
		$zoom = 15;
		
		$img = '';
		if(!empty($option['center']['kette']['logo']))
		{
			$img = '<a href="?page=betrieb&id='.(int)$option['center']['id'].'"><img style="float:right;margin-left:10px;" src="'.idimg($option['center']['kette']['logo'],100).'" /></a>';
		}
		
		$center_marker = '
		i++;
		var latLng = new google.maps.LatLng('.$lat.','.$lon.');
			  '.$id.'_markers[i] = new google.maps.Marker({
				\'position\': latLng,
				map:'.$id.'_map,
				content : \'<div style="height:130px;overflow:hidden;width:250px;"><div style="margin-right:5px;float:right;">'.$img.'</div><h1 style="font-size:13px;font-weight:bold;margin-bottom:8px;"><a onclick="betrieb('.(int)$option['center']['id'].');return false;" href="#">'.jsSafe($option['center']['name']).'</a></h1><p>'.jsSafe($option['center']['anschrift']).'</p><p>'.jsSafe($option['center']['plz']).' '.jsSafe($option['center']['stadt']).'</p><div style="clear:both;"></div><div style="text-align:center;padding:top:8px;"><span aria-disabled="false" role="button" class="bigbutton cardbutton ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" onclick="betriebRequest('.(int)$option['center']['id'].');"><div style="text-align:center;padding:top:8px;"><span class="ui-button-text">Ich möchte hier Essen abholen</span></div></span></div></div>\',
				id : '.(int)$option['center']['id'].',
				icon:   new google.maps.MarkerImage(
						"img/supermarkt.png",
				        	new google.maps.Size(32.0, 37.0),
				        	new google.maps.Point(0, 0),
				        	new google.maps.Point(16.0, 18.0)
						),
				shadow:shadow
			});
			
		  google.maps.event.addListener('.$id.'_markers[i], \'click\', function(e,ii) {
		    '.$id.'_infowindow.setContent(""+this.content);
		    '.$id.'_infowindow.open('.$id.'_map, this);
		   // profile(this.id);
		  });
		    
	
		    '.$id.'_infowindow.setOptions({
		        content: '.$id.'_markers[i].content,
		        position: new google.maps.LatLng('.$lat.','.$lon.'),
		    });
		    '.$id.'_infowindow.open('.$id.'_map); 
		   				
		   	';
		   
		
	}
	
	addJs('
		var '.$id.'_bounds = new google.maps.LatLngBounds();
	    var '.$id.'_center = new google.maps.LatLng('.$lat.','.$lon.');
	    
		var '.$id.'_options = {
		  \'zoom\': '.$zoom.',
		  \'center\': '.$id.'_center,
		  \'mapTypeId\': google.maps.MapTypeId.ROADMAP,
		  styles :[
	{
		featureType: "landscape",
		elementType: "all",
		stylers: [
			{ hue: "#F7F4ED" },
			{ saturation: 16 },
			{ lightness: 54 },
			{ visibility: "on" }
		]
	},{
		featureType: "road.highway",
		elementType: "all",
		stylers: [
			{ hue: "#DABF8F" },
			{ saturation: -50 },
			{ lightness: 19 },
			{ visibility: "simplified" }
		]
	},{
		featureType: "water",
		elementType: "all",
		stylers: [
			{ hue: "#5D8995" },
			{ saturation: -49 },
			{ lightness: -38 },
			{ visibility: "simplified" }
		]
	},
	{
		featureType: "poi",
		elementType: "all",
		stylers: [
			{ hue: "#DDE6D5" },
			{ saturation: -41 },
			{ lightness: 40 },
			{ visibility: "on" }
		]
	}
]
		};
		
		var '.$id.'_map = new google.maps.Map(document.getElementById("'.$id.'_map"), '.$id.'_options);
		var '.$id.'_infowindow = new google.maps.InfoWindow({content: \'Information!\'});
		var '.$id.'_markers = [];
				
		image = new google.maps.MarkerImage("img/marker-foodsaver.png",
		        new google.maps.Size(36.0, 50.0),
		        new google.maps.Point(0, 0),
		        new google.maps.Point(16.0, 18.0)
		);
		shadow = new google.maps.MarkerImage("img/shadow-marker.png",
		         new google.maps.Size(56.0, 50.0),
		         new google.maps.Point(0, 0),
		         new google.maps.Point(16.0, 18.0)
		);
		i = 0;
		for (i = 0; i < '.$id.'.length; i++) {
				
		  var latLng = new google.maps.LatLng('.$id.'[i].lat,'.$id.'[i].lon);
    	  '.$id.'_bounds.extend(latLng);
		  		
		  '.$id.'_markers[i] = new google.maps.Marker({
				\'position\': latLng,
				map:'.$id.'_map,
				id : '.$id.'[i].id,
				public : '.$id.'[i].photo_public,
				icon: image,
				shadow:shadow
			});
			
		  google.maps.event.addListener('.$id.'_markers[i], \'click\', function(e,ii) {
		    showLoader();
		  		
		  		$this = this;
		  		
		  		$.ajax({
					url : "xhr.php?f=fsBubble",
		  			data : {id:$this.id},
		  			dataType: "json",
		  			success:function(data){
		  				if(data.status==1)
		  				{
							'.$id.'_infowindow.setContent(data.html);
			   				'.$id.'_infowindow.open('.$id.'_map, $this);
		   				}
					},
		   			complete: function(){
						hideLoader();
					}
				});
		  });
		}
		
		image = new google.maps.MarkerImage("img/marker-supermarket.png",
		        new google.maps.Size(36.0, 50.0),
		        new google.maps.Point(0, 0),
		        new google.maps.Point(16.0, 18.0)
		);
		for (y = 0; y < g_betriebe.length; y++) {

		    		ii = (y+i)
		    
		  var latLng = new google.maps.LatLng(g_betriebe[y].lat,g_betriebe[y].lon);
			  '.$id.'_markers[ii] = new google.maps.Marker({
				\'position\': latLng,
				map:'.$id.'_map,
				id : g_betriebe[y].id,
				icon: image,
				shadow:shadow
			});
			
		  google.maps.event.addListener('.$id.'_markers[ii], \'click\', function(e,ii) {
		  		showLoader();
		  		
		  		$this = this;
		  		
		  		$.ajax({
					url : "xhr.php?f=bBubble",
		  			data : {id:$this.id},
		  			dataType: "json",
		  			success:function(data){
		  				if(data.status==1)
		  				{
							'.$id.'_infowindow.setContent(data.html);
			   				'.$id.'_infowindow.open('.$id.'_map, $this);
		   				}
					},
		   			complete: function(){
						hideLoader();
					}
				});
		  });
		}

		'.$center_marker.'
		
		    		
		var '.$id.'_markerCluster = new MarkerClusterer('.$id.'_map, '.$id.'_markers,{
			gridSize: 50, 
			maxZoom: 12,
			styles: [{
				height: 53,
				url: "img/m1.png",
				width: 53,
				textColor:\'#566E36\'
			},
			{
				height: 56,
				url: "img/m2.png",
				width: 56,
				textColor:\'#566E36\'
			},
			{
				height: 66,
				url: "img/m3.png",
				width: 66,
				textColor:\'#55412F\'
			},
			{
				height: 78,
				url: "http://google-maps-utility-library-v3.googlecode.com/svn/trunk/markerclusterer/images/m4.png",
				width: 78,
				textColor:\'#55412F\'
			},
			{
				height: 90,
				url: "http://google-maps-utility-library-v3.googlecode.com/svn/trunk/markerclusterer/images/m5.png",
				width: 90,
				textColor:\'#55412F\'
			}]

		});

	');
	
	return '<div class="map" id="'.$id.'_map"></div>';
    
}

function v_messageList($conversation)
{
	$id = id('messagelist');
	$out = '<ul id="messagelist" class="msgbar-dropdown-menu noxhr extended inbox">';
	$class = 'odd';
	
	foreach ($conversation as $m)
	{
		if($class == 'odd')
		{
			$class = 'even';
		}
		else
		{
			$class = 'odd';
		}
		
		$m['time'] = msgTime($m['time_ts']);
		$out .= '<li class="msg"><a class="'.$class.'" href="#'.$m['sender_id'].'"><span class="photo"><img alt="avatar" src="'.img($m['photo']).'"></span><span class="subject"><span class="from">'.$m['name'].'</span><span class="time">'.$m['time'].'</span></span><span class="message">&nbsp;</span><span style="display:block;clear:both;"></span></a></li>';
	}
	
	$out .= '</ul>';
	
	return $out;
}

function v_swapText($id,$value)
{
	return '<input class="swapText swap" onblur="if(this.value==\'\'){this.value=\''.$value.'\';$(this).addClass(\'swap\')}" onfocus="if(this.value==\''.$value.'\'){this.value=\'\';$(this).removeClass(\'swap\');}" onclick="if(this.value==\''.$value.'\'){this.value=\'\';$(this).removeClass(\'swap\');}" id="'.$id.'" type="text" name="'.$id.'" value="'.$value.'" />';
}

function v_bezirkChooser($id = 'bezirk_id',$bezirk = false,$option = array())
{
	addScript('/js/dynatree/jquery.dynatree.js');
	addScript('/js/jquery.cookie.js');
	addCss('/js/dynatree/skin/ui.dynatree.css');
	
	if(!$bezirk)
	{
		//$bezirk = getBezirk();
		$bezirk = array(
			'id' => 0,
			'name' => s('no_bezirk_choosen')
		);
	}
	$id = id($id);
	
	addJs('$("#'.$id.'-button").button().click(function(){
		$("#'.$id.'-dialog").dialog("open");
	});');
	addJs('$("#'.$id.'-dialog").dialog({
		autoOpen:false,
		modal:true,
		title:"Bezirk ändern",
		buttons:
		{
			"Übernehmen":function()
			{
				$("#'.$id.'").val($("#'.$id.'-hId").val());
				$("#'.$id.'-preview").html($("#'.$id.'-hName").val());
				$("#'.$id.'-dialog").dialog("close");
			}
		}
	});');
	addJs('$("#'.$id.'-tree").dynatree({
            onSelect: function(select, node) {
				$("#'.$id.'-hidden").html("");
				$.map(node.tree.getSelectedNodes(), function(node){
					if(node.data.type == 1 || node.data.type == 2 || node.data.type == 3 || node.data.type == 4 || node.data.type == 7)
					{
						$("#'.$id.'-hId").val(node.data.ident);
						$("#'.$id.'").val(node.data.ident);
						$("#'.$id.'-hName").val(node.data.title);
					}
					else
					{
						node.select(false);
						pulseError("Sorry, Du kannst nicht als Region ein Land oder ein Bundesland auswählen.");
					}
					
				});
			},
            persist: false,
			checkbox:true,
			selectMode: 1,
		    initAjax: {
				url: "xhr.php?f=bezirkTree",
				data: {p: "0" }
			},
			onLazyRead: function(node){
				 node.appendAjax({url: "xhr.php?f=bezirkTree",
					data: { "p": node.data.ident },
					dataType: "json",
					success: function(node) {
						
					},
					error: function(node, XMLHttpRequest, textStatus, errorThrown) {
						
					},
					cache: false 
				});
			}
        });');
	addHidden('<div id="'.$id.'-dialog"><div id="'.$id.'-tree"></div></div>');
	
	$label = s('bezirk');
	if(isset($option['label']))
	{
		$label = $option['label'];
	}
	
	return v_input_wrapper($label,'<span id="'.$id.'-preview">'.$bezirk['name'].'</span> <span id="'.$id.'-button">Bezirk &auml;ndern</span>
			<input type="hidden" name="'.$id.'" id="'.$id.'" value="'.$bezirk['id'].'" />
			<input type="hidden" name="'.$id.'-hName" id="'.$id.'-hName" value="'.$bezirk['id'].'" />
			<input type="hidden" name="'.$id.'hId" id="'.$id.'-hId" value="'.$bezirk['id'].'" />');
}

function v_msgBar()
{
	
	$new_msg = true;
	$new_info = false;
	
	$msg_class = '';
	$info_class = '';
	$basket_class = '';
	if($new_msg)
	{
		$msg_class .= ' msg-new';
	}
	if($new_info)
	{
		$info_class .= ' info-new';
	}
	
	$out = '
			<div id="msgbar-messages" style="display:none;">
							<ul class="msgbar-dropdown-menu extended inbox">
                               <li>
                                   <p>Keine neuen Nachrichten</p>
                               </li>
                               <li>
                                   <a href="?page=message">Alle Nachrichten anzeigen</a>
                               </li>
								<li><a href="?page=message&a=neu">'.s('write_a_messages').'</a></li>
                           </ul>
			</div>
			<div id="msgbar-infos" style="display:none;">
							<ul class="msgbar-dropdown-menu extended inbox">
                               <li>
                                   <p>Keine neuen Meldungen</p>
                               </li>
                           </ul>
			</div>
			<div id="msgbar-basket" style="display:none;">
							<ul class="msgbar-dropdown-menu extended inbox">
                               <li>
                                   <a href="#" onclick="ajreq(\'newbasket\',{app:\'basket\'});return false;">Neuen Essenskorb anbieten</a>
                               </li>
                           </ul>
			</div>
	<div id="msgBar-badge">
		<span class="bar-msg" style="opacity:0;">0</span>
		<span class="bar-info" style="opacity:0;">0</span>
		<span class="bar-basket" style="opacity:0;">0</span>
	</div>
	<div id="msgBar">
		<div class="bar-item bar-msg'.$msg_class.'"><span>&nbsp;</span></div>
		<div class="bar-item bar-info'.$info_class.'"><span>&nbsp;</span></div>
		<div class="bar-item bar-basket'.$basket_class.'"><span>&nbsp;</span></div>
		<div onclick="goTo(\'?page=suche\');" class="bar-item bar-search"><span>&nbsp;</span></div>
		<div style="clear:left;"></div>
	</div>';
	
	return $out;
}

function v_form_photo($id)
{
	
}

function v_success($msg,$title =false)
{
	if($title !== false)
	{
		$title = '<strong>'.$title.'</strong> ';
	}
	return '
	<div class="msg-inside success">
			<i class="fa fa-check-circle"></i> '.$msg.'
	</div>';
}

function v_info($msg,$title =false)
{
	if($title !== false)
	{
		$title = '<strong>'.$title.'</strong> ';
	}
	return '
	<div class="msg-inside info">
			<i class="fa fa-info-circle"></i> '.$msg.'
	</div>';
}

function v_error($msg,$title =false)
{
	if($title !== false)
	{
		$title = '<strong>'.$title.'</strong> ';
	}
	return '
	<div class="msg-inside error">
			<i class="fa fa-warning"></i> '.$msg.'
	</div>';
}

function v_form_time($id,$value = false)
{
	if($value == false)
	{
		$value = array();
		$value['hour'] = 20;
		$value['min'] = 0;
	}
	elseif(!is_array($value))
	{
		$v = explode(':', $value);
		$value = array('hour'=>$v[0],'min'=>$v[1]);
	}
	$id = id($id);
	$hours = range(0,23);
	$mins = array(0,5,10,15,20,25,30,35,40,45,50,55);
	
	$out = '<select name="'.$id.'[hour]">';
	
	foreach ($hours as $h)
	{
		$sel = '';
		if($h == $value['hour'])
		{
			$sel = ' selected="selected"';
		}
		$out .= '<option'.$sel.' value="'.$h.'">'.preZero($h).'</option>';
	}
	$out .= '</select>';
	
	$out .= '<select name="'.$id.'[min]">';
	
	foreach ($mins as $m)
	{
		$sel = '';
		if($m == $value['min'])
		{
			$sel = ' selected="selected"';
		}
		$out .= '<option'.$sel.' value="'.$m.'">'.preZero($m).'</option>';
	}
	$out .= '</select> Uhr';
	
	return $out;
	
	
}


function v_dialog_button($id,$label,$option = array())
{
	$new_id = id($id);
	$click = '';
	if(isset($option['click']))
	{
		$click = $option['click'].';';
	}
	
	$tclick = '';
	if(isset($option['title']))
	{
		$tclick = '$("#dialog_'.$id.'").dialog("option","title","'.$option['title'].'");';
	}
	$btoption = array();
	if(isset($option['icon']))
	{
		$btoption[] = 'icons: {primary: "ui-icon-'.$option['icon'].'"}';
	}
	if(isset($option['notext']))
	{
		$btoption[] = 'text:false';
	}
	
	addJs('$("#'.$new_id.'-button").button({'.implode(',', $btoption).'}).click(function(){'.$click.$tclick.'$("#dialog_'.$id.'").dialog("open");});');
	
	return '<span id="'.$new_id.'-button">'.$label.'</span>';
}

function v_form_tinymce($id,$option = array())
{
	addScript('/js/tinymce/jquery.tinymce.min.js');
	$id = id($id);
	$label = s($id);
	$value = getValue($id);
	
	addStyle('div#content {width: 580px;}div#right{width:222px;}');
	
	$css = 'css/content.css,css/foodsaver/jquery-ui-1.10.3.custom.min.css';
	$class = 'ui-widget ui-widget-content ui-padding';
	if(isset($option['public_content']))
	{
		$css = 'css/content.css.php';
		$class = 'post';
	}
	
	$plugins = array('autoresize', 'link', 'image', 'media', 'table', 'contextmenu', 'paste', 'code', 'advlist', 'autolink', 'lists', 'charmap', 'print', 'preview', 'hr', 'anchor', 'pagebreak', 'searchreplace', 'wordcount', 'visualblocks', 'visualchars', 'insertdatetime', 'nonbreaking', 'directionality', 'emoticons', 'textcolor');
	$toolbar = array('styleselect', 'bold italic', 'alignleft aligncenter alignright', 'bullist outdent indent', 'media image link', 'paste', 'code');
	$addOpt = '';
	$filemanager = '';
	if(isset($option['filemanager']))
	{
		$plugins[] = 'responsivefilemanager';
		$plugins[] = 'template';
		$toolbar[] = 'responsivefilemanager';
		$toolbar[] = 'template';
		$addOpt .= ',
		   templates: "/xhrapp.php?app=templates&m=templates",
		   image_advtab: true ,
		   external_filemanager_path:"/filemanager/",
		   filemanager_title:"Dateimanager" ,
		   external_plugins: { "filemanager" : "/filemanager/plugin.min.js"}';
	}
	
	if(isset($option['type']))
	{
		if($option['type'] == 'email')
		{
			$css = 'css/email.css';
			$class = '';
			/*
			$js = '
				$("#'.$id.'").tinymce({
			      script_url : "./js/tinymce//tinymce.min.js",
			      theme : "modern",
				  language : "de",
				  content_css : "'.$css.'",
				  menubar: false,
		    	  statusbar: false,
				  body_class: "'.$class.'",
				  valid_elements : "a[href|target=_blank],strong,b,div[align],br,p,ul,li,ol,table,tr,td[valign=top|align|style|width],th,tbody,thead,tfoot",
				  plugins: "autoresize link image media table contextmenu image link code paste",
		    	  toolbar: "bold italic alignleft aligncenter alignright bullist code link",
				  relative_urls: true,
				  convert_urls: false
			   });
			';
			*/
		}
	}
	
	$js = '
	$("#'.$id.'").tinymce({
		script_url : "./js/tinymce/tinymce.min.js",
		theme : "modern",
		language : "de",
		content_css : "'.$css.'",
		body_class: "'.$class.'",
		menubar: false,
		statusbar: false,
		plugins: "'.implode(' ', $plugins).'",
		toolbar: "'.implode(' | ', $toolbar).'",
		relative_urls: false,
		valid_elements : "a[href|name|target=_blank|class|style],span,strong,b,div[align|class],br,p[class],ul[class],li[class],ol,h1,h2,h3,h4,h5,h6,table,tr,td[valign=top|align|style],th,tbody,thead,tfoot,img[src|width|name|class]",
		convert_urls: false'.$addOpt.'
	 
	});';
	
	addJs($js);
	
	return v_input_wrapper($label, '<textarea name="'.$id.'" id="'.$id.'">'.$value.'</textarea>',$id,$option);
}

function v_form_desc($id,$text,$option = array())
{
	$id = id($id);
	return '<div id="'.$id.'">'.$text.'</div>';
}

function v_form_hidden($name,$value)
{
	$id = id($name);
	return '<input type="hidden" id="'.$id.'" name="'.$name.'" value="'.$value.'" />';
}

function v_form_select_foodsaver($option = array())
{
	global $db;
	$foodsaver = $db->getBasics_foodsaver();
	
	return v_form_select('foodsaver',array('values' => $foodsaver),$option);
}

function v_form_recip_chooser_mini()
{
	global $db;
	$id = 'recip_choose';
	$bezirk = $db->getBezirk();
	
	return v_input_wrapper(s('recip_chooser'), '
		<select class="select" name="'.$id.'" id="'.$id.'">
			<option value="botschafter">Alle Botschafter Bundesweit</option>
			<option value="orgateam">Orgateam Bundesweit</option>
			<option value="bezirk" selected="selected">'.sv('recip_all_bezirk',$bezirk['name']).'</option>
		</select>');
}

function v_form_recip_chooser()
{
	addScript('/js/dynatree/jquery.dynatree.js');
	addScript('/js/jquery.cookie.js');
	addCss('/js/dynatree/skin/ui.dynatree.css');
	global $db;
	
	$bezirk = $db->getBezirk();
	/*
	$regionen = $db->getBasics_region();

	$items = array();
	if(is_array($regionen))
	{
		foreach ($regionen as $r)
		{
			$bezirke = $db->getBezirkByRegionId($r['id']);
			
			$bitems = array();
			foreach ($bezirke as $b)
			{
				$bitems[] = '{title: "'.str_replace(array(',',"'",'"'),'',$b['name']).'","ident":'.(int)$b['id'].',"ttype":"bezirk"}';
			}
			$items[] = '{title: "'.str_replace(array(',',"'",'"'),'',$r['name']).'", isFolder: true, "ttype": "region",
	                    children: [
	                        '.implode(',', $bitems).'
	                    ]}';
		}
	}
	$items = implode(',', $items);
	*/
	$id = 'recip_choose';
	$out = '
		<select class="select" name="'.$id.'" id="'.$id.'">
			<option value="all">'.s('recip_all').'</option>
			<option value="newsletter">Alle Newsletter Abonnenten</option>
			<option value="botschafter">Alle Botschafter Weltweit</option>
			<option value="filialverantwortlich">Alle Filialverantwortlichen Weltweit</option>
			<option value="filialbot">Alle Filialverantwortlichen + Botscahfter</option>
			<option value="filialbez">Alle Filialverantwortlichen in Bezirken...</option>
					<option value="all_no_botschafter">Alle Foodsaver ohne Botschafter</option>
			<option value="orgateam">Orgateam</option>
			<option value="bezirk" selected="selected">'.sv('recip_all_bezirk',$bezirk['name']).'</option>
			<option value="choose">'.s('recip_choose_bezirk').'</option>
			<option value="choosebot">Botschafter in bestimmten Bezirken</option>
			<option value="manual">Manuelle Eingabe</option>
		</select>
		<div id="'.$id.'-hidden" style="display:none">
				
		</div>
		<div id="'.$id.'manual-wrapper" style="display:none">
			'.v_form_textarea($id.'manual').'
		</div>
		<div id="'.$id.'-tree-wrapper" style="display:none;">
			'.v_info('<strong>Hinweis</strong> Um untergeordnete Bezirke zu makrieren musst Du den Ordner erst öffnen! sonst, alle nciht sichtbaren Bezirke bekommen keine Mail.').'
			<div id="'.$id.'-tree">
				
			</div>
		</div>';
	
	addJs('
			$(\'#'.$id.'\').change(function(){
				if($(this).val() == "choose" || $(this).val() == "choosebot" || $(this).val() == "filialbez")
				{
					$("#'.$id.'-tree-wrapper").show();
					$("#'.$id.'manual-wrapper").hide();
				}
				else if($(this).val() == "manual")
				{
					$("#'.$id.'manual-wrapper").show();
					$("#'.$id.'-tree-wrapper").hide();
				}
				else
				{
					$("#'.$id.'manual-wrapper").hide();
					$("#'.$id.'-tree-wrapper").hide();
				}
						
			});
			
			$("#'.$id.'-tree").dynatree({
            onSelect: function(select, node) {
				$("#'.$id.'-hidden").html("");
				$.map(node.tree.getSelectedNodes(), function(node){
					$("#'.$id.'-hidden").append(\'<input type="hidden" name="'.$id.'-choose[]" value="\'+node.data.ident+\'" />\');
				});
			},
            persist: false,
			checkbox:true,
			selectMode: 3,
			clickFolderMode: 3, 
   			activeVisible: true,
		    initAjax: {
				url: "xhr.php?f=bezirkTree",
				data: {p: "0" }
			},
			onLazyRead: function(node){
				 node.appendAjax({url: "xhr.php?f=bezirkTree",
					data: { "p": node.data.ident },
					dataType: "json",
					success: function(node) {
						
					},
					error: function(node, XMLHttpRequest, textStatus, errorThrown) {
						
					},
					cache: false 
				});
			}
        });');
	
	return v_input_wrapper(s('recip_chooser'), $out);
}

function v_photo_edit($src,$fsid = false)
{
	if(!$fsid)
	{
		$fsid = fsId();
	}
	$id = id('fotoupload');
	
	$original = explode('_', $src);
	$original = end($original);
	
	addJs('
			
			$("#'.$id.'-link").fancybox({
				minWidth : 600,
				scrolling :"auto",
				closeClick : false,
				helpers : { 
				  overlay : {closeClick: false}
				}
			});
					
			$("a[href=\'#edit\']").click(function(){
				
				$("#'.$id.'-placeholder").html(\'<img src="images/'.$original.'" />\');
				$("#'.$id.'-link").trigger("click");
				$.fancybox.reposition();
				jcrop = $("#'.$id.'-placeholder img").Jcrop({
			         setSelect:   [ 100, 0, 400, 400 ],
			         aspectRatio: 35 / 45,
			         onSelect: function(c){
			        		$("#'.$id.'-x").val(c.x);
			        		$("#'.$id.'-y").val(c.y);
			        		$("#'.$id.'-w").val(c.w);
			        		$("#'.$id.'-h").val(c.h);
			         }
			     });
			        				
			     $("#'.$id.'-save").show();
				 $("#'.$id.'-save").button().click(function(){
					 showLoader();
					 $("#'.$id.'-action").val("crop");
					 $.ajax({
						url: "xhr.php?f=cropagain",
					 	data: {
							x:parseInt($("#'.$id.'-x").val()),
							y:parseInt($("#'.$id.'-y").val()),
							w:parseInt($("#'.$id.'-w").val()),
							h:parseInt($("#'.$id.'-h").val()),
							fsid:'.(int)$fsid.'
						},
						success:function(data){
							if(data == 1)
							{
								reload();		
							}
						},
						complete:function(){
							hideLoader();
						}
					 });
					 return false;
				 });
				 
				 $("#'.$id.'-placeholder").css("height","auto");
				 hideLoader();
				 setTimeout(function(){
					 $.fancybox.update();
					 $.fancybox.reposition();
					 $.fancybox.toggle();
				 },200);
			});
				
			$("a[href=\'#new\']").click(function(){
				$("#'.$id.'-link").trigger("click");
				return false;
			});
			');
	
	addHidden('
			<div class="fotoupload popbox" style="display:none;" id="'.$id.'">
				<h3>Fotoupload</h3>
				<p class="subtitle">Hier kannst Du ein Foto von Deinem Computer ausw&auml;hlen</p>
				<form id="'.$id.'-form" method="post" enctype="multipart/form-data" target="'.$id.'-frame" action="xhr.php?f=uploadPhoto">
					<input type="file" name="uploadpic" onchange="showLoader();$(\'#'.$id.'-form\')[0].submit();" />
					<input type="hidden" id="'.$id.'-action" name="action" value="upload" />
					<input type="hidden" id="'.$id.'-x" name="x" value="0" />
					<input type="hidden" id="'.$id.'-y" name="y" value="0" />
					<input type="hidden" id="'.$id.'-w" name="w" value="0" />
					<input type="hidden" id="'.$id.'-h" name="h" value="0" />
					<input type="hidden" id="'.$id.'-file" name="file" value="0" />
					<input type="hidden" name="pic_id" value="'.$id.'" />
				</form>
				<div id="'.$id.'-placeholder" style="margin-top:15px;margin-bottom:15px;background-repeat:no-repeat;background-position:center center;">
					
				</div>
				<a href="#" style="display:none" id="'.$id.'-save">Speichern</a>
				<iframe name="'.$id.'-frame" src="upload.php" width="1" height="1" style="visibility:hidden;"></iframe>
			</div>');
	
	if(isset($_GET['pinit']))
	{
		addJs('$("#'.$id.'-link").trigger("click");');
	}
	
	addHidden('<a id="'.$id.'-link" href="#'.$id.'">&nbsp;</a>');
	
	$menu = array(array('name' => s('edit_photo'),'href' => '#edit'));
	if($_GET['page'] == 'settings')
	{
		$menu[] = array('name' => s('upload_new_photo'),'href' => '#new');
	}
	return '
		<div align="center"><img src="'.$src.'" /></div>
		<div>
		'.v_menu($menu).'
		</div>
		<div style="visibility:hidden"><img src="'.$original.'" /></div>';
}

function v_accordion($sections)
{
	$id = id('accordion');
	$out = '
	<div id="'.$id.'">';
	foreach($sections as $s)
	{
		$out .= '
		<h3>'.$s['name'].'</h3>
		<div>
			'.$s['cnt'].'
		</div>';
	}
	
	$out .= '
	</div>';
	
	addJs('$("#'.$id.'").accordion({
			active: "false",
			collapsible: true
	});');
	
	return $out;
}

function v_form_accordion($sections)
{
	$id = id('form_accordion');
	$out = '
	<div id="'.$id.'">';
	foreach ($sections as $i => $section)
	{
		$i++;
		$out .= '
		<h3>'.$section['title'].'</h3>
		<div>'.v_form_section($i,$section['elements'],$id).'</div>';
	}
	$out .= '
	</div>';
	
	addJs('$("#'.$id.'").accordion({
			heightStyle: "content",
			animate: {duration:0}
		});');
	
	return $out;
}

function v_form_info($msg,$label = false)
{
	return '<div class="input-wrapper">'.v_info($msg,$label).'</div>';
}

function v_wizardform($title,$sections,$option = array())
{
	$id = id('wizard');
	$stepmenu = '<ul>';
	$out = '';
	foreach ($sections as $i => $section)
	{
		$i++;
		$stepmenu .=  '
		<li><a href="#step-'.$i.'">
                <label class="stepNumber">'.$i.'</label>
                <span class="stepDesc">
                   '.$section['title'].'<br />
                   <small>'.$section['desc'].'</small>
                </span>
			</a></li>';
		$out .= v_field(v_form_section($i,$section['elements']),$section['title']);
	}
	$stepmenu .= '
		</ul>';
	
	addJs('$("#'.$id.'").smartWizard({transitionEffect:"slide"});');
	
	return v_form('',array('<div id="'.$id.'">'.$stepmenu . $out.'</div>'),$option);
}

function v_statusbox()
{
	global $db;
	$out = '';
	
	if($bezirk = $db->getBezirk($_SESSION['client']['bezirk_id']))
	{
		$out .= v_menu(array(
					
				array
				(
						'href' => '?page=foodsaver',
						'name' => 'Foodsaver aus '.$bezirk['name']
				),
				array
				(
						'href' => '?page=betrieb',
						'name' => 'Zu den Betrieben'
				)
					
		),'Foodsaver-Bezirk '.$bezirk['name']);
	}
	
	if($rolle = $db->getRolle())
	{
		if(isset($rolle['botschafter']))
		{
			$out .= v_menu(array(
					
					array
					(
						'href' => '?page=email',
						'name' => 'E-Mail an alle foodsaver'		
					)
					
			),'Botschafter '.$rolle['botschafter']['bezirk_name']);
		}
		
		if(isset($rolle['verantwortlich']))
		{
			$menu = array();
			
			foreach($rolle['verantwortlich'] as $v)
			{
				$menu[] = array
				(
					'href' => '?page=betrieb&id='.(int)$v['betrieb_id'],
					'name' => $v['betrieb_name']
				);
			}
			if(!empty($menu))
			{
				$out .= v_menu($menu,'Deine Lebensmittel-Spender');
			}
		}
	}
	return $out;
}



function v_form($name,$elements,$option=array())
{
	global $v_id;
	$js = '';
	if(isset($option['id']))
	{
		$id = makeId($option['id']);
	}
	else
	{
		$id = makeId($name,$v_id);
	}
	
	if(isset($option['dialog']))
	{
		$noclose = '';
		if(isset($option['noclose']))
		{
			$noclose = ',
			closeOnEscape: false,
			open: function(event, ui) {$(this).parent().children().children(".ui-dialog-titlebar-close").hide();}';
		}
		addJs('$("#'.$id.'").dialog({modal:true,title:"'.$name.'"'.$noclose.'});');
	}
	
	$action = getSelf();
	if(isset($option['action']))
	{
		$action = $option['action'];
	}
	
	$out = '
	<div id="'.$id.'">
	<form method="post" id="'.$id.'-form" class="validate" enctype="multipart/form-data" action="'.$action.'">
		<input type="hidden" name="form_submit" value="'.$id.'" />';
	foreach ($elements as $el)
	{
		$out .= $el;
	}
	
	if(!isset($option['submit']))
	{
		$out .= v_form_submit('Senden',$id,$option);
	}
	else if($option['submit'] !== false)
	{
		$out .= v_form_submit($option['submit'],$id,$option);
	}
	
	
	
	$out .= '
	</div>
	</form>
	';
	
	addJs('$("#'.$id.'-form").submit(function(ev){
		
		check = true;
		$("#'.$id.'-form div.required .value").each(function(i,el){
			input = $(el);
			if(input.val() == "")
			{
				check = false;
				input.addClass("input-error");
				error($("#" + input.attr("id") + "-error-msg").val());
			}
		});

		if(check == false)
		{
			ev.preventDefault();
		}
			
	});');
	
	if(!empty($js))
	{
		$out .= '
		<script type="text/javascript">
		$(document).ready(function(){
		'.$js.'
		});
		</script>';
	}
	
	$v_id[$id] = true;
	
	return $out;
}

function v_getElement($el)
{
	
}

function v_menu($items,$title = false,$option = array())
{
	$id = id('vmenu');
	
	//addJs('$("#'.$id.'").menu();');
	$out = '
	<ul class="linklist">';
	
	foreach ($items as $item)
	{
		if(!isset($item['href']))
		{
			$item['href'] = '#';
		}
		
		$click = '';
		if(isset($item['click']))
		{
			$click = ' onclick="'.$item['click'].'"';
		}
		$sel = '';
		if($item['href'] == '?'.$_SERVER['QUERY_STRING'])
		{
			$sel = ' active';
		}
		$out .= '
				<li><a class="ui-corner-all'.$sel.'" href="'.$item['href'].'"'.$click.'>'.$item['name'].'</a></li>';
	}
	
	$out .= '
	</ul>';

	
	if(!$title)
	{
		return '
			<div class="ui-widget ui-widget-content ui-corner-all ui-padding">
				'.$out.'
			</div>';
	}
	else
	{
		return '
			<h3 class="head ui-widget-header ui-corner-top">'.$title.'</h3>
			<div class="ui-widget ui-widget-content ui-corner-bottom margin-bottom ui-padding">
				<div id="'.$id.'">
					'.$out.'
				</div>
			</div>';
	}
	
	return $out;
}

function v_toolbar($option = array())
{
	$id = 0;
	if(isset($option['id']))
	{
		$id = $option['id'];
	}
	if(isset($option['page']))
	{
		$page = $option['page'];
	}
	else
	{
		$page = getPage();
	}
	
	if(isset($_GET['bid']))
	{
		$bid = '&bid='.(int)$_GET['bid'];
	}
	else 
	{
		$bid = getBezirkId();
	}
	
	$out = '';
	if(!isset($option['types']))
	{
		$option['types'] = array('edit','delete');
	}
	
	$last = count($option['types'])-1;
	
	foreach ($option['types'] as $i => $t)
	{
		$corner = '';
		if($i==0)
		{
			$corner = ' ui-corner-left';
		}
		if($i==$last)
		{
			$corner .= ' ui-corner-right';
		}
		switch ($t)
		{
			case 'image' :
				$out .= '<li onclick="openPhotoDialog('.$option['id'].');" title="Foto Hochladen" class="ui-state-default'.$corner.'"><span class="ui-icon ui-icon-image"></span></li>';
				break;
			case 'mail' :
				$out .= '<li onclick="sendMail('.$option['fs_id'].');" title="E-Mail schreiben" class="ui-state-default'.$corner.'"><span class="ui-icon ui-icon-mail-closed"></span></li>';
				break;
			case 'new' :
				$out .= '<li onclick="goTo(\'?page='.$page.'&id='.$id.'&a=new\');" title="neu" class="ui-state-default'.$corner.'"><span class="ui-icon ui-icon-document"></span></li>';
				break;
			case 'comment' :
				$out .= '<li attr="'.$page.':'.$id.'" title="Notitz hinzuf&uuml;gen" class="toolbar-comment ui-state-default'.$corner.'"><span class="ui-icon ui-icon-comment"></span></li>';
				break;
	
			case 'edit' :
				$out .= '<li onclick="goTo(\'?page='.$page.'&id='.$id.'&a=edit\');" title="bearbeiten" class="ui-state-default'.$corner.'"><span class="ui-icon ui-icon-wrench"></span></li>';
				break;
					
			case 'delete' :
				if(isset($option['confirmMsg']))
				{
					$cmsg = $option['confirmMsg'];
				}
				else
				{
					$cmsg = 'Wirklich l&ouml;schen?';
				}
				$out .= '<li onclick="ifconfirm(\'?page='.$page.'&a=delete&id='.$id.'\',\''.jsSafe($cmsg).'\');" title="l&ouml;schen" class="ui-state-default'.$corner.'"><span class="ui-icon ui-icon-trash"></span></li>';
				break;
	
			default : break;
		}
	}
	
	$out = '<ul class="toolbar" class="ui-widget ui-helper-clearfix">'.$out.'</ul>';
	
	return $out;
}

function v_tablesorter($head,$data,$option = array())
{
	$id = id('table');

	
	$style = '';
	if(isset($option['noHead']))
	{
		$style = ' style="visibility:hidden;margin:0;paddin:0;height:1px;overflow:hidden;"';
	}
	
	$out = '
	<div class="tablesort-wrapper">
		<table id="'.$id.'" class="tablesorter">			
			<thead'.$style.'>
				<tr class="ui-corner-top"'.$style.'>';
		
		$i = 0;
		
		$jsoption = '';
		foreach ($head as $h)
		{
			$width = '';
			if(isset($h['width']))
			{
				$width = ' style="width:'.$h['width'].'px"';
			}
			$out .= '
					<th'.$width.''.$style.' class="ui-corner-all">'.$h['name'].'</th>';
			
			if(isset($h['sort']) && $h['sort'] == false)
			{
				$jsoption .= $i.':{sorter:false},';
			}
	
			$i++;
		}
	
		$out .= '
				</tr>
			</thead>
			<tbody>';
		
		foreach ($data as $row)
		{
			$out .= '
				<tr>';
			foreach ($row as $r)
			{
				$out .= '
					<td>'.$r['cnt'].'</td>';
			}
			$out .= '
				</tr>';
		}
		
		$out .= '
			</tbody>
		</table>
	</div>';
		
	addJs('
		$("table.tablesorter td ul.toolbar").css("visibility","hidden");

		$( "table.tablesorter tbody tr" ).hover(
				function() {
					$( this ).addClass("hover");
					$( this ).children("td:last").children("ul").css("visibility","visible");
				},
				function() {
					$( this ).removeClass("hover");
					$( this ).children("td:last").children("ul").css("visibility","hidden");
				}
		);
	');
	
	$pager_js ='';
	if(isset($option['pager']) && count($data) > 14)
	{
		addScript('/js/tablesorter/jquery.tablesorter.pager.js');
		addStyle('div.pager{position:relative !important;}');
		
		addJs('
			$(".prev").button({
				icons: {
					primary: "ui-icon-circle-arrow-w"
				},
				text: false
			});
			$(".next").button({
				icons: {
					primary: "ui-icon-circle-arrow-e"
				},
				text: false
			});
		');
		
		$out .= '
		<div id="'.$id.'-pager" class="pager ui-corner-all">
			<form>
				<!--<a class="first" href="#">&nbsp;</a>-->
				<a class="prev">&nbsp;</a>
				
				<input style="display:none" type="text" class="pagedisplay"/>
				<span class="pagedisplay2">
					<span>Seite</span> <span class="seite"></span> <span>von</span> <span class="anz"></span>
				</span>
				<a class="next">&nbsp;</a>
				<!-- <img src="http://tablesorter.com/addons/pager/icons/last.png" class="last"/> -->
				<span class="pagesize-wrapper">
					<select class="pagesize">
						<option selected="selected"  value="10">10</option>
						<option value="20">20</option>
						<option value="30">30</option>
						<option  value="40">40</option>
					</select> <span>Einträge pro Seite</span>
				</span>
			</form>
		</div>';
		
		$pager_js = '.tablesorterPager({container: $("#'.$id.'-pager")})';
	}
	
	if(!empty($jsoption))
	{
		$jsoption = 'headers:{'.substr($jsoption, 0,(strlen($jsoption)-1)).'}';
		
		addJs('$("#'.$id.'").tablesorter({
				'.$jsoption.',
				widgets: ["zebra"]
			})'.$pager_js.';');
	}
	else
	{
		addJs('$("#'.$id.'").tablesorter({widgets: ["zebra"]})'.$pager_js.';');
	}
	
	return $out;
}

function v_form_submit($val,$id,$option = array())
{
	$out = '';
	if(isset($option['buttons']))
	{
		foreach ($option['buttons'] as $b)
		{
			$out .= $b;
		}
	}
	return '
	<div class="input-wrapper">
		<p><input class="button" type="submit" value="'.$val.'" />'.$out.'</p>
	</div>';
}

function v_form_textarea($id,$option = array())
{
	$id = id($id);
	$value = getValue($id);
	$label = s($id);
	
	$style = '';
	if(isset($option['style']))
	{
		$style = ' style="'.$option['style'].'"';
	}
	
	$maxlength = '';
	if(isset($option['maxlength']))
	{
		$maxlength = ' maxlength="'.(int)$option['maxlength'].'"';
	}
	
	return v_input_wrapper(
			$label, 
			'<textarea'.$style.$maxlength.' class="input textarea value" name="'.$id.'" id="'.$id.'">'.$value.'</textarea>', 
			$id,
			$option);
}

function v_form_str_hsnr($name,$option = array())
{
	$str_id = id('str');
	$hsnr_id = id('hsnr');
	
	if(!isset($option['str']))
	{
		$option['str'] = array();
	}
	if(!isset($option['hsnr']))
	{
		$option['hsnr'] = array();
	}
	
	$str_val = getValue($str_id);
	$hsnr_val = getValue($hsnr_id);
	
	$str_check = checkInput($option['str'], $str_id, $str_val);
	$hsnr_check = checkInput($option['hsnr'], $str_id, $hsnr_val);
	
	$class = '';
	if(strpos($str_check.$hsnr_check, 'empty') !== false)
	{
		$class .= ' empty';
	}
	if(strpos($str_check.$hsnr_check, 'required') !== false)
	{
		$class .= ' required';
	}

	return v_input_wrapper(
			$name, 
			'<input class="input text input_str" type="text" name="'.$str_id.'" value="'.$str_val.'" id="'.$str_id.'" /><input class="input text input_hsnr" type="text" name="'.$hsnr_id.'" value="'.$hsnr_val.'" id="'.$hsnr_id.'" />', 
			$str_id,
			array('class' => $class)
	);
	/*
	return '
	<div class="input-wrapper'.$class.'" id="'.$str_id.'-wrapper">
	<label class="ui-widget" for="'.$str_id.'">'.$name.':</label>
	
	<div style="clear:both;"></div>
	</div>';
	*/
}

function v_form_combobox($name,$cats,$option = array())
{
	$id = id($name);
	$out = '
	<select name="'.$id.'" id="'.$id.'">
				<option></option>';

	foreach ($cats as $c)
	{
		$out .= '
		<option>'.$c['name'].'</option>';
	}

	$out .= '
	</select>';

	addJs('$("#'.$id.'").combobox()');

	return v_input_wrapper($name,$out,$id);
}

function v_form_checkbox($id,$option = array())
{
	$id = id($id);
	
	if(isset($option['checked']))
	{
		$value = $option['checked'];
	}
	else
	{
		$value = getValue($id);
	}
	$label = s($id);
	
	if(isset($option['values']))
	{
		$values = $option['values'];
	}
	elseif ($v = getDbValues($id))
	{
		$values = $v;
	}
	else
	{
		$values = array();
	}
	
	$checked = array();
	if(is_array($value))
	{
		foreach ($value as $key => $ch)
		{
			$checked[$ch] = true;
		}
	}
	else if($value == 1)
	{
		$checked[1] = true;
	}
	$out = '';
	if(!empty($values))
	{
		foreach ($values as $v)
		{
			$sel = '';
			if(isset($checked[$v['id']]) || isset($option['checkall']))
			{
				$sel = ' checked="checked"';
			}
			$v['name'] = trim($v['name']);
			if(!empty($v['name']))
			{
			$out .= '
				<label><input class="input cb-'.$id.'" type="checkbox" name="'.$id.'[]" value="'.$v['id'].'"'.$sel.' />&nbsp;'.$v['name'].'</label><br />';
			}
		}
	}
	
	return v_input_wrapper($label, $out, $id, $option);
}

function v_form_checkboxTagAlt($id,$option=array())
{
	$id = id($id);
	$out = '';
	
	$bindabei = false;
	
	$out .= '
		<ul class="tagedit-list tagAltlist" id="'.$id.'-tagAltlist">';
	if($values = getValue($id))
	{
		foreach ($values as $v)
		{
			$name = $v['name'];
			$class = 'tagedit-listelement tagedit-listelement-old';
			if(fsId() == $v['id'])
			{
				$class = 'dasbistDu tagedit-listelement tagedit-listelement-old';
				$name = 'Du';
				$bindabei = true;
			}
			$out .= '
			<li class="'.$class.'" onclick="profile('.(int)$v['id'].');">
				<input type="hidden" name="'.$id.'['.$v['id'].'-a]" value="'.$v['name'].'" />
				<span class="name">'.$name.'</span>
			</li>';
		}
	}
	$out .= '
		</ul>';
	
	if(!$bindabei)
	{
		$out .= '
			<span id="'.$id.'-button">Mich hier eintragen</span>';
	}
	else
	{
		$out .= '
			<span id="'.$id.'-buttonOut">Abmelden</span>';
	}
	
	
	addHidden('
	<div id="'.$id.'-timedialogOut">
		'.v_info('<strong>Achtung!</strong> Es darf '.$option['label'].' keine Lücke entstehen achte darauf das andere abholen.</span>').'
	</div>
	<div id="'.$id.'-timedialog">
		'.v_info('Möchtest Du Dich wirklich verbindlich für <strong>'.$option['label'].'</strong> Eintragen?</span>').'
	</div>');
	
	addJs('
			
			$("#'.$id.'-timedialog").dialog({
				title:"Sicher?",
				resizable: false,
				modal: true,
				autoOpen:false,
				buttons: {
					"Ja ich bin mir sicher": function() {
						$("#'.$id.'-button").last().remove();
						$("#'.$id.'-tagAltlist").append(\'<li><input type="hidden" name="'.$id.'['.(int)fsId().'-a]" value="'.(int)fsId().'" /><span class="notConfirmed">Du</span></li>\');
						$( this ).dialog( "close" );
						$("#zeiten-form").submit();
					},
					"Nein doch nicht": function() {
						$( this ).dialog( "close" );
					}
				}
			});
			
	$("#'.$id.'-button").button().click(function(){
		$("#'.$id.'-timedialog").dialog("open");
	});
				
	$("#'.$id.'-timedialogOut").dialog({
				title:"Denke an Nachfolger",
				resizable: false,
				modal: true,
				autoOpen:false,
				buttons: {
					"Mach Ich!": function() {
						$("#'.$id.'-buttonOut").last().remove();
						$("#'.$id.'-tagAltlist li.dasbistDu").remove();
						$( this ).dialog( "close" );
						$("#zeiten-form").submit();
					},
					"Abbrechen": function() {
						$( this ).dialog( "close" );
					}
				}
			});
			
	$("#'.$id.'-buttonOut").button().click(function(){
		$("#'.$id.'-timedialogOut").dialog("open");
	});
		
	');
	
	return v_input_wrapper(s($id),$out ,$id, $option);
	
}

function v_form_tagselect($id,$option=array())
{
	
	
	// term=h
	
	// [{"id":"3","label":"Hazel Grouse","value":"Hazel Grouse"},{"id":"5","label":"Common Pheasant","value":"Common Pheasant"},{"id":"6","label":"Northern Shoveler","value":"Northern Shoveler"},{"id":"20","label":"Bluethroat","value":"Bluethroat"},{"id":"22","label":"Wood Nuthatch","value":"Wood Nuthatch"},{"id":"26","label":"Chaffinch","value":"Chaffinch"},{"id":"28","label":"Hawfinch","value":"Hawfinch"}]
	
	$xhr = $id;
	if(isset($option['xhr']))
	{
		$xhr = $option['xhr'];
	}
	
	$url = 'get'.ucfirst($xhr);
	if(isset($option['url']))
	{
		$url = $option['url'];
	}
	
	$source = 'autocompleteURL: "xhr.php?f='.$url.'"';
	$post = '';
	
	if(isset($option['data']))
	{
		$source = 'autocompleteOptions: {source: '.json_encode($option['data']).',minLength: 0}';
		
	}
	
	addJs('
		$("#'.$id.' input.tag").tagedit({
			'.$source.',
			allowEdit: false,
			allowAdd: false,
			animSpeed:100
		});	
		
		$("#'.$id.'").keydown(function(event){
		    if(event.keyCode == 13) {
		      event.preventDefault();
		      return false;
		    }
		  });
	');
	
	$input = '<input type="text" name="'.$id.'[]" value="" class="tag input text value" />';
	if($values = getValue($id))
	{
		$input = '';
		foreach ($values as $v)
		{
			$input .= '<input type="text" name="'.$id.'['.$v['id'].'-a]" value="'.$v['name'].'" class="tag input text value" />';
		}
	}

	return v_input_wrapper(s($id), '<div id="'.$id.'">'.$input.'</div>',$id,$option);
}


function v_form_picture($id,$option = array())
{
	$id = id($id);

	addJs('
		$("#'.$id.'-link").fancybox({
			minWidth : 600,
			scrolling :"auto",
			closeClick : false,
			helpers : { 
			  overlay : {closeClick: false}
			}
		});	
				
		$("#'.$id.'-opener").button().click(function(){
			
			$("#'.$id.'-link").trigger("click");
			
		});		
	');
	
	$options = '';
	
	$crop = '0';
	if(isset($option['crop']))
	{
		$crop = '1';
		$options .= '<input type="hidden" id="'.$id.'-ratio" name="ratio" value="'.json_encode($option['crop']).'" />';
		$options .= '<input type="hidden" id="'.$id.'-ratio-i" name="ratio-i" value="0" />';
		$options .= '<input type="hidden" id="'.$id.'-ratio-val" name="ratio-val" value="[]" />';
	}
	
	if(isset($option['resize']))
	{
		$options .= '<input type="hidden" id="'.$id.'-resize" name="resize" value="'.json_encode($option['resize']).'" />';
	}
	
	addHidden('
	<div id="'.$id.'-fancy">
		<div class="popbox">
			<h3>'.s($id).' Upload</h3>
			<p class="subtitle">W&auml;hle ein Bild von Deinem Rechner</p>
			
			<form id="'.$id.'-form" method="post" enctype="multipart/form-data" target="'.$id.'-iframe" action="xhr.php?f=uploadPicture&id='.$id.'&crop='.$crop.'">
					
				<input type="file" name="uploadpic" onchange="showLoader();$(\'#'.$id.'-form\')[0].submit();" />
				
				<input type="hidden" id="'.$id.'-action" name="action" value="uploadPicture" />
				<input type="hidden" id="'.$id.'-id" name="id" value="'.$id.'" />

				<input type="hidden" id="'.$id.'-x" name="x" value="0" />
				<input type="hidden" id="'.$id.'-y" name="y" value="0" />
				<input type="hidden" id="'.$id.'-w" name="w" value="0" />
				<input type="hidden" id="'.$id.'-h" name="h" value="0" />
						
				'.$options.'
						
			</form>
						
			<div id="'.$id.'-crop"></div>
								
			<iframe src="" id="'.$id.'-iframe" name="'.$id.'-iframe" style="width:1px;height:1px;visibility:hidden;"></iframe>
		</div>
	</div>');
	
	$thumb = '';
	
	$pic = getValue($id);
	if(!empty($pic))
	{
		$thumb = '<img src="images/'.str_replace('/', '/thumb_', $pic).'" />';
	}
	$out = '
		<input type="hidden" name="'.$id.'" id="'.$id.'" value="" /><div id="'.$id.'-preview">'.$thumb.'</div>
		<span id="'.$id.'-opener">'.s('upload_picture').'</span><span style="display:none;"><a href="#'.$id.'-fancy" id="'.$id.'-link">&nbsp;</a></span>';
	
	return v_input_wrapper(s($id), $out);
}

function v_form_file($id,$option = array())
{
	$id = id($id);
	
	$val = getValue($id);
	if(!empty($val))
	{
		$val = json_decode($val,true);
		$val = substr($val['name'],0,30);
	}
	
	addJs('
	$("#'.$id.'-button").button().click(function(){$("#'.$id.'").click();});
	$("#'.$id.'").change(function(){$("#'.$id.'-info").html($("#'.$id.'").val().split("\\\").pop());});');
	
	$btlabel = s('choose_file');
	if(isset($option['btlabel']))
	{
		$btlabel = $option['btlabel'];
	}
	
	$out = '<input style="display:block;visibility:hidden;margin-bottom:-23px;" type="file" name="'.$id.'" id="'.$id.'" size="chars" maxlength="100000" /><span id="'.$id.'-button">'.$btlabel.'</span> <span id="'.$id.'-info">'.$val.'</span>';
	
	return v_input_wrapper(s($id), $out);
}

function v_form_list($id,$option = array())
{
	$id = id($id);
	$value = getValue($id);
	$label = s($id);
	
	$out = '<textarea class="input textarea value" name="'.$id.'" id="'.$id.'">';

	$val = '';
	if(is_array($value))
	{
		
		foreach ($value as $v)
		{
			$out .= $v['name']."\r\n";
		}
	}
	
	$out .= '</textarea>';
	
	return v_input_wrapper($label,$out,$id,$option);
}

function v_form_radio($id,$option = array())
{
	$id = id($id);
	$value = getValue($id);
	$label = s($id);
	
	$check = jsValidate($option, $id, $label);

	if(isset($option['values']))
	{
		$values = $option['values'];
	}
	elseif ($v = getDbValues($id))
	{
		$values = $v;
	}
	else
	{
		$values = array();
	}
	
	$disabled = '';
	if(isset($option['disabled']) && $option['disabled'] === true)
	{
		$disabled = 'disabled="disabled" ';
	}
	
	$out = '';
	if(!empty($values))
	{
		foreach ($values as $v)
		{
			$sel = '';
			if($value == $v['id'])
			{
				$sel = ' checked="checked"';
			}
			$out .= '
			<label><input name="'.$id.'" type="radio" value="'.$v['id'].'"'.$sel.' '.$disabled.'/>'.$v['name'].'</label><br />';
		}
	}
	$out .= '';
	
	return v_input_wrapper($label, $out,$id,$option);
	
}

function v_form_land()
{
	global $db;
	$values = $db->get_land();
	
	$out = v_form_select('land_id',array('values'=>$values));
	
	return $out;
	
}

function v_form_select($id,$option = array())
{
	$id = id($id);
	$value = getValue($id);
	$label = s($id);
	$check = jsValidate($option, $id, $label);

	if(isset($option['values']))
	{
		$values = $option['values'];
	}
	elseif ($v = getDbValues($id))
	{
		$values = $v;
	}
	else 
	{
		$values = array();
	}

	$out = '
	<select class="input select value" name="'.$id.'" id="'.$id.'">
		<option value="">Bitte Ausw&auml;hlen...</option>';
	if(!empty($values))
	{
		foreach ($values as $v)
		{
			$sel = '';
			if($value == $v['id'])
			{
				$sel = ' selected="selected"';
			}
			$out .= '
			<option value="'.$v['id'].'"'.$sel.'>'.$v['name'].'</option>';
		}
	}
	$out .= '
	</select>';
	
	if(isset($option['add']))
	{
		addHidden('
		<div id="'.$id.'-dialog" style="display:none;">
			'.v_form_text($id.': NEU').'
		</div>');
		
		$out .= '<a href="#" id="'.$id.'-add" class="select-add">&nbsp;</a>';
		
		addJs('
				
				$("#'.$id.'neu").keyup(function(e){
					
					if(e.keyCode == 13)
					{
					  addSelect("'.$id.'");
					}
				});
				

				
				$("#'.$id.'-add").button({
					icons:{primary:"ui-icon-plusthick"},
					text:false
				}).click(function(event){
				
					event.preventDefault();
					$("#'.$id.'-dialog label").remove();
					
					$("#'.$id.'-dialog").dialog({
						modal:true,
						title: "'.$label.' anlegen",
						buttons:
						{
							"Speichern":function()
							{
								addSelect("'.$id.'");
							}
						}
					});
				});
				
				
				');
	}
	
	return v_input_wrapper($label,$out,$id,$option);
}

function v_form_plz_ort($name,$option = array())
{
	
	$plz_id = id('plz');
	$ort_id = id('ort');
	
	$plz_value = getValue($plz_id);
	$ort_value = getValue($ort_id);
	
	return v_input_wrapper(
			$name, 
			'<input class="input text input_plz value" type="text" name="'.$plz_id.'" id="'.$plz_id.'" value="'.$plz_value.'" /><input class="input text input_ort" type="text" name="'.$ort_id.'" value="'.$ort_value.'" id="'.$ort_id.'" />', 
			$plz_id,
			$option
	);
	/*
	return '
	<div class="input-wrapper" id="'.$plz_id.'-wrapper">
	<label class="ui-widget" for="'.$plz_id.'">'.$name.':</label>
	
	<div style="clear:both;"></div>
	</div>';
	*/
}

function v_input_wrapper($label,$content,$id = false,$option = array())
{
	if(isset($option['nowrapper']))
	{
		return $content;
	}
	
	if($id === false)
	{
		$id = id('input');
	}
	$class = '';
	$star = '';
	$error_msg = '';
	$check = jsValidate($option, $id, $label);
	
	if(isset($option['required']))
	{
		$star = '<span class="req-star"> *</span>';
		if(isset($option['required']['msg']))
		{
			$error_msg = $option['required']['msg'];
		}
		else
		{
			$error_msg = $label.' darf nicht leer sein';
		}
	}
	
	if(isset($option['label']))
	{
		$label = $option['label'];
	}
	
	if(isset($option['click']))
	{
		$label = '<a href="#" onclick="'.$option['click'].';return false;">'.$label.'</a>';
	}
	
	$label_in = '<label class="wrapper-label ui-widget" for="'.$id.'">'.$label.$star.'</label>';
	if(isset($option['nolabel']))
	{
		$label_in = '';
	}
	
	$desc = '';
	if(isset($option['desc']))
	{
		$desc = '<div class="desc">'.$option['desc'].'</div>';
	}
	
	if(isset($option['class']))
	{
		$check['class'] .= ' '.$option['class'];
	}
	
	return '
	<div class="input-wrapper'.$check['class'].'" id="'.$id.'-wrapper">
	'.$label_in.'
	'.$desc.'
	<div class="element-wrapper">
		'.$content.'
	</div>
	<input type="hidden" id="'.$id.'-error-msg" value="'.$error_msg.'" />
	<div style="clear:both;"></div>
	</div>';
}

function v_form_date($id,$option = array())
{
	$id = id($id);
	$label = s($id);

	$value = getValue($id);

	addJs('$("#'.$id.'").datepicker({
		changeYear: true,
		changeMonth: true,
		dateFormat: "yy-mm-dd",
		monthNames: [ "Januar", "Februar", "M&auml;rz", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember" ],
		yearRange: "'.(date('Y')-60).':'.(date('Y')+60).'"
	});');
	
	return v_input_wrapper(
			$label,
			'<input class="input text date value" type="text" name="'.$id.'" id="'.$id.'" value="'.$value.'" />',
			$id,
			$option
	);
}

function v_form_text($id,$option = array())
{
	$id = id($id);
	$label = s($id);
	
	$value = getValue($id);
	
	return v_input_wrapper(
			$label, 
			'<input class="input text value" type="text" name="'.$id.'" id="'.$id.'" value="'.$value.'" />', 
			$id,
			$option
	);
}

function v_field($content,$title,$option = array())
{
	$class = '';
	if(isset($option['class']))
	{
		$class = ' '.$option['class'].'';
	}
	
	return '
	<div class="field">
		<div class="head ui-widget-header ui-corner-top">'.$title.'</div>
		<div class="ui-widget ui-widget-content ui-corner-bottom margin-bottom'.$class.'">
			'.$content.'
		</div>
	</div>';
}

function v_blankfield($content)
{
	return '
	<div class="ui-widget ui-widget-content ui-corner-all margin-bottom ui-padding">
		'.$content.'
	</div>';
}

function v_form_passwd($id,$option = array())
{
	$id = id($id);

	return v_input_wrapper(s($id), '<input class="input text" type="password" name="'.$id.'" id="'.$id.'" />', $id);
}


function v_getMessages($error,$info)
{
	
	$out = '';
	if(count($error) > 0)
	{
		$out .= '
		<div class="ui-widget pageblock ui-padding">
		<div class="ui-state-error ui-corner-all">
		<p><span style="float: left; margin-right: .3em;" class="ui-icon ui-icon-alert"></span>';
	
		foreach($error as $e)
		{
			$out .= qs($e).'<br />';
		}
	
		$out .= '
		</div>
		</div>';
	}
	
	if(count($info) > 0)
	{
		$out .= '
		<div class="ui-widget pageblock">
		<div class="ui-state-highlight ui-corner-all ui-padding">
		<p><span style="float: left; margin-right: .3em;" class="ui-icon ui-icon-info"></span>';
	
		foreach($info as $i)
		{
			$out .= qs($i).'<br />';
		}
	
		$out .= '
		</div>
		</div>';
	}
	
	return $out;
}



function v_linkrow($href,$rows = array())
{
	$out = '
	<a class="linkrow ui-corner-all" href="'.$href.'">';

	
	foreach ($rows as $r)
	{
		$style = '';
		if(isset($r['width']))
		{
			$style = ' style="width:'.$r['width'].'px;"';
		}
		$out .= '<span class="item" '.$style.'>'.qs($r['name']).'</span>';
	}
	$out .= '<span style="clear:both;display:block;"></span></a>';
	
	return $out;
}

function v_multimap($adress)
{
	addScript('http://maps.google.com/maps/api/js?sensor=false&amp;language=de');
	addScript('/js/gmap/gmap.js');
	
	$id = id('multimap');
	
	$js = array();
	foreach ($adress as $a)
	{
		$js[] = '{address:"'.$a['anschrift'].', '.$a['plz'].'", data:"'.$a['name'].'"}';
	}
	
	addJs('
		$("#'.$id.'").gmap3({
  map:{
    options: {
		zoom: 5
	} 
  },
  marker:{
    values:[
      '.implode(',', $js).'
    ],
    options:{
      draggable: false
    },
    events:{
      mouseover: function(marker, event, context){
        var map = $(this).gmap3("get"),
          infowindow = $(this).gmap3({get:{name:"infowindow"}});
        if (infowindow){
          infowindow.open(map, marker);
          infowindow.setContent(context.data);
        } else {
          $(this).gmap3({
            infowindow:{
              anchor:marker,
              options:{content: context.data}
            }
          });
        }
      },
      mouseout: function(){
        var infowindow = $(this).gmap3({get:{name:"infowindow"}});
        if (infowindow){
          infowindow.close();
        }
      }
    }
  }},"autofit");
	');
	
	return '<div class="map" id="'.$id.'"></div>';
}

function v_map($adress,$option = array())
{
	addScript('http://maps.google.com/maps/api/js?sensor=false&amp;language=de');
	addScript('/js/gmap/gmap.js');
	
	$id = id('map');
	
	addJs('
		$("#'.$id.'").gmap3({
			 map: {
			    options: {
			      maxZoom: 15
			    } 
			 },
			 marker:{
			    address: "'.$adress['anschrift'].', '.$adress['plz'].'",
			    options: {
			     icon: new google.maps.MarkerImage(
			       "img/alnatura.png",
			       new google.maps.Size(90, 79, "px", "px")
			     )
			    }
			 }
			},
			"autofit" );
	');
	
	$width = '';
	if(isset($option['width']))
	{
		$width = ' style="width:'.$option['width'].'px"';
	}
	
	return '<div'.$width.' class="map" id="'.$id.'"></div>';
}

function v_header($title)
{
	return '
	<div class="head ui-widget-header ui-corner-all">'.qs($title).'</div>';
	// 
}

function buttonset($buttons = array())
{
	$id = id('buttonset');
	$out = '
	<div id="'.$id.'">';
	
	$i=0;
	foreach($buttons as $b)
	{
		$i++;
		$bid = makeId($b['name']);
		$out .= '
		<a href="#" id="'.$id.'-'.$bid.'">'.$b['name'].'</a>';
	}
	
	$out .= '
	</div>';
}

function v_switch($views = array())
{
	$out = '<select class="v-switch"  onchange="goTo(this.value);">
				<!--<option value="#">Ansicht:</option>-->';
	
	foreach ($views as $v)
	{
		$id = makeId($v);
		$sel = '';
		if(isset($_GET['v']) && $id == $_GET['v'])
		{
			$sel = ' selected="selected"';
		}
		$out .= '
				<option value="'.addGet('v',$id).'"'.$sel.'>'.$v.'</a>';
	}
	
	return $out.'
			</select>';
}

function v_sortswitch($fields = array())
{
	$out = '<select class="v-switch"  onchange="goTo(this.value);">
				<!--<option value="#">Ansicht:</option>-->';

	foreach ($fields as $f)
	{
		$id = makeId($v);
		$sel = '';
		if(isset($_GET['sort']) && $id == $_GET['v'])
		{
			$sel = ' selected="selected"';
		}
		$out .= '
				<option value="'.addGet('v',$id).'"'.$sel.'>'.$v.'</a>';
	}

	return $out.'
			</select>';
}

function v_tabs($content = array())
{
	
	$id = id('tabs');
	
	addJs('
	$("#'.$id.'").tabs();');
	
	$ul = '';
	$cnt = '';
	
	$i=0;
	foreach ($content as $c)
	{
		$i++;
		$ul .= '
		<li><a href="#'.$id.'-'.$i.'">'.$c['title'].'</a></li>';
		
		$cnt .= '
		<div id="'.$id.'-'.$i.'">
			'.$c['content'].'
		</div>';
	}
	
	$out = '
	<div id="'.$id.'" class="margin-bottom">
		<ul>'.$ul.'
		</ul>
		'.$cnt.'
	</div>';
	
	return $out;
}



function v_form_section_start($steps)
{
	$out = '
	<ul>';
	foreach ($steps as $i => $s)
	{
		$out .= '
		<li><a href="#step-'.$i.'">
                <label class="stepNumber">'.$i.'</label>
                <span class="stepDesc">
                   '.$s['title'].'<br />
                   <small>'.$s['desc'].'</small>
                </span>
			</a></li>';
	}
	$out .= '
	</ul>';
}

function v_form_section($step,$elements = array(),$id)
{
	$out = '
	<div id="step-'.$step.'" class="v-form">';
	foreach ($elements as $e)
	{
		$out .= $e;
	}
	
	$out .= '<input onclick="accordionNext(\''.$id.'\','.count($elements).');" type="button" value="'.s('continue').'" class="button ui-button ui-widget ui-state-default ui-corner-all" role="button" aria-disabled="false" />';
	
	$out .= '
	</div>';
	
	return $out;
}



function v_getStatusAmpel($status)
{
	$out = '';
	switch ($status)
	{
		case 1 : $out = '<span class="hidden">1</span><a href="#" onclick="return false;" title="Es besteht noch kein Kontakt" class="ampel ampel-grau"><span>&nbsp;</span></a>'; break;
		case 2 : $out = '<span class="hidden">2</span><a href="#" onclick="return false;" title="Verhandlungen laufen" class="ampel ampel-gelb"><span>&nbsp;</span></a>'; break;
		case 3 : $out = '<span class="hidden">3</span><a href="#" onclick="return false;" title="Betrieb koorperiert bereits" class="ampel ampel-gruen"><span>&nbsp;</span></a>'; break;
		case 5 : $out = '<span class="hidden">3</span><a href="#" onclick="return false;" title="Betrieb koorperiert bereits" class="ampel ampel-gruen"><span>&nbsp;</span></a>'; break;
		case 4 : $out = '<span class="hidden">4</span><a href="#" onclick="return false;" title="Will nicht koorperieren" class="ampel ampel-rot"><span>&nbsp;</span></a>'; break;
	}
	
	return $out;
}


?>
