<?php

/*!
 *  Elberos Framework
 *
 *  (c) Copyright 2019-2021 "Ildar Bikmamatov" <support@elberos.org>
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      https://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */


namespace Elberos\AlfaBank;


/* Check if Wordpress */
if (!defined('ABSPATH')) exit;


if ( !class_exists( Settings::class ) ) 
{

class Settings
{
	
	public static function show()
	{
		
		if ( isset($_POST["nonce"]) && (int)wp_verify_nonce($_POST["nonce"], basename(__FILE__)) > 0 )
		{
			$alfabank_is_test = isset($_POST['alfabank_is_test']) ? $_POST['alfabank_is_test'] : '';
			$alfabank_username = isset($_POST['alfabank_username']) ? $_POST['alfabank_username'] : '';
			$alfabank_password = isset($_POST['alfabank_password']) ? $_POST['alfabank_password'] : '';
			
			\Elberos\save_option("alfabank_is_test", $alfabank_is_test);
			\Elberos\save_option("alfabank_username", $alfabank_username);
			\Elberos\save_option("alfabank_password", $alfabank_password);
		}
		
		$item = 
		[
			'alfabank_is_test' => \Elberos\get_option( 'alfabank_is_test', '' ),
			'alfabank_username' => \Elberos\get_option( 'alfabank_username', '' ),
			'alfabank_password' => \Elberos\get_option( 'alfabank_password', '' ),
		];
		
		?>
		<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
		<h2><?php _e('Alfabank Settings', 'pay-alfabank')?></h2>
		<div class="wrap">			
			<form id="form" method="POST">
				<input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
				<div class="metabox-holder" id="poststuff">
					<div id="post-body">
						<div id="post-body-content">
							<div class="add_or_edit_form" style="width: 60%">
								<? static::display_form($item) ?>
							</div>
							<input type="submit" id="submit" class="button-primary" name="submit"
								value="<?php _e('Save', 'pay-alfabank')?>" >
						</div>
					</div>
				</div>
			</form>
		</div>
		<?php
	}
	
	
	
	public static function display_form($item)
	{
		?>
		
		<!-- Is test -->
		<p>
		    <label for="alfabank_is_test"><?php _e('Тест:', 'pay-alfabank')?></label>
			<br>
			<select id="alfabank_is_test" name="alfabank_is_test" style="width:100%;max-width:100%;">
				<option value="0" <?= \Elberos\is_value_selected($item['alfabank_is_test'], "0") ?>>Нет</option>
				<option value="1" <?= \Elberos\is_value_selected($item['alfabank_is_test'], "1") ?>>Да</option>
			</select>
		</p>
		
		<!-- Username -->
		<p>
		    <label for="alfabank_username"><?php _e('Username:', 'pay-alfabank')?></label>
			<br>
            <input id="alfabank_username" name="alfabank_username" type="text" style="width: 100%"
				value="<?php echo esc_attr($item['alfabank_username'])?>" >
		</p>
		
		<!-- Password -->
		<p>
		    <label for="alfabank_password"><?php _e('Password:', 'pay-alfabank')?></label>
			<br>
            <input id="alfabank_password" name="alfabank_password" type="text" style="width: 100%"
				value="<?php echo esc_attr($item['alfabank_password'])?>" >
		</p>
		
		<?php
	}
	
}

}