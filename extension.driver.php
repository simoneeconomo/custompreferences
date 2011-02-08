<?php

	require_once(TOOLKIT . '/class.extensionmanager.php');

	Class extension_CustomPreferences extends Extension{

		public function about(){
			return array('name' => 'Custom Preferences',
						 'version' => '0.2.2',
						 'author' => array(
							'name' => 'Simone Economo',
							'website' => 'http://www.lineheight.net',
							'email' => 'my.ekoes@gmail.com'
						 ),
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
			);
		}

		public function dependenciesCheck($context) {
			$ExtensionManager = $this->_Parent->ExtensionManager;

			$static_section = $ExtensionManager->fetchStatus('static_section');
			$publish_tabs = $ExtensionManager->fetchStatus('publish_tabs');

			if($static_section != EXTENSION_ENABLED || $publish_tabs != EXTENSION_ENABLED) {

				Administration::instance()->Page->Alert = new Alert(
					__('<code>Custom Preferences</code> depends on both <code>%s</code> and <code>%s</code>. Make sure you have these extension installed and enabled.', array(__('Static Section'), __('Publish Tabs'))), 
					Alert::ERROR
				);

			}

		}

	}

?>
