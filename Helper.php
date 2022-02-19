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


class Helper
{
	
	
	/**
	 * Create transaction
	 */
	public static function createTransaction($params)
	{
		global $wpdb;
		
		$price = isset($params["price"]) ? (int)$params["price"] : 0;
		$invoice_id = isset($params["invoice_id"]) ? $params["invoice_id"] : "";
		$description = isset($params["description"]) ? $params["description"] : "";
		$currency = isset($params["currency"]) ? $params["currency"] : "398";
		
		/* Create transaction */
		$table_name_transactions = $wpdb->base_prefix . "elberos_pay_alfabank_transactions";
		$wpdb->insert
		(
			$table_name_transactions,
			[
				"invoice_id" => $invoice_id,
				"price" => $price,
				"price_pay" => 0,
				"currency" => $currency,
				"description" => $description,
				"gmtime_add" => gmdate("Y-m-d H:i:s"),
				"gmtime_update" => gmdate("Y-m-d H:i:s"),
			]
		);
		$transaction_id = (int)($wpdb->insert_id);
		
		/* Get created transaction */
		$sql = \Elberos\wpdb_prepare
		(
			"select * from `${table_name_transactions}` " .
			"where id = :id ",
			[
				"id" => $transaction_id,
			]
		);
		$transaction = $wpdb->get_row($sql, ARRAY_A);
		
		return $transaction;
	}
	
	
	
	/**
	 * Find transaction by order id
	 */
	public static function findTransactionByOrderId($order_id)
	{
		global $wpdb;
		
		/* Get created transaction */
		$table_name_transactions = $wpdb->base_prefix . "elberos_pay_alfabank_transactions";
		$sql = \Elberos\wpdb_prepare
		(
			"select * from `${table_name_transactions}` " .
			"where order_id = :order_id ",
			[
				"order_id" => $order_id,
			]
		);
		$transaction = $wpdb->get_row($sql, ARRAY_A);
		
		return $transaction;
	}
	
	
	
	/**
	 * Create and register transaction
	 */
	public static function registerTransaction($params)
	{
		global $wpdb;
		
		$table_name_transactions = $wpdb->base_prefix . "elberos_pay_alfabank_transactions";
		
		/* Параметры */
		$return_url = isset($params["return_url"]) ? $params["return_url"] : "";
		$transaction = isset($params["transaction"]) ? $params["transaction"] : null;
		$price = isset($transaction["price"]) ? (int)$transaction["price"] : 0;
		$invoice_id = isset($transaction["invoice_id"]) ? $transaction["invoice_id"] : "";
		$description = isset($transaction["description"]) ? $transaction["description"] : "";
		$currency = isset($transaction["currency"]) ? $transaction["currency"] : "398";
		$response = null;
		
		/* If transaction created */
		if ($transaction)
		{
			$transaction_id = $transaction["id"];
			
			/* Send request */
			$data = array
			(
				'returnUrl' => $return_url,
				'orderNumber' => $transaction_id,
				'description' => $description,
				'amount' => $price * 100, // Сумма платежа в копейках или центах
				'currency' => $currency,
			);
			
			$response = static::restCall('register', $data);
			
			/* Обработка результата */
			if ($response)
			{
				$errorCode = isset($response['errorCode']) ? $response['errorCode'] : 0;
				$errorMessage = isset($response['errorMessage']) ? $response['errorMessage'] : "";
				$orderId = isset($response['orderId']) ? $response['orderId'] : "";
				
				$wpdb->update
				(
					$table_name_transactions,
					[
						"res_code" => $errorCode,
						"res_desc" => $errorMessage,
						"order_id" => $orderId,
						"gmtime_update" => gmdate("Y-m-d H:i:s"),
					],
					[
						"id" => $transaction_id,
					]
				);
				
				/* Get updated transaction */
				$sql = \Elberos\wpdb_prepare
				(
					"select * from `${table_name_transactions}` " .
					"where id = :transaction_id ",
					[
						"id" => $transaction_id,
					]
				);
				$transaction = $wpdb->get_row($sql, ARRAY_A);
			}
		}
		
		return
		[
			"transaction_id" => $transaction_id,
			"transaction" => $transaction,
			"invoice_id" => $invoice_id,
			"response" => $response,
		];
	}
	
	
	
	/**
	 * Get and update transaction status
	 */
	public static function getOrderStatus($transaction)
	{
		global $wpdb;
		
		$response = null;
		$is_complete = false;
		$transaction_new = $transaction;
		$table_name_transactions = $wpdb->base_prefix . "elberos_pay_alfabank_transactions";
		
		if ($transaction)
		{
			$transaction_id = $transaction["id"];
			
			/* Send request */
			$data = array
			(
				'orderId' => $transaction["order_id"],
			);
			$response = static::restCall('getOrderStatus', $data);
			
			/* Обработка результата */
			$amount = isset($response['Amount']) ? $response['Amount'] : 0;
			$Pan = isset($response['Pan']) ? $response['Pan'] : 0;
			$cardholderName = isset($response['cardholderName']) ? $response['cardholderName'] : 0;
			$OrderStatus = isset($response['OrderStatus']) ? $response['OrderStatus'] : 0;
			$OrderNumber = isset($response['OrderNumber']) ? $response['OrderNumber'] : 0;
			$errorCode = isset($response['errorCode']) ? $response['errorCode'] : 
				(isset($response['ErrorCode']) ? $response['ErrorCode'] : 0);
			$errorMessage = isset($response['errorMessage']) ? $response['errorMessage'] : 
				(isset($response['ErrorMessage']) ? $response['ErrorMessage'] : "");
			
			if ($OrderStatus == 2)
			{
				// Сумма платежа в копейках или центах
				$amount = $amount / 100;
				
				$transaction_update = [];
				$transaction_update['status'] = $OrderStatus;
				$transaction_update['res_code'] = $errorCode;
				$transaction_update['res_desc'] = $errorMessage;
				$transaction_update['price_pay'] = $amount;
				$transaction_update['pay_desc'] = $Pan . " " . $cardholderName;
				$transaction_update['gmtime_update'] = gmdate("Y-m-d H:i:s");
				
				$wpdb->update
				(
					$table_name_transactions,
					$transaction_update,
					[
						"id" => $transaction_id,
					]
				);
				
				if ($transaction['status'] != $OrderStatus)
				{	
					$is_complete = true;
				}
			}
			else
			{
				if ($transaction['status'] != $OrderStatus)
				{
					$transaction_update = [];
					$transaction_update['status'] = $OrderStatus;
					$transaction_update['res_code'] = $errorCode;
					$transaction_update['res_desc'] = $errorMessage;
					$transaction_update['pay_desc'] = $Pan . " " . $cardholderName;
					$transaction_update['gmtime_update'] = gmdate("Y-m-d H:i:s");
					
					$wpdb->update
					(
						$table_name_transactions,
						$transaction_update,
						[
							"id" => $transaction_id,
						]
					);
				}
			}
			
			$sql = \Elberos\wpdb_prepare
			(
				"select * from `${table_name_transactions}` " .
				"where id = :transaction_id ",
				[
					"transaction_id" => $transaction_id,
				]
			);
			$transaction_new = $wpdb->get_row($sql, ARRAY_A);
		}
		
		return
		[
			"response" => $response,
			"is_complete" => $is_complete,
			"transaction_id" => $transaction_id,
			"transaction_old" => $transaction,
			"transaction_new" => $transaction_new,
		];
	}
	
	
	
	/**
	 * REST вызов
	 * @params string $method
	 * @params array $data
	 */
	public static function restCall($method, $data)
	{
		$url = "";
		
		$is_test = \Elberos\get_option("alfabank_is_test");
		$username = \Elberos\get_option("alfabank_username");
		$password = \Elberos\get_option("alfabank_password");
		
		/* Is test */
		if ($is_test)
		{
			if ($method == "register") $url = "https://web.rbsuat.com/ab/rest/register.do";
			else if ($method == "getOrderStatus") $url = "https://web.rbsuat.com/ab/rest/getOrderStatus.do";
		}
		else
		{
			if ($method == "register") $url = "https://pay.alfabank.kz/payment/rest/register.do";
			else if ($method == "getOrderStatus") $url = "https://pay.alfabank.kz/payment/rest/getOrderStatus.do";
		}
		
		// Устанавливаем логин и пароль
		$data['userName'] = $username;
		$data['password'] = $password;
		
		// Инициализируем запрос
		$curl = curl_init(); 
		curl_setopt_array($curl, array
		(
			CURLOPT_URL => $url, // Полный адрес метода
			CURLOPT_RETURNTRANSFER => true, // Возвращать ответ
			CURLOPT_POST => true, // Метод POST
			CURLOPT_POSTFIELDS => http_build_query($data) // Данные в запросе
		));
		
		// Выполняем запрос
		$response = curl_exec($curl); 
		
		// Декодируем из JSON в массив
		$response = json_decode($response, true);
		
		// Закрываем соединение
		curl_close($curl);
		
		// Возвращаем ответ
		return $response;
	}
	
}