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


if ( !class_exists( Transactions_Table::class ) && class_exists( \Elberos\Table::class ) ) 
{

class Transactions_Table extends \Elberos\Table 
{
	
	
	/**
	 * Table name
	 */
	function get_table_name()
	{
		global $wpdb;
		return $wpdb->base_prefix . 'elberos_pay_alfabank_transactions';
	}
	
	
	
	/**
	 * Page name
	 */
	function get_page_name()
	{
		return "pay-alfabank";
	}
	
	
	
	/**
	 * Return true if is_deleted enabled
	 */
	function is_enable_deleted_filter()
	{
		return false;
	}
	
	
	
	/**
	 * Create struct
	 */
	static function createStruct()
	{
		$struct = \Elberos\AlfaBank\Transactions::create
		(
			"admin_transactions",
			function ($struct)
			{
				
				$struct->table_fields =
				[
					"id",
					"invoice_id",
					"price",
					"price_pay",
					"currency",
					"order_id",
					"status",
					"pay_desc",
					"res_code",
					"res_desc",
					"gmtime_add",
					"gmtime_update",
				];
				
				$struct->form_fields =
				[
					"id",
					"invoice_id",
					"description",
					"price",
					"price_pay",
					"currency",
					"order_id",
					"status",
					"pay_desc",
					"res_code",
					"res_desc",
					"gmtime_add",
					"gmtime_update",
				];
				
				return $struct;
			}
		);
		
		return $struct;
	}
	
	
	
	/**
	 * Init struct
	 */
	function initStruct()
	{
		parent::initStruct();
	}
	
	
	
	/* Действия */
	function get_bulk_actions()
    {
		$actions = [];
        return $actions;
    }
	
	
	
	/**
	 * Column buttons
	 */
	function column_buttons($item)
	{
		$page_name = $this->get_page_name();
		return sprintf
		(
			'<a href="?page=' . $page_name . '&action=show&id=%s">%s</a>',
			$item['id'], 
			__('Открыть', 'elberos-commerce')
		);
	}
	
	
	
	/**
	 * Process bulk action
	 */
	function process_bulk_action()
	{
		$action = $this->current_action();
		
		/* Edit items */
		if (in_array($action, ['show']))
		{
			$this->do_get_item();
			$fields =
			[
				"id",
				"invoice_id",
				"description",
				"price",
				"price_pay",
				"currency",
				"order_id",
				"status",
				"pay_desc",
				"res_code",
				"res_desc",
				"gmtime_add",
				"gmtime_update",
			];
			foreach ($fields as $field_name)
			{
				$this->struct->editField($field_name, ["virtual"=>true, "readonly"=>true]);
			}
		}
		
	}
	
	
	
	/**
	 * Get item
	 */
	function do_get_item()
	{
		parent::do_get_item();
	}
	
	
	
	/**
	 * Process item
	 */
	function process_item($item, $old_item)
	{
		return $item;
	}
	
	
	
	/**
	 * Item validate
	 */
	function item_validate($item)
	{
		return "";
	}
	
	
	
	/**
	 * Returns true if show filter
	 */
	function is_show_filter()
	{
		list($_,$result) = apply_filters("elberos_table_is_show_filter_" . get_called_class(), [$this,true]);
		return $result;
	}
	
	
	
	/**
	 * Returns filter elements
	 */
	function get_filter()
	{
		return [
			"invoice_id",
			"status",
			"order_id",
		];
	}
	
	
	
	/**
	 * Show filter item
	 */
	function show_filter_item($item_name)
	{
		if ($item_name == "invoice_id")
		{
			?>
			<input type="text" name="invoice_id" class="web_form_value" placeholder="ID инвойса"
				value="<?= esc_attr( isset($_GET["invoice_id"]) ? $_GET["invoice_id"] : "" ) ?>">
			<?php
		}
		else if ($item_name == "order_id")
		{
			?>
			<input type="text" name="order_id" class="web_form_value" placeholder="ID транзакции"
				value="<?= esc_attr( isset($_GET["order_id"]) ? $_GET["order_id"] : "" ) ?>">
			<?php
		}
		else if ($item_name == "status")
		{
			?>
			<select name="status" class="web_form_value">
				<option value="">Выберите статус</option>
				<option value="1" <?= \Elberos\is_get_selected("status", "1") ?>>Оплачено</option>
				<option value="-1" <?= \Elberos\is_get_selected("status", "-1") ?>>Ошибка</option>
			</select>
			<?php
		}
		else
		{
			parent::show_filter_item($item_name);
		}
	}
	
	
	
	/**
	 * Process items params
	 */
	function prepare_table_items_filter($params)
	{
		global $wpdb;
		
		$params = parent::prepare_table_items_filter($params);
		
		/* invoice_id */
		if (isset($_GET["invoice_id"]))
		{
			$params["where"][] = "invoice_id = :invoice_id";
			$params["args"]["invoice_id"] = (int)$_GET["invoice_id"];
		}
		
		/* order_id */
		if (isset($_GET["order_id"]))
		{
			$params["where"][] = "order_id = :order_id";
			$params["args"]["order_id"] = $_GET["order_id"];
		}
		
		/* status */
		if (isset($_GET["status"]))
		{
			$status = (int)$_GET["status"];
			if ($status == 1)
			{
				$params["where"][] = "status = 2 and res_code = 0";
			}
			else if ($status == -1)
			{
				$params["where"][] = "status != 2 or res_code != 0";
			}
		}
		
		return $params;
	}
	
	
	
	/**
	 * Prepare table items
	 */
	function prepare_table_items()
	{
		parent::prepare_table_items();
	}
	
	
	
	/**
	 * CSS
	 */
	function display_css()
	{
		parent::display_css();
		?>
		<style>
		.subsub_table, .subsub_table .subsubsub{
			font-size: 16px;
		}
		.subsub_table_left{
			font-weight: bold;
			padding-right: 5px;
			text-align: right;
		}
		.subsub_table_right{
			padding-left: 5px;
		}
		</style>
		<?php
	}
	
	
	
	/**
	 * Display table sub
	 */
	function display_table_sub()
	{
		$page_name = $this->get_page_name();
		$is_deleted = isset($_REQUEST['is_deleted']) ? $_REQUEST['is_deleted'] : "";
	}
	
	
	
	/**
	 * Display form sub
	 */
	function display_form_sub()
	{
		$page_name = $this->get_page_name();
		?>
		<br/>
		<br/>
		<a type="button" class='button-primary' href='?page=<?= $page_name ?>'> Back </a>
		<br/>
		<?php
	}
	
	
	
	/**
	 * Returns form title
	 */
	function get_form_title($item)
	{
		return _e($item['id'] > 0 ? 'Транзакция' : 'Транзакция', 'app');
	}
	
	
	
	/**
	 * Display action
	 */
	function display_action()
	{
		$action = $this->current_action();
		if ($action == 'show')
		{
			$this->display_add_or_edit();
		}
		else
		{
			$this->display_table();
		}
	}
	
	
	/**
	 * Display buttons
	 */
	function display_form_buttons()
	{
		
	}
}

}