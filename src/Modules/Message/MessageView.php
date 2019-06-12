<?php

namespace Foodsharing\Modules\Message;

use Foodsharing\Modules\Core\View;

final class MessageView extends View
{
	public function top(): string
	{
		return '
		<div class="welcome ui-padding margin-bottom ui-corner-all">

			<div class="welcome_profile_image">
				<a onclick="profile(56);return false;" href="#">
					<img width="50" height="50" src="/img/message.png" alt="' . $this->translationHelper->s('messages') . '" class="image_online">
				</a>
			</div>
			<div class="welcome_profile_name">
				<div class="user_display_name">
					' . $this->translationHelper->s('your_messages') . '
				</div>
				<div class="welcome_quick_link">

					<div class="clear"></div>
				</div>
			</div>
			<div class="welcome_profile_survived v-desktop">
				<a class="button" href="#">' . $this->translationHelper->s('new_message') . '</a>
			</div>

			<div class="clear"></div>
		</div>';
	}

	public function leftMenu(): string
	{
		return $this->menu(array(
			array('name' => $this->translationHelper->s('new_message'), 'click' => 'msg.compose();return false;')
		));
	}

	private function peopleChooser($id, $option = array())
	{
		$this->pageHelper->addJs('
			var date = new Date(); 
			tstring = ""+date.getYear() + ""+date.getMonth() + ""+date.getDate() + ""+date.getHours();
			var localsource = [];
			$.ajax({
				url: "/api/search/legacyindex",
				dataType: "json",
				success: function(json){
					
					if(json.length > 0 && json[0] != undefined && json[0].key != undefined && json[0].key == "buddies")
					{
						
						for(y=0;y<json[0].result.length;y++)
						{
							localsource.push({id:json[0].result[y].id,value:json[0].result[y].name});
						}
						
					}
				},
				complete: function(){
					$("#' . $id . ' input.tag").tagedit({
						autocompleteOptions: {
							delay: 0,
							source: function(request, response) { 
					            /* Remote results only if string > 3: */
								
								if(request.term.length > 3)
								{
									$.ajax({
						                url: "/api/search/user",
										data: {q:request.term},
						                dataType: "json",
						                success: function(data) {
											response(data);
											// following doesn\'t work somehow => ignoring
											// local = [];
											// term = request.term.toLowerCase();
											// for(i=0;i<localsource.length;i++)
											// {
											// 	if(localsource[i].value.indexOf(term) > 0)
											// 	{
											// 		local.push(localsource[i]);
											// 	}
											// }
											// response(merge(local,data,"id"));
						                }
						            });
								}
								else
								{
									response(localsource);
								}
								
					        },
							minLength: 1
						},
						allowEdit: false,
						allowAdd: false,
						animSpeed:1
					});
				}
			});
				
				var localsource = [];
		');

		$input = '<input type="text" name="' . $id . '[]" value="" class="tag input text value" />';

		return $this->v_utils->v_input_wrapper($this->translationHelper->s($id), '<div id="' . $id . '">' . $input . '</div>', $id, $option);
	}

	public function compose(): string
	{
		$content = $this->peopleChooser('compose_recipients');

		$content .= $this->v_utils->v_form_textarea('compose_body');

		$content .= $this->v_utils->v_input_wrapper(false, '<a class="button" id="compose_submit" href="#">' . $this->translationHelper->s('send') . '</a>');

		return '<div id="compose">' . $this->v_utils->v_field($content, $this->translationHelper->s('new_message'), array('class' => 'ui-padding')) . '</div>';
	}

	public function conversationList(array $conversations): string
	{
		$list = '';

		if (!empty($conversations)) {
			foreach ($conversations as $c) {
				$pics = '';
				$title = '';

				if (!empty($c['members'])) {
					$pictureWidth = 50;
					$size = 'med';

					if (count($c['members']) > 2) {
						$pictureWidth = 25;
						$size = 'mini';
						shuffle($c['members']);
					}

					foreach ($c['members'] as $m) {
						if ($m['id'] == $this->session->id()) {
							continue;
						}
						$pics .= '<img src="' . $this->imageService->img($m['photo'], $size) . '" width="' . $pictureWidth . '" />';
						$title .= ', ' . $m['name'];
					}

					if ($c['name'] === null) {
						$title = substr($title, 2);
					} else {
						$title = $c['name'];
					}

					$list .= '<li id="convlist-' . $c['id'] . '" class="unread-' . (int)$c['has_unread_messages'] . '"><a href="#" onclick="msg.loadConversation(' . $c['id'] . ');return false;"><span class="pics">' . $pics . '</span><span class="names">' . $title . '</span><span class="msg">' . $c['last_message'] . '</span><span class="time">' . $this->timeHelper->niceDate($c['last_message_at']->getTimestamp()) . '</span><span class="clear"></span></a></li>';
				}
			}
		} else {
			$list = '<li class="noconv">' . $this->v_utils->v_info($this->translationHelper->s('no_conversations')) . '</li>';
		}

		return $list;
	}

	public function conversationListWrapper(string $list): string
	{
		return $this->v_utils->v_field('<div id="conversation-list"><ul class="linklist conversation-list">' . $list . '</ul></div>', $this->translationHelper->s('conversations'), [], 'fas fa-comments');
	}

	public function conversation(): string
	{
		$out = '
			<div id="msg-conversation" class="corner-all"><ul></ul><div class="loader" style="display:none;"><i class="fas fa-sync fa-spin"></i></div></div>
		';

		$out .= '
			<div id="msg-control">
				<form>
					' . $this->v_utils->v_form_textarea('msg_answer', array('style' => 'width: 88%;', 'nolabel' => true, 'placeholder' => $this->translationHelper->s('write_something'))) . '<input id="conv_submit" type="submit" class="button" name="submit" value="&#xf0a9;" />
				</form>
			</div>';

		return '<div id="msg-conversation-wrapper" style="display:none;">' . $this->v_utils->v_field($out, '', ['class' => 'ui-padding'], 'fas fa-comment', 'msg-conversation-title') . '</div>';
	}
}
