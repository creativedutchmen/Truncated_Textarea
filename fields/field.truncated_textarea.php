<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	include_once( TOOLKIT . '/fields/field.textarea.php');
	
	Class fieldtruncated_textarea extends fieldTextarea{
	
		function __construct(&$parent){
			
			parent::__construct($parent);
			$this->_name = __('Truncated Textarea');		
			$this->_required = true;
			
			// Set default
			$this->set('show_column', 'no');
			$this->set('required', 'yes');
		}
		
		public function displaySettingsPanel(&$wrapper, $errors = null) {
		
			parent::displaySettingsPanel($wrapper, $errors);
			
			$group = new XMLElement('div', NULL, array('class' => 'group'));
			
			$div = new XMLElement('div');
			
			## Textarea truncate
			$label = Widget::Label();
			$input = Widget::Input('fields['.$this->get('sortorder').'][truncate]', $this->get('truncate'));
			$input->setAttribute('size', '3');
			$label->setValue(__('Truncate after %s characters (I will be gentle with words and html)', array($input->generate())));
			$div->appendChild($label);

			$group->appendChild($div);
			
			$wrapper->appendChild($group);
			
			$this->appendShowColumnCheckbox($wrapper);						
		}
		
		function findDefaults(&$fields){
			if(!isset($fields['size'])) $fields['size'] = 15;				
			if(!isset($fields['truncate'])) $fields['truncate'] = 150;				
		}
		
		function commit(){
			
			if(!parent::commit()) return false;
			
			$id = $this->get('id');

			if($id === false) return false;
			
			$fields = array();
			
			$fields['field_id'] = $id;
			if($this->get('formatter') != 'none') $fields['formatter'] = $this->get('formatter');
			$fields['size'] = $this->get('size');
			$fields['truncate'] = $this->get('truncate');
			
			$this->_engine->Database->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");		
			return $this->_engine->Database->insert($fields, 'tbl_fields_' . $this->handle());
			
		}
		
		public function appendFormattedElement(&$wrapper, $data, $encode = false, $mode) {			
			if($mode != 'truncated'){
				parent::appendFormattedElement($wrapper, $data, $encode = false, $mode);
			}
			else{
				
				//very hackish way of finding out what the formatted value would be.
				
				$element = new XMLElement('temp');
				parent::appendFormattedElement($element, $data, $encode = false, 'formatted');
			
				$text_element = end($element->getChildren());

				$truncated_element = new XMLElement($this->get('element_name'));
				
				
				
				$text_value = $text_element->getValue();
				$text_value = $this->truncate($text_value, $this->get('truncate'));
				$truncated_element->setValue($text_value);
				
				//the truncated element should be identical to its normal formatted brother after this, only truncated.
				$truncated_element->setAttribute('word-count', General::countWords($text_value));
				$truncated_element->setAttribute('mode', 'truncated');
				
				$wrapper->appendChild($truncated_element);
			}
		}
		
		public function fetchIncludableElements() {
			
			if ($this->get('formatter')) {
				return array(
					$this->get('element_name') . ': formatted',
					$this->get('element_name') . ': unformatted',
					$this->get('element_name') . ': truncated'
				);
			}
		
			return array(
				$this->get('element_name')
			);
		}
		
	
		/**
		 * Truncates text.
		 *
		 * Borrowed from the cakePHP framework (text helper) 
		 *
		 * Cuts a string to the length of $length and replaces the last characters
		 * with the ending if the text is longer than length.
		 *
		 * @param string  $text String to truncate.
		 * @param integer $length Length of returned string, including ellipsis.
		 * @param mixed $ending If string, will be used as Ending and appended to the trimmed string. Can also be an associative array that can contain the last three params of this method.
		 * @param boolean $exact If false, $text will not be cut mid-word
		 * @param boolean $considerHtml If true, HTML tags would be handled correctly
		 * @return string Trimmed string.
		 */
		
		function truncate($text, $length = 100, $ending = '...', $exact = false, $considerHtml = true) {
			if (is_array($ending)) {
				extract($ending);
			}
			if ($considerHtml) {
				if (mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
					return $text;
				}
				$totalLength = mb_strlen($ending);
				$openTags = array();
				$truncate = '';
				preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);
				foreach ($tags as $tag) {
					if (!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2])) {
						if (preg_match('/<[\w]+[^>]*>/s', $tag[0])) {
							array_unshift($openTags, $tag[2]);
						} else if (preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $closeTag)) {
							$pos = array_search($closeTag[1], $openTags);
							if ($pos !== false) {
								array_splice($openTags, $pos, 1);
							}
						}
					}
					$truncate .= $tag[1];

					$contentLength = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3]));
					if ($contentLength + $totalLength > $length) {
						$left = $length - $totalLength;
						$entitiesLength = 0;
						if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE)) {
							foreach ($entities[0] as $entity) {
								if ($entity[1] + 1 - $entitiesLength <= $left) {
									$left--;
									$entitiesLength += mb_strlen($entity[0]);
								} else {
									break;
								}
							}
						}

						$truncate .= mb_substr($tag[3], 0 , $left + $entitiesLength);
						break;
					} else {
						$truncate .= $tag[3];
						$totalLength += $contentLength;
					}
					if ($totalLength >= $length) {
						break;
					}
				}

			} else {
				if (mb_strlen($text) <= $length) {
					return $text;
				} else {
					$truncate = mb_substr($text, 0, $length - strlen($ending));
				}
			}
			if (!$exact) {
				$spacepos = mb_strrpos($truncate, ' ');
				if (isset($spacepos)) {
					if ($considerHtml) {
						$bits = mb_substr($truncate, $spacepos);
						preg_match_all('/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER);
						if (!empty($droppedTags)) {
							foreach ($droppedTags as $closingTag) {
								if (!in_array($closingTag[1], $openTags)) {
									array_unshift($openTags, $closingTag[1]);
								}
							}
						}
					}
					$truncate = mb_substr($truncate, 0, $spacepos);
				}
			}
			$truncate .= $ending;
			if ($considerHtml) {
				foreach ($openTags as $tag) {
					$truncate .= '</'.$tag.'>';
				}
			}
			return $truncate;
		}
	}
	
