<?php

	require_once(CONTENT . '/content.systempreferences.php');
	require_once(TOOLKIT . '/class.sectionmanager.php');
	require_once(TOOLKIT . '/class.entrymanager.php');

	class contentExtensionCustomPreferencesPreferences extends AdministrationPage {

		var $currentsection;

		public function __construct(&$parent){
			parent::__construct($parent);
			$this->setPageType('form');
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Custom Preferences'))));
		}

		## Sections must be both static and hidden

		private function __getSections() {
			return Symphony::Database()->fetch("
				SELECT * from `tbl_sections`
				WHERE `static` = 'yes' AND `hidden` = 'yes'
			");
		}

		## Creates a fieldset for each `Publish Tabs` field.
		## If there's no one, an "Untitled" fieldset is created.

		private function __makeFieldsetLabel($field, $entry) {
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', $field->get('label')));

			return $group;
		}

		private function __makeFieldsetContent($field, $entry, $group) {
			$div = new XMLElement('div', NULL, array(
				'id' => $field->get('id'),
				'class' => 'field field-'.$field->handle().($field->get('required') == 'yes' ? ' required' : '')
			));

			$field->displayPublishPanel(
				$div,
				$fields ? $fields[$field->get('element_name')] : $entry->getData($field->get('id')),
				(isset($this->_errors[$field->get('id')]) ? $this->_errors[$field->get('id')] : NULL),
				null, null, (is_numeric($entry->get('id')) ? $entry->get('id') : NULL)
			);

			$group->appendChild($div);
		}

		public function view() {
			$this->_Parent->Page->addStylesheetToHead(URL . '/extensions/publish_tabs/assets/publish-tabs.css', 'screen', 200);
			$this->_Parent->Page->addStylesheetToHead(URL . '/extensions/custompreferences/assets/custompreferences.css', 'screen', 200);

			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));
			
			if ($formHasErrors) {
				$this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);
			
			} else if (isset($this->_context[1]) && $this->_context[1] == 'saved') {
				$this->pageAlert(__('Preferences saved.'), Alert::SUCCESS);
			}

			$sections = $this->__getSections();
			$currentsection = isset($this->_context[0]) ? $this->_context[0] : $sections[0]['id'];

			$menu = new XMLElement('ul', NULL, array('class' => 'tabs staticsections', 'id' => 'publish-tabs-controls'));

			foreach ($sections as $s) {
				$link = new XMLElement('a', $s['name'], array(
					'href' => URL . '/symphony/extension/custompreferences/preferences/' . $s['id'],
				));

				$attrs = $s['id'] == $currentsection ? array('class' => 'selected') : array();

				$listitem = new XMLElement('li', NULL, $attrs);
				$listitem->appendChild($link);

				$menu->appendChild($listitem);
			}

			$this->Form->appendChild($menu);

			$sectionManager = new SectionManager($this->_Parent);
			$fields = $sectionManager->fetch($currentsection)->fetchFields();

			$entryManager = new EntryManager($this->_Parent);
			$entry = $entryManager->fetch(NULL, $currentsection);
			$entry = $entry[0];

			if ($fields) {

				if (isset($entry)) {
					if (isset($_POST['fields'])) {
						$postfields = $_POST['fields'];

						$e =& $entryManager->create();
						$e->set('section_id', $entry->get('section_id'));
						$e->set('id', $entry->get('id'));

						$e->setDataFromPost($postfields, $error, true);
						$entry = $e;
					}
				} else {
					if (isset($_POST['fields'])) {
						$entry = $entryManager->create();
						$entry->set('section_id', $section_id);
						$entry->setDataFromPost($_POST['fields'], $error, true);
					} else {
						$entry = $entryManager->create();
						$entry->set('section_id', $section_id);
					}
				}

				$group = null;

				foreach ($fields as $f) {
					$type = $f->get('type');

					if ($group == null && $type != 'publish_tabs') {
						$group = new XMLElement('fieldset');
						$group->setAttribute('class', 'settings');
						$group->appendChild(new XMLElement('legend', __('Untitled')));
						$this->Form->appendChild($group);
					}

					if ($type == 'publish_tabs') {
						$group = $this->__makeFieldsetLabel($f, $entry);
						$this->Form->appendChild($group);
					 } else
						$this->__makeFieldsetContent($f, $entry, $group);
				}

			} else {
				$message = new XMLElement('p', __(
					'It looks like you\'re trying to create an entry. Perhaps you want fields first? <a href="%s">Click here to create some.</a>',
					array(
						URL . '/symphony/blueprints/sections/edit/' . $currentsection . '/'
					)
				));
				$this->Form->appendChild($message);
			}

			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			
			$attr = array('accesskey' => 's');
			$div->appendChild(Widget::Input('action[save]', __('Save Changes'), 'submit', $attr));

			$this->Form->appendChild($div);

		}

		public function action() {
			if (isset($_POST['action']['save'])) {

				if (!is_array($this->_errors) || empty($this->_errors)) {

					$sections = $this->__getSections();
					$currentsection = isset($this->_context[0]) ? $this->_context[0] : $sections[0]['id'];

					$entryManager = new EntryManager($this->_Parent);
					$entry = $entryManager->fetch(NULL, $currentsection);
					$entry = $entry[0];

					if (isset($entry)) {
						$post = General::getPostData();
						$fields = $post['fields'];
					} else {
						$entry =& $entryManager->create();
						$entry->set('section_id', $currentsection);
						$entry->set('author_id', $this->_Parent->Author->get('id'));
						$entry->set('creation_date', DateTimeObj::get('Y-m-d H:i:s'));
						$entry->set('creation_date_gmt', DateTimeObj::getGMT('Y-m-d H:i:s'));

						$fields = $_POST['fields'];
					}

#					## Combine FILES and POST arrays, indexed by their custom field handles
#					if(isset($_FILES['fields'])){
#						$filedata = General::processFilePostData($_FILES['fields']);

#						foreach($filedata as $handle => $data){
#							if(!isset($fields[$handle])) $fields[$handle] = $data;
#							elseif(isset($data['error']) && $data['error'] == 4) $fields['handle'] = NULL;
#							else{

#								foreach($data as $ii => $d){
#									if(isset($d['error']) && $d['error'] == 4) $fields[$handle][$ii] = NULL;
#									elseif(is_array($d) && !empty($d)){

#										foreach($d as $key => $val)
#											$fields[$handle][$ii][$key] = $val;
#									}
#								}
#							}
#						}
#					}

					if(__ENTRY_FIELD_ERROR__ == $entry->checkPostData($fields, $this->_errors)) {
						$this->pageAlert(__('Some errors were encountered while attempting to save.'), Alert::ERROR);

					} elseif(__ENTRY_OK__ != $entry->setDataFromPost($fields, $error)) {
						$this->pageAlert($error['message'], Alert::ERROR);

					} else {

						###
						# Delegate: EntryPreEdit
						# Description: Just prior to editing of an Entry.
						$this->_Parent->ExtensionManager->notifyMembers('EntryPreEdit', '/publish/edit/', array('section' => $section, 'entry' => &$entry, 'fields' => $fields));

						if(!$entry->commit()) {
							define_safe('__SYM_DB_INSERT_FAILED__', true);
							$this->pageAlert(NULL, Alert::ERROR);
						} else {

							###
							# Delegate: EntryPostEdit
							# Description: Editing an entry. Entry object is provided.
							$this->_Parent->ExtensionManager->notifyMembers('EntryPostEdit', '/publish/edit/', array('section' => $section, 'entry' => $entry, 'fields' => $fields));


							$prepopulate_field_id = $prepopulate_value = NULL;
							if(isset($_POST['prepopulate'])){
								$prepopulate_field_id = array_shift(array_keys($_POST['prepopulate']));
								$prepopulate_value = stripslashes(rawurldecode(array_shift($_POST['prepopulate'])));
							}

							redirect(sprintf(
								'%s/symphony/extension/custompreferences/preferences/%s/saved/',
								URL,
								$currentsection
							));

						}

					}

				}
			}
		}

	}
	
?>
