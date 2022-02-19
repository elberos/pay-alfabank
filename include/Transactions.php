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


if ( !class_exists( Transactions::class ) && class_exists( \Elberos\StructBuilder::class ) ) 
{

class Transactions extends \Elberos\StructBuilder
{
	
	/**
	 * Get entity name
	 */
	public static function getEntityName()
	{
		return "pay_alfabank_transactions";
	}
	
	
	
	/**
	 * Init struct
	 */
	public function init()
	{
		$this
			
			->addField
			([
				"api_name" => "invoice_id",
				"label" => "Номер инвойса",
				"type" => "input",
			])
			
			->addField
			([
				"api_name" => "description",
				"label" => "Описание",
				"type" => "input",
			])
			
			->addField
			([
				"api_name" => "price",
				"label" => "Сумма",
				"type" => "input",
			])
			
			->addField
			([
				"api_name" => "price_pay",
				"label" => "Оплата",
				"type" => "input",
			])
			
			->addField
			([
				"api_name" => "currency",
				"label" => "Валюта",
				"type" => "input",
			])
			
			->addField
			([
				"api_name" => "order_id",
				"label" => "Номер транзакции",
				"type" => "input",
			])
			
			->addField
			([
				"api_name" => "status",
				"label" => "Статус",
				"type" => "input",
			])
			
			->addField
			([
				"api_name" => "pay_desc",
				"label" => "Описание платежа",
				"type" => "input",
			])
			
			->addField
			([
				"api_name" => "res_code",
				"label" => "Код",
				"type" => "input",
			])
			
			->addField
			([
				"api_name" => "res_desc",
				"label" => "Результат",
				"type" => "input",
			])
			
			->addField
			([
				"api_name" => "gmtime_add",
				"label" => "Дата создания",
				"type" => "input",
				"column_value" => function($struct, $item)
				{
					return \Elberos\wp_from_gmtime( $item["gmtime_add"] );
				},
			])
			
			->addField
			([
				"api_name" => "gmtime_update",
				"label" => "Дата обновления",
				"type" => "input",
				"column_value" => function($struct, $item)
				{
					return \Elberos\wp_from_gmtime( $item["gmtime_update"] );
				},
			])
		;
	}
	
}

}