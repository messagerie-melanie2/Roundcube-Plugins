<?php
/**
 * Plugin Melanie2 Applications
*
* plugin melanie2 pour lister et configurer les app de roundcube
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

class melanie2_applications extends rcube_plugin
{
    /**
     *
     * @var string
     */
    public $task = '.*';

    /**
     * (non-PHPdoc)
     * @see rcube_plugin::init()
     */
    function init()
    {
        $rcmail = rcmail::get_instance();

        // use jQuery for draggable item
        $this->require_plugin('jqueryui');
        // Chargement de la conf
        $this->load_config();
        $this->add_texts('localization/', false);

        // Ajout du css
        $this->include_stylesheet($this->local_skin_path().'/melanie2_applications.css');

        // ajout de la tache
        $this->register_task('applications');
        // Ajout du bouton dans la taskbar
        $this->add_button(array(
            'command' => 'apps',
            'class'	=> 'button-melanie2_applications',
            'classsel' => 'button-melanie2_applications button-selected',
            'innerclass' => 'button-inner',
            'label'	=> 'melanie2_applications.task',
        ), 'taskbar');
        // Ajout des div dans la taskbar
        $this->api->add_content(
                html::tag('div', array("class" => "applications_list_m2", "id" => "applications_list_m2"),
                        html::tag('div', array("class" => "double app_list"),
                                html::tag('ul', array(),
                                    ""
                                ) .
                                html::tag('div', array("class" => "drop_add_toolbar droppable"), $this->gettext('Drop to add to toolbar'))
                        )
                ) .
                html::tag('div', array("class" => "applications_list_m2_tips", "id" => "applications_list_m2_tips"),
                        html::tag('div', array("class" => "applications_tips"),
                            $this->gettext('Drop on button to add to applications list')
                        )
                ),
        'taskbar');
        // Ajout des applications externes dans la taskbar
        $others_applications = $rcmail->config->get('others_applications');
        $is_internal = $this->is_internal();
        $i = 0;
        if (isset($others_applications)
                && is_array($others_applications)) {
            foreach($others_applications as $app) {
                if (isset($app['name'])) $name = $app['name'];
                else $name = $this->gettext('noname');
                if ($is_internal) {
                    if (!empty($app['internal_url'])) $url = $app['internal_url'];
                    else $url = '';
                    if (!empty($app['internal_icon'])) $icon = $app['internal_icon'];
                    else $icon = '';
                } else {
                    if (!empty($app['external_url'])) $url = $app['external_url'];
                    else $url = '';
                    if (!empty($app['external_icon'])) $icon = $app['external_icon'];
                    else $icon = '';
                }
                if (!empty($url)) {
	                	// Ajout des boutons
	                	$this->api->add_content(
	                			html::tag('a', array("id" => "rcmbtn31$i", "class" => "button-".strtolower($name), "target" => "_blank", "href" => "$url", "style" => "position: relative;"),
	                					html::tag('span', array("class" => "button-inner", "style" => "background: url($icon) 0px 0px no-repeat;"),
	                							$name
	                							)
	                					),
	                			'taskbar');
	                	$i++;
                }
            }
        }
        
        $rcmail->output->set_env('applications_list', $this->update_applications_list($rcmail->config->get('applications_list')));
        $rcmail->output->set_env('applications_ignore', $rcmail->config->get('applications_ignore'));

        // Appel le script de gestion des applications
        $this->include_script('applications.js');

        $this->register_action('modify_applications_order', array($this, 'modify_applications_order'));
    }
    /**
     * Enregistre l'ordre de la liste dans les paramètres de l'utilisateur
     */
    function modify_applications_order()
    {
        $applications_list = get_input_value('_applications_list', RCUBE_INPUT_POST);
        if (isset($applications_list)
                && is_array($applications_list)) {
            $applications_list['version'] = 2;
            rcmail::get_instance()->user->save_prefs(array('applications_list' => $applications_list));
        }
    }
    /**
     * Met à jour la liste des applications pour les utilisateurs pour les changements de versions
     * @param array $applications_list
     * @return array
     */
    private function update_applications_list($applications_list) {
    	if (!isset($applications_list['version'])) {
    		$applications_list['version'] = 1;
    	}
    	
    	switch ($applications_list['version']) {
    		case 1:
    			// Update applications list to v2
    			$applications_list[] = "button-jitsi";
    			$applications_list = array_unique($applications_list);
    			break;
    	}
    	unset($applications_list['version']);
    	return $applications_list;
    }
    
    /**
     * Défini si on est dans une instance interne ou extene de l'application
     * Permet la selection de la bonne url
     */
    private function is_internal() {
        return (!isset($_SERVER["HTTP_X_MINEQPROVENANCE"]) || strcasecmp($_SERVER["HTTP_X_MINEQPROVENANCE"], "intranet") === 0);
    }
}