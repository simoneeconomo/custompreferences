<?php

	require_once(TOOLKIT . '/class.extensionmanager.php');

	Class extension_CustomPreferences extends Extension{

		public function about(){
			return array('name' => 'Custom Preferences',
						 'version' => '0.1',
						 'release-date' => '2010-08-25',
						 'author' => array('name' => 'Simone Economo',
										   'website' => 'http://www.lineheight.net',
										   'email' => 'my.ekoes@gmail.com'),
						 'description' => 'Stop talking about Static Sections, Custom Preferences are come!'
				 		);
		}

		public function fetchNavigation() {
			return array(
				array(
					'location'	=> __('System'),
					'name'	=> __('Custom Preferences'),
					'link'	=> '/preferences/'
				)
			);
		}

		public function getSubscribedDelegates(){
			return array(
				array(
					'page' => '/backend/',
					'delegate' => 'AppendPageAlert', 
					'callback' => 'dependenciesCheck'
				),
				array(
					'page' => '/backend/',
					'delegate' => 'InitaliseAdminPageHead',
					'callback' => 'preferences'
				),
			);
		}

		private function __isEnabled($status) {
			return ($status != EXTENSION_NOT_INSTALLED && $status != EXTENSION_DISABLED);
		}

		public function dependenciesCheck($context) {
			$callback = $this->_Parent->getPageCallback();

			if($callback['driver'] == 'systemextensions') {

				$ExtensionManager = new ExtensionManager(Administration::instance());

				$static_section = $ExtensionManager->fetchStatus('static_section');
				$publish_tabs = $ExtensionManager->fetchStatus('publish_tabs');

				if(!$this->__isEnabled($static_section) || !$this->__isEnabled($publish_tabs)) {

					Administration::instance()->Page->Alert = new Alert(
						__('%s depends on both <code>%s</code> and <code>%s</code>. Make sure you have these extension installed and enabled.', array(__('Custom Preferences'), __('Static Section'), __('Publish Tabs'))), 
						Alert::ERROR
					);

				}
			}

		}

	}

?>
