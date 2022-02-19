<?php
/**
 * Plugin Name: Pay AlfaBank
 * Description: Pay AlfaBank
 * Version:     0.1.0
 * Author:      Elberos team <support@elberos.org>
 * License:     Apache License 2.0
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

if ( !class_exists( 'PAY_AlfaBank_Plugin' ) ) 
{


/* Check if Wordpress */
if (!defined('ABSPATH')) exit;


require_once __DIR__ . "/Helper.php";


class PAY_AlfaBank_Plugin
{
	
	/**
	 * Init Plugin
	 */
	public static function init()
	{
		add_action
		(
			'admin_init', 
			function()
			{
				require_once __DIR__ . "/include/admin-settings.php";
				require_once __DIR__ . "/include/Transactions.php";
				require_once __DIR__ . "/include/Transactions_Table.php";
			}
		);
		add_action('admin_menu', 'PAY_AlfaBank_Plugin::register_admin_menu');
		
		/* Remove plugin updates */
		add_filter( 'site_transient_update_plugins', 'PAY_AlfaBank_Plugin::filter_plugin_updates' );
		
		/* Add cron task */
		if ( !wp_next_scheduled( 'pay_alfabank_event' ) )
		{
			wp_schedule_event( time() + 60, 'hourly', 'pay_alfabank_event' );
		}
		add_action( 'pay_alfabank_event', 'PAY_AlfaBank_Plugin::cron_event' );
	}
	
	
	
	/**
	 * CIDR Match
	 */
	function cidr_match ($IP, $CIDR)
	{
		list ($net, $mask) = explode ("/", $CIDR);

		$ip_net = ip2long ($net);
		$ip_mask = ~((1 << (32 - $mask)) - 1);

		$ip_ip = ip2long ($IP);

		$ip_ip_net = $ip_ip & $ip_mask;

		return ($ip_ip_net == $ip_net);
	}
	
	
	
	/**
	 * Remove plugin updates
	 */
	public static function filter_plugin_updates($value)
	{
		$name = plugin_basename(__FILE__);
		if (isset($value->response[$name]))
		{
			unset($value->response[$name]);
		}
		return $value;
	}
	
	
	
	/**
	 * Register Admin Menu
	 */
	public static function register_admin_menu()
	{
		add_menu_page(
			'pay-alfabank', 'AlfaBank',
			'manage_options', 'pay-alfabank',
			function ()
			{
				(new \Elberos\AlfaBank\Transactions_Table())->display();
			},
			'/wp-content/plugins/pay-alfabank/images/alfabank.ico',
			100
		);
		
		add_submenu_page(
			'pay-alfabank',
			'Настройки', 'Настройки',
			'manage_options', 'pay-alfabank-settings',
			function()
			{
				\Elberos\AlfaBank\Settings::show();
			}
		);
		
	}
	
	
	
	/**
	 * Cron hourly event
	 */
	public static function cron_event()
	{
		global $wpdb;
		
		/* Поиск транзакций */
		$table_transactions = $wpdb->base_prefix . "pay_alfabank_transactions";
		$sql = \Elberos\wpdb_prepare
		(
			"select * from `${table_transactions}` " .
			"where `status` = 0 and `gmtime_add` < :gmtime_add and `order_id` != ''",
			[
				"gmtime_add" => gmdate("Y-m-d H:i:s", time() - 30*60),
			]
		);
		$transactions = $wpdb->get_results($sql, ARRAY_A);
		
		/* Обновляем информацию о платежах */
		foreach ($transactions as $transaction)
		{
			$res = \Elberos\AlfaBank\Helper::getOrderStatus($transaction);
			$transaction = $res["transaction_new"];
			
			/* Action update_status_pay */
			do_action("elberos_alfabank_update_status_pay", $transaction);
		}
		
		/* Отмена транзакций, где order_id пустой */
		$sql = "UPDATE `$table_transactions` set `status`=-1 where `order_id`=''";
		$wpdb->query($sql);
	}
}


PAY_AlfaBank_Plugin::init();


}