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


/**
 * КЛАСС ДЛЯ ВЗАИМОДЕЙСТВИЯ С ПЛАТЕЖНЫМ ШЛЮЗОМ
 * Класс наследуется от стандартного класса SoapClient.
 */
class Gateway extends \SoapClient 
{
	
	/**
	 * АВТОРИЗАЦИЯ В ПЛАТЕЖНОМ ШЛЮЗЕ
	 * Генерация SOAP-заголовка для WS_Security.
	 *
	 * ОТВЕТ
	 *	  SoapHeader	  SOAP-заголовок для авторизации
	 */
	private function generateWSSecurityHeader()
	{
		$username = \Elberos\get_option("alfabank_username");
		$password = \Elberos\get_option("alfabank_password");
		
		$xml = '
			<wsse:Security SOAP-ENV:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
				<wsse:UsernameToken>
					<wsse:Username>' . $username . '</wsse:Username>
					<wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . $password . '</wsse:Password>
					<wsse:Nonce EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">' . sha1(mt_rand()) . '</wsse:Nonce>
				</wsse:UsernameToken>
			</wsse:Security>';
		 
		return new \SoapHeader('http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd', 'Security', new \SoapVar($xml, XSD_ANYXML), true);
	}
	
	
	
	/**
	 * ВЫЗОВ МЕТОДА ПЛАТЕЖНОГО ШЛЮЗА
	 * Переопределение функции SoapClient::__call().
	 *
	 * ПАРАМЕТРЫ
	 *	  method	  Метод из API.
	 *	  data		Массив данных.
	 *	   
	 * ОТВЕТ
	 *	  response	Ответ.
	 */   
	public function __call($method, $data) 
	{
		$this->__setSoapHeaders($this->generateWSSecurityHeader()); // Устанавливаем заголовок для авторизации
		return parent::__call($method, $data); // Возвращаем результат метода SoapClient::__call()
	}
	
	
} 