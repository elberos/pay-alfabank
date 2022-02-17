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
	 * Create and register transaction
	 */
	public static function registerDo($params)
	{
		global $wpdb;
		
		$price = isset($params["price"]) ? (int)$params["price"] : 0;
		$invoice_id = isset($params["invoice_id"]) ? $params["invoice_id"] : "";
		$return_url = isset($params["return_url"]) ? $params["return_url"] : "";
		$description = isset($params["description"]) ? $params["description"] : "";
		$currency = isset($params["currency"]) ? $params["currency"] : "KZT";
		$transaction = null;
		$response = null;
		
		/* Create transaction */
		$table_name_transactions = $wpdb->base_prefix . "pay_alfabank_transactions";
		$wpdb->insert
		(
			$table_name_products,
			[
				"invoice_id" => $invoice_id,
				"price" => $price,
				"price_pay" => 0,
				"currency" => $currency,
			]
		);
		$transaction_id = $wpdb->insert_id;
		
		/* If transaction created */
		if ($transaction_id > 0)
		{
			/* Send request */
			$data = array
			(
				'returnUrl' => $return_url,
				'orderNumber' => $transaction_id,
				'description' => $invoice_number,
				'amount' => $price * 100, // Сумма платежа в копейках или центах
				'currency' => $currency,
			);
			
			$response = Gateway::restCall('register', $data);
			
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
	public static function getOrderStatus($params)
	{
		global $wpdb;
		
		$is_complete = false;
		$transaction_id = isset($params["transaction_id"]) ? $params["transaction_id"] : "";
		$response = null;
		
		/* Find transaction */
		$table_name_transactions = $wpdb->base_prefix . "pay_alfabank_transactions";
		$sql = \Elberos\wpdb_prepare
		(
			"select * from `${table_name_transactions}` " .
			"where id = :transaction_id ",
			[
				"id" => $transaction_id,
			]
		);
		$transaction = $wpdb->get_row($sql, ARRAY_A);
		$transaction_new = null;
		
		if ($transaction)
		{
			/* Send request */
			$data = array
			(
				'orderId' => $transaction_id,
			);
			$response = Gateway::restCall('getOrderStatus', $data);
			
			/* Обработка результата */
			$amount = isset($response['Amount']) ? $response['Amount'] : 0;
			$Pan = isset($response['Pan']) ? $response['Pan'] : 0;
			$cardholderName = isset($response['cardholderName']) ? $response['cardholderName'] : 0;
			$OrderStatus = isset($response['OrderStatus']) ? $response['OrderStatus'] : 0;
			$OrderNumber = isset($response['OrderNumber']) ? $response['OrderNumber'] : 0;
			$errorCode = isset($response['errorCode']) ? $response['errorCode'] : 
				(isset($response['ErrorCode']) ? $response['ErrorCode'] : 0);
			$errorMessage = isset($response['errorMessage']) ? $response['errorMessage'] : 
				(isset($response['ErrorMessage']) ? $response['ErrorMessage'] : 0);
			
			if ($OrderStatus == 2)
			{
				// Сумма платежа в копейках или центах
				$amount = $amount / 100;
				
				if ($transaction['status'] != $OrderStatus)
				{
					$transaction_update = [];
					$transaction_update['status'] = $OrderStatus;
					$transaction_update['res_code'] = $errorCode;
					$transaction_update['res_desc'] = $errorMessage;
					$transaction_update['price_pay'] = $amount;
					$transaction_update['pay_desc'] = $Pan . " " . $cardholderName;
					
					$wpdb->update
					(
						$table_name_transactions,
						$transaction_update,
						[
							"id" => $transaction_id,
						]
					);
					
					$is_complete = true;
				}
			}
			else
			{
				if ($alfabank_pay->status != $OrderStatus)
				{
					$transaction_update = [];
					$transaction_update['status'] = $OrderStatus;
					$transaction_update['res_code'] = $errorCode;
					$transaction_update['res_desc'] = $errorMessage;
					$transaction_update['pay_desc'] = $Pan . " " . $cardholderName;
					
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
					"id" => $transaction_id,
				]
			);
			$transaction_new = $wpdb->get_row($sql, ARRAY_A);
		}
		
		return
		[
			"response" => $response,
			"is_complete" => $is_complete,
			"transaction_id" => $transaction_id,
			"transaction_old" => $transaction_old,
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