<?php
/**
 * Melanie2 Labels
 *
 * Plugin de gestion des labels thunderbird pour Roundcube Webmail
 * Basé sur thunderbird_labels de Michael Kefeder (http://code.google.com/p/rcmail-thunderbird-labels/)
 * Permet d'afficher les 5 labels de base et de configurer de nouveau label pour imiter la configuration Thunderbird
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */
class melanie2_labels extends rcube_plugin
{
    /**** PRIVATE ***/
    /**
     * Liste des flags imap génériques
     * @var array
     */
    private $generic_flags = array(
        'undeleted',
        'deleted',
        'seen',
        'unseen',
        'flagged',
        'unflagged',
        'answered',
        'draft',
        'mdnsent',
        'nonjunk',
        'forwarded',
        'recent',
    );
    /**
     * Variable de mapping
     * @var string
     */
    private $map;
    /**
     * @var rcube
     */
    private $rc;
    /**
     * @var boolean
     */
    private $header_loaded = false;

    /**** PUBLIC ***/
    /**
     * Task courante pour le plugin
     * @var string
     */
	public $task = 'mail|settings';

	/**
	 * Initialisation du plugin
	 * @see rcube_plugin::init()
	 */
	function init()
	{
		$this->rc = rcmail::get_instance();

		// Ajout de la localization du plugin
		$this->add_texts('localization/', true);
		// Chargement de la conf
		$this->load_config();

		if ($this->rc->task == 'mail') {
		    // disable plugin when printing message
		    if ($this->rc->action == 'print'
		            || $this->rc->action == 'compose')
		        return;

		    // Ajoute le script javascript
		    $this->include_script('melanie2_label.js');
		    // Ajout du css
		    $this->include_stylesheet($this->local_skin_path() . '/tb_label.css');

		    // Configuration des hooks
		    $this->add_hook('messages_list', array($this, 'read_flags'));
		    $this->add_hook('message_load', array($this, 'read_single_flags'));
		    $this->add_hook('template_object_messageheaders', array($this, 'color_headers'));
		    $this->add_hook('render_page', array($this, 'tb_label_popup'));

		    // additional TB flags
		    $this->message_tb_labels = array();
		    $this->add_tb_flags = array();
		    $labels_name = array();
		    $labels_color = array();
		    foreach ($this->rc->config->get('labels_list', array()) as $label_key => $label_value) {
		        //$label_key = strtolower($label_key);
		        if (strpos($label_key, 'LABEL') === 0) {
		            $name = '$'.ucfirst($label_key);
		        } else {
		            $name = $label_key;
		        }
		        $this->add_tb_flags[$label_key] = $name;
		        $label_key = strtolower($label_key);
		        if (isset($label_value['default'])
		                && $label_value['default']) {
		            $labels_name[$label_key] = $this->gettext($label_value['name']);
		        } else {
		            $labels_name[$label_key] = $label_value['name'];
		        }
		        if (isset($label_value['color'])) {
		            $labels_color[$label_key] = $label_value['color'];
		        }
		    }

		    $this->rc->output->set_env('labels_translate', $labels_name);
		    $this->rc->output->set_env('labels_color', $labels_color);

		    // Ajoute le bouton en fonction de la skin
		    if ($this->rc->config->get('ismobile', false)) {
		        // Ajout du bouton dans la toolbar
		        $this->add_button(array(
    		            'command' => 'plugin.thunderbird_labels.rcm_tb_label_submenu',
    		            'id' => 'tb_label_popuplink',
    		            'title' => 'label', # gets translated
    		            'domain' => $this->ID,
    		            'type' => 'link',
    		            'content' => Q($this->gettext('labels')), # maybe put translated version of "Labels" here?
    		            'class' => 'mark_as_read icon ui-link ui-btn ui-shadow ui-corner-all ui-icon-location ui-btn-icon-left',
    		        ),
	                'toolbar_mobile'
		        );
		    }
		    else {
		        // Ajout du bouton dans la toolbar
		        $this->add_button(array(
    		            'command' => 'plugin.thunderbird_labels.rcm_tb_label_submenu',
    		            'id' => 'tb_label_popuplink',
    		            'title' => 'label', # gets translated
    		            'domain' => $this->ID,
    		            'type' => 'link',
    		            'content' => Q($this->gettext('labels')), # maybe put translated version of "Labels" here?
    		            'class' => ($this->rc->config->get('skin') == 'larry' || $this->rc->config->get('skin') == 'melanie2_larry') ? 'button thunderbird' : 'tb_noclass',
    		        ),
	                'toolbar'
		        );
		    }


		    // Register action
		    $this->register_action('plugin.thunderbird_labels.set_flags', array($this, 'set_flags'));
		    $this->register_action('plugin.thunderbird_labels.update_list_labels', array($this, 'update_list_labels'));
		}
		else if ($this->rc->task == 'settings') {
		    // Ajout du css
		    $this->include_stylesheet($this->local_skin_path() . '/tb_label.css');

 		    $this->add_hook('preferences_list', array($this, 'preferences_list'));
 		    $this->add_hook('preferences_save', array($this, 'preferences_save'));
 		    $this->add_hook('preferences_sections_list', array($this, 'preferences_sections_list'));
		}
	}

	/**
	 * Affichage de la gestion des labels dans le contextmenu
	 * @param array $args
	 */
	public function show_tb_label_contextmenu($args)
	{
		$li = html::tag('li', array('class' => 'submenu'), Q($this->gettext('label')) . $this->_gen_label_submenu($args, 'tb_label_ctxm_submenu'));
		$out .= html::tag('ul', array('id' => 'tb_label_ctxm_mainmenu'), $li);
		$this->api->output->add_footer(html::div(array('style' => 'display: none;'), $out));
	}

	/**
	 * Génération de la liste des labels pour le contextmenu
	 * @param array $args
	 * @param string $id
	 * @return string html
	 */
	private function _gen_label_submenu($args, $id)
	{
		$out = ''; $i = 0;
		foreach ($this->rc->config->get('labels_list', array()) as $label_key => $label_value) {
		    if (isset($label_value['list'])
		            && !$label_value['list']) {
		        continue;
		    }
		    $separator = ($i == 0) ? ' separator_below' : '';
		    $class = 'label_'.strtolower($label_key);
		    if (isset($label_value['default'])
		            && $label_value['default']) {
		        if (isset($label_value['command'])) {
		            $text = $label_value['command'] . ' ' . $this->gettext($label_value['name']);
		        } else {
		            $text = $this->gettext($label_value['name']);
		        }

		    } else {
		        $text = $label_value['name'];
		    }
		    $out .= '<li id="'.$label_key.'" class="'.$class.$separator.' ctxm_tb_label"><a href="#ctxm_tb_label" class="active" onclick="rcmail_ctxm_label_set('.strtolower($label_key).')">'.$text.'</a></li>';
		    $i++;
		}
		$out = html::tag('ul', array('class' => 'popupmenu toolbarmenu folders', 'id' => $id), $out);
		return $out;
	}

	/**
	 * Affichage des flags au chargement des messages
	 * @param array $args
	 */
	public function read_single_flags($args)
	{
		if (!$this->rc->config->get('show_labels', false)
		        || !isset($args['object']))
			return;

		if (is_array($args['object']->headers->flags)) {
			$this->message_tb_labels = array();
			foreach ($args['object']->headers->flags as $flagname => $flagvalue) {
			    $flag = is_numeric("$flagvalue") ? $flagname : $flagvalue; // for compatibility with < 0.5.4
			    $flag = strtolower($flag);
			    if (!in_array($flag, $this->generic_flags)) {
			        $this->message_tb_labels[] = "'".$flag."'";
			    }
			}
		}
		// no return value for this hook
	}

	/**
	 * Writes labelnumbers for single message display
	 * Coloring of Message header table happens via Javascript
	 * @param array $p
	 */
	public function color_headers($p)
	{
	    if (!$this->header_loaded) {
	        // always write array, even when empty
	        $p['content'] .= '<script type="text/javascript">
        		var tb_labels_for_message = ['.join(',', $this->message_tb_labels).'];
    	    	</script>';
	        $this->header_loaded = true;
	    }
		return $p;
	}

    /**
     * Lecture des flags du messages
     * @param array $args
     * @return array
     */
	public function read_flags($args)
	{
		// dont loop over all messages if we dont have any highlights or no msgs
		if (!$this->rc->config->get('show_labels', false)
		        || !isset($args['messages'])
		        || !is_array($args['messages']))
			return $args;

		$flags_list = $this->rc->config->get('labels_list', array());
		$flags_list_change = false;
		// Doit on ajouter les labels au messages ?
		$list_cols   = $this->rc->config->get('list_cols');
		$show_labels = false;
		if (in_array('labels', $list_cols)) {
		    $show_labels = true;
		}
		// loop over all messages and add $LabelX info to the extra_flags
		foreach($args['messages'] as $message) {
		    if ($show_labels)
		        $message->labels = "";
			$message->list_flags['extra_flags']['tb_labels'] = array(); // always set extra_flags, needed for javascript later!
			if (is_array($message->flags)) {
    			foreach ($message->flags as $flagname => $flagvalue) {
    				$flag = is_numeric("$flagvalue") ? $flagname : $flagvalue; // for compatibility with < 0.5.4
    			    if ($show_labels && !in_array(strtolower($flag), $this->generic_flags)) {
    			        $message->list_flags['extra_flags']['tb_labels'][] = strtolower($flag);

    			        if (!isset($flags_list[$flag])) {
    			            $flags_list[$flag] = array(
    			                'name' => strtolower($flag));
    			            $flags_list_change = true;
    			        }

    				    if (isset($flags_list[$flag]['default'])
    				            && $flags_list[$flag]['default']) {
    				        if ($message->labels != "") $message->labels .= ", ";
				            $message->labels .= $this->gettext($flags_list[$flag]['name']);
    				    } else {
    				        if ($message->labels != "") $message->labels .= ", ";
    				        $message->labels .= $flags_list[$flag]['name'];
    				    }
    				}
    			}
			}
		}
		// Enregistre la liste des labels dans les preferences si elle a changé
		if ($flags_list_change) {
		    $flags_list = $this->order_labels_list($flags_list);
		    $this->rc->user->save_prefs(array(
                    'labels_list' => $flags_list,
            ));
		}
		return($args);
	}

	/**
	 * Modification des flags pour un message
	 * @return boolean
	 */
	function set_flags()
	{
		$imap = $this->rc->imap;
		$cbox = rcube_utils::get_input_value('_cur', RCUBE_INPUT_GET);
		$mbox = rcube_utils::get_input_value('_mbox', RCUBE_INPUT_GET);
		$toggle_label = rcube_utils::get_input_value('_toggle_label', RCUBE_INPUT_GET);
		$flag_uids = rcube_utils::get_input_value('_flag_uids', RCUBE_INPUT_GET);
		$flag_uids = explode(',', $flag_uids);
		$unflag_uids = rcube_utils::get_input_value('_unflag_uids', RCUBE_INPUT_GET);
		$unflag_uids = explode(',', $unflag_uids);

		$imap->conn->flags = array_merge($imap->conn->flags, $this->add_tb_flags);

		if (melanie2_logs::is(melanie2_logs::TRACE)) melanie2_logs::get_instance()->log(melanie2_logs::TRACE, '[set_flags] ' . var_export(array(
		    '$cbox' => $cbox,
		    '$mbox' => $mbox,
		    '$toggle_label' => $toggle_label,
		    '$flag_uids' => $flag_uids,
		    '$unflag_uids' => $unflag_uids,
		    '$imap->conn->flags' => $imap->conn->flags,
		), true));

		if (!is_array($unflag_uids)
			    || !is_array($flag_uids))
			return false;

		$imap->set_flag($flag_uids, $toggle_label, $mbox);
		$imap->set_flag($unflag_uids, "UN$toggle_label", $mbox);

		$this->api->output->send();
	}

	/**
	 * Mise à jour de la liste des labels
	 * Appel ajax depuis le javascript
	 */
	function update_list_labels() {
	    if (melanie2_logs::is(melanie2_logs::TRACE)) melanie2_logs::get_instance()->log(melanie2_logs::TRACE, 'update_list_labels()');
	    $result = array(
	        'action' => 'plugin.thunderbird_labels.update_list_labels',
	        'html' => $this->get_tb_label_popup());
	    echo json_encode($result);
	    exit;
	}

	/**
	 * Génération du pop up contenant la liste des labels à selectionner
	 */
	function tb_label_popup()
	{
		$out = '<div id="tb_label_popup" class="popupmenu">'.$this->get_tb_label_popup().'</div>';
		$this->rc->output->add_gui_object('tb_label_popup_obj', 'tb_label_popup');
    	$this->rc->output->add_footer($out);
	}

	private function get_tb_label_popup()
	{
	    $out = '';
	    // Ajoute le menu si on n'est pas en mobile
	    if (!$this->rc->config->get('ismobile', false)) {
	        $out .= html::div('tb_label_div_manage_labels', '<a href="#" id="rcube_manage_labels" onclick="show_rcube_manage_labels()" class="active">'.$this->gettext('manage_labels').'</a>');
	    }

	    $out .= '<ul class="toolbarmenu">';
	    $out .= '<li id="label0" class="label0 click0"><a href="#">0 '.$this->gettext('label0').'</a></li>';
	    $i = 0;
	    foreach ($this->rc->config->get('labels_list', array()) as $label_key => $label_value) {
	        if (isset($label_value['list'])
	                && !$label_value['list']) {
	            continue;
	        }
	        $separator = ($i == 0) ? ' separator_below' : '';
	        $class = 'label_'.strtolower($label_key);
	        if (isset($label_value['default'])
	                && $label_value['default']) {
	            if (strpos($label_key, '~') === false
	                    && $i < 9) {
	                $i++;
	                $text = $i . ' ' . $this->gettext($label_value['name']);
	                $class .= ($class == "" ? "" : " ") . "click$i";
	            } else {
	                $text = $this->gettext($label_value['name']);
	            }

	        } else {
	            if ($i < 9) {
	                $i++;
	                $text = $i . ' ' . $label_value['name'];
	                $class .= ($class == "" ? "" : " ") . "click$i";
	            } else {
	                $text = $label_value['name'];
	            }
	        }
	        $out .= '<li id="'.$label_key.'" class="'.$class.' separator_below"><a href="#">'.$text.'</a></li>';
	    }

	    $out .= '</ul>';

	    return $out;
	}

	/**
	 * Handler for preferences_list hook.
	 * Adds options blocks into Labels settings sections in Preferences.
	 *
	 * @param array Original parameters
	 * @return array Modified parameters
	 */
	public function preferences_list($p)
	{
	    if ($p['section'] != 'labels') {
	        return $p;
	    }
	    $p['blocks']['show_labels']['name'] = $this->gettext('labels list');
	    $labels_list = '';
	    $i = 0;

	    foreach ($this->rc->config->get('labels_list', array()) as $label_key => $label_value) {
	        $field_class = 'rcmfd_label_' . $label_key;

	        if (isset($label_value['color'])) {
	            $color = $label_value['color'];
	        } else {
	            $color = "";
	        }

	        $label_color = new html_inputfield(array('name' => "_colors[$label_key]", 'class' => "$field_class colors", 'size' => 6));
	        if (isset($label_value['default'])
	                && $label_value['default']) {
	            $name = $this->gettext($label_value['name']);
	            $label_name  = new html_inputfield(array('name' => "_labels[$label_key]", 'disabled' => true,  'class' => $field_class, 'size' => 30));
	            $labels_list .= html::div(null, $hidden . $label_name->show($name) . '&nbsp;' . $label_color->show($color));
	        } else {
	            $name = $label_value['name'];
	            $label_name  = new html_inputfield(array('name' => "_labels[$label_key]", 'class' => $field_class, 'size' => 30, 'disabled' => $this->driver->categoriesimmutable));
	            $label_remove = new html_inputfield(array('type' => 'button', 'value' => 'X', 'class' => 'button', 'onclick' => '$(this).parent().remove()', 'title' => $this->gettext('remove_label')));
	            $labels_list .= html::div(null, $hidden . $label_name->show($name) . '&nbsp;' . $label_color->show($color) . '&nbsp;' . $label_remove->show());
	        }

	    }

	    $p['blocks']['show_labels']['options']['labels_list']['content'] = html::div(array('id' => 'labelslist'), $labels_list);

	    $field_id = 'rcmfd_new_label';
	    $new_label = new html_inputfield(array('name' => '_new_label', 'id' => $field_id, 'size' => 30));
	    $add_label = new html_inputfield(array('type' => 'button', 'class' => 'button', 'value' => $this->gettext('add_label'),  'onclick' => "rcube_label_add_label()"));
	    $p['blocks']['show_labels']['options']['new_label'] = array(
	        'content' => $new_label->show('') . '&nbsp;' . $add_label->show(),
	    );


	    $p['blocks']['thunderbirs_settings']['name'] = $this->gettext('thunderbird_settings');
	    $p['blocks']['thunderbirs_settings']['options']['informations']['content'] = html::div(null, $this->gettext('thunderbird_settings_infos'));
	    $thunderbird_settings = new html_textarea(array('name' => '_thunderbird_settings', 'id' => 'rcmfd_label_thunderbird_settings', 'cols' => 70, 'rows' => 15));
	    $p['blocks']['thunderbirs_settings']['options']['textarea']['content'] = $thunderbird_settings->show();

	    $this->rc->output->add_script('function rcube_label_add_label() {
          var name = $("#rcmfd_new_label").val();
          var label = name.toUpperCase().replace(/ /g, "_");
          if (name.length) {
            var input = $("<input>").attr("type", "text").attr("name", "_labels["+label+"]").attr("size", 30).val(name);
            var color = $("<input>").attr("type", "text").attr("name", "_colors["+label+"]").attr("size", 6).addClass("colors").val("");
            var button = $("<input>").attr("type", "button").attr("value", "X").addClass("button").click(function(){ $(this).parent().remove() });
            $("<div>").append(input).append("&nbsp;").append(color).append("&nbsp;").append(button).appendTo("#labelslist");
            color.miniColors({ colorValues:([]) });
            $("#rcmfd_new_label").val("");
          }
        }');

	    // include color picker
	    $this->include_script('lib/js/jquery.miniColors.min.js');
	    $this->include_stylesheet($this->local_skin_path() . '/jquery.miniColors.css');
	    $this->rc->output->add_script('$("input.colors").miniColors({ colorValues:([]) })', 'docready');
	    return $p;
	}

	/**
	 * Handler for preferences_save hook.
	 * Executed on Labels settings form submit.
	 *
	 * @param array Original parameters
	 * @return array Modified parameters
	 */
	public function preferences_save($p)
	{
	    if ($p['section'] == 'labels') {
	        $labels_list = $this->rc->config->get('labels_list', array());

	        $thunderbird_settings = rcube_utils::get_input_value('_thunderbird_settings', RCUBE_INPUT_POST);
	        if (!empty($thunderbird_settings)) {
	            $thunderbird_settings = explode("\r\n", $thunderbird_settings);
	            $new_label_list = array();
	            foreach($thunderbird_settings as $setting) {
	                if (strpos($setting, 'user_pref') === 0) {
	                    $setting = str_replace('user_pref(', '', $setting);
	                    $setting = str_replace('mailnews.tags.', '', $setting);
	                    $setting = str_replace(');', '', $setting);
	                    $setting = explode(',', $setting);
	                    if (count($setting) == 2) {
	                        $setting_name = trim($setting[0], ' "\'');
	                        $setting_value = trim($setting[1], ' "\'');
	                        if (strpos($setting_name, '.color') !== false) {
	                            $setting_type = 'color';
	                            $setting_name = strtoupper(trim(str_replace('.color', '', $setting_name)));
	                        } else if (strpos($setting_name, '.tag') !== false) {
	                            $setting_type = 'tag';
	                            $setting_name = strtoupper(trim(str_replace('.tag', '', $setting_name)));
	                        }
	                        $setting_name = str_replace('$', '', $setting_name);
	                        if (isset($labels_list[$setting_name])
	                                && isset($labels_list[$setting_name]['default'])
	                                && $labels_list[$setting_name]['default']) {
	                            continue;
	                        }
                            if (!isset($new_label_list[$setting_name])) {
                                $new_label_list[$setting_name] = array();
                            }
    	                    if ($setting_type == 'tag') {
    	                        $new_label_list[$setting_name]['name'] = $setting_value;
    	                    } else if ($setting_type == 'color') {
    	                        $new_label_list[$setting_name]['color'] = $setting_value;
    	                        // Ajoute le nom au cas ou
    	                        if (!isset($new_label_list[$setting_name]['name'])) {
    	                            $new_label_list[$setting_name]['name'] = strtolower($setting_name);
    	                        }
    	                    }
	                    }
	                }
	            }
	            // Supprime toutes les étiquettes créées par l'utilisateur
	            foreach ($labels_list as $label_key => $label_value) {
	                if (!isset($label_value['default'])
	                        || !$label_value['default']) {
	                    unset($labels_list[$label_key]);
	                }
	            }
	            // Merge les array
	            $labels_list = array_merge($labels_list, $new_label_list);
	        } else {
    	        $labels = (array) rcube_utils::get_input_value('_labels', RCUBE_INPUT_POST);
    	        $colors     = (array) rcube_utils::get_input_value('_colors', RCUBE_INPUT_POST);



    	        // Parcour les labels pour ajouter les nouveaux
    	        foreach ($labels as $key => $label) {
    	            if (!isset($labels_list[$key])
    	                    || isset($labels_list[$key])
    	                        && $labels_list[$key]['name'] != $label) {
    	                $labels_list[$key] = array(
    	                        'name' => $label,
    	                    );
    	            }
    	        }
    	        // Parcour les couleurs pour les modifier
    	        foreach($colors as $key => $color) {
    	            if (isset($labels_list[$key])) {
    	                if (!isset($labels_list[$key]['color'])
    	                        && !empty($color)) {
    	                    $labels_list[$key]['color'] = $color;
    	                } else if (isset($labels_list[$key]['color'])
    	                        && $labels_list[$key]['color'] != $color) {
    	                    if (empty($color)) {
    	                        unset($labels_list[$key]['color']);
    	                    } else {
    	                        $labels_list[$key]['color'] = $color;
    	                    }
    	                }
    	            }
    	        }
    	        // Parcour la liste des labels pour supprimer ceux qui doivent l'être
    	        foreach ($labels_list as $key => $label) {
    	            if (!isset($labels[$key])
    	                    && (!isset($label['default'])
    	                            || !$label['default'])) {
    	                unset($labels_list[$key]);
    	            }
    	        }
	        }


	        $labels_list = $this->order_labels_list($labels_list);
	        $p['prefs'] = array('labels_list' => $labels_list);
	    }
	    return $p;
	}

	/**
	 * Permet de trier la list des labels de façon alphabetique
	 * @param array $labels_list
	 */
	private function order_labels_list($labels_list) {
	    $tmp_list = array();
	    $labels_list_order = array();
	    foreach ($labels_list as $label_key => $label_value) {
	        if (isset($label_value['default'])
	                && $label_value['default']) {
	            $labels_list_order[$label_key] = $label_value;
	        } else {
                $tmp_list[] = $label_key;
	        }
	    }
	    // tri la liste
	    sort($tmp_list);
	    foreach($tmp_list as $value) {
	        $labels_list_order[$value] = $labels_list[$value];
	    }

	    return $labels_list_order;
	}

	/**
	 * Handler for preferences_sections_list hook.
	 * Adds Labels settings sections into preferences sections list.
	 *
	 * @param array Original parameters
	 * @return array Modified parameters
	 */
	public function preferences_sections_list($p)
	{
	    $p['list']['labels'] = array(
	        'id' => 'labels', 'section' => $this->gettext('labels settings'),
	    );

	    return $p;
	}
}
?>
