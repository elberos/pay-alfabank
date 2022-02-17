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
	 * REST �����
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
		
		
		// ������������� ����� � ������
		$data['userName'] = $username;
		$data['password'] = $password;
		
		// �������������� ������
		$curl = curl_init(); 
		curl_setopt_array($curl, array
		(
			CURLOPT_URL => $url, // ������ ����� ������
			CURLOPT_RETURNTRANSFER => true, // ���������� �����
			CURLOPT_POST => true, // ����� POST
			CURLOPT_POSTFIELDS => http_build_query($data) // ������ � �������
		));
		
		// ��������� ������
		$response = curl_exec($curl); 
		
		// ���������� �� JSON � ������
		$response = json_decode($response, true);
		
		// ��������� ����������
		curl_close($curl);
		
		// ���������� �����
		return $response;
	}
	
}