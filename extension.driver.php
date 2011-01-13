<?php

	require_once(TOOLKIT . '/class.entrymanager.php');
	
	
	Class extension_truncated_textarea extends Extension{
	
		public function about(){
			return array('name' => 'Truncated Textarea',
						 'version' => '1.2',
						 'release-date' => '2009-12-15',
						 'author' => array('name' => 'Huib Keemink',
										   'website' => 'http://www.creativedutchmen.com',
										   'email' => 'huib@creativedutchmen.com')
				 		);
		}
		
		public function install(){
			return $this->_Parent->Database->query("CREATE TABLE `tbl_fields_truncated_textarea` (
				`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`field_id` INT( 11 ) UNSIGNED NOT NULL ,
				`formatter` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
				`size` INT( 3 ) UNSIGNED NOT NULL ,
				`truncate` INT( 5 ) UNSIGNED NULL
				)
			");
		}
		
		public function uninstall(){
			return $this->_Parent->Database->query("DROP TABLE `tbl_fields_truncated_textarea`");
		}
	}

?>
