<?php
/*
* CnabPHP - Gera��o de arquivos de remessa e retorno em PHP
*
* LICENSE: The MIT License (MIT)
*
* Copyright (C) 2013 Ciatec.net
*
* Permission is hereby granted, free of charge, to any person obtaining a copy of this
* software and associated documentation files (the "Software"), to deal in the Software
* without restriction, including without limitation the rights to use, copy, modify,
* merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
* permit persons to whom the Software is furnished to do so, subject to the following
* conditions:
*
* The above copyright notice and this permission notice shall be included in all copies
* or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
* INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A
* PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
* HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
* OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
* SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/
namespace CnabPHP;
use Exception;

abstract class RegistroRetAbstract
{
	protected $data; // array contendo os dados do objeto
	protected $meta;
	protected $children;

	/* m�todo __construct()
	* instancia registro qualquer
	* @$data = array de dados para o registro
	*/
	public function __construct($linhaTxt)
	{
		// carrega o objeto correspondente
		$posicao = 0;
		foreach($this->meta as $key =>$value){
			$this->$key = substr($linhaTxt,$posicao,$value['tamanho']);
			$posicao += $value['tamanho'];
		}
	}


	/*
	* m�todo __set()
	* executado sempre que uma propriedade for atribu�da.
	*/
	public function __set($prop, $value)
	{
		// verifica se existe m�todo set_<propriedade>
		if (method_exists($this, 'set_'.$prop))
		{
			// executa o m�todo set_<propriedade>
			call_user_func(array($this, 'set_'.$prop), $value);
		}
		else
		{
			$metaData = (isset($this->meta[$prop]))?$this->meta[$prop]:null;
			switch ($metaData['tipo']) {
				case 'decimal':
					$inteiro    = abs(substr($value, 0, $metaData['tamanho']-$metaData['precision'])); 
					$decimal   = abs(substr($value, $metaData['tamanho']-$metaData['precision'], $metaData['precision']))/100;
					$retorno = ($inteiro+$decimal); 
					$this->data[$prop] =  $retorno;
					break;
				case 'int':
					$retorno = abs($value); 
					$this->data[$prop] =  $retorno;
					break;
				case 'alfa':
					$retorno = trim($value); 
					$this->data[$prop] =  $retorno;
					break;
				case $metaData['tipo'] == 'date' && $metaData['tamanho']==6:
					$retorno = date("y-m-d",strtotime($value));
					$this->data[$prop] =  $retorno;
					break;
				case $metaData['tipo'] == 'date' && $metaData['tamanho']==8:
					$retorno = date("Y-m-d",strtotime($value));
					$this->data[$prop] =  $retorno;
					break;
				default:
					$this->data[$prop] = null;
					break;
			}
		}
	}

	/*
	* m�todo __get()
	* executado sempre que uma propriedade for requerida
	*/
	public function __get($prop)
	{
		// verifica se existe m�todo get_<propriedade>
		if (method_exists($this, 'get_'.$prop))
		{
			// executa o m�todo get_<propriedade>
			return call_user_func(array($this, 'get_'.$prop));
		}
		else
		{
			return $this->___get($prop);
		}
	}

	/*
	* m�todo ___get()
	* metodo auxiliar para ser chamado para dentro de metodo get personalizado
	*/
	public function ___get($prop)
	{
		// retorna o valor da propriedade
		if (isset($this->meta[$prop]))
		{
			//$metaData = (isset($this->meta[$prop]))?$this->meta[$prop]:null;
			//$this->data[$prop] = !isset($this->data[$prop]) || $this->data[$prop]==''?$metaData['default']:$this->data[$prop];
			//if($metaData['required']==true && ($this->data[$prop]=='' || !isset($this->data[$prop])))
			//{
			//	throw new Exception('Campo faltante ou com valor nulo:'.$prop);
			//}
			switch ($metaData['tipo']) {
				case 'decimal':
					return ($this->data[$prop])?number_format($this->data[$prop],$metaData['precision'],',','.'):''; 
					//return str_pad($retorno,$metaData['tamanho'],'0',STR_PAD_LEFT);
					break;
				case 'int':
					return (isset($this->data[$prop]))?abs($this->data[$prop]):''; 
					//return str_pad($retorno,$metaData['tamanho'],'0',STR_PAD_LEFT);
					break;
				case 'alfa':
					return ($this->data[$prop])?$this->prepareText($this->data[$prop]):''; 
					//return str_pad(substr($retorno,0,$metaData['tamanho']),$metaData['tamanho'],' ',STR_PAD_RIGHT);
					break;
				case $metaData['tipo'] == 'date' && $metaData['tamanho']==6:
					return ($this->data[$prop])?date("dmy",strtotime($this->data[$prop])):'';
					//return str_pad($retorno,$metaData['tamanho'],'0',STR_PAD_LEFT);
					break;
				case $metaData['tipo'] == 'date' && $metaData['tamanho']==8:
					return ($this->data[$prop])?date("dmY",strtotime($this->data[$prop])):'';
					//return str_pad($retorno,$metaData['tamanho'],'0',STR_PAD_LEFT);
					break;
				default:
					return null;
					break;
			}
		}
	}

	/*
	* m�todo getUnformated()
	* busca o valor de dentro do campo dentro do objeto de forma simples sem formata��o de valor por exemplo
	*/
	public function getUnformated($prop)
	{
		// retorna o valor da propriedade
		if (isset($this->data[$prop]))
		{
			return $this->data[$prop];
		}
	}
	/* m�todo prepareText()
	* metodo retirado do projeto andersondanilo/CnabPHP e usado como esta sem altera��o
	* recebe um texto e prepara para inserir no arquivo de texto
	*/
	private function prepareText($text, $remove=null)
	{
		$result = strtoupper($this->removeAccents(trim(html_entity_decode($text))));
		if($remove)
			$result = str_replace(str_split($remove), '', $result);
		return $result;
	}

	/*
	* m�todo removeAccents()
	* metodo retirado do projeto andersondanilo/CnabPHP e usado como esta sem altera��o
	* recebe um texto e prepara para inserir no arquivo de texto
	*/
	private function removeAccents($string)
	{
		return preg_replace(
			array(
				'/\xc3[\x80-\x85]/',
				'/\xc3\x87/',
				'/\xc3[\x88-\x8b]/',
				'/\xc3[\x8c-\x8f]/',
				'/\xc3([\x92-\x96]|\x98)/',
				'/\xc3[\x99-\x9c]/',

				'/\xc3[\xa0-\xa5]/',
				'/\xc3\xa7/',
				'/\xc3[\xa8-\xab]/',
				'/\xc3[\xac-\xaf]/',
				'/\xc3([\xb2-\xb6]|\xb8)/',
				'/\xc3[\xb9-\xbc]/',
			),
			str_split( 'ACEIOUaceiou' , 1 ),
			$this->isUtf8( $string ) ? $string : utf8_encode( $string )
		);
	}
	/*
	* m�todo removeAccents()
	* metodo retirado do projeto andersondanilo/CnabPHP e usado como esta sem altera��o
	* recebe um texto e prepara para inserir no arquivo de texto
	*/
	private function isUtf8($string)
	{
		return preg_match( '%^(?:
			[\x09\x0A\x0D\x20-\x7E]
			| [\xC2-\xDF][\x80-\xBF]
			| \xE0[\xA0-\xBF][\x80-\xBF]
			| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}
			| \xED[\x80-\x9F][\x80-\xBF]
			| \xF0[\x90-\xBF][\x80-\xBF]{2}
			| [\xF1-\xF3][\x80-\xBF]{3}
			| \xF4[\x80-\x8F][\x80-\xBF]{2}
			)*$%xs',
			$string
		);
	}
	/*
	* m�todo getText()
	* metodo magico que trabalha recursivamente nos filhos e netos desse objeto 
	* prepara as linhas para serem exportadas para txt
	*/
	public function getText(){
		$retorno = '';
		foreach($this->meta as $key=>$value){
			$retorno .= $this->$key;
		}
		RemessaAbstract::$retorno[] = $retorno;
		if ($this->children)
		{
			// percorre todos objetos filhos
			foreach ($this->children as $child)
			{
				$child->getText();
			}
		}
	}
}
?>